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
        Schema::table('meters', function (Blueprint $table) {
            // SAP Company and Entity Information
            $table->string('sap_company_code')->nullable()->after('status');
            $table->string('sap_business_entity')->nullable();
            
            // Building Information
            $table->string('sap_building')->nullable();
            $table->string('sap_building_description')->nullable();
            $table->string('sap_land')->nullable();
            $table->string('sap_land_description')->nullable();
            
            // Rental Object Information
            $table->string('sap_rental_object')->nullable();
            $table->string('sap_rental_object_name')->nullable();
            $table->string('sap_usage_type')->nullable();
            $table->date('sap_ro_valid_from')->nullable();
            $table->date('sap_ro_valid_to')->nullable();
            
            // Contract Information
            $table->string('sap_contract_number')->nullable();
            
            // Meter Characteristics
            $table->string('sap_meter_characteristic')->nullable();
            $table->string('sap_measuring_point')->nullable();
            $table->string('sap_measuring_point_desc')->nullable();
            $table->integer('sap_measurement_sequence')->nullable();
            $table->string('sap_measurement_separator')->nullable();
            $table->string('sap_participation_group')->nullable();
            
            // SAP Metadata
            $table->date('sap_creation_date')->nullable();
            $table->string('sap_creation_by')->nullable();
            $table->date('sap_last_change_on')->nullable();
            $table->string('sap_last_change_by')->nullable();
            $table->string('sap_source')->default('SAP'); // SAP or SEP
            
            // Index for common queries
            $table->index(['sap_business_entity', 'name']);
            $table->index('sap_measuring_point');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('meters', function (Blueprint $table) {
            $table->dropIndex(['sap_business_entity', 'name']);
            $table->dropIndex(['sap_measuring_point']);
            
            $table->dropColumn([
                'sap_company_code',
                'sap_business_entity',
                'sap_building',
                'sap_building_description',
                'sap_land',
                'sap_land_description',
                'sap_rental_object',
                'sap_rental_object_name',
                'sap_usage_type',
                'sap_ro_valid_from',
                'sap_ro_valid_to',
                'sap_contract_number',
                'sap_meter_characteristic',
                'sap_measuring_point',
                'sap_measuring_point_desc',
                'sap_measurement_sequence',
                'sap_measurement_separator',
                'sap_participation_group',
                'sap_creation_date',
                'sap_creation_by',
                'sap_last_change_on',
                'sap_last_change_by',
                'sap_source',
            ]);
        });
    }
};
