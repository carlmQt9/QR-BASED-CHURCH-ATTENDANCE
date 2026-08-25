<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <link rel="icon" type="image/svg+xml" href="{{ asset('favicon.svg') }}">
    <title>Approvals / Gather</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body>
    <main class="page-view" style="max-width:900px;margin:auto;min-height:100vh">
        <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:48px"><a class="auth-brand" style="color:#111" href="{{ route('dashboard') }}"><span class="brand-mark" style="filter:invert(1)"><i></i><i></i><i></i><i></i></span><strong>GATHER</strong></a><form action="{{ route('logout') }}" method="POST">@csrf<button class="button button-light" type="submit">Log out</button></form></div>
        <p class="eyebrow">Super admin</p><h1>Account approvals</h1><p class="muted" style="margin:12px 0 35px">Review new accounts before they can enter the attendance workspace.</p>
        @if (session('status')) <p style="color:#5e875f;margin-bottom:20px">{{ session('status') }}</p> @endif
        <section class="panel" style="padding:24px">
            @forelse ($pendingUsers as $pendingUser)
                <div style="display:flex;justify-content:space-between;align-items:center;gap:15px;border-bottom:1px solid #e7e7e3;padding:17px 0"><div><strong>{{ $pendingUser->name }}</strong><p class="muted" style="font-size:11px;margin-top:4px">{{ $pendingUser->email }} · {{ $pendingUser->role === 'admin' ? 'Admin' : 'Leader' }} · Registered {{ $pendingUser->created_at->diffForHumans() }}</p></div><form action="{{ route('admin.approvals.approve', $pendingUser) }}" method="POST">@csrf<button class="button button-dark" type="submit">Approve {{ $pendingUser->role === 'admin' ? 'admin' : 'leader' }}</button></form></div>
            @empty
                <p class="muted">No accounts are waiting for approval.</p>
            @endforelse
        </section>
    </main>
</body>
</html>