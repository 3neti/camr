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
        Schema::create('gateways', function (Blueprint $table) {
            $table->id();
            $table->foreignId('site_id')->constrained()->cascadeOnDelete();
            $table->foreignId('location_id')->nullable()->constrained()->nullOnDelete();
            $table->string('site_code')->nullable();
            $table->string('serial_number')->unique();
            $table->string('mac_address')->unique();
            $table->string('ip_address')->unique();
            $table->string('connection_type')->default('LAN'); // LAN, 3G, 4G, 5G
            $table->string('ip_netmask')->nullable();
            $table->string('ip_gateway')->nullable();
            $table->string('server_ip')->nullable();
            $table->text('description')->nullable();
            
            // Configuration flags
            $table->boolean('update_csv')->default(false);
            $table->boolean('update_site_code')->default(false);
            $table->boolean('ssh_enabled')->default(false);
            $table->boolean('force_load_profile')->default(false);
            
            // Network infrastructure
            $table->string('idf_number')->nullable();
            $table->string('switch_name')->nullable();
            $table->string('idf_port')->nullable();
            
            // Status tracking
            $table->timestamp('last_log_update')->nullable();
            $table->string('software_version')->nullable();
            
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            
            $table->index(['site_id', 'last_log_update']);
            $table->index('mac_address');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('gateways');
    }
};
