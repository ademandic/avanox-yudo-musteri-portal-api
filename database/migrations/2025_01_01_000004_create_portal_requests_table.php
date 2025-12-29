<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('portal_requests', function (Blueprint $table) {
            $table->id();

            // Portal talep numarası
            $table->string('request_no', 20)->unique();

            // İlişkiler
            $table->unsignedBigInteger('portal_user_id');
            $table->unsignedBigInteger('company_id');
            $table->unsignedBigInteger('job_id');

            // Talep bilgileri
            $table->smallInteger('request_type'); // 1: Tasarım Talebi, 2: Teklif Talebi

            // Müşteri referansları
            $table->string('customer_reference_code', 100)->nullable();
            $table->string('customer_mold_code', 100)->nullable();

            // Müşteri beklentileri / notları
            $table->text('customer_notes')->nullable();
            $table->date('expected_delivery_date')->nullable();
            $table->smallInteger('priority')->default(2); // 1: Düşük, 2: Normal, 3: Yüksek, 4: Acil

            // Portal'a özel ek alanlar (ERP'de karşılığı yok)
            $table->decimal('kalip_z', 10, 2)->nullable();

            // Portal durumu
            $table->unsignedBigInteger('current_state_id')->default(1);

            // Meta
            $table->tinyInteger('is_active')->default(1);
            $table->dateTime('created_at')->nullable();
            $table->dateTime('updated_at')->nullable();

            // Foreign keys
            $table->foreign('portal_user_id')->references('id')->on('portal_users');
            $table->foreign('company_id')->references('id')->on('companies');
            $table->foreign('job_id')->references('id')->on('jobs');
            $table->foreign('current_state_id')->references('id')->on('portal_request_states');

            // Indexes
            $table->index('company_id', 'IX_portal_requests_company');
            $table->index('job_id', 'IX_portal_requests_job');
            $table->index('current_state_id', 'IX_portal_requests_state');
            $table->index('created_at', 'IX_portal_requests_created');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('portal_requests');
    }
};
