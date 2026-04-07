<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('doctors', function (Blueprint $table) {
            $table->id(); 
            $table->string('nombre');
            $table->string('cedula')->unique();
            $table->string('email')->unique();
            $table->string('password');
            
            $table->string('telefono')->nullable();
            $table->string('whatsapp')->nullable();
            $table->string('direccion', 150)->nullable();
            $table->string('descripcion', 500)->nullable();
            $table->string('horario')->nullable();
            
            // 👇 AQUÍ ESTÁN LAS COLUMNAS PARA TUS CHIPS MORADOS 👇
            $table->json('servicios')->nullable(); 
            $table->json('especialidades_extra')->nullable();

            $table->foreignId('especialidad_id')->constrained('especialidades');
            $table->string('estado')->default('activo'); 
            $table->boolean('verificado')->default(false); 
            $table->string('ip_registro')->nullable(); 
            $table->timestamp('consent_accepted_at')->nullable(); 
            $table->boolean('activo')->default(true); 
            $table->string('foto_url')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void { 
        Schema::dropIfExists('doctors'); 
    }
};