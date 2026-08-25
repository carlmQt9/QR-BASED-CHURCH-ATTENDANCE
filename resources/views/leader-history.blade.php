<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Session history / Gather</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body>
<div class="app-shell">
    <aside class="sidebar" aria-label="Leader navigation">
        <div class="brand"><div class="brand-mark"><span></span><span></span><span></span><span></span></div><div><strong>GATHER</strong><small>Church attendance</small></div></div>
        <div class="workspace-label">Leader workspace</div>
        <nav class="nav-list"><a class="nav-item" href="{{ route('dashboard') }}"><span class="icon">⌂</span>My sessions</a><a class="nav-item active" href="{{ route('leader.history') }}"><span class="icon">▤</span>Session history</a></nav>
        <div class="sidebar-bottom"><form class="logout-form" action="{{ route('logout') }}" method="POST">@csrf<button class="nav-item" style="width:100%" type="submit"><span class="icon">↪</span>Log out</button></form><div class="user-chip"><div class="avatar">{{ collect(explode(' ', auth()->user()->name))->map(fn ($part) => substr($part, 0, 1))->join('') }}</div><div><strong>{{ auth()->user()->name }}</strong><small>Attendance leader</small></div></div></div>
    </aside>
    <main class="main-content">
        <header class="topbar"><button class="mobile-menu" aria-label="Open navigation">☰</button><div class="breadcrumb"><span>{{ now()->format('l, d F Y') }}</span><i>/</i><strong>Session history</strong></div><div class="top-actions"><button class="avatar avatar-small">{{ collect(explode(' ', auth()->user()->name))->map(fn ($part) => substr($part, 0, 1))->join('') }}</button></div></header>
        <div class="page-view leader-page">
            <section class="page-heading"><div><p class="eyebrow">Your records</p><h1>Session history</h1><p class="muted">A short list of your recent gatherings.</p></div></section>
            <section class="session-history">@forelse ($sessions as $session)<article class="panel history-session"><div class="history-header"><div><span class="section-kicker">{{ $session->type }}</span><h2>{{ $session->name }}</h2><p class="muted">{{ $session->started_at->format('D, d M Y · h:i A') }} · {{ $session->location ?: 'Main campus' }} · {{ $session->duration_minutes }} minutes</p></div><div class="history-total"><strong>{{ $session->records_count }}</strong><span>attended</span></div></div><div class="history-attendees">@forelse ($session->records as $record)<span class="attendee-chip">{{ $record->member->name }}<small>{{ $record->checked_in_at->format('h:i A') }}</small></span>@empty<p class="muted">No members attended.</p>@endforelse</div></article>@empty<section class="panel empty-history"><h2>No session history yet.</h2><p class="muted">Your completed gatherings will appear here.</p></section>@endforelse</section>
            @if ($sessions->hasPages())<nav class="history-pagination" aria-label="Session history pages"><span class="history-pagination-summary">Showing {{ $sessions->firstItem() }}–{{ $sessions->lastItem() }} of {{ $sessions->total() }}</span><div class="history-pagination-controls">@if ($sessions->onFirstPage())<span class="pagination-control disabled">Previous</span>@else<a class="pagination-control" href="{{ $sessions->previousPageUrl() }}">Previous</a>@endif @foreach ($sessions->getUrlRange(max(1, $sessions->currentPage() - 1), min($sessions->lastPage(), $sessions->currentPage() + 1)) as $page => $url) @if ($page === $sessions->currentPage())<span class="pagination-control current" aria-current="page">{{ $page }}</span>@else<a class="pagination-control" href="{{ $url }}">{{ $page }}</a>@endif @endforeach @if ($sessions->hasMorePages())<a class="pagination-control" href="{{ $sessions->nextPageUrl() }}">Next</a>@else<span class="pagination-control disabled">Next</span>@endif</div></nav>@endif
        </div>
    </main>
</div>
</body>
</html>
