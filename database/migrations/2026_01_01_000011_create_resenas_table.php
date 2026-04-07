<?php

use Illuminate\Database\Migrations\Migration; // <-- ESTA ES LA QUE FALTA
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('resenas', function (Blueprint $table) {
            $table->id();
            $table->foreignId('doctor_id')->constrained('doctors')->onDelete('cascade');
            $table->string('paciente_nombre');
            $table->tinyInteger('estrellas'); // 1-5
            $table->text('comentario');
            $table->text('respuesta')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void { Schema::dropIfExists('resenas'); }
};
