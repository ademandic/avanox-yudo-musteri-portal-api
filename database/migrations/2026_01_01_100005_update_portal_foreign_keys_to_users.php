<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

/**
 * portal_users tablosuna bağlı foreign key'leri users tablosuna günceller
 */
return new class extends Migration
{
    public function up(): void
    {
        // 1. portal_requests tablosu
        DB::statement("
            IF EXISTS (SELECT * FROM sys.foreign_keys WHERE name = 'portal_requests_portal_user_id_foreign')
            ALTER TABLE portal_requests DROP CONSTRAINT portal_requests_portal_user_id_foreign
        ");

        Schema::table('portal_requests', function (Blueprint $table) {
            $table->foreign('portal_user_id')
                  ->references('id')
                  ->on('users')
                  ->onDelete('no action');
        });

        // 2. portal_request_state_logs tablosu
        DB::statement("
            IF EXISTS (SELECT * FROM sys.foreign_keys WHERE name = 'portal_request_state_logs_changed_by_portal_user_id_foreign')
            ALTER TABLE portal_request_state_logs DROP CONSTRAINT portal_request_state_logs_changed_by_portal_user_id_foreign
        ");

        Schema::table('portal_request_state_logs', function (Blueprint $table) {
            $table->foreign('changed_by_portal_user_id')
                  ->references('id')
                  ->on('users')
                  ->onDelete('set null');
        });

        // 3. portal_invitations tablosu
        DB::statement("
            IF EXISTS (SELECT * FROM sys.foreign_keys WHERE name = 'portal_invitations_portal_user_id_foreign')
            ALTER TABLE portal_invitations DROP CONSTRAINT portal_invitations_portal_user_id_foreign
        ");

        Schema::table('portal_invitations', function (Blueprint $table) {
            $table->foreign('portal_user_id')
                  ->references('id')
                  ->on('users')
                  ->onDelete('set null');
        });
    }

    public function down(): void
    {
        // 1. portal_requests - geri al
        Schema::table('portal_requests', function (Blueprint $table) {
            $table->dropForeign(['portal_user_id']);
        });
        Schema::table('portal_requests', function (Blueprint $table) {
            $table->foreign('portal_user_id')
                  ->references('id')
                  ->on('portal_users')
                  ->onDelete('no action');
        });

        // 2. portal_request_state_logs - geri al
        Schema::table('portal_request_state_logs', function (Blueprint $table) {
            $table->dropForeign(['changed_by_portal_user_id']);
        });
        Schema::table('portal_request_state_logs', function (Blueprint $table) {
            $table->foreign('changed_by_portal_user_id')
                  ->references('id')
                  ->on('portal_users')
                  ->onDelete('set null');
        });

        // 3. portal_invitations - geri al
        Schema::table('portal_invitations', function (Blueprint $table) {
            $table->dropForeign(['portal_user_id']);
        });
        Schema::table('portal_invitations', function (Blueprint $table) {
            $table->foreign('portal_user_id')
                  ->references('id')
                  ->on('portal_users')
                  ->onDelete('set null');
        });
    }
};
