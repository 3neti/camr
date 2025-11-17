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
        Schema::create('meters', function (Blueprint $table) {
            $table->id();
            $table->foreignId('site_id')->constrained()->cascadeOnDelete();
            $table->foreignId('gateway_id')->constrained()->cascadeOnDelete();
            $table->foreignId('location_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('building_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('configuration_file_id')->nullable()->constrained()->nullOnDelete();
            
            $table->string('site_code')->nullable();
            $table->string('name');
            $table->boolean('is_addressable')->default(true);
            $table->boolean('has_load_profile')->default(false);
            $table->string('default_name')->nullable();
            $table->string('type')->nullable();
            $table->string('brand')->nullable();
            $table->string('role')->default('Client Meter'); // Main, Sub, Check, Client
            $table->text('remarks')->nullable();
            $table->string('customer_name')->nullable();
            $table->decimal('multiplier', 10, 2)->default(1.00);
            $table->string('status')->default('Active'); // Active, Inactive
            
            $table->timestamp('last_log_update')->nullable();
            $table->string('software_version')->nullable();
            
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            
            $table->index(['site_id', 'name']);
            $table->index(['gateway_id', 'status']);
            $table->index('last_log_update');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('meters');
    }
};
