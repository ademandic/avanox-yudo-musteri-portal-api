<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     * Users tablosuna portal kullanıcıları için gerekli alanları ekler.
     */
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            // Portal kullanıcı flag'i
            $table->boolean('is_portal_user')->default(false)->after('remember_token');

            // Firma bağlantısı (ERP companies tablosu)
            $table->bigInteger('company_id')->nullable()->after('is_portal_user');

            // Admin flag
            $table->boolean('is_company_admin')->default(false)->after('company_id');

            // Aktif/Pasif durumu
            $table->boolean('is_active')->default(true)->after('is_company_admin');

            // 2FA alanları
            $table->string('two_factor_code', 6)->nullable()->after('is_active');
            $table->dateTime('two_factor_expires_at')->nullable()->after('two_factor_code');
            $table->smallInteger('two_factor_attempts')->default(0)->after('two_factor_expires_at');
            $table->dateTime('locked_until')->nullable()->after('two_factor_attempts');

            // Session takibi
            $table->dateTime('last_login_at')->nullable()->after('locked_until');
            $table->string('last_login_ip', 45)->nullable()->after('last_login_at');
            $table->dateTime('last_activity_at')->nullable()->after('last_login_ip');
            $table->string('current_session_id', 36)->nullable()->after('last_activity_at');

            // Tercihler
            $table->string('portal_theme', 20)->default('light')->after('current_session_id');
            $table->string('portal_language', 5)->default('tr')->after('portal_theme');

            // İndeksler
            $table->index('is_portal_user');
            $table->index('company_id');
            $table->index(['is_portal_user', 'is_active']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropIndex(['is_portal_user']);
            $table->dropIndex(['company_id']);
            $table->dropIndex(['is_portal_user', 'is_active']);

            $table->dropColumn([
                'is_portal_user',
                'company_id',
                'is_company_admin',
                'is_active',
                'two_factor_code',
                'two_factor_expires_at',
                'two_factor_attempts',
                'locked_until',
                'last_login_at',
                'last_login_ip',
                'last_activity_at',
                'current_session_id',
                'portal_theme',
                'portal_language',
            ]);
        });
    }
};
