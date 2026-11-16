<?php

namespace App\Http\Controllers\Admin\Campus;

use App\Http\Controllers\Controller;
use App\Models\Branch;
use App\Models\Country;
use Devrabiul\ToastMagic\Facades\ToastMagic;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

/**
 * Branch controller — manages campus & short-course operations within the admin portal.
 *
 * @author Renatus Bernardo
 */
class BranchController extends Controller
{
    /**
     * Display a listing of the resource records.
     */
    public function index(Request $request)
    {
        $branches = Branch::with('country')
            ->withCount(['venues', 'enrollments', 'users'])
            ->when($request->filled('search'), fn ($q) => $q->where(function ($w) use ($request) {
                $w->where('name', 'like', '%' . $request->search . '%')
                    ->orWhere('branch_code', 'like', '%' . $request->search . '%');
            }))
            ->orderBy('name')
            ->paginate(15)
            ->withQueryString();

        $countries = Country::orderBy('name')->get(['id', 'name', 'nicename']);

        return view('admin.campus.branches.index', compact('branches', 'countries'));
    }

    /**
     * Validate and persist a newly created resource record.
     */
    public function store(Request $request)
    {
        Branch::create($this->validated($request));
        ToastMagic::success('Branch created successfully.');

        return redirect()->route('admin.campus.branches.index');
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Branch $branch)
    {
        $countries = Country::orderBy('name')->get(['id', 'name', 'nicename']);

        return view('admin.campus.branches.edit', compact('branch', 'countries'));
    }

    /**
     * Validate and update the specified resource record.
     */
    public function update(Request $request, Branch $branch)
    {
        $branch->update($this->validated($request, $branch));
        ToastMagic::success('Branch updated successfully.');

        return redirect()->route('admin.campus.branches.index');
    }

    /**
     * Remove the specified resource record from storage.
     */
    public function destroy(Branch $branch)
    {
        if ($branch->enrollments()->exists() || $branch->invoices()->exists()) {
            ToastMagic::error('This branch has enrollments or invoices. Deactivate it instead of deleting.');
            return back();
        }

        $branch->delete();
        ToastMagic::success('Branch deleted.');

        return back();
    }

    /**
     * @return array<string, mixed>
     */
    private function validated(Request $request, ?Branch $branch = null): array
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'branch_code' => ['nullable', 'string', 'max:50', Rule::unique('branches', 'branch_code')->ignore($branch?->id)],
            'email' => ['nullable', 'email', 'max:255'],
            'phone' => ['nullable', 'string', 'max:50'],
            'address' => ['nullable', 'string', 'max:1000'],
            'country_id' => ['nullable', 'exists:countries,id'],
            'is_active' => ['nullable', 'boolean'],
        ]);
        $data['is_active'] = $request->boolean('is_active');

        return $data;
    }
}
