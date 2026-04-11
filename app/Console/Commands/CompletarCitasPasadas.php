<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Cita;
use Carbon\Carbon;

class CompletarCitasPasadas extends Command
{
    protected $signature = 'citas:completar-pasadas';
    protected $description = 'Marca como completadas las citas aceptadas cuya hora ya pasó';

    public function handle()
    {
        $ahora = Carbon::now();

        $actualizadas = Cita::where('estado', 'aceptada')
            ->where(function($q) use ($ahora) {
                $q->where('fecha', '<', $ahora->toDateString())
                  ->orWhere(function($q2) use ($ahora) {
                      $q2->where('fecha', $ahora->toDateString())
                         ->where('hora', '<=', $ahora->format('H:i:s'));
                  });
            })
            ->update(['estado' => 'completada']);

        $this->info("Citas completadas: {$actualizadas}");
    }
}