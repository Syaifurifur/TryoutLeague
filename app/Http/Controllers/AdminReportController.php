<?php

namespace App\Http\Controllers;

use App\Models\Event;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class AdminReportController extends Controller
{
    public function results(Request $request, Event $event)
    {
        $rows = DB::table('event_registrations as r')
            ->join('participants as p', 'p.id', '=', 'r.participant_id')
            ->join('users as u', 'u.id', '=', 'p.user_id')
            ->leftJoin('schools as sc', 'sc.id', '=', 'p.school_id')
            ->leftJoin('exam_attempts as a', 'a.registration_id', '=', 'r.id')
            ->leftJoin('exam_sessions as es', 'es.id', '=', 'a.exam_session_id')
            ->leftJoin('exam_packages as ep', 'ep.id', '=', 'es.exam_package_id')
            ->leftJoin('subjects as s', 's.id', '=', 'ep.subject_id')
            ->leftJoin('scores as score', fn ($join) => $join->on('score.attempt_id', '=', 'a.id')->where('score.status', 'finalized'))
            ->where('r.event_id', $event->id)
            ->select('r.participant_number', 'u.name', 'u.email', 'p.nisn', 'sc.name as school', 'p.province', 'p.city', 'r.status as registration_status', 's.name as subject', 'a.status as attempt_status', 'a.submit_type', 'score.raw_score', 'score.scaled_score', 'score.correct_count', 'score.wrong_count', 'score.unanswered_count', 'score.percentile')
            ->orderBy('u.name')->orderBy('s.name')->get();
        $this->auditExport($request, $event->id, 'results', $rows->count());

        return response()->streamDownload(function () use ($rows) {
            $output = fopen('php://output', 'wb');
            fputcsv($output, ['nomor_peserta', 'nama', 'email', 'nisn', 'sekolah', 'provinsi', 'kota', 'status_registrasi', 'mata_uji', 'status_attempt', 'tipe_submit', 'raw_score', 'scaled_score', 'benar', 'salah', 'kosong', 'percentile']);
            foreach ($rows as $row) {
                fputcsv($output, (array) $row);
            }
            fclose($output);
        }, 'hasil-'.$event->slug.'-'.now()->format('Ymd-His').'.csv', ['Content-Type' => 'text/csv; charset=UTF-8']);
    }

    public function audit(Request $request)
    {
        $rows = DB::table('audit_logs as a')->leftJoin('users as u', 'u.id', '=', 'a.user_id')->select('a.id', 'u.name as actor', 'a.action', 'a.entity_type', 'a.entity_id', 'a.ip_address', 'a.created_at')->latest('a.created_at')->get();

        return response()->streamDownload(function () use ($rows) {
            $output = fopen('php://output', 'wb');
            fputcsv($output, ['id', 'actor', 'action', 'entity_type', 'entity_id', 'ip_address', 'created_at']);
            foreach ($rows as $row) {
                fputcsv($output, (array) $row);
            }
            fclose($output);
        }, 'audit-log-'.now()->format('Ymd-His').'.csv', ['Content-Type' => 'text/csv; charset=UTF-8']);
    }

    private function auditExport(Request $request, int $eventId, string $report, int $rows): void
    {
        DB::table('audit_logs')->insert(['user_id' => $request->user()->id, 'action' => 'report.exported', 'entity_type' => 'event', 'entity_id' => $eventId, 'after' => json_encode(['report' => $report, 'rows' => $rows]), 'ip_address' => $request->ip(), 'created_at' => now()]);
    }
}
