<?php

namespace App\Http\Controllers\Admin\Campus;

use App\Http\Controllers\Controller;
use App\Models\Country;
use Devrabiul\ToastMagic\Facades\ToastMagic;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

/**
 * Countries / Nationality Reference Manager (migrated from SCMS CountryController).
 * @author Renatus Bernardo
 */
class CountryController extends Controller
{
    /**
     * Display a listing of the resource records.
     */
    public function index(Request $request): View
    {
        $q = trim((string) $request->query('q', ''));

        $countries = Country::withCount(['branches', 'applicants'])
            ->when($q !== '', fn ($query) => $query->where('name', 'like', "%{$q}%")
                ->orWhere('nicename', 'like', "%{$q}%")
                ->orWhere('code', 'like', "%{$q}%"))
            ->orderBy('name')
            ->paginate(25)
            ->withQueryString();

        return view('admin.campus.countries.index', compact('countries', 'q'));
    }

    /**
     * Validate and persist a newly created resource record.
     */
    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'code'      => ['required', 'string', 'max:10', 'unique:countries,code'],
            'name'      => ['required', 'string', 'max:120'],
            'nicename'  => ['nullable', 'string', 'max:120'],
            'iso3'      => ['nullable', 'string', 'max:5'],
            'phonecode' => ['required', 'integer', 'min:0', 'max:99999'],
        ]);

        $data['code'] = strtoupper(trim($data['code']));
        $data['iso'] = $data['code'];
        $data['nicename'] = $data['nicename'] ?: $data['name'];

        Country::create($data);
        ToastMagic::success("Country \"{$data['nicename']}\" added.");

        return back();
    }

    /**
     * Validate and update the specified resource record.
     */
    public function update(Request $request, Country $country): RedirectResponse
    {
        $data = $request->validate([
            'code'      => ['required', 'string', 'max:10', Rule::unique('countries', 'code')->ignore($country->id)],
            'name'      => ['required', 'string', 'max:120'],
            'nicename'  => ['nullable', 'string', 'max:120'],
            'iso3'      => ['nullable', 'string', 'max:5'],
            'phonecode' => ['required', 'integer', 'min:0', 'max:99999'],
        ]);

        $data['code'] = strtoupper(trim($data['code']));
        $data['iso'] = $data['code'];
        $data['nicename'] = $data['nicename'] ?: $data['name'];

        $country->update($data);
        ToastMagic::success("Country \"{$country->display_name}\" updated.");

        return back();
    }

    /**
     * Remove the specified resource record from storage.
     */
    public function destroy(Country $country): RedirectResponse
    {
        if ($country->branches()->exists() || $country->applicants()->exists()) {
            ToastMagic::error('Cannot delete country currently linked to branches or applicants.');
            return back();
        }

        $country->delete();
        ToastMagic::success('Country deleted.');

        return back();
    }
}
