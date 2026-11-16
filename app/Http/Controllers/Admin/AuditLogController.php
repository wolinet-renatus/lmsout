<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Spatie\Activitylog\Models\Activity;

/**
 * Security & Operations Audit Log Viewer (SRS Section 5.1.3 & 5.1.6).
 * @author Renatus Bernardo
 */
class AuditLogController extends Controller
{
    /**
     * Display a listing of the resource records.
     */
    public function index(Request $request): View
    {
        $logName = $request->query('log_name');
        $event = $request->query('event');
        $causerId = $request->query('causer_id');
        $q = trim((string) $request->query('q', ''));
        $dateFrom = $request->query('date_from');
        $dateTo = $request->query('date_to');

        $activities = Activity::with('causer')
            ->when($logName, fn ($query) => $query->where('log_name', $logName))
            ->when($event, fn ($query) => $query->where('event', $event))
            ->when($causerId, fn ($query) => $query->where('causer_id', $causerId))
            ->when($dateFrom, fn ($query) => $query->whereDate('created_at', '>=', $dateFrom))
            ->when($dateTo, fn ($query) => $query->whereDate('created_at', '<=', $dateTo))
            ->when($q !== '', fn ($query) => $query->where(fn ($w) => $w
                ->where('description', 'like', "%{$q}%")
                ->orWhere('event', 'like', "%{$q}%")
                ->orWhere('subject_type', 'like', "%{$q}%")))
            ->latest('id')
            ->paginate(30)
            ->withQueryString();

        $logNames = Activity::query()->whereNotNull('log_name')->distinct()->pluck('log_name');
        $actors = User::orderBy('name')->get(['id', 'name', 'email']);

        return view('admin.audit-logs.index', compact('activities', 'logNames', 'actors'));
    }
}
