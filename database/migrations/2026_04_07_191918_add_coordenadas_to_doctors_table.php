<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::table('doctors', function (Blueprint $table) {
            // Agregamos latitud y longitud. 
            // (10, 8) y (11, 8) es el estándar para coordenadas GPS.
            // nullable() es crucial para que no marque error con los doctores que ya tienes registrados.
            $table->decimal('latitud', 10, 8)->nullable()->after('direccion');
            $table->decimal('longitud', 11, 8)->nullable()->after('latitud');
        });
    }

    public function down()
    {
        Schema::table('doctors', function (Blueprint $table) {
            // Esto sirve por si algún día necesitas revertir la migración
            $table->dropColumn(['latitud', 'longitud']);
        });
    }
};