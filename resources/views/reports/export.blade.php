<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <title>Attendance report</title>
    <style>
        body{font-family:Arial,sans-serif;color:#111;font-size:12px}h1{font-size:24px;margin-bottom:6px}.meta{color:#666;margin-bottom:24px}.summary{display:flex;gap:30px;margin-bottom:24px}.summary strong{display:block;font-size:20px}.summary span{color:#666}table{width:100%;border-collapse:collapse}th,td{text-align:left;padding:9px;border-bottom:1px solid #ddd}th{font-size:10px;text-transform:uppercase;color:#666}
    </style>
</head>
<body>
    <h1>Attendance report</h1>
    <p class="meta">{{ ucfirst($period) }} · {{ $start->format('d M Y') }} to {{ $end->format('d M Y') }} · {{ $selectedSession?->name ?: 'All services' }}</p>
    <div class="summary"><div><strong>{{ $records->count() }}</strong><span>Total check-ins</span></div><div><strong>{{ $records->pluck('user_id')->unique()->count() }}</strong><span>Unique members</span></div></div>
    <table><thead><tr><th>Member</th><th>Member code</th><th>Service</th><th>Type</th><th>Checked in</th></tr></thead><tbody>
    @forelse ($records as $record)<tr><td>{{ $record->member->name }}</td><td>{{ $record->member->member_code }}</td><td>{{ $record->session->name }}</td><td>{{ $record->session->type }}</td><td>{{ $record->checked_in_at->format('d M Y, h:i A') }}</td></tr>@empty<tr><td colspan="5">No attendance records found for this filter.</td></tr>@endforelse
    </tbody></table>
</body>
</html>
