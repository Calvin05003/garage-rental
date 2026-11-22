<?php

namespace App\Http\Controllers\Tenant;

use App\Http\Controllers\Controller;
use App\Http\Requests\QuoteRentalRequest;
use App\Http\Requests\StoreRentalRequest;
use App\Models\GarageType;
use App\Models\Payment;
use App\Models\Rental;
use App\Models\User;
use App\Services\RentalService;
use Illuminate\Contracts\Encryption\DecryptException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;
use JsonException;
use Symfony\Component\HttpFoundation\StreamedResponse;

class RentalController extends Controller
{
    public function index(Request $request): View
    {
        return view('tenant.rentals', [
            'rentals' => Rental::where('tenant_id', $request->user()->user_id)
                ->with(['garage.garageType', 'vehicle', 'payments'])->orderByDesc('rental_id')->paginate(15),
        ]);
    }

    public function create(Request $request, RentalService $service): View
    {
        $quote = null;
        $token = old('booking_quote');

        if (is_string($token)) {
            try {
                $quote = $this->decodeQuote($token, $request->user());
            } catch (ValidationException) {
                $token = null;
            }
        }

        return $this->bookingForm($service, $quote ?? old(), $quote, $token);
    }

    public function available(Request $request, RentalService $service): JsonResponse
    {
        $data = $request->validate([
            'vehicle_type' => ['required', Rule::in(['sepeda', 'sepeda_motor', 'mobil', 'mobil_listrik'])],
            'garage_type_id' => ['required', 'integer', 'exists:garage_types,garage_type_id'],
            ...RentalService::dateRules(),
        ]);

        return response()->json([
            'garages' => $service->availableGarages($data['vehicle_type'], (int) $data['garage_type_id'], $data['booking_type'], $data['start_date'], $data['end_date'])
                ->map(fn ($garage) => [
                    'id' => $garage->garage_id,
                    'code' => $garage->code,
                    'daily_rate' => $garage->daily_rate,
                    'electricity_rate' => $garage->electricity_rate,
                ]),
        ]);
    }

    public function quote(QuoteRentalRequest $request, RentalService $service): View|RedirectResponse
    {
        try {
            $quote = $service->quote($request->user(), $request->validated());
        } catch (ValidationException $exception) {
            return to_route('tenant.rentals.create')->withErrors($exception->errors())->withInput();
        }

        $token = Crypt::encryptString(json_encode($quote, JSON_THROW_ON_ERROR));

        return $this->bookingForm($service, $quote, $quote, $token);
    }

    public function store(StoreRentalRequest $request, RentalService $service): RedirectResponse
    {
        try {
            $quote = $this->decodeQuote($request->validated('booking_quote'), $request->user());
            $service->createForTenant($request->user(), $quote, $request->file('payment_receipt'));
        } catch (ValidationException $exception) {
            return to_route('tenant.rentals.create')->withErrors($exception->errors())->withInput($request->except('payment_receipt'));
        }

        return to_route('tenant.rentals.index')->with('status', 'Garage booked.');
    }

    public function receipt(Request $request, Payment $payment): StreamedResponse
    {
        abort_unless($payment->rental->tenant_id === $request->user()->user_id, 404);
        abort_unless(Storage::disk('local')->exists($payment->proof_path), 404);

        return Storage::disk('local')->response($payment->proof_path, null, [
            'X-Content-Type-Options' => 'nosniff',
            'Cache-Control' => 'private, no-store',
        ]);
    }

    private function bookingForm(RentalService $service, array $form, ?array $quote, ?string $token): View
    {
        $form = array_map(fn ($value) => is_scalar($value) || $value === null ? $value : '', $form);
        $form += ['start_date' => today()->toDateString(), 'end_date' => today()->addDay()->toDateString()];
        $garages = collect();

        try {
            $garages = $service->availableGarages(
                (string) ($form['vehicle_type'] ?? ''),
                (int) ($form['garage_type_id'] ?? 0),
                (string) ($form['booking_type'] ?? 'daily'),
                (string) $form['start_date'],
                (string) $form['end_date'],
            );
        } catch (ValidationException) {
        }

        return view('tenant.new-rental', [
            'form' => $form,
            'quote' => $quote,
            'bookingToken' => $token,
            'garageTypes' => GarageType::orderBy('garage_type_id')->get(),
            'garages' => $garages,
        ]);
    }

    private function decodeQuote(string $token, User $tenant): array
    {
        try {
            $quote = json_decode(Crypt::decryptString($token), true, flags: JSON_THROW_ON_ERROR);
        } catch (DecryptException|JsonException) {
            throw ValidationException::withMessages(['booking_quote' => 'Your quote is invalid. Calculate your booking again.']);
        }

        if (! is_array($quote) || ! isset($quote['tenant_id'], $quote['garage_id'], $quote['start_date'], $quote['end_date'], $quote['duration_days'], $quote['total_amount'])) {
            throw ValidationException::withMessages(['booking_quote' => 'Your quote is invalid. Calculate your booking again.']);
        }

        abort_unless($quote['tenant_id'] === $tenant->user_id, 403);

        return $quote;
    }
}
