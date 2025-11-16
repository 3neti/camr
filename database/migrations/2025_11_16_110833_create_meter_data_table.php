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
        Schema::create('meter_data', function (Blueprint $table) {
            $table->id();
            $table->string('location')->default('Home');
            $table->string('meter_name'); // Links to meters.name
            $table->timestamp('reading_datetime');
            
            // Voltage (RMS)
            $table->decimal('vrms_a', 10, 2)->nullable();
            $table->decimal('vrms_b', 10, 2)->nullable();
            $table->decimal('vrms_c', 10, 2)->nullable();
            
            // Current (RMS)
            $table->decimal('irms_a', 10, 2)->nullable();
            $table->decimal('irms_b', 10, 2)->nullable();
            $table->decimal('irms_c', 10, 2)->nullable();
            
            // Power measurements
            $table->decimal('frequency', 10, 2)->nullable();
            $table->decimal('power_factor', 10, 3)->nullable();
            $table->decimal('watt', 12, 2)->nullable();
            $table->decimal('va', 12, 2)->nullable();
            $table->decimal('var', 12, 2)->nullable();
            
            // Energy measurements (Wh)
            $table->decimal('wh_delivered', 15, 2)->nullable();
            $table->decimal('wh_received', 15, 2)->nullable();
            $table->decimal('wh_net', 15, 2)->nullable();
            $table->decimal('wh_total', 15, 2)->nullable();
            
            // Reactive energy (VARh)
            $table->decimal('varh_negative', 15, 2)->nullable();
            $table->decimal('varh_positive', 15, 2)->nullable();
            $table->decimal('varh_net', 15, 2)->nullable();
            $table->decimal('varh_total', 15, 2)->nullable();
            
            // Apparent energy (VAh)
            $table->decimal('vah_total', 15, 2)->nullable();
            
            // Demand measurements
            $table->decimal('max_rec_kw_demand', 12, 2)->nullable();
            $table->timestamp('max_rec_kw_demand_time')->nullable();
            $table->decimal('max_del_kw_demand', 12, 2)->nullable();
            $table->timestamp('max_del_kw_demand_time')->nullable();
            $table->decimal('max_pos_kvar_demand', 12, 2)->nullable();
            $table->timestamp('max_pos_kvar_demand_time')->nullable();
            $table->decimal('max_neg_kvar_demand', 12, 2)->nullable();
            $table->timestamp('max_neg_kvar_demand_time')->nullable();
            
            // Phase angles
            $table->decimal('v_phase_angle_a', 10, 2)->nullable();
            $table->decimal('v_phase_angle_b', 10, 2)->nullable();
            $table->decimal('v_phase_angle_c', 10, 2)->nullable();
            $table->decimal('i_phase_angle_a', 10, 2)->nullable();
            $table->decimal('i_phase_angle_b', 10, 2)->nullable();
            $table->decimal('i_phase_angle_c', 10, 2)->nullable();
            
            // Metadata
            $table->string('mac_address')->nullable();
            $table->string('software_version')->nullable();
            $table->boolean('relay_status')->nullable();
            $table->boolean('genset_status')->nullable();
            
            $table->timestamps();
            
            // Critical index for report queries
            $table->index(['meter_name', 'reading_datetime', 'location'], 'meter_data_query_index');
            $table->index('reading_datetime');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('meter_data');
    }
};
