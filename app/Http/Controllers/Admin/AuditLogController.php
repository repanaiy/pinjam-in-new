<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ActivityLog;
use App\Models\Loan;
use Illuminate\Http\Request;
use Illuminate\View\View;

/** Audit log: read-only. */
class AuditLogController extends Controller
{
    public function index(Request $request): View
    {
        $loanId = $request->query('loan');
        $actor = trim((string) $request->query('aktor', ''));
        $action = (string) $request->query('aksi', '');
        $q = trim((string) $request->query('q', ''));
        $from = $request->query('dari');
        $to = $request->query('sampai');

        $logs = ActivityLog::query()
            ->with(['actor', 'loan'])
            ->when($loanId, fn ($b) => $b->where('loan_id', $loanId))
            ->when($q !== '', fn ($b) => $b->whereHas('loan', fn ($l) => $l->where('request_code', 'like', "%{$q}%")))
            ->when($actor !== '', fn ($b) => $b->whereHas('actor', fn ($a) => $a->where('name', 'like', "%{$actor}%")->orWhere('username', 'like', "%{$actor}%")))
            ->when($action !== '', fn ($b) => $b->where('action', $action))
            ->when($from, fn ($b) => $b->whereDate('created_at', '>=', $from))
            ->when($to, fn ($b) => $b->whereDate('created_at', '<=', $to))
            ->latest('created_at')->latest('id')
            ->paginate(25)->withQueryString();

        return view('admin.audit', [
            'logs' => $logs,
            'actions' => ActivityLog::query()->distinct()->orderBy('action')->pluck('action'),
            'loanFilter' => $loanId ? Loan::find($loanId) : null,
            'q' => $q, 'actor' => $actor, 'action' => $action, 'from' => $from, 'to' => $to,
        ]);
    }
}
