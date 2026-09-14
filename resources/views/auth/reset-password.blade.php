<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Reset password / Gather</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="auth-body">
    <main class="auth-shell">
        <section class="auth-intro">
            <a class="auth-brand" href="{{ route('login') }}"><span class="brand-mark"><i></i><i></i><i></i><i></i></span><strong>GATHER</strong></a>
            <div class="intro-copy"><span class="section-kicker">Secure account recovery</span><h1>Choose a<br><em>new password.</em></h1><p>Use a password you have not used before and keep your workspace protected.</p></div>
        </section>
        <section class="auth-panel">
            <div class="auth-panel-top"><span>New password</span><span class="secure-label">⌁ Secure access</span></div>
            <div class="auth-form-wrap"><span class="section-kicker">Account recovery</span><h2>Reset your password</h2><p class="form-intro">Choose a new password with at least 8 characters.</p>
                @if ($errors->any()) <p style="color:#a05a5a;font-size:11px;line-height:1.5">{{ $errors->first() }}</p> @endif
                <form action="{{ route('password.update') }}" method="POST">
                    @csrf
                    <input type="hidden" name="token" value="{{ $token }}">
                    <label>Email address<input type="email" name="email" value="{{ old('email', $email) }}" autocomplete="email" required></label>
                    <label>New password<div class="password-field"><input type="password" name="password" placeholder="At least 8 characters" minlength="8" autocomplete="new-password" required><button type="button" class="password-toggle" aria-label="Show password" title="Show password"><svg viewBox="0 0 24 24" aria-hidden="true"><path d="M2.5 12s3.5-6 9.5-6 9.5 6 9.5 6-3.5 6-9.5 6-9.5-6-9.5-6Z"/><circle cx="12" cy="12" r="2.5"/></svg></button></div></label>
                    <label>Confirm password<div class="password-field"><input type="password" name="password_confirmation" placeholder="Repeat your password" autocomplete="new-password" required><button type="button" class="password-toggle" aria-label="Show password" title="Show password"><svg viewBox="0 0 24 24" aria-hidden="true"><path d="M2.5 12s3.5-6 9.5-6 9.5 6 9.5 6-3.5 6-9.5 6-9.5-6-9.5-6Z"/><circle cx="12" cy="12" r="2.5"/></svg></button></div></label>
                    <button class="auth-button" type="submit"><span class="auth-button-label"><span class="auth-button-text">Reset password</span><span>↗</span></span></button>
                </form>
                <p class="switch-auth"><a href="{{ route('login') }}">Back to sign in</a></p>
            </div>
            <div class="auth-footer"><span>Protected workspace</span><span>© {{ date('Y') }} Gather</span></div>
        </section>
    </main>
</body>
</html>