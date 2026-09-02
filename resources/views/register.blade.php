<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <link rel="icon" type="image/svg+xml" href="{{ asset('favicon.svg') }}">
    <title>Create account / Gather</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="auth-body">
    <main class="auth-shell">
        <section class="auth-intro">
            <a class="auth-brand" href="{{ url('/') }}"><span class="brand-mark"><i></i><i></i><i></i><i></i></span><strong>GATHER</strong></a>
            <div class="intro-copy"><span class="section-kicker">A calmer way to count</span><h1><span class="typing-line typing-line-first">Make every</span><span class="typing-line typing-line-second"><em>arrival matter.</em></span></h1><p>Set up your secure workspace and give your team a simple, reliable way to welcome every member.</p></div>
            <div class="intro-note"><span>02</span><p>Built for admins, leaders,<br>and the people they serve.</p></div>
        </section>
        <section class="auth-panel">
            <div class="auth-panel-top"><span>Create workspace</span><span class="secure-label">⌁ Encrypted setup</span></div>
            <div class="auth-form-wrap"><span class="section-kicker">Start with your team</span><h2>Create your account</h2><p class="form-intro">Your admin can invite leaders and add members later.</p>
                @if ($errors->any()) <p style="color:#a05a5a;font-size:11px;line-height:1.5">{{ $errors->first() }}</p> @endif
                <form action="{{ route('register') }}" method="POST">
                    @csrf
                    <label>Full name<input type="text" name="name" value="{{ old('name') }}" placeholder="Jordan Davis" autocomplete="name" required></label>
                    <label>Church email<input type="email" name="email" value="{{ old('email') }}" placeholder="you@church.org" autocomplete="email" required></label>
                    <label>Your role<select name="role"><option value="leader">Attendance leader</option><option value="admin">Super admin</option></select></label>
                    <label>Create password<div class="password-field"><input type="password" name="password" placeholder="At least 8 characters" minlength="8" autocomplete="new-password" required><button type="button" class="password-toggle" aria-label="Show password" title="Show password"><svg viewBox="0 0 24 24" aria-hidden="true"><path d="M2.5 12s3.5-6 9.5-6 9.5 6 9.5 6-3.5 6-9.5 6-9.5-6-9.5-6Z"/><circle cx="12" cy="12" r="2.5"/></svg></button></div></label>
                    <label>Confirm password<div class="password-field"><input type="password" name="password_confirmation" placeholder="Repeat your password" autocomplete="new-password" required><button type="button" class="password-toggle" aria-label="Show password" title="Show password"><svg viewBox="0 0 24 24" aria-hidden="true"><path d="M2.5 12s3.5-6 9.5-6 9.5 6 9.5 6-3.5 6-9.5 6-9.5-6-9.5-6Z"/><circle cx="12" cy="12" r="2.5"/></svg></button></div></label>
                    <label class="check-label terms-check"><input type="checkbox" required> <span>I agree to the <a href="#" data-open-terms>terms of service</a> and privacy policy.</span></label>
                    <button class="auth-button" type="submit" data-loading-label="Creating account..."><span class="auth-button-label"><span class="auth-button-text">Create account</span><span>↗</span></span><span class="auth-button-loader" aria-hidden="true"></span></button>
                </form>
                <p class="switch-auth">Already have an account? <a href="{{ route('login') }}">Sign in</a></p>
            </div>
            <div class="auth-footer"><span>Protected workspace</span><span>© {{ date('Y') }} Gather</span></div>
        </section>
    </main>
    <div class="modal-backdrop" id="terms-modal" aria-hidden="true">
        <section class="modal terms-modal" role="dialog" aria-modal="true" aria-labelledby="terms-title">
            <button class="modal-close" data-close-terms type="button" aria-label="Close terms">×</button>
            <span class="section-kicker">Important information</span>
            <h2 id="terms-title">Terms of service</h2>
            <p class="muted">Gather helps your church manage member accounts, QR check-ins, and attendance records in one secure workspace.</p>
            <ul><li>Provide accurate account information.</li><li>Keep your password and QR card secure.</li><li>New administrator and leader accounts require Super Admin approval.</li><li>Use attendance records only for your church’s legitimate administration.</li></ul>
            <button class="button button-dark" data-close-terms type="button">Close</button>
        </section>
    </div>
</body>
</html>
