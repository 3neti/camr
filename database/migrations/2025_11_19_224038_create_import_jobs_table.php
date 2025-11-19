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
        Schema::create('import_jobs', function (Blueprint $table) {
            $table->id();
            $table->string('type'); // 'sql_dump' or 'csv_import'
            $table->string('filename');
            $table->integer('total_records')->default(0);
            $table->integer('processed_records')->default(0);
            $table->string('status')->default('pending'); // 'pending', 'processing', 'completed', 'failed', 'cancelled'
            $table->json('options')->nullable(); // Import options
            $table->json('result')->nullable(); // Stats after completion
            $table->text('error')->nullable();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->timestamp('started_at')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->timestamps();
            
            $table->index(['user_id', 'status']);
            $table->index('created_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('import_jobs');
    }
};
