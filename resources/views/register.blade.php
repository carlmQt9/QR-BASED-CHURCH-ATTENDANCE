<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Create account / Gather</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="auth-body">
    <main class="auth-shell">
        <section class="auth-intro">
            <a class="auth-brand" href="{{ url('/') }}"><span class="brand-mark"><i></i><i></i><i></i><i></i></span><strong>GATHER</strong></a>
            <div class="intro-copy"><span class="section-kicker">A calmer way to count</span><h1>Make every<br><em>arrival matter.</em></h1><p>Set up your secure workspace and give your team a simple, reliable way to welcome every member.</p></div>
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
                    <label>Your role<select name="role"><option value="leader">Attendance leader</option></select></label>
                    <label>Create password<div class="password-field"><input type="password" name="password" placeholder="At least 8 characters" minlength="8" autocomplete="new-password" required><button type="button" class="password-toggle" aria-label="Show password">Show</button></div></label>
                    <label>Confirm password<input type="password" name="password_confirmation" placeholder="Repeat your password" autocomplete="new-password" required></label>
                    <label class="check-label terms-check"><input type="checkbox" required> <span>I agree to the <a href="#">terms of service</a> and privacy policy.</span></label>
                    <button class="auth-button" type="submit">Create account <span>↗</span></button>
                </form>
                <p class="switch-auth">Already have an account? <a href="{{ route('login') }}">Sign in</a></p>
            </div>
            <div class="auth-footer"><span>Protected workspace</span><span>© {{ date('Y') }} Gather</span></div>
        </section>
    </main>
</body>
</html>
