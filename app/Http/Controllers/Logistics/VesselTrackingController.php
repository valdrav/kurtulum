<?php

namespace App\Http\Controllers\Logistics;

use App\Http\Controllers\Controller;
use App\Models\Vessel;
use App\Services\VesselPublicInfoService;
use App\Services\VesselTrackingService;
use Illuminate\Http\Request;

class VesselTrackingController extends Controller
{
    public function index(VesselTrackingService $tracker)
    {
        return view('logistics.vessels.track', [
            'recentVessels' => Vessel::query()
                ->orderByDesc('tracked_at')
                ->orderByDesc('updated_at')
                ->limit(24)
                ->get(),
            'apiConfigured' => $tracker->isApiConfigured(),
            'activeProvider' => $tracker->activeProvider(),
        ]);
    }

    public function search(Request $request, VesselTrackingService $tracker, VesselPublicInfoService $publicInfo)
    {
        $query = trim($request->input('q', ''));

        if ($query === '') {
            return redirect()->route('vessels.track.index');
        }

        $direct = $publicInfo->findOrCreateByIdentifier($query);

        if ($direct) {
            return redirect()->route('vessels.track.show', $direct);
        }

        $results = $tracker->search($query);

        if ($results->count() === 1) {
            return redirect()->route('vessels.track.show', $results->first());
        }

        return view('logistics.vessels.track', [
            'query' => $query,
            'results' => $results,
            'recentVessels' => Vessel::query()
                ->orderByDesc('tracked_at')
                ->orderByDesc('updated_at')
                ->limit(24)
                ->get(),
            'apiConfigured' => $tracker->isApiConfigured(),
            'activeProvider' => $tracker->activeProvider(),
            'searchEmpty' => $results->isEmpty(),
        ]);
    }

    public function show(Vessel $vessel, VesselTrackingService $tracker, Request $request)
    {
        $data = $tracker->track($vessel, $request->boolean('refresh'));
        $data['apiConfigured'] = $tracker->isApiConfigured();

        return view('logistics.vessels.show', $data);
    }

    public function destroy(Vessel $vessel)
    {
        if ($vessel->shipments()->exists()) {
            return back()->withErrors(['vessel' => __('logistics.vessel_linked_shipment')]);
        }

        $vessel->delete();

        return redirect()->route('vessels.track.index')->with('success', __('logistics.vessel_removed'));
    }
}
