<?php

namespace App\Services;

use App\Models\AuditoriaLog;
use App\Models\CasoOperativo;
use App\Models\Comprobante;
use App\Models\SaldoFavor;
use App\Models\Sesion;
use App\Models\ValidacionIdentidad;
use Carbon\CarbonInterface;
use Illuminate\Support\Collection;

class ResumenGerencialService
{
    public function generar(CarbonInterface $inicio, CarbonInterface $fin): array
    {
        $dias = (int) $inicio->copy()->startOfDay()->diffInDays($fin->copy()->startOfDay()) + 1;
        $finAnterior = $inicio->copy()->subSecond();
        $inicioAnterior = $finAnterior->copy()->subDays($dias - 1)->startOfDay();

        $actual = $this->periodo($inicio, $fin, true);
        $anterior = $this->periodo($inicioAnterior, $finAnterior, false);
        $comparables = ['interacciones', 'clientes', 'pagos', 'monto', 'creditos', 'sin_evidencia', 'casos_detectados', 'casos_resueltos'];
        $variaciones = collect($comparables)->mapWithKeys(fn (string $clave) => [
            $clave => $this->variacion((float) $actual['metricas'][$clave], (float) $anterior['metricas'][$clave]),
        ])->all();

        return [
            'inicio' => $inicio,
            'fin' => $fin,
            'inicio_anterior' => $inicioAnterior,
            'fin_anterior' => $finAnterior,
            'dias' => $dias,
            'actual' => $actual,
            'anterior' => $anterior,
            'variaciones' => $variaciones,
            'sla' => app(SeguimientoOperativo::class)->resumen(),
        ];
    }

    private function periodo(CarbonInterface $inicio, CarbonInterface $fin, bool $detalle): array
    {
        $sesiones = Sesion::whereBetween('inicio', [$inicio, $fin])->get();
        $pagos = Sesion::with([
            'comprobantePrincipal',
            'comprobantes' => fn ($query) => $query->orderBy('fecha_hora')->orderBy('id'),
            'eventos' => fn ($query) => $query->orderBy('fecha_evento'),
        ])->pagoProcesado()->whereBetween('inicio', [$inicio, $fin])->get();
        $pagosConComprobante = $pagos->map(fn (Sesion $sesion) => [
            'sesion' => $sesion,
            'comprobante' => $this->comprobanteDelPago($sesion),
        ]);
        $pagosConComprobanteUnicos = $pagosConComprobante
            ->filter(fn (array $pago) => $pago['comprobante'] !== null)
            ->unique(fn (array $pago) => $pago['comprobante']->id)
            ->values();
        $comprobantes = $pagosConComprobanteUnicos->pluck('comprobante');
        $creditos = SaldoFavor::whereBetween('fecha_registro', [$inicio, $fin])->get();
        $casos = CasoOperativo::with(['asignadoA', 'resueltoPor'])->whereBetween('detectado_en', [$inicio, $fin])->get();
        $casosResueltos = CasoOperativo::with(['asignadoA', 'resueltoPor'])->whereBetween('resuelto_en', [$inicio, $fin])->get();
        $kyc = ValidacionIdentidad::whereBetween('actualizado_en', [$inicio, $fin])->get();

        $metricas = [
            'interacciones' => $sesiones->count(),
            'clientes' => $sesiones->map(fn (Sesion $sesion) => $sesion->cedula ?: $sesion->numero_whatsapp)->filter()->unique()->count(),
            'pagos' => $pagos->count(),
            'monto' => (float) $comprobantes->sum(fn (Comprobante $comprobante) => (float) $comprobante->monto),
            'creditos' => (float) $creditos->sum(fn (SaldoFavor $saldo) => (float) $saldo->excedente),
            'sin_evidencia' => $pagosConComprobante->whereNull('comprobante')->count(),
            'casos_detectados' => $casos->count(),
            'casos_resueltos' => $casosResueltos->count(),
            'tasa_pago' => $sesiones->count() ? round(($pagos->count() / $sesiones->count()) * 100, 1) : 0,
            'kyc' => $kyc->count(),
            'kyc_revision' => $kyc->where('derivado_revision', true)->count(),
            'correos_verificados' => $kyc->where('correo_verificado', true)->count(),
            'acciones_administrativas' => AuditoriaLog::whereBetween('fecha_hora', [$inicio, $fin])->count(),
        ];

        if (! $detalle) {
            return ['metricas' => $metricas];
        }

        return [
            'metricas' => $metricas,
            'serie' => $this->serie($inicio, $fin, $sesiones, $pagos, $pagosConComprobanteUnicos, $creditos, $casos),
            'bancos' => $comprobantes->groupBy(fn (Comprobante $comprobante) => $comprobante->banco ?: 'No identificado')
                ->map(fn (Collection $grupo, string $banco) => ['banco' => $banco, 'cantidad' => $grupo->count(), 'monto' => (float) $grupo->sum(fn ($c) => (float) $c->monto)])
                ->sortByDesc('monto')->values()->take(8),
            'tipos_caso' => $casos->groupBy('tipo')->map(fn (Collection $grupo, string $tipo) => ['tipo' => $tipo, 'cantidad' => $grupo->count()])->sortByDesc('cantidad')->values(),
            'responsables' => $this->responsables($casosResueltos),
        ];
    }

    private function serie(CarbonInterface $inicio, CarbonInterface $fin, Collection $sesiones, Collection $pagos, Collection $pagosConComprobante, Collection $creditos, Collection $casos): Collection
    {
        $porSesion = $sesiones->groupBy(fn (Sesion $sesion) => $this->claveFechaOperativa($sesion, 'inicio'));
        $porPago = $pagos->groupBy(fn (Sesion $sesion) => $this->claveFechaOperativa($sesion, 'inicio'));
        // El valor se atribuye al día en que la sesión procesó el pago. Así el
        // total diario siempre concilia con el total del periodo seleccionado.
        $porMonto = $pagosConComprobante->groupBy(fn (array $pago) => $this->claveFechaOperativa($pago['sesion'], 'inicio'));
        $porCredito = $creditos->groupBy(fn (SaldoFavor $saldo) => $this->claveFechaOperativa($saldo, 'fecha_registro'));
        $porCaso = $casos->groupBy(fn (CasoOperativo $caso) => $this->claveFechaOperativa($caso, 'detectado_en'));
        $filas = collect();

        for ($fecha = $inicio->copy()->startOfDay(); $fecha->lte($fin); $fecha = $fecha->addDay()) {
            $clave = $fecha->format('Y-m-d');
            $filas->push([
                'fecha' => $fecha->copy(),
                'interacciones' => $porSesion->get($clave, collect())->count(),
                'pagos' => $porPago->get($clave, collect())->count(),
                'monto' => (float) $porMonto->get($clave, collect())->sum(fn (array $pago) => (float) $pago['comprobante']->monto),
                'creditos' => (float) $porCredito->get($clave, collect())->sum(fn ($s) => (float) $s->excedente),
                'casos' => $porCaso->get($clave, collect())->count(),
            ]);
        }

        return $filas;
    }

    /**
     * Los timestamps del bot se almacenan como hora operativa sin zona. Usar el
     * valor original evita desplazar al día anterior los registros cercanos a
     * medianoche cuando BotDatetime los presenta en America/Guayaquil.
     */
    private function claveFechaOperativa(object $modelo, string $atributo): ?string
    {
        $valor = $modelo->getRawOriginal($atributo);

        return $valor ? substr((string) $valor, 0, 10) : null;
    }

    private function responsables(Collection $casos): Collection
    {
        return $casos->whereNotNull('resuelto_por')->groupBy('resuelto_por')->map(function (Collection $grupo) {
            $usuario = $grupo->first()->resueltoPor;
            $minutos = $grupo->filter(fn (CasoOperativo $caso) => $caso->detectado_en && $caso->resuelto_en)
                ->map(fn (CasoOperativo $caso) => $caso->detectado_en->diffInMinutes($caso->resuelto_en));

            return [
                'usuario' => $usuario,
                'resueltos' => $grupo->count(),
                'promedio_minutos' => $minutos->isEmpty() ? null : (int) round($minutos->average()),
            ];
        })->sortByDesc('resueltos')->values();
    }

    private function comprobanteDelPago(Sesion $sesion): ?Comprobante
    {
        if ($sesion->comprobantePrincipal) {
            return $sesion->comprobantePrincipal;
        }

        $referencia = $sesion->eventos->firstWhere('paso', 'reactivacion_exitosa')?->fecha_evento ?? $sesion->fin;
        $ordenados = $sesion->comprobantes->sortBy(fn (Comprobante $comprobante) => $comprobante->fecha_hora?->timestamp ?? $comprobante->id);
        if ($referencia) {
            $previos = $ordenados->filter(fn (Comprobante $comprobante) => $comprobante->fecha_hora && $comprobante->fecha_hora <= $referencia);
            if ($previos->isNotEmpty()) {
                return $previos->last();
            }
        }

        return $ordenados->last();
    }

    private function variacion(float $actual, float $anterior): ?float
    {
        if ($anterior == 0.0) {
            return $actual == 0.0 ? 0.0 : null;
        }

        return round((($actual - $anterior) / abs($anterior)) * 100, 1);
    }
}
