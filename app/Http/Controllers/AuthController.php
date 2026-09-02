<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class AuthController extends Controller
{
    public function showLogin(): View
    {
        return view('login');
    }

    public function login(Request $request): RedirectResponse
    {
        $credentials = $request->validate(['email' => ['required', 'email'], 'password' => ['required', 'string']]);

        if (! Auth::attempt($credentials, $request->boolean('remember'))) {
            return back()->withErrors(['email' => 'Those credentials do not match our records.'])->withInput($request->only('email'));
        }

        $request->session()->regenerate();
        $user = $request->user();

        if (! $user->isApproved()) {
            Auth::logout();
            return redirect()->route('login')->with('status', 'Your account is awaiting super admin approval.');
        }

        return redirect()->intended(route('dashboard'));
    }

    public function showRegister(): View
    {
        return view('register');
    }

    public function register(Request $request): RedirectResponse
    {
        $validated = $request->validate(['name' => ['required', 'string', 'max:120'], 'email' => ['required', 'email', 'unique:users,email'], 'role' => ['required', 'in:leader,admin'], 'password' => ['required', 'confirmed', 'min:8']]);
        User::create([...$validated, 'approval_status' => 'pending']);

        return redirect()->route('login')->with('registration_success', 'Account created successfully! Please wait for admin approval before signing in.');
    }

    public function logout(Request $request): RedirectResponse
    {
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('login');
    }
}