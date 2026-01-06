<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     * ERP'den davetiye gönderilirken first_name ve last_name alanlarını ekler.
     */
    public function up(): void
    {
        Schema::table('portal_invitations', function (Blueprint $table) {
            $table->string('first_name', 100)->nullable()->after('email');
            $table->string('last_name', 100)->nullable()->after('first_name');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('portal_invitations', function (Blueprint $table) {
            $table->dropColumn(['first_name', 'last_name']);
        });
    }
};
