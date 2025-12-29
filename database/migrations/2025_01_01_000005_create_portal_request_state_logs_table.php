<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('portal_request_state_logs', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('portal_request_id');
            $table->unsignedBigInteger('portal_request_state_id');

            $table->string('aciklama', 500)->nullable();

            // Kim değiştirdi?
            $table->unsignedBigInteger('changed_by_user_id')->nullable(); // ERP user değiştirdiyse
            $table->unsignedBigInteger('changed_by_portal_user_id')->nullable(); // Portal user değiştirdiyse

            $table->tinyInteger('is_active')->default(1);
            $table->dateTime('created_at')->nullable();

            // Foreign keys
            $table->foreign('portal_request_id')
                ->references('id')
                ->on('portal_requests')
                ->onDelete('cascade');
            $table->foreign('portal_request_state_id')
                ->references('id')
                ->on('portal_request_states');
            $table->foreign('changed_by_user_id')
                ->references('id')
                ->on('users');
            $table->foreign('changed_by_portal_user_id')
                ->references('id')
                ->on('portal_users');

            // Indexes
            $table->index('portal_request_id', 'IX_portal_state_logs_request');
            $table->index('created_at', 'IX_portal_state_logs_created');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('portal_request_state_logs');
    }
};
