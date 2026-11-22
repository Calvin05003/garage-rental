<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\ExtendRentalRequest;
use App\Models\Payment;
use App\Models\Rental;
use App\Services\RentalService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

class RentalController extends Controller
{
    public function index(): View
    {
        return view('admin.rentals', [
            'rentals' => Rental::activeOn(today()->toDateString())
                ->with(['garage.garageType', 'tenant', 'vehicle'])
                ->orderBy('end_date')->orderBy('rental_id')->paginate(15),
        ]);
    }

    public function edit(ExtendRentalRequest $request, Rental $rental, RentalService $service): View
    {
        abort_unless($rental->isActiveOn(today()), 404);
        $data = $request->validated();

        return view('admin.extend-rental', [
            'rental' => $rental->load(['garage.garageType', 'tenant', 'vehicle', 'payments']),
            'quote' => isset($data['end_date']) ? $service->extensionQuote($rental, $data['end_date']) : null,
            'newEndDate' => $data['end_date'] ?? null,
        ]);
    }

    public function update(ExtendRentalRequest $request, Rental $rental, RentalService $service): RedirectResponse
    {
        $data = $request->validated();
        $service->extendRental(
            $request->user(),
            $rental,
            $data['end_date'],
            $data['expected_end_date'],
            $request->file('payment_receipt'),
        );

        return to_route('admin.rentals.edit', $rental)->with('status', 'Booking extended.');
    }

    public function receipt(Payment $payment): StreamedResponse
    {
        abort_unless(Storage::disk('local')->exists($payment->proof_path), 404);

        return Storage::disk('local')->response($payment->proof_path, null, [
            'X-Content-Type-Options' => 'nosniff',
            'Cache-Control' => 'private, no-store',
        ]);
    }
}
