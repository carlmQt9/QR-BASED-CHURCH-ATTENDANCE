<?php

namespace App\Http\Controllers;

use App\Models\AttendanceRecord;
use App\Models\AttendanceSession;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class AttendanceController extends Controller
{
    public function dashboard(): JsonResponse
    {
        $user = request()->user();
        $session = AttendanceSession::query()->when($user?->role === 'leader', function ($query) use ($user) {
            $query->where('started_by', $user->id);
        })->where(function ($query) {
            $query->whereNull('ended_at')->orWhere('ended_at', '>', now());
        })->latest('started_at')->first();

        return response()->json([
            'active_session' => $session?->loadCount('records'),
            'today_count' => $session?->records()->count() ?? 0,
            'recent_check_ins' => $session?->records()->with('member:id,name')->latest('checked_in_at')->limit(10)->get(),
        ]);
    }

    public function adminDashboard(): JsonResponse
    {
        abort_unless(request()->user()?->isSuperAdmin(), 403);
        $todayCount = AttendanceRecord::whereDate('checked_in_at', today())->count();
        $memberCount = User::where('role', 'member')->count();
        $sessions = AttendanceSession::withCount('records')->whereDate('started_at', today())->latest('started_at')->limit(5)->get();
        $records = AttendanceRecord::with(['member:id,name', 'session:id,name'])->whereDate('checked_in_at', today())->latest('checked_in_at')->limit(5)->get();
        $trendRecords = AttendanceRecord::whereBetween('checked_in_at', [now()->subDays(6)->startOfDay(), now()->endOfDay()])->get();
        $trend = collect(range(0, 6))->map(function ($index) use ($trendRecords) {
            $date = now()->subDays(6 - $index);
            return ['label' => $date->format('D'), 'count' => $trendRecords->filter(fn ($record) => $record->checked_in_at->isSameDay($date))->count()];
        });

        return response()->json([
            'today_count' => $todayCount,
            'member_count' => $memberCount,
            'checkin_rate' => $memberCount ? round(($todayCount / $memberCount) * 100) : 0,
            'trend' => $trend,
            'sessions' => $sessions->map(fn ($session) => ['time' => $session->started_at->format('h:i A'), 'name' => $session->name, 'location' => $session->location ?: 'Main campus', 'type' => $session->type, 'count' => $session->records_count]),
            'recent_check_ins' => $records,
        ]);
    }

    public function start(Request $request): JsonResponse|RedirectResponse
    {
        $validated = $request->validate(['name' => ['required', 'string', 'max:120'], 'type' => ['required', 'string', 'max:80'], 'location' => ['nullable', 'string', 'max:120'], 'started_at' => ['nullable', 'date'], 'duration_minutes' => ['required', 'integer', 'min:1', 'max:720']]);
        $user = $request->user();
        abort_unless($user?->isApproved(), 403);
        $durationMinutes = (int) $validated['duration_minutes'];
        $startedAt = isset($validated['started_at']) ? now()->parse($validated['started_at']) : now();
        $session = AttendanceSession::create([...$validated, 'started_by' => $user->id, 'started_at' => $startedAt, 'duration_minutes' => $durationMinutes, 'ended_at' => $startedAt->copy()->addMinutes($durationMinutes)]);

        return $request->expectsJson() ? response()->json($session, 201) : redirect()->route('dashboard')->with('status', 'Session started.');
    }

    public function checkIn(Request $request): JsonResponse
    {
        $validated = $request->validate(['session_id' => ['required', 'exists:attendance_sessions,id'], 'qr_token' => ['required', 'string']]);
        $member = User::query()->where('qr_token', $validated['qr_token'])->where('role', 'member')->first();
        if (! $member) {
            return response()->json(['status' => 'failed', 'message' => 'QR code not recognized.'], 422);
        }

        $session = AttendanceSession::findOrFail($validated['session_id']);
        if ($session->ended_at && $session->ended_at->isPast()) {
            return response()->json(['status' => 'failed', 'message' => 'This attendance session has ended.'], 422);
        }
        $existing = AttendanceRecord::query()->where('attendance_session_id', $session->id)->where('user_id', $member->id)->first();
        if ($existing) {
            return response()->json(['status' => 'already_attended', 'message' => $member->name . ' is already checked in.', 'member' => $member->only(['id', 'name', 'member_code']), 'checked_in_at' => $existing->checked_in_at], 409);
        }

        $record = AttendanceRecord::firstOrCreate(
            ['attendance_session_id' => $validated['session_id'], 'user_id' => $member->id],
            ['checked_in_at' => now()]
        );

        return response()->json(['status' => 'success', 'new' => $record->wasRecentlyCreated, 'member' => $member->only(['id', 'name', 'member_code']), 'checked_in_at' => $record->checked_in_at]);
    }

    public function manualCheckIn(Request $request): JsonResponse
    {
        $validated = $request->validate(['session_id' => ['required', 'exists:attendance_sessions,id'], 'member_id' => ['required', 'exists:users,id']]);
        $leader = $request->user();
        $session = AttendanceSession::findOrFail($validated['session_id']);
        $member = User::whereKey($validated['member_id'])->where('role', 'member')->firstOrFail();

        abort_unless($leader?->role === 'leader' && $leader->isApproved() && $session->started_by === $leader->id, 403);
        if ($session->ended_at && $session->ended_at->isPast()) {
            return response()->json(['status' => 'failed', 'message' => 'This attendance session has ended.'], 422);
        }

        $record = AttendanceRecord::firstOrCreate(
            ['attendance_session_id' => $session->id, 'user_id' => $member->id],
            ['checked_in_at' => now()]
        );

        return response()->json([
            'status' => $record->wasRecentlyCreated ? 'success' : 'already_attended',
            'member' => $member->only(['id', 'name', 'member_code']),
            'checked_in_at' => $record->checked_in_at,
            'message' => $record->wasRecentlyCreated ? $member->name . ' is present.' : $member->name . ' is already checked in.',
        ], $record->wasRecentlyCreated ? 201 : 409);
    }

    public function end(Request $request, AttendanceSession $session): JsonResponse|RedirectResponse
    {
        abort_unless($request->user()?->id === $session->started_by && $request->user()->isApproved(), 403);
        $session->update(['ended_at' => now()]);

        return $request->expectsJson() ? response()->json(['ended' => true]) : redirect()->route('dashboard');
    }

    public function storeMember(Request $request): JsonResponse
    {
        abort_unless($request->user()?->isSuperAdmin(), 403);
        $validated = $request->validate(['name' => ['required', 'string', 'max:120'], 'email' => ['required', 'email', 'unique:users,email'], 'password' => ['nullable', 'string', 'min:8']]);
        $member = User::create([...$validated, 'role' => 'member', 'member_code' => 'G-' . str_pad((string) ((User::max('id') ?? 0) + 1), 5, '0', STR_PAD_LEFT), 'qr_token' => (string) Str::uuid(), 'password' => $validated['password'] ?? Str::random(32)]);

        return response()->json($member->only(['id', 'name', 'email', 'member_code', 'qr_token']), 201);
    }

    public function destroyMember(Request $request, User $member): JsonResponse
    {
        abort_unless($request->user()?->isSuperAdmin() && $member->role === 'member', 403);
        $member->delete();

        return response()->json(['deleted' => true, 'member_id' => $member->id]);
    }
}