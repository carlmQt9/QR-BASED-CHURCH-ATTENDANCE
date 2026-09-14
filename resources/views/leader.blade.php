<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <link rel="icon" type="image/svg+xml" href="{{ asset('favicon.svg') }}">
    <title>Leader dashboard / Gather</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    <!-- EMERGENCY DROPDOWN FIX - Remove after assets work -->
    <style>
        select {
            appearance: none !important;
            -webkit-appearance: none !important;
            -moz-appearance: none !important;
            background: #fafaf8 !important;
            border: 1px solid #e7e7e3 !important;
            border-radius: 6px !important;
            padding: 12px 35px 12px 12px !important;
            font: 11px 'Manrope', sans-serif !important;
            color: #111 !important;
            cursor: pointer !important;
            display: block !important;
            width: 100% !important;
            background-image: url("data:image/svg+xml;charset=UTF-8,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 24 24' fill='none' stroke='%23666' stroke-width='2' stroke-linecap='round' stroke-linejoin='round'%3E%3Cpath d='m6 9 6 6 6-6'/%3E%3C/svg%3E") !important;
            background-repeat: no-repeat !important;
            background-position: right 12px center !important;
            background-size: 16px !important;
        }
        select:hover { border-color: #999 !important; }
        select:focus { outline: 2px solid #2563eb !important; outline-offset: 2px !important; border-color: #2563eb !important; }
        .modal select, #member-group, #manual-member { 
            background: #fff !important; 
            border: 1px solid #ddd !important;
            padding: 11px 35px 11px 11px !important;
        }
        
        /* Fix duration control buttons */
        .duration-control {
            height: 39px !important;
            border: 1px solid #e7e7e3 !important;
            border-radius: 6px !important;
            background: #fafaf8 !important;
            display: flex !important;
            align-items: center !important;
            justify-content: space-between !important;
            overflow: hidden !important;
        }
        .duration-control button {
            height: 100% !important;
            width: 38px !important;
            background: #fff !important;
            border: none !important;
            border-right: 1px solid #e7e7e3 !important;
            font-size: 18px !important;
            cursor: pointer !important;
            transition: background 0.2s !important;
        }
        .duration-control button:last-child {
            border-left: 1px solid #e7e7e3 !important;
            border-right: 0 !important;
        }
        .duration-control button:hover {
            background: #f0f0f0 !important;
        }
        .duration-control strong {
            color: #111 !important;
            font: 12px 'DM Mono', monospace !important;
            text-transform: none !important;
            padding: 0 10px !important;
        }
        
        /* Fix manual member search in scanner */
        #manual-attendance-form {
            display: grid !important;
            gap: 8px !important;
        }
        #manual-attendance-form select {
            min-width: 0 !important;
            padding: 9px 30px 9px 9px !important;
            border: 1px solid #334155 !important;
            border-radius: 5px !important;
            background: #1e293b !important;
            color: #fff !important;
            font-size: 11px !important;
        }
        #manual-attendance-form .button {
            min-height: 35px !important;
            justify-content: center !important;
        }
    </style>
    
    <!-- Emergency JavaScript fixes -->
    <script>
    document.addEventListener('DOMContentLoaded', function() {
        // Fix duration controls
        function setupDurationControls() {
            const durationValue = document.querySelector('#duration-value');
            const durationLabel = document.querySelector('#duration-label');
            const durationButtons = document.querySelectorAll('[data-duration]');
            
            function formatDuration(minutes) {
                const h = Math.floor(minutes / 60);
                const m = minutes % 60;
                if (h === 0) return m + ' min';
                if (m === 0) return h + ' hr';
                return h + ' hr ' + m + ' min';
            }
            
            function updateDuration(change) {
                if (!durationValue || !durationLabel) return;
                const current = parseInt(durationValue.value) || 90;
                const newValue = Math.max(15, Math.min(720, current + change));
                durationValue.value = newValue;
                durationLabel.textContent = formatDuration(newValue);
            }
            
            // Set initial label
            if (durationValue && durationLabel) {
                durationLabel.textContent = formatDuration(parseInt(durationValue.value) || 90);
            }
            
            // Add click handlers
            durationButtons.forEach(btn => {
                btn.addEventListener('click', function(e) {
                    e.preventDefault();
                    const change = parseInt(this.dataset.duration) || 0;
                    updateDuration(change);
                });
            });
        }
        
        // Fix manual attendance form
        function setupManualAttendance() {
            const form = document.querySelector('#manual-attendance-form');
            if (!form) return;
            
            form.addEventListener('submit', async function(e) {
                e.preventDefault();
                const memberSelect = this.querySelector('#manual-member');
                const button = this.querySelector('button[type="submit"]');
                
                if (!memberSelect?.value || !button) return;
                
                const originalText = button.textContent;
                button.disabled = true;
                button.textContent = 'Marking...';
                
                try {
                    const sessionId = document.body.dataset.sessionId || 
                                   document.querySelector('[data-session-id]')?.dataset.sessionId;
                    
                    const response = await fetch('/api/attendance/manual-check-ins', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'Accept': 'application/json',
                            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content || ''
                        },
                        body: JSON.stringify({
                            session_id: sessionId,
                            member_id: memberSelect.value
                        })
                    });
                    
                    const data = await response.json();
                    
                    if (data.status === 'success' || data.status === 'already_attended') {
                        memberSelect.value = '';
                        // Refresh attendance list
                        location.reload();
                    } else {
                        alert(data.message || 'Could not mark attendance');
                    }
                } catch (error) {
                    console.error('Manual attendance error:', error);
                    alert('Failed to mark attendance. Please try again.');
                } finally {
                    button.disabled = false;
                    button.textContent = originalText;
                }
            });
        }
        
        // Initialize controls
        setupDurationControls();
        setupManualAttendance();
        
        // Re-initialize when modals open
        const observer = new MutationObserver(function(mutations) {
            mutations.forEach(function(mutation) {
                if (mutation.type === 'attributes' && mutation.attributeName === 'class') {
                    const target = mutation.target;
                    if (target.classList.contains('open')) {
                        setTimeout(() => {
                            setupManualAttendance();
                        }, 100);
                    }
                }
            });
        });
        
        const modals = document.querySelectorAll('.modal-backdrop');
        modals.forEach(modal => {
            observer.observe(modal, { attributes: true });
        });
    });
    </script>
</head>
<body data-auto-open-scanner="{{ session('status') ? 'true' : 'false' }}" data-session-id="{{ $activeSession?->id }}" data-app-url="{{ url('/') }}">
{{-- Success notification --}}
<div class="notification-container" id="notification-container"></div>
<div class="app-shell">
    <aside class="sidebar" aria-label="Leader navigation">
        <div class="brand"><div class="brand-mark"><span></span><span></span><span></span><span></span></div><div><strong>GATHER</strong><small>Church attendance</small></div></div>
        <div class="workspace-label">Leader workspace</div>
        <nav class="nav-list"><a class="nav-item active" href="{{ route('dashboard') }}"><span class="icon">⌂</span>My sessions</a><a class="nav-item" href="{{ route('leader.history') }}"><span class="icon">▤</span>Session history</a></nav>
        <div class="sidebar-bottom"><form class="logout-form" action="{{ route('logout') }}" method="POST">@csrf<button class="nav-item" style="width:100%" type="submit"><span class="icon">↪</span>Log out</button></form><div class="user-chip"><div class="avatar">{{ collect(explode(' ', auth()->user()->name))->map(fn ($part) => substr($part, 0, 1))->join('') }}</div><div><strong>{{ auth()->user()->name }}</strong><small>Attendance leader</small></div></div></div>
    </aside>
    <main class="main-content">
        <header class="topbar"><button class="mobile-menu" aria-label="Open navigation">☰</button><div class="breadcrumb"><span>{{ now()->format('l, d F Y') }}</span><i>/</i><strong>Leader dashboard</strong></div><div class="top-actions"><button class="avatar avatar-small">{{ collect(explode(' ', auth()->user()->name))->map(fn ($part) => substr($part, 0, 1))->join('') }}</button></div></header>
        <div class="page-view leader-page" data-session-id="{{ $activeSession?->id }}">
            <section class="page-heading"><div><p class="eyebrow">Attendance leader</p><h1>Set up your gathering.</h1><p class="muted">Start a session before people arrive, then keep the live check-in moving.</p></div>@if ($activeSession)<button class="button button-dark" data-open-scanner><span>⌁</span>Open scanner</button>@endif</section>
            @if (session('status'))<div class="notice">{{ session('status') }}</div>@endif
            @if ($activeSession)
                <section class="leader-live panel"><div><span class="section-kicker">Session in progress <span class="pulse"></span></span><h2>{{ $activeSession->name }}</h2><p class="muted">{{ $activeSession->type }} · {{ $activeSession->started_at->format('D, d M Y · h:i A') }} · {{ $activeSession->location ?: 'Main campus' }}</p><div class="big-count"><strong id="attendance-count">{{ $activeSession->records()->count() }}</strong><span>members present</span></div></div><div class="session-status"><span class="live-dot">● Live</span><strong>Ready to scan</strong><small>Ends {{ $activeSession->ended_at->format('h:i A') }} · {{ $activeSession->durationForHumans() }}</small><button class="button button-light" data-open-scanner>Open scanner</button><button class="button button-quiet" data-end-session data-url="{{ url('/api/attendance/sessions/' . $activeSession->id . '/end') }}" type="button">End session</button></div></section><section class="panel attendee-panel" id="attendee-panel"><div class="panel-heading"><div><span class="section-kicker">Live list</span><h2>Members already attended</h2></div><span class="muted">Updates every 3 seconds</span></div><div class="attendee-list" id="attendee-list">@forelse ($activeSession->records as $record)<div class="attendee-row"><strong>{{ $record->member->name }}</strong><span>{{ $record->checked_in_at->format('h:i A') }}</span><em>Present</em></div>@empty<p class="muted">No members checked in yet.</p>@endforelse</div></section>
            @else
                <section class="session-builder panel"><div class="builder-intro"><span class="section-kicker">New attendance session</span><h2>What are you gathering for?</h2><p class="muted">Create a timed session for today’s service, meeting, or rehearsal.</p></div><form action="{{ url('/api/attendance/sessions') }}" method="POST" class="session-form">@csrf<input type="hidden" name="duration_minutes" id="duration-value" value="90"><label>Gathering type<select name="type" required>@foreach($gatheringTypes as $type)<option>{{ $type }}</option>@endforeach</select></label><label>Session name<input type="text" name="name" placeholder="Morning worship" required></label><label>Date and time<input type="datetime-local" name="started_at" value="{{ now()->format('Y-m-d\\TH:i') }}" required></label><label>Location<input type="text" name="location" placeholder="Grace Hall"></label><label>Duration<div class="duration-control"><button type="button" data-duration="-15">−</button><strong id="duration-label">90 min</strong><button type="button" data-duration="15">＋</button></div></label><button class="button button-dark start-session" type="submit">Start attendance session <span>↗</span></button></form></section>
            @endif
            <section class="leader-tips"><div><span class="section-kicker">On the day</span><h2>Keep check-in simple.</h2></div><div class="tip-grid"><div><strong>01</strong><p>Open the scanner before members arrive.</p></div><div><strong>02</strong><p>Hold each member QR card inside the frame.</p></div><div><strong>03</strong><p>Watch your live count update as people enter.</p></div></div></section>
        </div>
    </main>
</div>
{{-- Delete confirmation modal --}}
<div class="modal-backdrop" id="delete-confirm-modal" aria-hidden="true">
    <section class="modal confirm-modal" role="alertdialog" aria-modal="true" aria-labelledby="delete-confirm-title" aria-describedby="delete-confirm-desc">
        <div class="confirm-icon">⚠</div>
        <h2 id="delete-confirm-title">Delete member?</h2>
        <p id="delete-confirm-desc" class="muted">This will permanently remove the member and all their attendance history. This action cannot be undone.</p>
        <div class="confirm-actions">
            <button class="button button-quiet" id="delete-cancel-btn" type="button">Cancel</button>
            <button class="button button-danger" id="delete-confirm-btn" type="button">Delete member</button>
        </div>
    </section>
</div>

{{-- End session confirmation modal --}}
<div class="modal-backdrop" id="end-session-confirm-modal" aria-hidden="true">
    <section class="modal confirm-modal" role="alertdialog" aria-modal="true" aria-labelledby="end-session-confirm-title" aria-describedby="end-session-confirm-desc">
        <div class="confirm-icon">⏹</div>
        <h2 id="end-session-confirm-title">End attendance session?</h2>
        <p id="end-session-confirm-desc" class="muted">This will close the current session and stop accepting new check-ins. You can view the results in session history.</p>
        <div class="confirm-actions">
            <button class="button button-quiet" id="end-session-cancel-btn" type="button">Keep session active</button>
            <button class="button button-danger" id="end-session-confirm-btn" type="button">End session</button>
        </div>
    </section>
</div>

@if ($activeSession)<div class="modal-backdrop" id="scanner-modal" aria-hidden="true"><section class="modal scanner-modal" role="dialog" aria-modal="true" aria-labelledby="scanner-title"><button class="modal-close" data-close-modal aria-label="Close scanner">×</button><div class="scanner-copy"><span class="section-kicker">Session live <span class="pulse"></span></span><h2 id="scanner-title">Scan member QR</h2><p class="muted">Hold the QR card inside the frame. Scanning is continuous.</p><div class="scanner-meta"><span><b class="live-dot"></b> Camera ready</span><span>{{ $activeSession->type }} · {{ $activeSession->started_at->format('h:i A') }}</span></div></div><div class="camera-frame"><div class="camera-guide" style="height:300px;position:relative;overflow:hidden;border-radius:8px;background:#111;"><i></i><i></i><i></i><i></i><div class="scan-line"></div><span id="scanner-message">Position QR inside frame</span></div><div class="camera-bottom"><span>● Auto-scan on</span><span>Scanning continuously</span></div></div><aside class="scanner-roster"><div class="scanner-roster-head"><strong>Live roster</strong><span id="scanner-roster-count">{{ $activeSession->records->count() }}</span></div><form id="manual-attendance-form"><select id="manual-member" required><option value="">— Manual mark member —</option>@foreach ($members as $member)<option value="{{ $member->id }}">{{ $member->name }}</option>@endforeach</select><button class="button button-dark" type="submit">Mark present</button></form><div class="scanner-roster-list" id="scanner-roster-list">@forelse ($activeSession->records as $record)<div class="scanner-roster-item"><strong>{{ $record->member->name }}</strong><span>{{ $record->checked_in_at->format('h:i A') }}</span></div>@empty<p class="muted">Waiting for first scan...</p>@endforelse</div></aside><div class="modal-footer"><button class="button button-light" data-close-modal>Close scanner</button></div></section></div>@endif
</body>
</html>
