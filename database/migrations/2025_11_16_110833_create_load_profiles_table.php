<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('load_profiles', function (Blueprint $table) {
            $table->id();
            $table->string('meter_name'); // Links to meters.name
            $table->timestamp('reading_datetime');
            $table->string('event_id')->nullable();
            
            // Load profile channels (15-minute intervals)
            $table->decimal('channel_1', 12, 2)->nullable()->comment('1.5.0 kW');
            $table->decimal('channel_2', 12, 2)->nullable()->comment('1-1:1.30.2 kWh');
            $table->decimal('channel_3', 12, 2)->nullable()->comment('1-1:3.30.2 kvarh');
            $table->decimal('channel_4', 12, 2)->nullable()->comment('2.5.0 kW');
            $table->decimal('channel_5', 12, 2)->nullable()->comment('1-1:2.30.2 kWh');
            $table->decimal('channel_6', 12, 2)->nullable()->comment('1-1:4.30.2 kvarh');
            $table->decimal('channel_7', 12, 2)->nullable();
            $table->decimal('channel_8', 12, 2)->nullable();
            
            $table->timestamps();
            
            $table->index(['meter_name', 'reading_datetime']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('load_profiles');
    }
};
