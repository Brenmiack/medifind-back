<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('horarios', function (Blueprint $table) {
    $table->id();
    $table->foreignId('doctor_id')->constrained('doctors')->onDelete('cascade');
    $table->enum('dia', ['Lunes', 'Martes', 'Miercoles', 'Jueves', 'Viernes', 'Sabado', 'Domingo']);
    $table->time('hora_inicio');
    $table->time('hora_fin');
    $table->integer('duracion_cita')->default(30); // minutos
    $table->timestamps();
});
    }

    public function down(): void
    {
        Schema::dropIfExists('horarios');
    }
};