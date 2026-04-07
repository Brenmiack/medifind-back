<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('pacientes', function (Blueprint $table) {
            $table->id(); // Llave Primaria
            $table->string('nombre');
            $table->string('email')->unique();
            $table->string('password');
            $table->string('telefono')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void { Schema::dropIfExists('pacientes'); }
};  