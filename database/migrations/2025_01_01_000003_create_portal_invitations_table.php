<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('portal_invitations', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('contact_id');
            $table->unsignedBigInteger('company_id');

            // Davetiye bilgileri
            $table->string('token', 100)->unique();
            $table->string('email', 100);

            // Gönderen (ERP user - satışçı)
            $table->unsignedBigInteger('invited_by_user_id');

            // Tarihler
            $table->dateTime('sent_at')->nullable();
            $table->dateTime('expires_at');
            $table->dateTime('accepted_at')->nullable();

            // Oluşturulan portal user (kabul edildiyse)
            $table->unsignedBigInteger('portal_user_id')->nullable();

            // Durum: 1=Bekliyor, 2=Kabul Edildi, 3=Süresi Doldu, 4=İptal
            $table->smallInteger('status')->default(1);

            $table->tinyInteger('is_active')->default(1);
            $table->dateTime('created_at')->nullable();
            $table->dateTime('updated_at')->nullable();

            // Foreign keys
            $table->foreign('contact_id')->references('id')->on('contacts');
            $table->foreign('company_id')->references('id')->on('companies');
            $table->foreign('invited_by_user_id')->references('id')->on('users');
            $table->foreign('portal_user_id')->references('id')->on('portal_users');

            // Indexes
            $table->index('email', 'IX_portal_invitations_email');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('portal_invitations');
    }
};
