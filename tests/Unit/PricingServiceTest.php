<?php

namespace Tests\Unit;

use App\Services\PricingService;
use App\Services\RentalService;
use Carbon\CarbonImmutable;
use Illuminate\Validation\ValidationException;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class PricingServiceTest extends TestCase
{
    #[DataProvider('rentalDateCases')]
    public function test_rental_duration_and_price_are_calculated_from_dates(string $type, string $start, string $end, int $days, string $amount): void
    {
        $this->travelTo(CarbonImmutable::parse($start.' 12:00:00', 'Asia/Jakarta'));
        [$first, $last] = app(RentalService::class)->rentalDates($type, $start, $end);

        $this->assertSame($days, (int) $first->diffInDays($last));
        $this->assertSame($amount, app(PricingService::class)->rentalCharge($type, $days, '100000.00'));
    }

    public static function rentalDateCases(): array
    {
        return [
            'one day' => ['daily', '2026-11-22', '2026-11-23', 1, '100000.00'],
            'month boundary' => ['daily', '2026-11-30', '2026-12-02', 2, '200000.00'],
            'November maximum' => ['daily', '2026-11-22', '2026-12-21', 29, '2900000.00'],
            '31 day month maximum' => ['daily', '2027-03-01', '2027-03-31', 30, '3000000.00'],
            'February maximum' => ['daily', '2027-02-24', '2027-03-23', 27, '2700000.00'],
            'leap February maximum' => ['daily', '2028-02-24', '2028-03-23', 28, '2800000.00'],
            '31 day month' => ['monthly', '2027-03-01', '2027-04-01', 31, '2500000.00'],
            '30 day month' => ['monthly', '2026-11-22', '2026-12-22', 30, '2500000.00'],
            'short February' => ['monthly', '2027-01-31', '2027-02-28', 28, '2500000.00'],
            'leap February' => ['monthly', '2028-01-31', '2028-02-29', 29, '2500000.00'],
        ];
    }

    #[DataProvider('invalidRentalDateCases')]
    public function test_rental_dates_reject_invalid_periods(string $type, string $start, string $end): void
    {
        $this->travelTo(CarbonImmutable::parse($start.' 12:00:00', 'Asia/Jakarta'));
        $this->expectException(ValidationException::class);

        app(RentalService::class)->rentalDates($type, $start, $end);
    }

    public static function invalidRentalDateCases(): array
    {
        return [
            'November over limit' => ['daily', '2026-11-22', '2026-12-22'],
            '31 day month over limit' => ['daily', '2027-03-01', '2027-04-01'],
            'February over limit' => ['daily', '2027-02-24', '2027-03-24'],
            'leap February over limit' => ['daily', '2028-02-24', '2028-03-24'],
            'same day checkout' => ['daily', '2026-11-22', '2026-11-22'],
            'earlier checkout' => ['daily', '2026-11-22', '2026-11-21'],
            'monthly too short' => ['monthly', '2026-11-22', '2026-12-21'],
            'monthly too long' => ['monthly', '2026-11-22', '2026-12-23'],
        ];
    }

    #[DataProvider('extensionCases')]
    public function test_extension_uses_successive_calendar_month_lengths(
        string $end,
        int $days,
        string $rate,
        string $expected,
        array $blockDays,
    ): void {
        $date = CarbonImmutable::parse($end);
        $quote = app(PricingService::class)->extensionBreakdown($date, $date->addDays($days), $rate);

        $this->assertSame($days, $quote['additional_days']);
        $this->assertSame($expected, $quote['amount']);
        $this->assertSame($blockDays, array_column($quote['blocks'], 'days'));
    }

    public static function extensionCases(): array
    {
        return [
            '67 days after February' => ['2027-02-24', 67, '100000.00', '5600000.00', [31, 30, 6]],
            'leap year February end' => ['2028-02-24', 67, '100000.00', '5600000.00', [31, 30, 6]],
            'partial month' => ['2027-02-24', 6, '100000.00', '600000.00', [6]],
            'one day' => ['2027-02-24', 1, '0.01', '0.01', [1]],
            '31 day month' => ['2027-02-24', 31, '100000.00', '2500000.00', [31]],
            'two months' => ['2027-02-24', 61, '100000.00', '5000000.00', [31, 30]],
            'February' => ['2027-01-31', 28, '100000.00', '2500000.00', [28]],
            'leap February' => ['2028-01-31', 29, '100000.00', '2500000.00', [29]],
            'year change' => ['2026-12-24', 32, '100000.00', '2600000.00', [31, 1]],
            'decimal rate' => ['2027-02-24', 67, '100000.01', '5600000.56', [31, 30, 6]],
        ];
    }

    public function test_a_full_block_has_a_monthly_rate_and_remainder_has_a_daily_rate(): void
    {
        $service = app(PricingService::class);
        $end = CarbonImmutable::parse('2027-02-24');
        $quote = $service->extensionBreakdown($end, $end->addDays(67), '100000.00');

        $this->assertSame(['March 2027', 'April 2027', 'May 2027'], array_column($quote['blocks'], 'month'));
        $this->assertSame(['Monthly', 'Monthly', 'Daily'], array_column($quote['blocks'], 'type'));
        $this->assertSame(['2500000.00', '2500000.00', '600000.00'], array_column($quote['blocks'], 'amount'));
        $this->assertSame('5600000.00', $service->extensionCharge($end, $end->addDays(67), '100000.00'));
        $this->assertSame('2027-02-24', $end->toDateString());
    }

    public function test_extension_must_add_days(): void
    {
        $this->expectException(ValidationException::class);

        app(PricingService::class)->extensionCharge(
            CarbonImmutable::parse('2027-02-24'), CarbonImmutable::parse('2027-02-24'), '100000.00',
        );
    }

    public function test_charge_cannot_exceed_the_database_amount_limit(): void
    {
        $this->expectException(ValidationException::class);

        app(PricingService::class)->extensionCharge(
            CarbonImmutable::parse('2027-02-24'), CarbonImmutable::parse('2027-02-26'), '999999999999.99',
        );
    }

    public function test_accumulated_amount_cannot_exceed_the_database_limit(): void
    {
        $this->expectException(ValidationException::class);

        app(PricingService::class)->addAmounts('999999999999.99', '0.01');
    }

    public function test_daily_and_monthly_rental_prices_use_the_entered_daily_rate(): void
    {
        $service = app(PricingService::class);

        $this->assertSame('300000.03', $service->rentalCharge('daily', 3, '100000.01'));
        $this->assertSame('2500000.25', $service->rentalCharge('monthly', 1, '100000.01'));
    }

    public function test_charging_fills_only_the_missing_capacity(): void
    {
        $service = app(PricingService::class);

        $this->assertSame('36.000', $service->chargingKwh('60', '40'));
        $this->assertSame('60.000', $service->chargingKwh('60', '0'));
        $this->assertSame('0.000', $service->chargingKwh('60', '100'));
        $this->assertSame('90000.00', $service->electricityCharge('36.000', '2500.00'));
        $this->assertSame('0.00', $service->electricityCharge('0.000', '2500.00'));
        $this->assertSame('33.92', $service->electricityCharge('0.125', '271.32'));
    }

    public function test_initial_rental_rejects_more_than_30_days(): void
    {
        $this->expectException(ValidationException::class);

        app(PricingService::class)->rentalCharge('daily', 31, '100000.00');
    }

    public function test_electricity_price_cannot_overflow_the_database_amount(): void
    {
        $this->expectException(ValidationException::class);

        app(PricingService::class)->electricityCharge('9999999.999', '999999999999.99');
    }
}
