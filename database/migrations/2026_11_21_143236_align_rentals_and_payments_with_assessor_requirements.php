<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (DB::table('rentals')->whereNotNull('previous_rental_id')->exists()
            || DB::table('rentals')->where('booking_type', 'mixed')->exists()) {
            throw new RuntimeException('Convert linked and mixed rentals before applying this migration.');
        }

        Schema::table('rentals', function (Blueprint $table) {
            $table->dropForeign(['previous_rental_id']);
            $table->dropUnique(['previous_rental_id']);
            $table->dropColumn(['previous_rental_id', 'status']);
            $table->enum('booking_type', ['daily', 'monthly'])->change();
        });

        Schema::table('payments', function (Blueprint $table) {
            $table->index('rental_id');
        });

        Schema::table('payments', function (Blueprint $table) {
            $table->dropForeign(['verified_by']);
            $table->dropUnique(['rental_id']);
            $table->dropColumn(['status', 'verified_by', 'verified_at']);
        });
    }

    public function down(): void
    {
        if (DB::table('payments')->select('rental_id')
            ->groupBy('rental_id')->havingRaw('COUNT(*) > 1')->exists()) {
            throw new RuntimeException('Cannot restore one payment per rental while extension payments exist.');
        }

        Schema::table('payments', function (Blueprint $table) {
            $table->enum('status', ['pending', 'approved', 'rejected'])
                ->default('pending');
            $table->unsignedInteger('verified_by')->nullable();
            $table->dateTime('verified_at')->nullable();
            $table->foreign('verified_by')
                ->references('user_id')->on('users')->restrictOnDelete();
            $table->unique('rental_id');
        });

        Schema::table('payments', function (Blueprint $table) {
            $table->dropIndex(['rental_id']);
        });

        Schema::table('rentals', function (Blueprint $table) {
            $table->enum('booking_type', ['daily', 'monthly', 'mixed'])->change();
            $table->unsignedInteger('previous_rental_id')->nullable()->unique();
            $table->enum('status', ['pending', 'confirmed', 'rejected'])
                ->default('pending');
            $table->foreign('previous_rental_id')
                ->references('rental_id')->on('rentals')->restrictOnDelete();
        });
    }
};
