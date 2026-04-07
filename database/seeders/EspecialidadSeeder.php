<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Especialidad;

class EspecialidadSeeder extends Seeder
{
    public function run(): void
    {
        $especialidades = [
            'Medicina General', 'Medicina Interna', 'Cirugía General',
            'Ginecología y Obstetricia', 'Fisioterapia', 'Naturopatía',
            'Neumología', 'Odontología', 'Otorrinolaringología',
            'Pediatría', 'Radiología', 'Traumatología',
            'Medicina Crítica y Terapia Intensiva', 'Acupuntura',
            'Alergología', 'Anatomía Patológica', 'Anestesiología',
            'Angiología y Cirugía Vascular', 'Audiología, Otoneurología y Foniatría',
            'Cardiología', 'Cardiología Pediátrica', 'Cirugía Bariátrica',
            'Cirugía Cardiovascular', 'Cirugía Cardiovascular y del Tórax',
            'Cirugía de la Mano', 'Cirugía Estética y Cosmética'
        ];

        foreach ($especialidades as $nombre) {
            Especialidad::firstOrCreate(['nombre' => $nombre]);
        }
    }
}