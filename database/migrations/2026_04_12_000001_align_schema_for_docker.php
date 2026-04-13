<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('pacientes', function (Blueprint $table) {
            if (!Schema::hasColumn('pacientes', 'paterno')) {
                $table->string('paterno', 150)->nullable()->after('nombre');
            }
            if (!Schema::hasColumn('pacientes', 'materno')) {
                $table->string('materno', 150)->nullable()->after('paterno');
            }
            if (!Schema::hasColumn('pacientes', 'direccion')) {
                $table->string('direccion')->nullable()->after('telefono');
            }
            if (!Schema::hasColumn('pacientes', 'foto_url')) {
                $table->string('foto_url')->nullable()->after('direccion');
            }
            if (!Schema::hasColumn('pacientes', 'expo_push_token')) {
                $table->string('expo_push_token')->nullable()->after('foto_url');
            }
        });

        Schema::table('conversaciones', function (Blueprint $table) {
            if (!Schema::hasColumn('conversaciones', 'paciente_id')) {
                $table->foreignId('paciente_id')
                    ->nullable()
                    ->after('doctor_id')
                    ->constrained('pacientes')
                    ->nullOnDelete();
            }
        });

        Schema::table('mensajes', function (Blueprint $table) {
            if (!Schema::hasColumn('mensajes', 'leido')) {
                $table->boolean('leido')->default(false)->after('contenido');
            }
        });

        Schema::table('citas', function (Blueprint $table) {
            if (!Schema::hasColumn('citas', 'tiene_resena')) {
                $table->boolean('tiene_resena')->default(false)->after('estado');
            }
        });
    }

    public function down(): void
    {
        Schema::table('citas', function (Blueprint $table) {
            if (Schema::hasColumn('citas', 'tiene_resena')) {
                $table->dropColumn('tiene_resena');
            }
        });

        Schema::table('mensajes', function (Blueprint $table) {
            if (Schema::hasColumn('mensajes', 'leido')) {
                $table->dropColumn('leido');
            }
        });

        Schema::table('conversaciones', function (Blueprint $table) {
            if (Schema::hasColumn('conversaciones', 'paciente_id')) {
                $table->dropConstrainedForeignId('paciente_id');
            }
        });

        Schema::table('pacientes', function (Blueprint $table) {
            foreach (['expo_push_token', 'foto_url', 'direccion', 'materno', 'paterno'] as $column) {
                if (Schema::hasColumn('pacientes', $column)) {
                    $table->dropColumn($column);
                }
            }
        });
    }
};
