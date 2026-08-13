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
        if (!Schema::hasTable('activity_logs')) {
            Schema::create('activity_logs', function (Blueprint $table) {
                $table->id();
                $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
                $table->string('action'); // e.g., 'login', 'logout', 'create_user', 'edit_permission'
                $table->string('subject'); // e.g., 'User', 'Permission'
                $table->unsignedBigInteger('subject_id')->nullable();
                $table->text('description')->nullable();
                $table->string('ip_address')->nullable();
                $table->text('user_agent')->nullable();
                $table->json('changes')->nullable(); // Pour tracker les modifications
                $table->string('status')->default('success'); // success, warning, error
                $table->timestamps();

                $table->index('user_id');
                $table->index('action');
                $table->index('subject');
                $table->index('created_at');
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('activity_logs');
    }
};
