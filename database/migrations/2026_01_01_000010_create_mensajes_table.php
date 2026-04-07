<?php
use Illuminate\Database\Migrations\Migration; // <-- ESTA ES LA QUE FALTA
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
return new class extends Migration {
    public function up(): void
    {
        Schema::create('mensajes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('conversacion_id')->constrained('conversaciones')->onDelete('cascade');
            $table->enum('emisor', ['doctor', 'paciente']);
            $table->text('contenido');
            $table->timestamps();
        });
    }

    public function down(): void { Schema::dropIfExists('mensajes'); }
};