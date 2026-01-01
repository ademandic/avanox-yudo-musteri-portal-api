<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     * Portal Admin'in davetiye göndermesi için gerekli alanları ekler.
     */
    public function up(): void
    {
        Schema::table('portal_invitations', function (Blueprint $table) {
            // Davet eden portal kullanıcısı (admin)
            $table->bigInteger('invited_by_portal_user_id')->nullable()->after('invited_by_user_id');

            // Rol bilgisi
            $table->string('role_name', 50)->default('Portal User')->after('invited_by_portal_user_id');

            // IP takibi
            $table->string('invited_from_ip', 45)->nullable()->after('role_name');
            $table->string('accepted_from_ip', 45)->nullable()->after('invited_from_ip');

            // İndeks
            $table->index('invited_by_portal_user_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('portal_invitations', function (Blueprint $table) {
            $table->dropIndex(['invited_by_portal_user_id']);

            $table->dropColumn([
                'invited_by_portal_user_id',
                'role_name',
                'invited_from_ip',
                'accepted_from_ip',
            ]);
        });
    }
};
