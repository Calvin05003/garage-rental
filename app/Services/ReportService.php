<?php

namespace App\Services;

use App\Models\GarageType;
use App\Models\Payment;
use App\Models\Rental;
use App\Models\User;
use Carbon\CarbonImmutable;
use Illuminate\Support\Collection;

class ReportService
{
    public function monthly(int $year): Collection
    {
        $start = CarbonImmutable::create($year, 1, 1)->startOfDay();
        $rows = collect();

        for ($month = 1; $month <= 12; $month++) {
            $date = $start->setMonth($month);
            $rows->put($date->format('Y-m'), $this->emptyRow($date->format('F')));
        }

        return $this->byPeriod($rows, $start, $start->endOfYear(), 'Y-m');
    }

    public function daily(int $year, int $month): Collection
    {
        $start = CarbonImmutable::create($year, $month, 1)->startOfDay();
        $rows = collect();

        for ($day = 1; $day <= $start->daysInMonth; $day++) {
            $date = $start->setDay($day);
            $rows->put($date->toDateString(), $this->emptyRow($date->format('d/m/Y')));
        }

        return $this->byPeriod($rows, $start, $start->endOfMonth(), 'Y-m-d');
    }

    public function byGarageType(): Collection
    {
        $rows = GarageType::orderBy('garage_type_id')->get()->mapWithKeys(
            fn ($type) => [$type->garage_type_id => $this->emptyRow($type->displayName())],
        );
        $rentals = Rental::join('garages', 'garages.garage_id', '=', 'rentals.garage_id')
            ->select(['rentals.rental_id', 'garages.garage_type_id']);
        $payments = Payment::join('rentals', 'rentals.rental_id', '=', 'payments.rental_id')
            ->join('garages', 'garages.garage_id', '=', 'rentals.garage_id')
            ->select(['payments.payment_id', 'payments.amount', 'garages.garage_type_id']);

        foreach ($rentals->cursor() as $rental) {
            $this->addBooking($rows, $rental->garage_type_id);
        }

        foreach ($payments->cursor() as $payment) {
            $this->addPayment($rows, $payment->garage_type_id, $payment->amount);
        }

        return $rows;
    }

    public function byTenant(): Collection
    {
        $rows = User::whereIn('user_id', Rental::select('tenant_id'))
            ->orderBy('name')->orderBy('user_id')->get()->mapWithKeys(fn ($tenant) => [
                $tenant->user_id => [...$this->emptyRow($tenant->name), 'email' => $tenant->email],
            ]);

        foreach (Rental::select(['rental_id', 'tenant_id'])->cursor() as $rental) {
            $this->addBooking($rows, $rental->tenant_id);
        }

        $payments = Payment::join('rentals', 'rentals.rental_id', '=', 'payments.rental_id')
            ->select(['payments.payment_id', 'payments.amount', 'rentals.tenant_id']);

        foreach ($payments->cursor() as $payment) {
            $this->addPayment($rows, $payment->tenant_id, $payment->amount);
        }

        return $rows;
    }

    public function totals(Collection $rows): array
    {
        $total = $this->emptyRow('Total');

        foreach ($rows as $row) {
            $total['bookings'] += $row['bookings'];
            $total['payments'] += $row['payments'];
            $total['amount_received'] = $this->addMoney($total['amount_received'], $row['amount_received']);
        }

        return $total;
    }

    public function formatAmount(string $amount): string
    {
        [$whole, $fraction] = explode('.', $amount);

        return 'Rp '.preg_replace('/\B(?=(\d{3})+(?!\d))/', '.', $whole).','.$fraction;
    }

    private function byPeriod(Collection $rows, CarbonImmutable $start, CarbonImmutable $end, string $format): Collection
    {
        $rentals = Rental::whereDate('start_date', '>=', $start->toDateString())
            ->whereDate('start_date', '<=', $end->toDateString())
            ->select(['rental_id', 'start_date']);

        foreach ($rentals->cursor() as $rental) {
            $this->addBooking($rows, $rental->start_date->format($format));
        }

        $payments = Payment::whereBetween('created_at', [
            $start->format('Y-m-d H:i:s'), $end->format('Y-m-d H:i:s.u'),
        ])->select(['payment_id', 'amount', 'created_at']);

        foreach ($payments->cursor() as $payment) {
            $this->addPayment($rows, $payment->created_at->format($format), $payment->amount);
        }

        return $rows;
    }

    private function emptyRow(string $label): array
    {
        return ['label' => $label, 'bookings' => 0, 'payments' => 0, 'amount_received' => '0.00'];
    }

    private function addBooking(Collection $rows, int|string $key): void
    {
        $row = $rows->get($key);
        $row['bookings']++;
        $rows->put($key, $row);
    }

    private function addPayment(Collection $rows, int|string $key, string $amount): void
    {
        $row = $rows->get($key);
        $row['payments']++;
        $row['amount_received'] = $this->addMoney($row['amount_received'], $amount);
        $rows->put($key, $row);
    }

    private function addMoney(string $left, string $right): string
    {
        $left = strrev(str_replace('.', '', $left));
        $right = strrev(str_replace('.', '', $right));
        $digits = '';
        $carry = 0;

        for ($i = 0; $i < max(strlen($left), strlen($right)); $i++) {
            $sum = (int) ($left[$i] ?? 0) + (int) ($right[$i] ?? 0) + $carry;
            $digits .= (string) ($sum % 10);
            $carry = intdiv($sum, 10);
        }

        if ($carry > 0) {
            $digits .= (string) $carry;
        }

        $digits = str_pad(strrev($digits), 3, '0', STR_PAD_LEFT);

        return substr($digits, 0, -2).'.'.substr($digits, -2);
    }
}
