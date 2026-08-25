<?php

namespace App\Http\Controllers;

use App\Models\AttendanceRecord;
use App\Models\AttendanceSession;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\View\View;

class ReportController extends Controller
{
    public function exportPdf(Request $request): Response
    {
        abort_unless($request->user()?->isSuperAdmin(), 403);
        $report = $this->reportData($request);

        return Pdf::loadView('reports.export', $report)->download('attendance-report.pdf');
    }

    public function exportWord(Request $request): Response
    {
        abort_unless($request->user()?->isSuperAdmin(), 403);
        $report = $this->reportData($request);
        $html = view('reports.export', $report)->render();

        return response($html, 200, [
            'Content-Type' => 'application/msword; charset=UTF-8',
            'Content-Disposition' => 'attachment; filename="attendance-report.doc"',
        ]);
    }

    public function reportData(Request $request): array
    {
        $period = $request->string('period')->toString();
        $period = in_array($period, ['weekly', 'monthly', 'yearly'], true) ? $period : 'monthly';
        $sessionId = $request->integer('session_id');
        $churchName = trim($request->string('church_name')->toString());
        $churchLocation = trim($request->string('church_location')->toString());
        $start = match ($period) {
            'weekly' => now()->subDays(6)->startOfDay(),
            'yearly' => now()->startOfYear(),
            default => now()->startOfMonth(),
        };
        $end = now()->endOfDay();

        $query = AttendanceRecord::with(['member:id,name,member_code', 'session:id,name,type,location'])
            ->whereBetween('checked_in_at', [$start, $end])
            ->when($sessionId, fn ($query) => $query->where('attendance_session_id', $sessionId))
            ->latest('checked_in_at');

        $records = $query->get();
        $paginatedRecords = $query->paginate(5)->withQueryString();

        return [
            'period' => $period,
            'start' => $start,
            'end' => $end,
            'selectedSession' => $sessionId ? AttendanceSession::find($sessionId) : null,
            'churchName' => $churchName ?: 'Church attendance',
            'churchLocation' => $churchLocation ?: 'Location not provided',
            'records' => $records,
            'paginatedRecords' => $paginatedRecords,
        ];
    }
}
