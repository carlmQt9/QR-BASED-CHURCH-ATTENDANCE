<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Forgot password / Gather</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="auth-body">
    <main class="auth-shell">
        <section class="auth-intro">
            <a class="auth-brand" href="{{ route('login') }}"><span class="brand-mark"><i></i><i></i><i></i><i></i></span><strong>GATHER</strong></a>
            <div class="intro-copy"><span class="section-kicker">Secure account recovery</span><h1><span class="typing-line typing-line-first">Come back</span><span class="typing-line typing-line-second"><em>with confidence.</em></span></h1><p>We will send a secure password reset link to your account email.</p></div>
        </section>
        <section class="auth-panel">
            <div class="auth-panel-top"><span>Reset password</span><span class="secure-label">⌁ Secure access</span></div>
            <div class="auth-form-wrap"><span class="section-kicker">Account recovery</span><h2>Forgot your password?</h2><p class="form-intro">Enter your account email and we will send instructions to reset it.</p>
                @if (session('status')) <div class="auth-toast" role="status"><span>✓</span>{{ session('status') }}</div> @endif
                @if ($errors->any()) <p style="color:#a05a5a;font-size:11px;line-height:1.5">{{ $errors->first() }}</p> @endif
                <form action="{{ route('password.email') }}" method="POST">
                    @csrf
                    <label>Email address<input type="email" name="email" value="{{ old('email') }}" placeholder="you@church.org" autocomplete="email" required></label>
                    <button class="auth-button" type="submit"><span class="auth-button-label"><span class="auth-button-text">Send reset link</span><span>↗</span></span></button>
                </form>
                <p class="switch-auth"><a href="{{ route('login') }}">Back to sign in</a></p>
            </div>
            <div class="auth-footer"><span>Protected workspace</span><span>© {{ date('Y') }} Gather</span></div>
        </section>
    </main>
</body>
</html>