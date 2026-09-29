<?php

namespace App\Http\Controllers;

use App\Models\AuditLog;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\StreamedResponse;

class AuditController extends Controller
{
    public function index(Request $request)
    {
        $query = $this->filtered($request, AuditLog::query()->where('scope', 'platform'));

        if ($request->input('export') === 'csv') {
            return $this->csv($query, 'logs-plataforma.csv');
        }

        return view('logs.index', [
            'logs' => $query->latest('id')->paginate(50)->withQueryString(),
            'filters' => $request->only(['action', 'from', 'to', 'q']),
            'reported' => AuditLog::where('action', 'invite.reported')->count(),
        ]);
    }

    private function filtered(Request $request, $query)
    {
        if ($action = $request->input('action')) {
            $query->where('action', 'like', '%' . $action . '%');
        }
        if ($from = $request->input('from')) {
            $query->whereDate('created_at', '>=', $from);
        }
        if ($to = $request->input('to')) {
            $query->whereDate('created_at', '<=', $to);
        }
        if ($q = $request->input('q')) {
            $query->where(function ($where) use ($q) {
                $where->where('ip', 'like', '%' . $q . '%')
                    ->orWhere('entity_type', 'like', '%' . $q . '%')
                    ->orWhere('entity_id', 'like', '%' . $q . '%');
            });
        }

        return $query;
    }

    private function csv($query, string $filename): StreamedResponse
    {
        return response()->streamDownload(function () use ($query) {
            $out = fopen('php://output', 'w');
            fputcsv($out, ['id', 'scope', 'organization_id', 'actor_type', 'actor_user_id', 'action', 'entity_type', 'entity_id', 'ip', 'created_at', 'hash_self']);
            foreach ($query->latest('id')->cursor() as $log) {
                fputcsv($out, [
                    $log->id, $log->scope, $log->organization_id, $log->actor_type, $log->actor_user_id,
                    $log->action, $log->entity_type, $log->entity_id, $log->ip,
                    optional($log->created_at)->toDateTimeString(), $log->hash_self,
                ]);
            }
            fclose($out);
        }, $filename, ['Content-Type' => 'text/csv; charset=UTF-8']);
    }
}
