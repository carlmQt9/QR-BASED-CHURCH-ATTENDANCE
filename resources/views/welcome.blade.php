<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <link rel="icon" type="image/svg+xml" href="{{ asset('favicon.svg') }}">
    <title>Gather / Church attendance</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="admin-dashboard dashboard-view-{{ $currentView }} {{ $sessions->isEmpty() ? 'empty-dashboard' : '' }}" data-session-id="{{ $activeSession?->id }}" data-app-url="{{ url('/') }}" data-current-view="{{ $currentView }}" data-member-count="{{ $memberCount }}">
{{-- Success notification --}}
<div class="notification-container" id="notification-container"></div>
<script type="application/json" id="dashboard-data">{!! json_encode($dashboardData) !!}</script>
<style>.admin-dashboard .history-pagination:not(.attendance-pagination){display:none!important}.admin-dashboard .attendance-pagination{max-width:1100px;margin-left:auto;margin-right:auto}.dashboard-view-reports [data-page="reports"] .page-heading .button-light{display:none!important}</style>
<div class="app-shell">
    <aside class="sidebar" aria-label="Main navigation">
        <div class="brand"><div class="brand-mark"><span></span><span></span><span></span><span></span></div><div><strong>GATHER</strong><small>Church attendance</small></div></div>
        <div class="workspace-label">Workspace</div>
        <nav class="nav-list">
            <button class="nav-item {{ $currentView === 'overview' ? 'active' : '' }}" data-view="overview" type="button"><span class="icon">⌂</span>Overview</button>
            <button class="nav-item {{ $currentView === 'attendance' ? 'active' : '' }}" data-view="attendance" type="button"><span class="icon">⌁</span>Attendance</button>
            <button class="nav-item {{ $currentView === 'members' ? 'active' : '' }}" data-view="members" type="button"><span class="icon">◉</span>Users</button>
            <button class="nav-item {{ $currentView === 'reports' ? 'active' : '' }}" data-view="reports" type="button"><span class="icon">▥</span>Reports</button>
            @if (auth()->user()->isSuperAdmin())
                <a class="nav-item" href="{{ route('settings') }}"><span class="icon">⚙</span>Settings</a>
            @endif
        </nav>
        <div class="sidebar-bottom">@if (auth()->user()->isSuperAdmin())<button class="nav-item" data-open-approvals type="button"><span class="icon">✓</span>Approvals</button>@endif<form class="logout-form" action="{{ route('logout') }}" method="POST">@csrf<button class="nav-item" style="width:100%;margin-top:4px" type="submit"><span class="icon">↪</span>Log out</button></form><div class="user-chip"><div class="avatar">JD</div><div><strong>{{ auth()->user()->name }}</strong><small>{{ auth()->user()->isSuperAdmin() ? 'Super admin' : 'Attendance leader' }}</small></div><span class="more">•••</span></div></div>
    </aside>
    <main class="main-content">
        <header class="topbar"><button class="mobile-menu" aria-label="Open navigation">☰</button><div class="breadcrumb"><span>{{ now()->format('l, d F Y') }}</span><i>/</i><strong data-breadcrumb>{{ ucfirst($currentView) }}</strong></div><div class="top-actions"><button class="icon-button" title="Notifications" aria-label="Notifications">♧<b></b></button><button class="avatar avatar-small">{{ collect(explode(' ', auth()->user()->name))->map(fn ($part) => substr($part, 0, 1))->join('') }}</button></div></header>
        <div class="page-view" data-page="overview">
            <section class="page-heading"><div><p class="eyebrow">Good morning, {{ auth()->user()->name }}</p><h1>Sunday service overview</h1><p class="muted">A live pulse of your congregation, ready when you are.</p></div></section>
            <section class="stat-grid"><article class="stat-card stat-dark"><div class="stat-label">Today <span class="live-dot">Live</span></div><strong id="attendance-count">{{ $attendanceCount }}</strong><div class="stat-foot"><span>Checked in so far</span><span>0%</span></div></article><article class="stat-card"><div class="stat-label">Weekly average</div><strong>{{ (int) $weeklyAverage }}</strong><div class="stat-foot"><span>Across recorded sessions</span><span>0%</span></div></article><article class="stat-card"><div class="stat-label">Active members</div><strong>{{ $members->count() }}</strong><div class="stat-foot"><span>Registered members</span><span>{{ $members->count() ? '100%' : '0%' }}</span></div></article><article class="stat-card"><div class="stat-label">Check-in rate</div><strong>{{ $checkinRate }}<span class="unit">%</span></strong><div class="stat-foot"><span>Current attendance</span><span>0%</span></div></article></section>
            <section class="content-grid"><article class="panel chart-panel"><div class="panel-heading"><div><span class="section-kicker">Attendance pulse</span><h2>Service attendance</h2></div><select aria-label="Attendance period"><option>Last 7 days</option><option>Last 30 days</option><option>This year</option></select></div><div class="chart-wrap"><div class="y-axis"><span>250</span><span>200</span><span>150</span><span>100</span><span>50</span><span>0</span></div><div class="chart"><div class="grid-lines"></div><svg viewBox="0 0 700 210" preserveAspectRatio="none" aria-label="Attendance trend chart"><defs><linearGradient id="area" x1="0" y1="0" x2="0" y2="1"><stop offset="0" stop-color="#111" stop-opacity=".16"/><stop offset="1" stop-color="#111" stop-opacity="0"/></linearGradient></defs><path class="area" d="M0 170 C35 165, 50 124, 90 138 S145 155, 180 105 S230 133, 270 115 S315 80, 350 107 S405 114, 445 65 S490 100, 525 82 S575 40, 610 62 S660 60, 700 18 L700 210 L0 210Z"/><path class="line" d="M0 170 C35 165, 50 124, 90 138 S145 155, 180 105 S230 133, 270 115 S315 80, 350 107 S405 114, 445 65 S490 100, 525 82 S575 40, 610 62 S660 60, 700 18"/></svg><div class="x-axis"><span>Mon</span><span>Tue</span><span>Wed</span><span>Thu</span><span>Fri</span><span>Sat</span><span>Sun</span></div></div></div></article><article class="panel service-panel"><div class="panel-heading"><div><span class="section-kicker">Today</span><h2>Service sessions</h2></div><button class="text-button" data-open-scanner>Manage <span>↗</span></button></div><div class="service-list"><div class="service-row"><div class="service-time">08:00 <small>AM</small></div><div class="service-info"><strong>Morning worship</strong><span>Grace Hall · Main campus</span></div><div class="service-count">186 <small>present</small></div></div><div class="service-row"><div class="service-time">10:30 <small>AM</small></div><div class="service-info"><strong>Family service</strong><span>Grace Hall · Main campus</span></div><div class="service-count">— <small>upcoming</small></div></div><div class="service-row"><div class="service-time">05:00 <small>PM</small></div><div class="service-info"><strong>Evening gathering</strong><span>Grace Hall · Main campus</span></div><div class="service-count">— <small>upcoming</small></div></div></div></article></section>
            <section class="panel activity-panel"><div class="panel-heading"><div><span class="section-kicker">Live feed <span class="pulse"></span></span><h2>Recent check-ins</h2></div><button class="text-button" data-view-link="attendance">View all <span>↗</span></button></div><div class="activity-table"><div class="table-head"><span>Member</span><span>Service</span><span>Time</span><span>Status</span></div><div id="activity-list"><div class="activity-row"><div class="member-cell"><div class="member-avatar">AM</div><strong>Alex Morgan</strong></div><span>Morning worship</span><span>08:42 AM</span><em>Present</em></div><div class="activity-row"><div class="member-cell"><div class="member-avatar shade">RB</div><strong>Riley Brooks</strong></div><span>Morning worship</span><span>08:41 AM</span><em>Present</em></div><div class="activity-row"><div class="member-cell"><div class="member-avatar light">TC</div><strong>Taylor Chen</strong></div><span>Morning worship</span><span>08:39 AM</span><em>Present</em></div><div class="activity-row"><div class="member-cell"><div class="member-avatar">JW</div><strong>Jamie Wilson</strong></div><span>Morning worship</span><span>08:38 AM</span><em>Present</em></div></div></div></section>
        </div>
        <div class="page-view hidden" data-page="attendance"><section class="page-heading"><div><p class="eyebrow">Attendance records</p><h1>Every gathering, accounted for.</h1><p class="muted">Review each session and the members who attended.</p></div></section><section class="session-history">@forelse ($sessions as $session)<article class="panel history-session"><div class="history-header"><div><span class="section-kicker">{{ $session->type }}</span><h2>{{ $session->name }}</h2><p class="muted">{{ $session->started_at->format('D, d M Y · h:i A') }} · {{ $session->location ?: 'Main campus' }} · {{ $session->duration_minutes }} minutes · <strong>Set by {{ $session->leader?->name ?: 'Unknown leader' }}</strong></p></div><div class="history-total"><strong>{{ $session->records->count() }}</strong><span>attended</span></div></div><div class="history-attendees">@forelse ($session->records as $record)<span class="attendee-chip"><b>{{ collect(explode(' ', $record->member->name))->map(fn ($part) => substr($part, 0, 1))->join('') }}</b>{{ $record->member->name }}<small>{{ $record->checked_in_at->format('h:i A') }}</small></span>@empty<p class="muted">No members attended this session.</p>@endforelse</div></article>@empty<section class="panel empty-history"><h2>No attendance sessions yet.</h2><p class="muted">Leader-created gatherings will appear here after they begin.</p></section>@endforelse</section></div>
        <div class="page-view hidden" data-page="members">
            <section class="page-heading">
                <div>
                    <p class="eyebrow">CONGREGATION</p>
                    <h1>User directory</h1>
                    <p class="muted">Manage members, leaders, and administrators.</p>
                </div>
                <button class="button button-dark" data-open-member><span>+</span>Add member</button>
            </section>
            <section class="panel member-directory">
                <div class="directory-toolbar">
                    <input type="search" placeholder="Search users" aria-label="Search users">
                    <span id="member-count">{{ $users->total() }} users</span>
                </div>
                <div class="member-cards" id="member-cards">
                    @forelse ($users as $member)
                        <div class="directory-row" data-member-id="{{ $member->id }}">
                            <div class="member-cell">
                                <div class="member-avatar">{{ collect(explode(' ', $member->name))->map(fn ($part) => substr($part, 0, 1))->join('') }}</div>
                                <strong>{{ $member->name }}</strong>
                            </div>
                            <span>Member since {{ $member->created_at->format('Y') }}</span>
                            <span class="tag role-tag">{{ $member->isSuperAdmin() ? 'Admin' : ($member->role === 'leader' ? 'Leader' : 'Member') }}</span>
                            <div class="row-actions">
                                @if ($member->role === 'member')
                                    <button class="row-action view-qr" data-name="{{ $member->name }}" data-code="{{ $member->member_code }}" data-token="{{ $member->qr_token }}" type="button" aria-label="View member QR code" title="View member QR code">
                                        <svg viewBox="0 0 24 24" aria-hidden="true"><path d="M4 4h6v6H4zM14 4h6v6h-6zM4 14h6v6H4zM14 14h2v2h-2zM18 14h2v6h-2zM14 18h4"/></svg>
                                    </button>
                                @endif
                                @if ($member->id !== auth()->id())
                                    <button class="row-action archive-user" data-url="{{ url('/api/users/' . $member->id . '/archive') }}" type="button" aria-label="Archive user" title="Archive user">
                                        <svg viewBox="0 0 24 24" aria-hidden="true"><path d="M4 8.5h16v10H4zM3 5.5h18v3H3zM9 12h6"/></svg>
                                    </button>
                                    <button class="row-action force-delete-user" data-url="{{ url('/api/users/' . $member->id) }}" data-method="DELETE" type="button" aria-label="Delete user permanently" title="Delete user permanently">
                                        <svg viewBox="0 0 24 24" aria-hidden="true"><path d="M5 7h14M10 4h4l1 3H9zM7 7l1 13h8l1-13M10 11v5M14 11v5"/></svg>
                                    </button>
                                @endif
                            </div>
                        </div>
                    @empty
                        <p class="muted">No users have been added yet.</p>
                    @endforelse
                </div>
            </section>
        </div>
        <div class="page-view hidden" data-page="reports"><section class="page-heading"><div><p class="eyebrow">Insights</p><h1>Attendance reports</h1><p class="muted">Understand the rhythm of your church over time.</p></div><button class="button button-light">Download report <span>↓</span></button></section><section class="report-grid"><article class="report-card"><span class="section-kicker">This month</span><strong>{{ $monthlyCheckins }}</strong><span class="muted">total check-ins</span><div class="report-bar"><i style="width: {{ $monthlyCheckins ? 74 : 0 }}%"></i></div></article><article class="report-card"><span class="section-kicker">This year</span><strong>{{ $yearlyCheckins }}</strong><span class="muted">total check-ins</span><div class="report-bar"><i style="width: {{ $yearlyCheckins ? 88 : 0 }}%"></i></div></article><article class="report-card"><span class="section-kicker">Returning members</span><strong>{{ $returningRate }}<span class="unit">%</span></strong><span class="muted">monthly retention</span><div class="report-bar"><i style="width: {{ $returningRate }}%"></i></div></article></section></div>
        @if ($currentView === 'reports')<section class="panel report-results"><div class="report-toolbar"><form method="GET" action="{{ route('dashboard') }}"><input type="hidden" name="view" value="reports"><label>Period<select name="period"><option value="weekly" @selected($reportPeriod === 'weekly')>Weekly</option><option value="monthly" @selected($reportPeriod === 'monthly')>Monthly</option><option value="yearly" @selected($reportPeriod === 'yearly')>Yearly</option></select></label><label>Service<select name="session_id"><option value="">All services</option>@foreach ($reportSessions as $reportSession)<option value="{{ $reportSession->id }}" @selected($selectedReportSession?->id === $reportSession->id)>{{ $reportSession->name }} · {{ $reportSession->type }}</option>@endforeach</select></label><button class="button button-dark" type="submit">Apply filters</button></form><div class="report-actions"><a class="button button-light" href="{{ route('admin.reports.pdf', request()->query()) }}">Download PDF ↓</a><a class="button button-light" href="{{ route('admin.reports.word', request()->query()) }}">Download Word ↓</a></div></div><div class="report-summary"><strong>{{ $reportRecords->count() }}</strong><span>matching check-ins · {{ ucfirst($reportPeriod) }} · {{ $selectedReportSession?->name ?: 'All services' }}</span></div><div class="report-records"><div class="table-head"><span>Member</span><span>Service</span><span>Checked in</span><span>Status</span></div>@forelse ($reportRecords as $record)<div class="activity-row"><div class="member-cell"><div class="member-avatar">{{ collect(explode(' ', $record->member->name))->map(fn ($part) => substr($part, 0, 1))->join('') }}</div><strong>{{ $record->member->name }}</strong></div><span>{{ $record->session->name }}</span><span>{{ $record->checked_in_at->format('d M Y, h:i A') }}</span><em>Present</em></div>@empty<p class="muted empty-service">No attendance records match these filters.</p>@endforelse</div></section>@endif
        @if ($currentView === 'reports' && $reportRecords->hasPages())<nav class="history-pagination attendance-pagination report-pagination" aria-label="Report pages"><span class="history-pagination-summary">Showing {{ $reportRecords->firstItem() }}–{{ $reportRecords->lastItem() }} of {{ $reportRecords->total() }}</span><div class="history-pagination-controls">@if ($reportRecords->onFirstPage())<span class="pagination-control disabled">Previous</span>@else<a class="pagination-control" href="{{ $reportRecords->previousPageUrl() }}">Previous</a>@endif @foreach ($reportRecords->getUrlRange(max(1, $reportRecords->currentPage() - 1), min($reportRecords->lastPage(), $reportRecords->currentPage() + 1)) as $page => $url) @if ($page === $reportRecords->currentPage())<span class="pagination-control current" aria-current="page">{{ $page }}</span>@else<a class="pagination-control" href="{{ $url }}">{{ $page }}</a>@endif @endforeach @if ($reportRecords->hasMorePages())<a class="pagination-control" href="{{ $reportRecords->nextPageUrl() }}">Next</a>@else<span class="pagination-control disabled">Next</span>@endif</div></nav>@endif
        @if ($currentView === 'attendance' && $sessions->hasPages())<div class="history-pagination">{{ $sessions->links() }}</div>@endif
        @if ($currentView === 'members' && $users->hasPages())<nav class="history-pagination attendance-pagination user-pagination" aria-label="User pages"><span class="history-pagination-summary">Showing {{ $users->firstItem() }}-{{ $users->lastItem() }} of {{ $users->total() }}</span><div class="history-pagination-controls">@if ($users->onFirstPage())<span class="pagination-control disabled">Previous</span>@else<a class="pagination-control" href="{{ $users->previousPageUrl() }}">Previous</a>@endif @foreach ($users->getUrlRange(max(1, $users->currentPage() - 1), min($users->lastPage(), $users->currentPage() + 1)) as $page => $url) @if ($page === $users->currentPage())<span class="pagination-control current" aria-current="page">{{ $page }}</span>@else<a class="pagination-control" href="{{ $url }}">{{ $page }}</a>@endif @endforeach @if ($users->hasMorePages())<a class="pagination-control" href="{{ $users->nextPageUrl() }}">Next</a>@else<span class="pagination-control disabled">Next</span>@endif</div></nav>@endif
        @if ($currentView === 'attendance' && $sessions->hasPages())<nav class="history-pagination attendance-pagination" aria-label="Attendance pages"><span class="history-pagination-summary">Showing {{ $sessions->firstItem() }}–{{ $sessions->lastItem() }} of {{ $sessions->total() }}</span><div class="history-pagination-controls">@if ($sessions->onFirstPage())<span class="pagination-control disabled">Previous</span>@else<a class="pagination-control" href="{{ $sessions->previousPageUrl() }}">Previous</a>@endif @foreach ($sessions->getUrlRange(max(1, $sessions->currentPage() - 1), min($sessions->lastPage(), $sessions->currentPage() + 1)) as $page => $url) @if ($page === $sessions->currentPage())<span class="pagination-control current" aria-current="page">{{ $page }}</span>@else<a class="pagination-control" href="{{ $url }}">{{ $page }}</a>@endif @endforeach @if ($sessions->hasMorePages())<a class="pagination-control" href="{{ $sessions->nextPageUrl() }}">Next</a>@else<span class="pagination-control disabled">Next</span>@endif</div></nav>@endif
        <div class="page-view hidden archive-page" data-page="attendance">
            <section class="panel archive-panel">
                <div class="panel-heading"><div><span class="section-kicker">Session management</span><h2>Manage attendance</h2></div><span class="muted">Archive sessions you no longer need in the active list</span></div>
                @foreach ($sessions as $session)
                    <div class="archive-row" data-archive-row><div><strong>{{ $session->name }}</strong><span>{{ $session->started_at->format('d M Y · h:i A') }} · {{ $session->records->count() }} attended</span></div><button class="row-action archive-session" data-url="{{ url('/api/attendance/sessions/' . $session->id . '/archive') }}" type="button">Archive</button></div>
                @endforeach
            </section>
            <section class="panel archive-panel">
                <div class="panel-heading"><div><span class="section-kicker">Session management</span><h2>Archived attendance</h2></div><span class="muted">Restore or permanently remove sessions</span></div>
                @forelse ($archivedSessions as $session)
                    <div class="archive-row" data-archive-row><div><strong>{{ $session->name }}</strong><span>{{ $session->started_at->format('d M Y · h:i A') }} · {{ $session->records->count() }} attended</span></div><div class="row-actions"><button class="row-action restore-session" data-url="{{ url('/api/attendance/sessions/' . $session->id . '/restore') }}" type="button">Restore</button><button class="row-action force-delete-session" data-url="{{ url('/api/attendance/sessions/' . $session->id) }}" type="button">Delete</button></div></div>
                @empty
                    <p class="muted archive-empty">No archived attendance sessions.</p>
                @endforelse
            </section>
        </div>
        <div class="page-view hidden archive-page" data-page="members">
            <section class="panel archive-panel">
                <div class="panel-heading"><div><span class="section-kicker">User management</span><h2>Manage users</h2></div><span class="muted">Archive users to remove them from active lists</span></div>
                @foreach ($users as $managedUser)
                    @if ($managedUser->id !== auth()->id())<div class="archive-row" data-archive-row><div><strong>{{ $managedUser->name }}</strong><span>{{ ucfirst($managedUser->role) }} · {{ $managedUser->email }}</span></div><button class="row-action archive-user" data-url="{{ url('/api/users/' . $managedUser->id . '/archive') }}" type="button">Archive</button></div>@endif
                @endforeach
            </section>
            <section class="panel archive-panel">
                <div class="panel-heading"><div><span class="section-kicker">User management</span><h2>Archived users</h2></div><span class="muted">Restore or permanently remove users</span></div>
                @forelse ($archivedUsers as $archivedUser)
                    <div class="archive-row" data-archive-row><div><strong>{{ $archivedUser->name }}</strong><span>{{ ucfirst($archivedUser->role) }} · {{ $archivedUser->email }}</span></div><div class="row-actions"><button class="row-action restore-user" data-url="{{ url('/api/users/' . $archivedUser->id . '/restore') }}" type="button">Restore</button><button class="row-action force-delete-user" data-url="{{ url('/api/users/' . $archivedUser->id) }}" type="button">Delete</button></div></div>
                @empty
                    <p class="muted archive-empty">No archived users.</p>
                @endforelse
            </section>
        </div>
    </main>
</div>
<div class="modal-backdrop" id="scanner-modal" aria-hidden="true"><section class="modal scanner-modal" role="dialog" aria-modal="true" aria-labelledby="scanner-title"><button class="modal-close" data-close-modal aria-label="Close scanner">×</button><div class="scanner-copy"><span class="section-kicker">Session live <span class="pulse"></span></span><h2 id="scanner-title">Scan member QR</h2><p class="muted">Point the camera at a member card. We will check for a new arrival every 3 seconds.</p><div class="scanner-meta"><span><b class="live-dot"></b> Camera ready</span><span>Morning worship · 08:00 AM</span></div></div><div class="camera-frame"><div class="camera-guide"><i></i><i></i><i></i><i></i><div class="scan-line"></div><span id="scanner-message">Position QR inside frame</span></div><div class="camera-bottom"><span>● Auto-scan on</span><span>Next check in <b id="scan-countdown">3</b>s</span></div></div><div class="modal-footer"><button class="button button-light" data-close-modal>End session</button><button class="button button-dark" data-close-modal>Done</button></div></section></div>
<div class="modal-backdrop" id="member-modal" aria-hidden="true"><section class="modal member-modal" role="dialog" aria-modal="true" aria-labelledby="member-title"><button class="modal-close" data-close-modal aria-label="Close add member">×</button><div class="member-form"><span class="section-kicker">New member</span><h2 id="member-title">Create a QR card</h2><p class="muted">A unique code will be generated automatically after registration.</p><label>Full name<input id="member-name" type="text" placeholder="Enter full name"></label><label>Email address<input id="member-email" type="email" placeholder="member@church.org"></label><label>Membership group<select>@foreach($membershipGroups as $group)<option>{{ $group }}</option>@endforeach</select></label><button class="button button-dark" id="generate-member" type="button">Generate member card <span>↗</span></button></div><div class="qr-preview"><div class="qr-code" id="member-qr" aria-label="Generated QR code"></div><strong id="qr-member-name">New member</strong><span id="qr-member-code">Register a member to generate their code</span><button class="text-button" id="print-card" type="button">Print card <span>↗</span></button></div></section></div>
<div class="modal-backdrop" id="qr-view-modal" aria-hidden="true"><section class="modal qr-view-modal" role="dialog" aria-modal="true" aria-labelledby="qr-view-title"><button class="modal-close" data-close-modal aria-label="Close QR code">×</button><span class="section-kicker">Member QR card</span><h2 id="qr-view-title">Member QR</h2><div class="qr-view-code" id="qr-view-code"></div><strong id="qr-view-name"></strong><span id="qr-view-member-code"></span><button class="button button-dark" id="print-existing-card" type="button">Print card <span>↗</span></button></section></div>
<div class="modal-backdrop" id="approvals-modal" aria-hidden="true"><section class="modal approvals-modal" role="dialog" aria-modal="true" aria-labelledby="approvals-title"><button class="modal-close" data-close-modal aria-label="Close approvals">×</button><div class="approvals-header"><span class="section-kicker">Super admin</span><h2 id="approvals-title">Leader approvals</h2><p class="muted">Approve new leaders without leaving your dashboard.</p></div><div class="approval-list" id="approval-list">@forelse ($pendingUsers as $pendingUser)<div class="approval-row" data-user-id="{{ $pendingUser->id }}"><div class="approval-info"><strong>{{ $pendingUser->name }}</strong><small>{{ $pendingUser->email }}</small></div><div class="approval-actions"><button class="approval-btn approve-user" data-url="{{ route('admin.approvals.approve', $pendingUser) }}" type="button" title="Approve">✓</button><button class="approval-btn decline-user" data-url="{{ route('admin.approvals.decline', $pendingUser) }}" type="button" title="Decline">✕</button></div></div>@empty<p class="muted" id="no-approvals">No leader accounts are waiting for approval.</p>@endforelse</div><div class="modal-footer"><button class="button button-light" data-close-modal type="button">Close</button></div></section></div>
<div class="modal-backdrop" id="report-details-modal" aria-hidden="true"><section class="modal report-details-modal" role="dialog" aria-modal="true" aria-labelledby="report-details-title"><button class="modal-close" data-close-modal aria-label="Close report details">×</button><span class="section-kicker">Report details</span><h2 id="report-details-title">Add church information</h2><p class="muted">These details will appear at the top of the downloaded report.</p><label>Church name<input id="report-church-name" type="text" required placeholder="Grace Community Church"></label><label>Location<input id="report-church-location" type="text" required placeholder="Main campus, City"></label><div class="modal-footer"><button class="button button-light" data-close-modal type="button">Cancel</button><button class="button button-dark" id="continue-report-download" type="button">Continue download</button></div></section></div>
{{-- Delete confirmation modal --}}
<div class="modal-backdrop" id="delete-confirm-modal" aria-hidden="true">
    <section class="modal confirm-modal" role="alertdialog" aria-modal="true" aria-labelledby="delete-confirm-title" aria-describedby="delete-confirm-desc">
        <div class="confirm-icon">⚠</div>
        <h2 id="delete-confirm-title">Archive member?</h2>
        <p id="delete-confirm-desc" class="muted">The member will leave the active directory. You can restore them from user management.</p>
        <div class="confirm-actions">
            <button class="button button-quiet" id="delete-cancel-btn" type="button">Cancel</button>
            <button class="button button-danger" id="delete-confirm-btn" type="button">Archive member</button>
        </div>
    </section>
</div>
<script>
    window.activeSessionIds = @json($sessions->pluck('id')->values());
    window.activeUserIds = @json($users->pluck('id')->values());
    window.currentUserId = {{ auth()->id() }};
</script>
</body>
</html>
