<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('garage_types', function (Blueprint $table) {
            $table->increments('garage_type_id');
            $table->string('name', 30)->unique();
            $table->char('prefix', 1)->unique();
            $table->unsignedSmallInteger('last_number')->default(0);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('garage_types');
    }
};
