<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('portal_request_states', function (Blueprint $table) {
            $table->id();
            $table->string('name', 50);
            $table->string('english_name', 50)->nullable();
            $table->string('color_class', 50)->nullable();
            $table->smallInteger('sort_order')->default(0);
            $table->string('aciklama', 255)->nullable();
            $table->tinyInteger('is_active')->default(1);
        });

        // Varsayılan durumları ekle
        DB::table('portal_request_states')->insert([
            ['name' => 'Talep Alındı', 'english_name' => 'Request Received', 'color_class' => 'blue', 'sort_order' => 1, 'is_active' => 1],
            ['name' => 'İnceleniyor', 'english_name' => 'Under Review', 'color_class' => 'yellow', 'sort_order' => 2, 'is_active' => 1],
            ['name' => 'Çalışılıyor', 'english_name' => 'In Progress', 'color_class' => 'orange', 'sort_order' => 3, 'is_active' => 1],
            ['name' => 'Revizyon Bekliyor', 'english_name' => 'Pending Revision', 'color_class' => 'purple', 'sort_order' => 4, 'is_active' => 1],
            ['name' => 'Tamamlandı', 'english_name' => 'Completed', 'color_class' => 'green', 'sort_order' => 5, 'is_active' => 1],
            ['name' => 'İptal Edildi', 'english_name' => 'Cancelled', 'color_class' => 'red', 'sort_order' => 6, 'is_active' => 1],
        ]);
    }

    public function down(): void
    {
        Schema::dropIfExists('portal_request_states');
    }
};
