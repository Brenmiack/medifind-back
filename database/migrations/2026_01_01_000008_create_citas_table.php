<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
{
    Schema::create('citas', function (Blueprint $table) {
        $table->id();
        
        // El dueño de la cita (El doctor)
        $table->foreignId('doctor_id')->constrained('doctors')->onDelete('cascade');
        
        // Para el futuro: El ID del paciente que venga de la App Móvil (Puede estar vacío por ahora)
        $table->unsignedBigInteger('paciente_id')->nullable(); 
        
        // Los datos del modal de tu HTML (Para las citas manuales)
        $table->string('paciente_nombre'); 
        $table->string('paciente_tel', 15)->nullable();
        $table->date('fecha');
        $table->time('hora');
        $table->text('notas')->nullable();
        
        // El estado de la cita (pendiente por defecto, como en tu HTML)
        $table->string('estado')->default('pendiente'); 
        
        $table->timestamps();
    });
}

    public function down(): void { Schema::dropIfExists('citas'); }
};