<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
   public function up(): void
{
    Schema::table('doctors', function (Blueprint $table) {
        // Añadimos las columnas que nos faltan como JSON o String
        if (!Schema::hasColumn('doctors', 'especialidades_extra')) {
            $table->json('especialidades_extra')->nullable()->after('especialidad_id');
        }
        if (!Schema::hasColumn('doctors', 'whatsapp')) {
            $table->string('whatsapp', 15)->nullable()->after('telefono');
        }
    });
}

public function down(): void
{
    Schema::table('doctors', function (Blueprint $table) {
        $table->dropColumn(['especialidades_extra', 'whatsapp']);
    });
}
};
