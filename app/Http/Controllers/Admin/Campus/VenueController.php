<?php

namespace App\Http\Controllers\Admin\Campus;

use App\Http\Controllers\Controller;
use App\Models\Branch;
use App\Models\Venue;
use Devrabiul\ToastMagic\Facades\ToastMagic;
use Illuminate\Http\Request;

/**
 * Venue controller — manages campus & short-course operations within the admin portal.
 *
 * @author Renatus Bernardo
 */
class VenueController extends Controller
{
    /**
     * Display a listing of the resource records.
     */
    public function index(Request $request)
    {
        $venues = Venue::with('branch')
            ->withCount('courseAssignments')
            ->when($request->filled('branch_id'), fn ($q) => $q->where('branch_id', $request->branch_id))
            ->when($request->filled('search'), fn ($q) => $q->where('name', 'like', '%' . $request->search . '%'))
            ->orderBy('name')
            ->paginate(15)
            ->withQueryString();

        $branches = Branch::where('is_active', true)->orderBy('name')->get(['id', 'name']);

        return view('admin.campus.venues.index', compact('venues', 'branches'));
    }

    /**
     * Validate and persist a newly created resource record.
     */
    public function store(Request $request)
    {
        Venue::create($this->validated($request));
        ToastMagic::success('Venue created successfully.');

        return redirect()->route('admin.campus.venues.index');
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Venue $venue)
    {
        $branches = Branch::orderBy('name')->get(['id', 'name']);

        return view('admin.campus.venues.edit', compact('venue', 'branches'));
    }

    /**
     * Validate and update the specified resource record.
     */
    public function update(Request $request, Venue $venue)
    {
        $venue->update($this->validated($request));
        ToastMagic::success('Venue updated successfully.');

        return redirect()->route('admin.campus.venues.index');
    }

    /**
     * Remove the specified resource record from storage.
     */
    public function destroy(Venue $venue)
    {
        if ($venue->courseAssignments()->exists()) {
            ToastMagic::error('Venue is used by timetable entries. Deactivate it instead.');
            return back();
        }

        $venue->delete();
        ToastMagic::success('Venue deleted.');

        return back();
    }

    /**
     * JSON list of active venues for a branch (used by applicant & timetable forms).
     */
    public function byBranch(Branch $branch)
    {
        return response()->json(
            Venue::withoutGlobalScope('branch')->where('branch_id', $branch->id)->where('is_active', true)
                ->orderBy('name')->get(['id', 'name', 'capacity'])
        );
    }

    /**
     * @return array<string, mixed>
     */
    private function validated(Request $request): array
    {
        $data = $request->validate([
            'branch_id' => ['required', 'exists:branches,id'],
            'name' => ['required', 'string', 'max:255'],
            'capacity' => ['required', 'integer', 'min:1', 'max:5000'],
            'equipment' => ['nullable', 'string', 'max:255'],
            'notes' => ['nullable', 'string', 'max:1000'],
            'is_active' => ['nullable', 'boolean'],
        ]);
        $data['is_active'] = $request->boolean('is_active');

        return $data;
    }
}
