<?php

namespace App\Support;

use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Pagination\Paginator;
use Illuminate\Support\Collection;

/**
 * Shared filter normalization, date window resolution, and collection pagination
 * for portal dashboards.
 *
 * @package App\Support
 * @author Renatus Bernardo
 */
class DashboardFilters
{
    /**
     * Extract and sanitize search, date range, status, and page-size parameters.
     */
    public static function fromRequest(Request $request, int $defaultPerPage = 5): array
    {
        $dateRange = strtolower($request->string('date_range')->trim()->value() ?: 'all');
        if (! in_array($dateRange, ['all', 'today', '7d', '30d', 'month'], true)) {
            $dateRange = 'all';
        }

        $perPage = (int) $request->input('per_page', $defaultPerPage);
        if (! in_array($perPage, [5, 10, 15, 25], true)) {
            $perPage = $defaultPerPage;
        }

        return [
            'search' => $request->string('search')->trim()->value(),
            'date_range' => $dateRange,
            'status' => strtolower($request->string('status')->trim()->value() ?: 'all'),
            'per_page' => $perPage,
        ];
    }

    /**
     * Resolve [start, end] Carbon bounds for a filter period, or null for all-time.
     */
    public static function dateWindow(string $dateRange): ?array
    {
        return match ($dateRange) {
            'today' => [Carbon::today()->startOfDay(), Carbon::now()->endOfDay()],
            '7d'    => [Carbon::now()->subDays(7)->startOfDay(), Carbon::now()->endOfDay()],
            '30d'   => [Carbon::now()->subDays(30)->startOfDay(), Carbon::now()->endOfDay()],
            'month' => [Carbon::now()->startOfMonth(), Carbon::now()->endOfDay()],
            default => null,
        };
    }

    /**
     * Paginate an in-memory collection using an independent named query parameter.
     */
    public static function paginateCollection(Collection $items, int $perPage, string $pageName): LengthAwarePaginator
    {
        $page = Paginator::resolveCurrentPage($pageName);

        return (new LengthAwarePaginator(
            $items->forPage($page, $perPage)->values(),
            $items->count(),
            $perPage,
            $page,
            ['path' => Paginator::resolveCurrentPath(), 'pageName' => $pageName]
        ))->withQueryString();
    }
}
