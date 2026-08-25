<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <title>Attendance report</title>
    <style>
        @page{margin:26px 32px}body{font-family:Arial,sans-serif;color:#111;font-size:10px;line-height:1.35}h1{font-size:22px;letter-spacing:.01em;margin:0 0 4px}.eyebrow{font-size:8px;letter-spacing:2px;text-transform:uppercase;color:#5f7c64;margin:0 0 7px}.church-location{color:#666;font-size:10px;margin:0 0 3px}.meta{color:#666;margin:0 0 16px}.summary{display:table;border-collapse:separate;border-spacing:0;margin-bottom:17px}.summary div{display:table-cell;min-width:135px;padding:9px 13px;background:#f4f6f3;border:1px solid #e1e6df}.summary div+div{border-left:0}.summary strong{display:block;font-size:18px;margin-bottom:1px}.summary span{color:#666;font-size:9px}table{width:100%;border-collapse:collapse;table-layout:fixed}th,td{text-align:left;padding:7px 7px;border-bottom:1px solid #ddd;vertical-align:top;word-wrap:break-word}th{font-size:8px;letter-spacing:.8px;text-transform:uppercase;color:#666;background:#f7f7f5}th:nth-child(1){width:22%}th:nth-child(2){width:16%}th:nth-child(3){width:22%}th:nth-child(4){width:16%}th:nth-child(5){width:24%}
    </style>
</head>
<body>
    <p class="eyebrow">Gather attendance</p><h1>{{ $churchName }}</h1><p class="church-location">{{ $churchLocation }}</p><h2>Attendance report</h2>
    <p class="meta">{{ ucfirst($period) }} · {{ $start->format('d M Y') }} to {{ $end->format('d M Y') }} · {{ $selectedSession?->name ?: 'All services' }}</p>
    <div class="summary"><div><strong>{{ $records->count() }}</strong><span>Total check-ins</span></div><div><strong>{{ $records->pluck('user_id')->unique()->count() }}</strong><span>Unique members</span></div></div>
    <table><thead><tr><th>Member</th><th>Member code</th><th>Service</th><th>Type</th><th>Checked in</th></tr></thead><tbody>
    @forelse ($records as $record)<tr><td>{{ $record->member->name }}</td><td>{{ $record->member->member_code }}</td><td>{{ $record->session->name }}</td><td>{{ $record->session->type }}</td><td>{{ $record->checked_in_at->format('d M Y, h:i A') }}</td></tr>@empty<tr><td colspan="5">No attendance records found for this filter.</td></tr>@endforelse
    </tbody></table>
</body>
</html>
