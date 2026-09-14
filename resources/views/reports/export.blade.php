<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <title>Attendance report</title>
    <style>
        @page { margin: 32px 40px }
        body { font-family: Arial, sans-serif; color: #111; font-size: 10px; line-height: 1.4; margin: 0 }

        /* ── Header ── */
        .report-header { text-align: center; margin-bottom: 18px; border-bottom: 1.5px solid #111; padding-bottom: 14px }
        .eyebrow { font-size: 7.5px; letter-spacing: 2.5px; text-transform: uppercase; color: #888; margin: 0 0 6px }
        h1 { font-size: 22px; font-weight: 700; margin: 0 0 2px; letter-spacing: .01em }
        .church-location { color: #666; font-size: 9.5px; margin: 0 0 10px }
        h2 { font-size: 13px; font-weight: 700; margin: 0 0 4px; text-transform: uppercase; letter-spacing: .06em }
        .meta { color: #666; font-size: 9px; margin: 0 }

        /* ── Table ── */
        table { width: 100%; border-collapse: collapse; table-layout: fixed }
        thead tr { border-top: 1.5px solid #111; border-bottom: 1px solid #ccc }
        th { font-size: 7.5px; letter-spacing: 1px; text-transform: uppercase; color: #888; padding: 6px 8px; text-align: left; font-weight: 600 }
        td { padding: 7px 8px; border-bottom: 1px solid #eee; font-size: 9.5px; vertical-align: top; word-wrap: break-word }
        tbody tr:last-child td { border-bottom: none }
        td:first-child { color: #999; font-size: 8.5px; text-align: center }

        /* Column widths */
        th:nth-child(1), td:nth-child(1) { width: 5% }
        th:nth-child(2), td:nth-child(2) { width: 22% }
        th:nth-child(3), td:nth-child(3) { width: 20% }
        th:nth-child(4), td:nth-child(4) { width: 22% }
        th:nth-child(5), td:nth-child(5) { width: 15% }
        th:nth-child(6), td:nth-child(6) { width: 16% }

        .empty { color: #999; font-style: italic; text-align: center; padding: 16px }
        .footer { margin-top: 18px; text-align: center; font-size: 8px; color: #bbb }
    </style>
</head>
<body>

<div class="report-header">
    <p class="eyebrow">Gather Attendance</p>
    <h1>{{ $churchName }}</h1>
    <p class="church-location">{{ $churchLocation }}</p>
    <h2>Attendance Report</h2>
    <p class="meta">{{ ucfirst($period) }} &nbsp;·&nbsp; {{ $start->format('d M Y') }} – {{ $end->format('d M Y') }} &nbsp;·&nbsp; {{ $selectedSession?->name ?: 'All services' }}</p>
</div>



<table>
    <thead>
        <tr>
            <th>#</th>
            <th>Member</th>
            <th>Group</th>
            <th>Service</th>
            <th>Type</th>
            <th>Checked in</th>
        </tr>
    </thead>
    <tbody>
        @forelse ($records as $i => $record)
        <tr>
            <td>{{ $i + 1 }}</td>
            <td>{{ $record->member->name }}</td>
            <td>{{ $record->member->membership_group ?: '—' }}</td>
            <td>{{ $record->session->name }}</td>
            <td>{{ $record->session->type }}</td>
            <td>{{ $record->checked_in_at->format('d M Y, h:i A') }}</td>
        </tr>
        @empty
        <tr><td colspan="6" class="empty">No attendance records found for this period.</td></tr>
        @endforelse
    </tbody>
</table>

<p class="footer">Generated {{ now()->format('d M Y, h:i A') }} &nbsp;·&nbsp; Gather Attendance</p>

</body>
</html>
