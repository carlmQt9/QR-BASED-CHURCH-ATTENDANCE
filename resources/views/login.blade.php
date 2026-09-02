<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <link rel="icon" type="image/svg+xml" href="{{ asset('favicon.svg') }}">
    <title>Sign in / Gather</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    <style>
        .reg-toast-wrap {
            position: fixed;
            top: 20px;
            left: 50%;
            transform: translateX(-50%) translateY(-12px);
            z-index: 999;
            opacity: 0;
            pointer-events: none;
            transition: opacity .3s ease, transform .3s ease;
            width: calc(100% - 32px);
            max-width: 380px;
        }
        .reg-toast-wrap.visible {
            opacity: 1;
            transform: translateX(-50%) translateY(0);
            pointer-events: auto;
        }
        .reg-toast {
            background: #fff;
            border: 1px solid #c7ddc7;
            border-radius: 10px;
            box-shadow: 0 8px 32px rgba(0,0,0,.13);
            padding: 14px 14px 14px 14px;
            display: flex;
            align-items: flex-start;
            gap: 11px;
            width: 100%;
        }
        .reg-toast-icon {
            width: 28px;
            height: 28px;
            border-radius: 50%;
            background: #3f6842;
            color: #fff;
            display: grid;
            place-items: center;
            font-size: 13px;
            flex-shrink: 0;
            margin-top: 1px;
        }
        .reg-toast-body {
            flex: 1;
            min-width: 0;
        }
        .reg-toast-title {
            font-size: 12px;
            font-weight: 700;
            color: #111;
            margin: 0 0 3px;
        }
        .reg-toast-msg {
            font-size: 11px;
            color: #555;
            line-height: 1.5;
            margin: 0;
            word-break: break-word;
        }
        .reg-toast-close {
            background: none;
            border: 0;
            color: #aaa;
            font-size: 18px;
            line-height: 1;
            cursor: pointer;
            padding: 0;
            flex-shrink: 0;
            margin-top: -1px;
        }
        .reg-toast-close:hover { color: #333; }
        @media (max-width: 480px) {
            .reg-toast-wrap {
                top: 12px;
                width: calc(100% - 24px);
            }
            .reg-toast {
                padding: 12px;
                gap: 9px;
            }
            .reg-toast-icon {
                width: 24px;
                height: 24px;
                font-size: 11px;
            }
            .reg-toast-title { font-size: 11px; }
            .reg-toast-msg { font-size: 10px; }
        }
    </style>
</head>
<body class="auth-body">

    @if (session('registration_success'))
    <div class="reg-toast-wrap" id="reg-toast" role="status" aria-live="polite">
        <div class="reg-toast">
            <div class="reg-toast-icon">✓</div>
            <div class="reg-toast-body">
                <p class="reg-toast-title">Account created!</p>
                <p class="reg-toast-msg">Please wait for admin approval before signing in.</p>
            </div>
            <button class="reg-toast-close" id="reg-toast-close" aria-label="Dismiss">×</button>
        </div>
    </div>
    <script>
        document.addEventListener('DOMContentLoaded', function () {
            var toast = document.getElementById('reg-toast');
            var closeBtn = document.getElementById('reg-toast-close');
            if (toast) {
                setTimeout(function () { toast.classList.add('visible'); }, 120);
                setTimeout(function () { toast.classList.remove('visible'); }, 7000);
                closeBtn && closeBtn.addEventListener('click', function () {
                    toast.classList.remove('visible');
                });
            }
        });
    </script>
    @endif

    <main class="auth-shell">
        <section class="auth-intro">
            <a class="auth-brand" href="{{ url('/') }}"><span class="brand-mark"><i></i><i></i><i></i><i></i></span><strong>GATHER</strong></a>
            <div class="intro-copy"><span class="section-kicker">Church attendance platform</span><h1><span class="typing-line typing-line-first">Gather your people.</span><span class="typing-line typing-line-second"><em>Keep them close.</em></span></h1><p>One secure place for your church community, from the first welcome to every Sunday after.</p></div>
            <div class="intro-note"><span>01</span><p>Trusted by growing congregations<br>to make every arrival count.</p></div>
        </section>
        <section class="auth-panel">
            <div class="auth-panel-top"><span>Welcome back</span><span class="secure-label">⌁ Secure access</span></div>
            <div class="auth-form-wrap"><span class="section-kicker">Administrator / Leader</span><h2>Sign in to Gather</h2><p class="form-intro">Access your church attendance workspace.</p>
                @if (session('status')) <div class="auth-toast" role="status"><span>✓</span>{{ session('status') }}</div> @endif
                @if ($errors->any()) <p style="color:#a05a5a;font-size:11px;line-height:1.5">{{ $errors->first() }}</p> @endif
                <form action="{{ route('login') }}" method="POST">
                    @csrf
                    <label>Email address<input type="email" name="email" value="{{ old('email') }}" placeholder="you@church.org" autocomplete="email" required></label>
                    <label>Password<div class="password-field"><input type="password" name="password" placeholder="Enter your password" autocomplete="current-password" required><button type="button" class="password-toggle" aria-label="Show password" title="Show password"><svg viewBox="0 0 24 24" aria-hidden="true"><path d="M2.5 12s3.5-6 9.5-6 9.5 6 9.5 6-3.5 6-9.5 6-9.5-6-9.5-6Z"/><circle cx="12" cy="12" r="2.5"/></svg></button></div></label>
                    <div class="form-options"><label class="check-label"><input type="checkbox" name="remember"> <span>Remember me</span></label><a href="#">Forgot password?</a></div>
                    <button class="auth-button" type="submit" data-loading-label="Signing in..."><span class="auth-button-label"><span class="auth-button-text">Sign in</span><span>↗</span></span><span class="auth-button-loader" aria-hidden="true"></span></button>
                </form>
                <p class="switch-auth">New to Gather? <a href="{{ route('register') }}">Create an account</a></p>
            </div>
            <div class="auth-footer"><span>Protected workspace</span><span>© {{ date('Y') }} Gather</span></div>
        </section>
    </main>
</body>
</html>
