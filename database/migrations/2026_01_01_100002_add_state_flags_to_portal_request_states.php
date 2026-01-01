<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     * Talep durumlarına kontrol flag'leri ekler.
     */
    public function up(): void
    {
        Schema::table('portal_request_states', function (Blueprint $table) {
            // Final durum mu? (Tamamlandı, İptal edildi)
            $table->boolean('is_final')->default(false)->after('color');

            // Müşteri iptal edebilir mi?
            $table->boolean('customer_can_cancel')->default(true)->after('is_final');
        });

        // Mevcut durumları güncelle
        // state_id 3 (Çalışılıyor): customer_can_cancel = false
        // state_id 5,6 (Tamamlandı, İptal): is_final = true, customer_can_cancel = false
        \DB::table('portal_request_states')
            ->where('id', 3)
            ->update(['customer_can_cancel' => false]);

        \DB::table('portal_request_states')
            ->whereIn('id', [5, 6])
            ->update(['is_final' => true, 'customer_can_cancel' => false]);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('portal_request_states', function (Blueprint $table) {
            $table->dropColumn(['is_final', 'customer_can_cancel']);
        });
    }
};
