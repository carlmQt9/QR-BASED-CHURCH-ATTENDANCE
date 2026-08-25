<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <link rel="icon" type="image/svg+xml" href="{{ asset('favicon.svg') }}">
    <title>Settings / Gather</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body data-app-url="{{ url('/') }}">
{{-- Success notification --}}
<div class="notification-container" id="notification-container"></div>
<div class="app-shell">
    <aside class="sidebar" aria-label="Admin navigation">
        <div class="brand"><div class="brand-mark"><span></span><span></span><span></span><span></span></div><div><strong>GATHER</strong><small>Church attendance</small></div></div>
        <div class="workspace-label">{{ auth()->user()->role === 'leader' ? 'Leader workspace' : 'Admin workspace' }}</div>
        <nav class="nav-list">
            @if(auth()->user()->role === 'leader')
                <a class="nav-item" href="{{ route('dashboard') }}"><span class="icon">⌂</span>My sessions</a>
                <a class="nav-item" href="{{ route('leader.history') }}"><span class="icon">▤</span>Session history</a>
            @else
                <a class="nav-item" href="{{ route('dashboard', ['view' => 'overview']) }}"><span class="icon">⌂</span>Overview</a>
                <a class="nav-item" href="{{ route('dashboard', ['view' => 'attendance']) }}"><span class="icon">⌁</span>Attendance</a>
                <a class="nav-item" href="{{ route('dashboard', ['view' => 'members']) }}"><span class="icon">◉</span>Users</a>
                <a class="nav-item" href="{{ route('dashboard', ['view' => 'reports']) }}"><span class="icon">▥</span>Reports</a>
            @endif
            <a class="nav-item active" href="{{ route('settings') }}"><span class="icon">⚙</span>Settings</a>
        </nav>
        <div class="sidebar-bottom">
            <form class="logout-form" action="{{ route('logout') }}" method="POST">
                @csrf
                <button class="nav-item" style="width:100%" type="submit"><span class="icon">↪</span>Log out</button>
            </form>
            <div class="user-chip">
                <div class="avatar">{{ collect(explode(' ', auth()->user()->name))->map(fn ($part) => substr($part, 0, 1))->join('') }}</div>
                <div><strong>{{ auth()->user()->name }}</strong><small>{{ ucfirst(auth()->user()->role) }}</small></div>
            </div>
        </div>
    </aside>
    <main class="main-content">
        <header class="topbar">
            <button class="mobile-menu" aria-label="Open navigation">☰</button>
            <div class="breadcrumb"><span>{{ now()->format('l, d F Y') }}</span><i>/</i><strong>Settings</strong></div>
            <div class="top-actions">
                <button class="avatar avatar-small">{{ collect(explode(' ', auth()->user()->name))->map(fn ($part) => substr($part, 0, 1))->join('') }}</button>
            </div>
        </header>
        <div class="page-view settings-page">
            <section class="page-heading">
                <div>
                    <p class="eyebrow">Configuration</p>
                    <h1>Application settings</h1>
                    <p class="muted">Customize gathering types and membership groups for your church.</p>
                </div>
            </section>

            {{-- Gathering Types --}}
            <section class="panel settings-panel">
                <div class="panel-heading">
                    <div>
                        <span class="section-kicker">Session configuration</span>
                        <h2>Gathering types</h2>
                        <p class="muted">These options appear when creating a new attendance session.</p>
                    </div>
                </div>
                <div class="settings-list" id="gathering-types-list">
                    @foreach($gatheringTypes as $index => $type)
                        <div class="settings-item">
                            <input type="text" class="settings-input" value="{{ $type }}" data-index="{{ $index }}">
                            <button class="button button-quiet remove-item" type="button" aria-label="Remove {{ $type }}" title="Remove">
                                <svg viewBox="0 0 20 20" aria-hidden="true"><path d="M5 5l10 10M15 5L5 15" /></svg>
                            </button>
                        </div>
                    @endforeach
                </div>
                <div class="settings-actions">
                    <button class="button button-light" id="add-gathering-type" type="button">+ Add gathering type</button>
                    <button class="button button-dark" id="save-gathering-types" type="button">Save changes</button>
                </div>
            </section>

            {{-- Membership Groups --}}
            <section class="panel settings-panel">
                <div class="panel-heading">
                    <div>
                        <span class="section-kicker">Member configuration</span>
                        <h2>Membership groups</h2>
                        <p class="muted">These options appear when adding a new church member.</p>
                    </div>
                </div>
                <div class="settings-list" id="membership-groups-list">
                    @foreach($membershipGroups as $index => $group)
                        <div class="settings-item">
                            <input type="text" class="settings-input" value="{{ $group }}" data-index="{{ $index }}">
                            <button class="button button-quiet remove-item" type="button" aria-label="Remove {{ $group }}" title="Remove">
                                <svg viewBox="0 0 20 20" aria-hidden="true"><path d="M5 5l10 10M15 5L5 15" /></svg>
                            </button>
                        </div>
                    @endforeach
                </div>
                <div class="settings-actions">
                    <button class="button button-light" id="add-membership-group" type="button">+ Add membership group</button>
                    <button class="button button-dark" id="save-membership-groups" type="button">Save changes</button>
                </div>
            </section>
        </div>
    </main>
</div>
</body>
</html>
