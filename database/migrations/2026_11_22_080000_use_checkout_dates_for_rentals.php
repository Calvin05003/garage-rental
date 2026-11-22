<?php

use Carbon\CarbonImmutable;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        $this->shiftEndDates(1);
    }

    public function down(): void
    {
        $this->shiftEndDates(-1);
    }

    private function shiftEndDates(int $days): void
    {
        DB::transaction(function () use ($days) {
            DB::table('rentals')->orderBy('rental_id')->chunkById(200, function ($rentals) use ($days) {
                foreach ($rentals as $rental) {
                    DB::table('rentals')->where('rental_id', $rental->rental_id)->update([
                        'end_date' => CarbonImmutable::parse($rental->end_date)->addDays($days)->toDateString(),
                    ]);
                }
            }, 'rental_id');
        });
    }
};
