<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Services\RoleDashboardService;
use Illuminate\Http\Request;

/**
 * Dashboard controller — handles administrative portal workflows and governance.
 *
 * @author Renatus Bernardo
 */
class DashboardController extends Controller
{
    /**
     * Initialize dependencies for this class.
     */
    public function __construct(private RoleDashboardService $dashboards)
    {
    }

    /**
     * Display a listing of the resource records.
     */
    public function index(Request $request)
    {
        $filters = [
            'branch_id' => $request->filled('branch_id') ? (int) $request->input('branch_id') : null,
            'search' => $request->string('search')->trim()->value(),
            'date_range' => $request->string('date_range')->trim()->lower()->value() ?: 'all',
            'status' => $request->string('status')->trim()->lower()->value() ?: 'all',
            'per_page' => (int) $request->input('per_page', 5),
        ];

        $analytics = $this->dashboards->buildForUser($request->user(), $filters);

        return view('admin.dashboard', compact('analytics'));
    }
}
