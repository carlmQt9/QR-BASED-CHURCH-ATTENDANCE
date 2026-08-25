<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Leader dashboard / Gather</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body data-auto-open-scanner="{{ session('status') ? 'true' : 'false' }}" data-session-id="{{ $activeSession?->id }}">
<div class="app-shell">
    <aside class="sidebar" aria-label="Leader navigation">
        <div class="brand"><div class="brand-mark"><span></span><span></span><span></span><span></span></div><div><strong>GATHER</strong><small>Church attendance</small></div></div>
        <div class="workspace-label">Leader workspace</div>
        <nav class="nav-list"><button class="nav-item active"><span class="icon">⌂</span>My sessions</button>@if ($activeSession)<button class="nav-item" data-view-attendees type="button"><span class="icon">◉</span>Attendance list</button>@endif</nav>
        <div class="sidebar-bottom"><form class="logout-form" action="{{ route('logout') }}" method="POST">@csrf<button class="nav-item" style="width:100%" type="submit"><span class="icon">↪</span>Log out</button></form><div class="user-chip"><div class="avatar">{{ collect(explode(' ', auth()->user()->name))->map(fn ($part) => substr($part, 0, 1))->join('') }}</div><div><strong>{{ auth()->user()->name }}</strong><small>Attendance leader</small></div></div></div>
    </aside>
    <main class="main-content">
        <header class="topbar"><button class="mobile-menu" aria-label="Open navigation">☰</button><div class="breadcrumb"><span>{{ now()->format('l, d F Y') }}</span><i>/</i><strong>Leader dashboard</strong></div><div class="top-actions"><button class="avatar avatar-small">{{ collect(explode(' ', auth()->user()->name))->map(fn ($part) => substr($part, 0, 1))->join('') }}</button></div></header>
        <div class="page-view leader-page" data-session-id="{{ $activeSession?->id }}">
            <section class="page-heading"><div><p class="eyebrow">Attendance leader</p><h1>Set up your gathering.</h1><p class="muted">Start a session before people arrive, then keep the live check-in moving.</p></div>@if ($activeSession)<button class="button button-dark" data-open-scanner><span>⌁</span>Open scanner</button>@endif</section>
            @if (session('status'))<div class="notice">{{ session('status') }}</div>@endif
            @if ($activeSession)
                <section class="leader-live panel"><div><span class="section-kicker">Session in progress <span class="pulse"></span></span><h2>{{ $activeSession->name }}</h2><p class="muted">{{ $activeSession->type }} · {{ $activeSession->started_at->format('D, d M Y · h:i A') }} · {{ $activeSession->location ?: 'Main campus' }}</p><div class="big-count"><strong id="attendance-count">{{ $activeSession->records()->count() }}</strong><span>members present</span></div></div><div class="session-status"><span class="live-dot">● Live</span><strong>Ready to scan</strong><small>Ends {{ $activeSession->ended_at->format('h:i A') }} · {{ $activeSession->duration_minutes }} minutes</small><button class="button button-light" data-open-scanner>Open scanner</button></div></section><section class="panel attendee-panel" id="attendee-panel"><div class="panel-heading"><div><span class="section-kicker">Live list</span><h2>Members already attended</h2></div><span class="muted">Updates every 3 seconds</span></div><div class="attendee-list" id="attendee-list">@forelse ($activeSession->records as $record)<div class="attendee-row"><strong>{{ $record->member->name }}</strong><span>{{ $record->checked_in_at->format('h:i A') }}</span><em>Present</em></div>@empty<p class="muted">No members checked in yet.</p>@endforelse</div></section>
            @else
                <section class="session-builder panel"><div class="builder-intro"><span class="section-kicker">New attendance session</span><h2>What are you gathering for?</h2><p class="muted">Create a timed session for today’s service, meeting, or rehearsal.</p></div><form action="{{ url('/api/attendance/sessions') }}" method="POST" class="session-form">@csrf<input type="hidden" name="duration_minutes" id="duration-value" value="90"><label>Gathering type<select name="type" required><option>Sunday worship</option><option>Prayer meeting</option><option>Saturday worship practice</option><option>Youth fellowship</option><option>Small group</option><option>Special event</option></select></label><label>Session name<input type="text" name="name" placeholder="Morning worship" required></label><label>Date and time<input type="datetime-local" name="started_at" value="{{ now()->format('Y-m-d\\TH:i') }}" required></label><label>Location<input type="text" name="location" placeholder="Grace Hall"></label><label>Duration<div class="duration-control"><button type="button" data-duration="-15">−</button><strong id="duration-label">90 min</strong><button type="button" data-duration="15">＋</button></div></label><button class="button button-dark start-session" type="submit">Start attendance session <span>↗</span></button></form></section>
            @endif
            <section class="leader-tips"><div><span class="section-kicker">On the day</span><h2>Keep check-in simple.</h2></div><div class="tip-grid"><div><strong>01</strong><p>Open the scanner before members arrive.</p></div><div><strong>02</strong><p>Hold each member QR card inside the frame.</p></div><div><strong>03</strong><p>Watch your live count update as people enter.</p></div></div></section>
        </div>
    </main>
</div>
<div class="modal-backdrop" id="scanner-modal" aria-hidden="true"><section class="modal scanner-modal" role="dialog" aria-modal="true" aria-labelledby="scanner-title"><button class="modal-close" data-close-modal aria-label="Close scanner">×</button><div class="scanner-copy"><span class="section-kicker">Session live <span class="pulse"></span></span><h2 id="scanner-title">Scan member QR</h2><p class="muted">Point the camera at a member card. We will check for a new arrival every 3 seconds.</p><div class="scanner-meta"><span><b class="live-dot"></b> Camera ready</span><span>{{ $activeSession?->type ?: 'Gathering' }} · {{ $activeSession?->started_at?->format('h:i A') }}</span></div></div><div class="camera-frame"><div class="camera-guide"><i></i><i></i><i></i><i></i><div class="scan-line"></div><span id="scanner-message">Position QR inside frame</span></div><div class="camera-bottom"><span>● Auto-scan on</span><span>Next check in <b id="scan-countdown">3</b>s</span></div></div><div class="modal-footer"><button class="button button-light" data-close-modal>Close scanner</button></div></section></div>
</body>
</html>
