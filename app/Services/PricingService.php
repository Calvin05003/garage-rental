<?php

namespace App\Services;

use Carbon\CarbonImmutable;
use Carbon\CarbonInterface;
use Illuminate\Validation\ValidationException;
use InvalidArgumentException;

class PricingService
{
    private const MAX_CENTS = 99999999999999;

    public function rentalCharge(string $type, int $days, string $rate): string
    {
        if (! in_array($type, ['daily', 'monthly'], true) || ($type === 'daily' && ($days < 1 || $days > 30))) {
            throw ValidationException::withMessages(['days' => 'Daily rentals must be between 1 and 30 days.']);
        }

        $multiplier = $type === 'monthly' ? 25 : $days;
        $cents = $this->toCents($rate);

        if ($cents > intdiv(self::MAX_CENTS, $multiplier)) {
            $this->amountTooLarge('garage_id');
        }

        return $this->fromCents($multiplier * $cents);
    }

    public function chargingKwh(string $capacity, string $percent): string
    {
        $capacityMilli = $this->scaledNumber($capacity, 3);
        $percentHundredths = $this->scaledNumber($percent, 2);

        if ($capacityMilli < 1 || $capacityMilli > 9999999999 || $percentHundredths > 10000) {
            throw ValidationException::withMessages(['initial_charge_percent' => 'Enter a charge between 0 and 100 percent.']);
        }

        $milli = intdiv($capacityMilli * (10000 - $percentHundredths) + 5000, 10000);

        return intdiv($milli, 1000).'.'.str_pad((string) ($milli % 1000), 3, '0', STR_PAD_LEFT);
    }

    public function electricityCharge(string $kwh, string $rate): string
    {
        $milli = $this->scaledNumber($kwh, 3);
        $cents = $this->toCents($rate);
        $wholeKwh = intdiv($milli, 1000);

        if ($wholeKwh > 0 && $cents > intdiv(self::MAX_CENTS, $wholeKwh)) {
            $this->amountTooLarge('garage_id');
        }

        $total = ($wholeKwh * $cents) + intdiv(($milli % 1000) * $cents + 500, 1000);

        if ($total > self::MAX_CENTS) {
            $this->amountTooLarge('garage_id');
        }

        return $this->fromCents($total);
    }

    public function extensionCharge(CarbonInterface $end, CarbonInterface $newEnd, string $rate): string
    {
        return $this->extensionBreakdown($end, $newEnd, $rate)['amount'];
    }

    public function extensionBreakdown(CarbonInterface $end, CarbonInterface $newEnd, string $rate): array
    {
        $end = CarbonImmutable::parse($end->toDateString());
        $newEnd = CarbonImmutable::parse($newEnd->toDateString());
        $days = (int) $end->diffInDays($newEnd, false);

        if ($days < 1) {
            throw ValidationException::withMessages([
                'end_date' => 'The new end date must be after the current end date.',
            ]);
        }

        $rateCents = $this->toCents($rate);
        $remaining = $days;
        $month = $end->startOfMonth()->addMonth();
        $blocks = [];
        $multiplier = 0;

        while ($remaining > 0) {
            $monthly = $remaining >= $month->daysInMonth;
            $blockDays = $monthly ? $month->daysInMonth : $remaining;
            $chargedDays = $monthly ? 25 : $blockDays;
            $multiplier += $chargedDays;

            if ($rateCents > intdiv(self::MAX_CENTS, $multiplier)) {
                $this->amountTooLarge();
            }

            $blocks[] = [
                'month' => $month->format('F Y'),
                'days' => $blockDays,
                'type' => $monthly ? 'Monthly' : 'Daily',
                'amount' => $this->fromCents($chargedDays * $rateCents),
            ];
            $remaining -= $blockDays;
            $month = $month->addMonth();
        }

        return [
            'additional_days' => $days,
            'blocks' => $blocks,
            'amount' => $this->fromCents($multiplier * $rateCents),
        ];
    }

    public function addAmounts(string $first, string $second, string $field = 'end_date'): string
    {
        $total = $this->toCents($first) + $this->toCents($second);

        if ($total > self::MAX_CENTS) {
            $this->amountTooLarge($field);
        }

        return $this->fromCents($total);
    }

    private function toCents(string $amount): int
    {
        if (! preg_match('/^[0-9]{1,12}(?:\.[0-9]{1,2})?$/D', $amount)) {
            throw new InvalidArgumentException('Invalid monetary amount.');
        }

        [$whole, $fraction] = array_pad(explode('.', $amount), 2, '');

        return ((int) $whole * 100) + (int) str_pad($fraction, 2, '0');
    }

    private function scaledNumber(string $value, int $decimals): int
    {
        if (! preg_match('/^[0-9]{1,12}(?:\.[0-9]{1,'.$decimals.'})?$/D', $value)) {
            throw new InvalidArgumentException('Invalid decimal value.');
        }

        [$whole, $fraction] = array_pad(explode('.', $value), 2, '');

        return ((int) $whole * (10 ** $decimals)) + (int) str_pad($fraction, $decimals, '0');
    }

    private function fromCents(int $amount): string
    {
        return intdiv($amount, 100).'.'.str_pad((string) ($amount % 100), 2, '0', STR_PAD_LEFT);
    }

    private function amountTooLarge(string $field = 'end_date'): never
    {
        throw ValidationException::withMessages([
            $field => 'The total exceeds the maximum supported rental amount.',
        ]);
    }
}
