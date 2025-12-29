<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('portal_users', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('contact_id');
            $table->unsignedBigInteger('company_id');
            $table->string('email', 100)->unique();
            $table->string('password', 255);
            $table->string('remember_token', 255)->nullable();
            $table->dateTime('last_login_at')->nullable();
            $table->string('last_login_ip', 45)->nullable();
            $table->tinyInteger('is_active')->default(1);
            $table->dateTime('created_at')->nullable();
            $table->dateTime('updated_at')->nullable();

            // Foreign keys - mevcut ERP tablolarına referans
            $table->foreign('contact_id')->references('id')->on('contacts');
            $table->foreign('company_id')->references('id')->on('companies');

            // Indexes
            $table->index('company_id', 'IX_portal_users_company');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('portal_users');
    }
};
