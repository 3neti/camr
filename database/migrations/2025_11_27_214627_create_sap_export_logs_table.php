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
        Schema::create('sap_export_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('site_id')->nullable()->constrained()->nullOnDelete();
            $table->string('business_entity');
            $table->string('company_code');
            $table->date('cut_off_date');
            $table->string('file_name');
            $table->string('file_path');
            $table->string('status'); // success, failed
            $table->integer('total_meters')->default(0);
            $table->integer('exported_meters')->default(0);
            $table->integer('skipped_meters')->default(0);
            $table->text('validation_summary')->nullable();
            $table->text('errors')->nullable();
            $table->timestamp('started_at')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->integer('duration_seconds')->nullable();
            $table->timestamps();
            
            $table->index('business_entity');
            $table->index('cut_off_date');
            $table->index('status');
            $table->index('created_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('sap_export_logs');
    }
};
