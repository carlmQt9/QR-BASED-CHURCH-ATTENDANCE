<?php

use App\Http\Controllers\AttendanceController;
use App\Http\Controllers\AuthController;
use App\Models\User;
use App\Models\AttendanceSession;
use App\Models\AttendanceRecord;
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
        return view('leader', ['activeSession' => AttendanceSession::with('records.member')->where('started_by', $user->id)->where(function ($query) {
            $query->whereNull('ended_at')->orWhere('ended_at', '>', now());
        })->latest('started_at')->first()]);
    }
    $members = User::where('role', 'member')->latest()->get();
    $sessions = AttendanceSession::with('records.member', 'leader')->latest('started_at')->get();
    $attendanceCount = AttendanceRecord::whereDate('checked_in_at', today())->count();
    $monthlyCheckins = AttendanceRecord::whereBetween('checked_in_at', [now()->startOfMonth(), now()->endOfMonth()])->count();
    $yearlyCheckins = AttendanceRecord::whereBetween('checked_in_at', [now()->startOfYear(), now()->endOfYear()])->count();
    return view('welcome', ['members' => $members, 'sessions' => $sessions, 'attendanceCount' => $attendanceCount, 'weeklyAverage' => $sessions->filter(fn ($session) => $session->started_at->greaterThanOrEqualTo(now()->subDays(7)))->avg(fn ($session) => $session->records->count()) ?: 0, 'checkinRate' => $members->count() ? round(($attendanceCount / $members->count()) * 100) : 0, 'monthlyCheckins' => $monthlyCheckins, 'yearlyCheckins' => $yearlyCheckins, 'returningRate' => 0, 'pendingUsers' => $user->isSuperAdmin() ? User::where('role', 'leader')->where('approval_status', 'pending')->latest()->get() : collect(), 'activeSession' => AttendanceSession::where(function ($query) {
        $query->whereNull('ended_at')->orWhere('ended_at', '>', now());
    })->latest('started_at')->first()]);
})->middleware('auth')->name('dashboard');

Route::get('/login', [AuthController::class, 'showLogin'])->name('login');
Route::post('/login', [AuthController::class, 'login']);
Route::get('/register', [AuthController::class, 'showRegister'])->name('register');
Route::post('/register', [AuthController::class, 'register']);
Route::post('/logout', [AuthController::class, 'logout'])->name('logout');

Route::middleware('auth')->group(function () {
    Route::get('/admin/approvals', function () {
        /** @var User $user */
        $user = Auth::user();
        abort_unless($user->isSuperAdmin(), 403);
        return view('approvals', ['pendingUsers' => User::where('role', 'leader')->where('approval_status', 'pending')->latest()->get()]);
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
});

Route::prefix('api')->middleware('auth')->group(function () {
    Route::get('/attendance/dashboard', [AttendanceController::class, 'dashboard']);
    Route::post('/attendance/sessions', [AttendanceController::class, 'start']);
    Route::post('/attendance/check-ins', [AttendanceController::class, 'checkIn']);
    Route::post('/members', [AttendanceController::class, 'storeMember']);
    Route::delete('/members/{member}', [AttendanceController::class, 'destroyMember']);
});
