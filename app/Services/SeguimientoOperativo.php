<?php

namespace App\Services;

use App\Models\CasoOperativo;
use Illuminate\Support\Collection;

class SeguimientoOperativo
{
    public function resumen(): array
    {
        $limites = config('costybot.sla_casos_horas', ['alta' => 2, 'media' => 8, 'baja' => 24]);
        $casos = CasoOperativo::with(['asignadoA', 'resueltoPor'])->get();
        $abiertos = $casos->whereIn('estado', ['pendiente', 'en_revision']);
        $asignados = $casos->filter(fn (CasoOperativo $caso) => $caso->asignado_en !== null);
        $cerrados = $casos->filter(fn (CasoOperativo $caso) => $caso->resuelto_en !== null);

        $presentados = $abiertos->map(function (CasoOperativo $caso) use ($limites) {
            $horas = max((int) ($limites[$caso->prioridad] ?? 8), 1);
            $venceEn = $caso->detectado_en?->copy()->addHours($horas);

            return [
                'caso' => $caso,
                'limite_horas' => $horas,
                'vence_en' => $venceEn,
                'vencido' => $venceEn?->isPast() ?? false,
                'minutos_transcurridos' => $caso->detectado_en ? (int) round($caso->detectado_en->diffInMinutes(now())) : 0,
            ];
        })->sortBy(fn (array $item) => sprintf(
            '%d-%020d',
            $item['vencido'] ? 0 : 1,
            $item['vence_en']?->timestamp ?? PHP_INT_MAX
        ))->values();

        return [
            'limites' => $limites,
            'total' => $casos->count(),
            'abiertos' => $abiertos->count(),
            'sin_asignar' => $abiertos->whereNull('asignado_en')->count(),
            'vencidos' => $presentados->where('vencido', true)->count(),
            'promedio_toma_minutos' => $this->promedioMinutos($asignados, 'asignado_en'),
            'promedio_resolucion_minutos' => $this->promedioMinutos($cerrados, 'resuelto_en'),
            'por_prioridad' => collect($limites)->mapWithKeys(function ($horas, $prioridad) use ($presentados) {
                $grupo = $presentados->filter(fn (array $item) => $item['caso']->prioridad === $prioridad);

                return [$prioridad => [
                    'limite_horas' => $horas,
                    'abiertos' => $grupo->count(),
                    'vencidos' => $grupo->where('vencido', true)->count(),
                ]];
            })->all(),
            'criticos' => $presentados->take(10),
            'responsables' => $this->responsables($casos),
        ];
    }

    private function promedioMinutos(Collection $casos, string $campo): ?int
    {
        $valores = $casos->filter(fn (CasoOperativo $caso) => $caso->detectado_en && $caso->{$campo})
            ->map(fn (CasoOperativo $caso) => $caso->detectado_en->diffInMinutes($caso->{$campo}));

        return $valores->isEmpty() ? null : (int) round($valores->average());
    }

    private function responsables(Collection $casos): Collection
    {
        return $casos->whereNotNull('asignado_a')->groupBy('asignado_a')->map(function (Collection $grupo) {
            $usuario = $grupo->first()->asignadoA;
            $resueltos = $grupo->whereIn('estado', ['resuelto', 'descartado']);

            return [
                'usuario' => $usuario,
                'asignados' => $grupo->count(),
                'abiertos' => $grupo->whereIn('estado', ['pendiente', 'en_revision'])->count(),
                'cerrados' => $resueltos->count(),
                'promedio_resolucion_minutos' => $this->promedioMinutos($resueltos, 'resuelto_en'),
            ];
        })->sortByDesc('abiertos')->values();
    }

    public static function duracion(?int $minutos): string
    {
        if ($minutos === null) {
            return 'Sin datos';
        }

        if ($minutos < 60) {
            return $minutos.' min';
        }

        $horas = intdiv($minutos, 60);
        $resto = $minutos % 60;

        return $horas.' h'.($resto ? ' '.$resto.' min' : '');
    }
}
