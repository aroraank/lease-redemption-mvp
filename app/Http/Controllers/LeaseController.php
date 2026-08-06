<?php

namespace App\Http\Controllers;

use App\Http\Requests\LeaseLookupRequest;
use App\Services\LeaseApiService;
use Illuminate\Http\Request;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class LeaseController extends Controller
{
    public function __construct(private LeaseApiService $api)
    {
    }

    public function home(): View
    {
        return view('home');
    }

    public function lookup(LeaseLookupRequest $request): RedirectResponse
    {
        $data = $request->validated();

        $result = $this->api->getLeases($data['dealer_code'], $data['zip']);

        if (!$result['ok'] || empty($result['leases'])) {
            return back()->withInput()->with('error', $result['message']);
        }

        $request->session()->put('lease_lookup', [
            'dealer_code' => $data['dealer_code'],
            'zip'         => $data['zip'],
            'leases'      => $result['leases'],
        ]);

        return redirect()->route('leases.index')->with('success', $result['message']);
    }

    public function index(Request $request): View|RedirectResponse
    {
        $lookup = $request->session()->get('lease_lookup');

        if (!$lookup || empty($lookup['leases'])) {
            return redirect()->route('home')->with('error', 'Please look up your leases first.');
        }

        return view('leases', [
            'dealerCode' => $lookup['dealer_code'],
            'zip'        => $lookup['zip'],
            'leases'     => $lookup['leases'],
        ]);
    }

    public function redeem(Request $request): RedirectResponse
    {
        $lookup = $request->session()->get('lease_lookup');

        if (!$lookup || empty($lookup['leases'])) {
            return redirect()->route('home')->with('error', 'Your session expired. Please look up your leases again.');
        }

        $validated = $request->validate([
            'lease_ids'   => ['required', 'array', 'min:1'],
            'lease_ids.*' => ['string'],
        ], [
            'lease_ids.required' => 'Select at least one lease to redeem.',
            'lease_ids.min'      => 'Select at least one lease to redeem.',
        ]);

        $validIds = collect($lookup['leases'])->pluck('id')->all();
        $selected = array_values(array_intersect($validated['lease_ids'], $validIds));

        if (empty($selected)) {
            return redirect()->route('leases.index')->with('error', 'The selected leases are no longer valid. Please try again.');
        }

        $result = $this->api->redeem($selected);

        if (!$result['ok']) {
            return redirect()->route('leases.index')->with('error', $result['message']);
        }

        $request->session()->forget('lease_lookup');

        return redirect()->route('home')->with('success', $result['message']);
    }

    /** Demo helper: clear all persisted redemptions so the catalog is full again. */
    public function reset(Request $request): RedirectResponse
    {
        $this->api->resetRedemptions();
        $request->session()->forget('lease_lookup');

        return redirect()->route('home')->with('success', 'Demo data reset — all leases are available again.');
    }
}
