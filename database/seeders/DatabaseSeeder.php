<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Schema;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        // 1. Sembrar las 26 Especialidades
        $especialidades = [
            'Medicina General', 'Medicina Interna', 'Cirugía General', 'Ginecología y Obstetricia',
            'Fisioterapia', 'Naturopatía', 'Neumología', 'Odontología', 'Otorrinolaringología',
            'Pediatría', 'Radiología', 'Traumatología', 'Medicina Crítica y Terapia Intensiva',
            'Acupuntura', 'Alergología', 'Anatomía Patológica', 'Anestesiología',
            'Angiología y Cirugía Vascular', 'Audiología, Otoneurología y Foniatría',
            'Cardiología', 'Cardiología Pediátrica', 'Cirugía Bariátrica', 'Cirugía Cardiovascular',
            'Cirugía Cardiovascular y del Tórax', 'Cirugía de la Mano', 'Cirugía Estética y Cosmética'
        ];

        foreach ($especialidades as $nombre) {
            DB::table('especialidades')->updateOrInsert(
                ['nombre' => $nombre],
                ['created_at' => now(), 'updated_at' => now()]
            );
        }

        // 2. Sembrar Doctores de prueba (IMPORTANTE para testear el login)
        // Usamos updateOrInsert para que no choque si ya existen
        DB::table('doctors')->updateOrInsert(
            ['email' => 'juan@medifind.com'],
            [
                'nombre' => 'Dr. Juan Pérez',
                'cedula' => '12345678',
                'password' => Hash::make('12345678'), // Recuerda: mínimo 8 caracteres
                'telefono' => '555-1234',
                'direccion' => 'Consultorio 101, Hospital Central',
                'especialidad_id' => 1, 
                'created_at' => now(),
                'updated_at' => now(),
            ]
        );

        DB::table('doctors')->updateOrInsert(
            ['email' => 'ana@medifind.com'],
            [
                'nombre' => 'Dra. Ana Gómez',
                'cedula' => '87654321',
                'password' => Hash::make('12345678'),
                'telefono' => '555-9876',
                'direccion' => 'Clínica del Sol, Piso 2',
                'especialidad_id' => 2,
                'created_at' => now(),
                'updated_at' => now(),
            ]
        );

        // 3. Unir en tabla pivote (Si es que todavía usas esa tabla)
        if (Schema::hasTable('doctor_especialidad')) {
            DB::table('doctor_especialidad')->updateOrInsert(
                ['doctor_id' => 1, 'especialidad_id' => 1],
                ['created_at' => now(), 'updated_at' => now()]
            );
            DB::table('doctor_especialidad')->updateOrInsert(
                ['doctor_id' => 2, 'especialidad_id' => 2],
                ['created_at' => now(), 'updated_at' => now()]
            );
        }
    } // <-- Aquí termina la función run
} // <-- Aquí termina la clase