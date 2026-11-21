<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('rentals', function (Blueprint $table) {
            $table->increments('rental_id');

            $table->unsignedInteger('tenant_id');
            $table->unsignedInteger('created_by');
            $table->unsignedInteger('previous_rental_id')
                ->nullable()
                ->unique();

            $table->unsignedInteger('vehicle_id');
            $table->unsignedInteger('garage_id');

            $table->enum('booking_type', [
                'daily',
                'monthly',
                'mixed',
            ]);

            $table->date('start_date');
            $table->date('end_date');
            $table->unsignedSmallInteger('base_month_days');

            $table->decimal('booked_daily_rate', 14, 2);
            $table->decimal('booked_electricity_rate', 14, 2)
                ->nullable();

            $table->decimal('booked_battery_capacity_kwh', 10, 3)
                ->nullable();

            $table->decimal('initial_charge_percent', 5, 2)
                ->nullable();

            $table->decimal('electricity_kwh', 10, 3)
                ->default(0);

            $table->decimal('rental_amount', 14, 2);
            $table->decimal('electricity_amount', 14, 2)
                ->default(0);

            $table->enum('status', [
                'pending',
                'confirmed',
                'rejected',
            ])->default('pending');

            $table->timestamps();

            $table->foreign('tenant_id')
                ->references('user_id')
                ->on('users')
                ->restrictOnDelete();

            $table->foreign('created_by')
                ->references('user_id')
                ->on('users')
                ->restrictOnDelete();

            $table->foreign('previous_rental_id')
                ->references('rental_id')
                ->on('rentals')
                ->restrictOnDelete();

            $table->foreign('vehicle_id')
                ->references('vehicle_id')
                ->on('vehicles')
                ->restrictOnDelete();

            $table->foreign('garage_id')
                ->references('garage_id')
                ->on('garages')
                ->restrictOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('rentals');
    }
};