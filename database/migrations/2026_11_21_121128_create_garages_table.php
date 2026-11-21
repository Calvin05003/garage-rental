<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('garages', function (Blueprint $table) {
            $table->increments('garage_id');
            $table->unsignedInteger('garage_type_id');
            $table->char('code', 4)->unique();
            $table->decimal('daily_rate', 14, 2);
            $table->decimal('electricity_rate', 14, 2)->nullable();

            $table->enum('status', ['available', 'used'])
                ->default('available');

            $table->timestamps();

            $table->foreign('garage_type_id')
                ->references('garage_type_id')
                ->on('garage_types')
                ->restrictOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('garages');
    }
};