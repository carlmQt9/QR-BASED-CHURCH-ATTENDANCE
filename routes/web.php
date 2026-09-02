<?php

use App\Http\Controllers\AttendanceController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\ReportController;
use App\Http\Controllers\SettingsController;
use App\Models\User;
use App\Models\AttendanceSession;
use App\Models\AttendanceRecord;
use App\Models\Setting;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return redirect()->route('login');
});

Route::get('/dashboard', function () {
    /** @var User|null $user */
    $user = Auth::user();
    abort_unless($user?->isApproved(), 403);
    if ($user->role === 'leader') {
        return view('leader', [
            'members' => User::where('role', 'member')->orderBy('name')->get(['id', 'name']), 
            'sessions' => AttendanceSession::with('records.member')->whereNull('deleted_at')->where('started_by', $user->id)->orderByDesc('started_at')->orderByDesc('id')->get(), 
            'activeSession' => AttendanceSession::with('records.member')->whereNull('deleted_at')->where('started_by', $user->id)->where(function ($query) {
                $query->whereNull('ended_at')->orWhere('ended_at', '>', now());
            })->orderByDesc('started_at')->orderByDesc('id')->first(),
            'gatheringTypes' => Setting::get('gathering_types', ['Sunday worship', 'Prayer meeting', 'Youth fellowship']),
            'membershipGroups' => Setting::get('membership_groups', ['General congregation', 'Volunteer team', 'Youth ministry'])
        ]);
    }
    $currentView = request()->query('view', 'overview');
    abort_unless(in_array($currentView, ['overview', 'attendance', 'members', 'reports'], true), 404);
    $memberCount = User::where('role', 'member')->count();
    $members = User::where('role', 'member')->latest()->paginate(10)->withQueryString();
    $users = User::orderByRaw("CASE role WHEN 'admin' THEN 1 WHEN 'leader' THEN 2 WHEN 'member' THEN 3 ELSE 4 END")
        ->latest()
        ->paginate(7)
        ->withQueryString();
    $allSessions = AttendanceSession::with('records.member', 'leader')->orderByDesc('started_at')->orderByDesc('id')->get();
    $sessions = AttendanceSession::with('records.member', 'leader')->orderByDesc('started_at')->orderByDesc('id')->paginate(3)->withQueryString();
    $archivedSessions = AttendanceSession::onlyTrashed()->with('records.member', 'leader')->latest('deleted_at')->get();
    $archivedUsers = User::onlyTrashed()->orderBy('name')->get();
    $todaySessions = $allSessions->filter(fn ($session) => $session->started_at->isToday())->take(5);
    $recentCheckIns = AttendanceRecord::with(['member', 'session'])->whereDate('checked_in_at', today())->latest('checked_in_at')->limit(5)->get();
    $attendanceCount = AttendanceRecord::whereDate('checked_in_at', today())->count();
    $monthlyCheckins = AttendanceRecord::whereBetween('checked_in_at', [now()->startOfMonth(), now()->endOfMonth()])->count();
    $yearlyCheckins = AttendanceRecord::whereBetween('checked_in_at', [now()->startOfYear(), now()->endOfYear()])->count();
    $weekStart = now()->subDays(6)->startOfDay();
    $weekEnd = now()->endOfDay();
    $weeklyRecords = AttendanceRecord::whereBetween('checked_in_at', [$weekStart, $weekEnd])->get();
    $attendanceTrend = collect(range(0, 6))->map(function ($daysAgo) use ($weeklyRecords) {
        $date = now()->subDays(6 - $daysAgo);
        return ['label' => $date->format('D'), 'count' => $weeklyRecords->filter(fn ($record) => $record->checked_in_at->isSameDay($date))->count()];
    });
    $weeklyAverage = $allSessions->filter(fn ($session) => $session->started_at->between($weekStart, $weekEnd))->avg(fn ($session) => $session->records->count()) ?: 0;
    $returningMembers = AttendanceRecord::whereBetween('checked_in_at', [now()->startOfMonth(), now()->endOfMonth()])->distinct('user_id')->count('user_id');
    $returningRate = $memberCount ? round(($returningMembers / $memberCount) * 100) : 0;
    $reportData = app(ReportController::class)->reportData(request());
    $reportSessions = AttendanceSession::query()->orderByDesc('started_at')->orderByDesc('id')->get(['id', 'name', 'type']);
    $dashboardData = ['trend' => $attendanceTrend, 'todayCount' => $attendanceCount, 'memberCount' => $memberCount, 'sessions' => $todaySessions->map(fn ($session) => ['time' => $session->started_at->format('h:i A'), 'name' => $session->name, 'location' => $session->location ?: 'Main campus', 'type' => $session->type, 'count' => $session->records->count()]), 'checkIns' => $recentCheckIns->map(fn ($record) => ['name' => $record->member->name, 'session' => $record->session->name, 'time' => $record->checked_in_at->format('h:i A')])];
    return view('welcome', [
        'currentView' => $currentView, 
        'members' => $members, 
        'users' => $users,
        'memberCount' => $memberCount, 
        'sessions' => $sessions, 
        'archivedSessions' => $archivedSessions,
        'archivedUsers' => $archivedUsers,
        'todaySessions' => $todaySessions, 
        'recentCheckIns' => $recentCheckIns, 
        'attendanceTrend' => $attendanceTrend, 
        'dashboardData' => $dashboardData, 
        'attendanceCount' => $attendanceCount, 
        'weeklyAverage' => $weeklyAverage, 
        'checkinRate' => $memberCount ? round(($attendanceCount / $memberCount) * 100) : 0, 
        'monthlyCheckins' => $monthlyCheckins, 
        'yearlyCheckins' => $yearlyCheckins, 
        'returningRate' => $returningRate, 
        'reportRecords' => $reportData['paginatedRecords'], 
        'reportPeriod' => $reportData['period'], 
        'reportStart' => $reportData['start'], 
        'reportEnd' => $reportData['end'], 
        'selectedReportSession' => $reportData['selectedSession'], 
        'reportSessions' => $reportSessions, 
        'pendingUsers' => $user->isSuperAdmin() ? User::whereIn('role', ['leader', 'admin'])->where('approval_status', 'pending')->latest()->get() : collect(), 
        'activeSession' => AttendanceSession::where(function ($query) {
            $query->whereNull('ended_at')->orWhere('ended_at', '>', now());
        })->latest('started_at')->first(),
        'gatheringTypes' => Setting::get('gathering_types', ['Sunday worship', 'Prayer meeting', 'Youth fellowship']),
        'membershipGroups' => Setting::get('membership_groups', ['General congregation', 'Volunteer team', 'Youth ministry'])
    ]);
})->middleware('auth')->name('dashboard');

Route::get('/leader/history', function () {
    /** @var User|null $user */
    $user = Auth::user();
    abort_unless($user?->role === 'leader' && $user->isApproved(), 403);
    $leaderId = $user->getAuthIdentifier();

    return view('leader-history', ['sessions' => AttendanceSession::with(['records.member', 'leader'])->withCount('records')->whereNull('deleted_at')->where('started_by', $leaderId)->orderByDesc('started_at')->orderByDesc('id')->paginate(3)->withQueryString()]);
})->middleware('auth')->name('leader.history');

Route::get('/login', [AuthController::class, 'showLogin'])->name('login');
Route::post('/login', [AuthController::class, 'login']);
Route::get('/register', [AuthController::class, 'showRegister'])->name('register');
Route::post('/register', [AuthController::class, 'register']);
Route::post('/logout', [AuthController::class, 'logout'])->name('logout');

Route::middleware('auth')->group(function () {
    Route::get('/settings', function () {
        /** @var User|null $user */
        $user = Auth::user();
        abort_unless($user?->isSuperAdmin(), 403);
        return app(SettingsController::class)->index();
    })->name('settings');
    Route::get('/admin/reports/pdf', [ReportController::class, 'exportPdf'])->name('admin.reports.pdf');
    Route::get('/admin/reports/word', [ReportController::class, 'exportWord'])->name('admin.reports.word');
    Route::get('/admin/approvals', function () {
        /** @var User $user */
        $user = Auth::user();
        abort_unless($user->isSuperAdmin(), 403);
        return view('approvals', ['pendingUsers' => User::whereIn('role', ['leader', 'admin'])->where('approval_status', 'pending')->latest()->get()]);
    })->name('admin.approvals');
    Route::post('/admin/approvals/{user}', function (Request $request, User $user) {
        /** @var User $admin */
        $admin = Auth::user();
        abort_unless($admin->isSuperAdmin(), 403);
        $user->update(['approval_status' => 'approved', 'approved_at' => now()]);
        return $request->expectsJson()
            ? response()->json(['approved' => true, 'user_id' => $user->id])
            : back()->with('status', $user->name . ' is now approved.');
    })->name('admin.approvals.approve');
    Route::delete('/admin/approvals/{user}', function (Request $request, User $user) {
        /** @var User $admin */
        $admin = Auth::user();
        abort_unless($admin->isSuperAdmin(), 403);
        $user->forceDelete();
        return $request->expectsJson()
            ? response()->json(['declined' => true, 'user_id' => $user->id])
            : back()->with('status', $user->name . ' has been declined and removed.');
    })->name('admin.approvals.decline');
});

Route::prefix('api')->middleware('auth')->group(function () {
    Route::get('/attendance/dashboard', [AttendanceController::class, 'dashboard']);
    Route::get('/admin/dashboard', [AttendanceController::class, 'adminDashboard']);
    Route::post('/attendance/sessions', [AttendanceController::class, 'start']);
    Route::post('/attendance/sessions/{session}/end', [AttendanceController::class, 'end']);
    Route::post('/attendance/sessions/{session}/archive', [AttendanceController::class, 'archiveSession']);
    Route::post('/attendance/sessions/{session}/restore', [AttendanceController::class, 'restoreSession']);
    Route::delete('/attendance/sessions/{session}', [AttendanceController::class, 'forceDeleteSession']);
    Route::post('/attendance/check-ins', [AttendanceController::class, 'checkIn']);
    Route::post('/attendance/manual-check-ins', [AttendanceController::class, 'manualCheckIn']);
    Route::post('/members', [AttendanceController::class, 'storeMember']);
    Route::delete('/members/{member}', [AttendanceController::class, 'destroyMember']);
    Route::post('/users/{user}/archive', [AttendanceController::class, 'archiveUser']);
    Route::post('/users/{user}/restore', [AttendanceController::class, 'restoreUser']);
    Route::delete('/users/{user}', [AttendanceController::class, 'forceDeleteUser']);
    Route::post('/settings/gathering-types', function (Request $request) {
        /** @var User|null $user */
        $user = $request->user();
        abort_unless($user?->isSuperAdmin(), 403);
        return app(SettingsController::class)->updateGatheringTypes($request);
    });
    Route::post('/settings/membership-groups', function (Request $request) {
        /** @var User|null $user */
        $user = $request->user();
        abort_unless($user?->isSuperAdmin(), 403);
        return app(SettingsController::class)->updateMembershipGroups($request);
    });
});
