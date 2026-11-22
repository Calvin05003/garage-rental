<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('payments', function (Blueprint $table) {
            $table->increments('payment_id');
            $table->unsignedInteger('rental_id')->unique();
            $table->unsignedInteger('uploaded_by');
            $table->string('proof_path');
            $table->decimal('amount', 14, 2);

            $table->enum('status', [
                'pending',
                'approved',
                'rejected',
            ])->default('pending');

            $table->unsignedInteger('verified_by')->nullable();
            $table->dateTime('verified_at')->nullable();
            $table->timestamps();

            $table->foreign('rental_id')
                ->references('rental_id')
                ->on('rentals')
                ->restrictOnDelete();

            $table->foreign('uploaded_by')
                ->references('user_id')
                ->on('users')
                ->restrictOnDelete();

            $table->foreign('verified_by')
                ->references('user_id')
                ->on('users')
                ->restrictOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('payments');
    }
};
