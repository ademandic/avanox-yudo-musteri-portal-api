<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * portal_requests tablosunu ERP entegrasyonu için günceller
 * - technical_data_id FK ekler
 * - internal_notes ekler
 * - kalip_z kaldırır
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('portal_requests', function (Blueprint $table) {
            // technical_data_id FK ekle (job_id'den sonra)
            $table->bigInteger('technical_data_id')->nullable()->after('job_id');

            // internal_notes ekle (müşteri görmez, customer_notes'tan sonra)
            $table->text('internal_notes')->nullable()->after('customer_notes');
        });

        // Foreign key ayrı statement'ta (SQL Server uyumluluğu için)
        Schema::table('portal_requests', function (Blueprint $table) {
            $table->foreign('technical_data_id')
                  ->references('id')
                  ->on('technical_datas')
                  ->onDelete('set null');
        });

        // kalip_z sütununu kaldır
        Schema::table('portal_requests', function (Blueprint $table) {
            $table->dropColumn('kalip_z');
        });
    }

    public function down(): void
    {
        // kalip_z'yi geri ekle
        Schema::table('portal_requests', function (Blueprint $table) {
            $table->decimal('kalip_z', 10, 2)->nullable()->after('priority');
        });

        // FK'yı kaldır
        Schema::table('portal_requests', function (Blueprint $table) {
            $table->dropForeign(['technical_data_id']);
        });

        // Sütunları kaldır
        Schema::table('portal_requests', function (Blueprint $table) {
            $table->dropColumn(['technical_data_id', 'internal_notes']);
        });
    }
};
