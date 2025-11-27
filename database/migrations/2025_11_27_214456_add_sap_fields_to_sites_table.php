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
        Schema::table('sites', function (Blueprint $table) {
            $table->string('sap_company_code')->nullable();
            $table->string('sap_business_entity')->nullable();
            $table->integer('sap_cut_off_day')->nullable();
            $table->string('sap_service_charge_key')->nullable();
            $table->string('sap_participation_group')->nullable();
            $table->string('sap_settlement_unit')->nullable();
            $table->string('sap_settlement_variant')->nullable();
            $table->date('sap_settlement_valid_from')->nullable();
            $table->date('sap_settlement_valid_to')->nullable();
            $table->string('sap_source')->default('SAP');
            
            $table->index('sap_business_entity');
            $table->index('sap_cut_off_day');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('sites', function (Blueprint $table) {
            $table->dropIndex(['sap_business_entity']);
            $table->dropIndex(['sap_cut_off_day']);
            
            $table->dropColumn([
                'sap_company_code',
                'sap_business_entity',
                'sap_cut_off_day',
                'sap_service_charge_key',
                'sap_participation_group',
                'sap_settlement_unit',
                'sap_settlement_variant',
                'sap_settlement_valid_from',
                'sap_settlement_valid_to',
                'sap_source',
            ]);
        });
    }
};
