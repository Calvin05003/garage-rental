<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('vehicles', function (Blueprint $table) {
            $table->increments('vehicle_id');
            $table->unsignedInteger('user_id');

            $table->enum('type', [
                'sepeda',
                'sepeda_motor',
                'mobil',
                'mobil_listrik',
            ]);

            $table->string('plate_number', 20)->nullable();
            $table->string('brand', 100);
            $table->string('model', 100);
            $table->decimal('battery_capacity_kwh', 10, 3)
                ->nullable();

            $table->timestamps();

            $table->foreign('user_id')
                ->references('user_id')
                ->on('users')
                ->restrictOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('vehicles');
    }
};