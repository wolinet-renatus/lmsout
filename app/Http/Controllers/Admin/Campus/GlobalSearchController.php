<?php

namespace App\Http\Controllers\Admin\Campus;

use App\Http\Controllers\Controller;
use App\Models\Applicant;
use App\Models\Course;
use App\Models\Invoice;
use App\Models\User;
use Illuminate\Http\Request;

/**
 * Institutional Global Search across Applicants, Invoices/Control Numbers, Users & Courses (migrated from SCMS).
 * @author Renatus Bernardo
 */
class GlobalSearchController extends Controller
{
    /**
     * Search.
     */
    public function search(Request $request)
    {
        $q = trim((string) $request->input('q', ''));
        $empty = strlen($q) < 2;

        $applicants = collect();
        $invoices = collect();
        $users = collect();
        $courses = collect();

        if (! $empty) {
            $applicants = Applicant::with('branch:id,name')
                ->where(fn ($w) => $w->where('full_name', 'like', "%{$q}%")
                    ->orWhere('first_name', 'like', "%{$q}%")
                    ->orWhere('last_name', 'like', "%{$q}%")
                    ->orWhere('email', 'like', "%{$q}%")
                    ->orWhere('phone', 'like', "%{$q}%"))
                ->limit(10)
                ->get();

            $invoices = Invoice::with(['user:id,name', 'applicant:id,full_name,first_name,last_name', 'course:id,title'])
                ->where(fn ($w) => $w->where('invoice_number', 'like', "%{$q}%")
                    ->orWhere('control_number', 'like', "%{$q}%"))
                ->limit(10)
                ->get();

            $users = User::whereIn('role', ['student', 'instructor'])
                ->where(fn ($w) => $w->where('name', 'like', "%{$q}%")
                    ->orWhere('email', 'like', "%{$q}%")
                    ->orWhere('registration_number', 'like', "%{$q}%"))
                ->limit(10)
                ->get(['id', 'name', 'email', 'role', 'registration_number']);

            $courses = Course::where('title', 'like', "%{$q}%")
                ->limit(10)
                ->get(['id', 'title', 'delivery_mode', 'status', 'regular_price', 'sale_price']);
        }

        if ($request->wantsJson()) {
            return response()->json(compact('q', 'applicants', 'invoices', 'users', 'courses'));
        }

        return view('admin.campus.search', compact('q', 'empty', 'applicants', 'invoices', 'users', 'courses'));
    }
}
