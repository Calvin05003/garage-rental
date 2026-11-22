<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreGarageRequest;
use App\Models\Garage;
use App\Models\GarageType;
use App\Services\GarageService;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class GarageController extends Controller
{
    public function index(GarageService $service): View
    {
        $service->syncAvailability(today()->toDateString());

        return view('admin.garages', [
            'garageTypes' => GarageType::orderBy('garage_type_id')->get(),
            'garages' => Garage::with('garageType')
                ->orderByDesc('garage_id')->paginate(15),
        ]);
    }

    public function store(StoreGarageRequest $request, GarageService $service): RedirectResponse
    {
        $data = $request->validated();
        $garages = $service->createBatch(
            (int) $data['garage_type_id'],
            (int) $data['quantity'],
            (string) $data['daily_rate'],
            isset($data['electricity_rate']) ? (string) $data['electricity_rate'] : null,
        );

        $codes = $garages->first()->code;

        if ($garages->count() > 1) {
            $codes .= '–'.$garages->last()->code;
        }

        return to_route('admin.garages.index')->with(
            'status',
            'Created '.$garages->count().' garage'.($garages->count() === 1 ? '' : 's').': '.$codes.'.',
        );
    }
}
