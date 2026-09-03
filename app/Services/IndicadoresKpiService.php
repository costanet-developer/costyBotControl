<?php

namespace App\Services;

use App\Models\EncuestaCes;
use App\Models\Sesion;
use Carbon\CarbonInterface;
use Illuminate\Support\Collection;

class IndicadoresKpiService
{
    private const OPCION_REACTIVACION = 'reactivacion';

    private const OPCION_SALDO = 'saldo_pagar';

    /**
     * Calcula FCR y CES por recorrido del bot dentro del periodo indicado.
     *
     * CES se registra en eventos_interaccion con paso encuesta_ces_respondida y:
     * {"opcion":"reactivacion|saldo_pagar", "puntuacion":1..7}.
     */
    public function resumen(CarbonInterface $desde, CarbonInterface $hasta): array
    {
        $sesiones = Sesion::query()
            ->whereBetween('inicio', [$desde, $hasta])
            ->withExists([
                'comprobantes as comprobante_exitoso_existe' => fn ($query) => $query->where('estado', 'reactivacion_exitosa'),
                'comprobantePrincipal as comprobante_principal_exitoso' => fn ($query) => $query->where('estado', 'reactivacion_exitosa'),
            ])
            ->with(['eventos' => fn ($query) => $query
                ->select('id', 'sesion_id', 'paso', 'fecha_evento', 'datos_adicionales')
                ->orderBy('fecha_evento')
            ->orderBy('id')])
            ->get();

        $encuestas = EncuestaCes::query()
            ->whereBetween('creado_en', [$desde, $hasta])
            ->orderBy('creado_en')
            ->get();

        return [
            self::OPCION_REACTIVACION => $this->calcularOpcion($sesiones, $encuestas, self::OPCION_REACTIVACION),
            self::OPCION_SALDO => $this->calcularOpcion($sesiones, $encuestas, self::OPCION_SALDO),
        ];
    }

    private function calcularOpcion(Collection $sesiones, Collection $encuestas, string $opcion): array
    {
        $elegibles = $sesiones->filter(fn (Sesion $sesion) => $this->perteneceAOpcion($sesion, $opcion));
        $resueltas = $elegibles->filter(fn (Sesion $sesion) => $this->resueltaEnPrimerContacto($sesion, $opcion))->count();
        $tipoGestion = $opcion === self::OPCION_REACTIVACION ? 'reactivacion' : 'consulta_valores';
        $encuestasOpcion = $encuestas->where('tipo_gestion', $tipoGestion)->values();
        $enviadas = $encuestasOpcion->filter(fn (EncuestaCes $encuesta) => in_array($encuesta->estado, [
            'enviada',
            'respondida',
            'vencida',
        ], true));
        $respuestasCes = $encuestasOpcion
            ->pluck('puntuacion')
            ->filter(fn ($valor) => is_numeric($valor) && (int) $valor >= 1 && (int) $valor <= 7)
            ->map(fn ($valor) => (float) $valor)
            ->values();
        $pendientes = $encuestasOpcion->whereIn('estado', ['pendiente', 'enviando'])->count();

        return [
            'fcr' => $elegibles->isEmpty() ? null : round(($resueltas / $elegibles->count()) * 100, 1),
            'fcr_resueltas' => $resueltas,
            'fcr_total' => $elegibles->count(),
            'ces' => $respuestasCes->isEmpty() ? null : round((float) $respuestasCes->average(), 1),
            'ces_respuestas' => $respuestasCes->count(),
            'ces_programadas' => $encuestasOpcion->count(),
            'ces_enviadas' => $enviadas->count(),
            'ces_pendientes' => $pendientes,
            'ces_tasa_respuesta' => $enviadas->isEmpty()
                ? null
                : round(($respuestasCes->count() / $enviadas->count()) * 100, 1),
            'ces_favorable' => $respuestasCes->isEmpty()
                ? null
                : round(($respuestasCes->filter(fn (float $valor) => $valor >= 5)->count() / $respuestasCes->count()) * 100, 1),
        ];
    }

    private function perteneceAOpcion(Sesion $sesion, string $opcion): bool
    {
        $pasoSeleccion = $opcion === self::OPCION_REACTIVACION
            ? 'menu_reactivar_seleccionado'
            : 'menu_consultar_seleccionado';

        if ($sesion->eventos->contains('paso', $pasoSeleccion)) {
            return true;
        }

        // Compatibilidad con sesiones históricas anteriores al registro de la selección del menú.
        return match ($opcion) {
            self::OPCION_REACTIVACION => in_array($sesion->resultado, [
                'reactivado',
                'cerrado_sin_comprobante',
                'comprobante_duplicado',
            ], true) || $sesion->eventos->contains('paso', 'reactivacion_exitosa'),
            self::OPCION_SALDO => in_array($sesion->resultado, [
                'cuenta_al_dia',
                'consulta_exitosa',
                'transferido_pagos',
            ], true),
        };
    }

    private function resueltaEnPrimerContacto(Sesion $sesion, string $opcion): bool
    {
        if ($opcion === self::OPCION_REACTIVACION) {
            return $sesion->resultado === 'reactivado'
                || $sesion->eventos->contains('paso', 'reactivacion_exitosa')
                || (bool) $sesion->comprobante_exitoso_existe
                || (bool) $sesion->comprobante_principal_exitoso;
        }

        return $sesion->resultado === 'cuenta_al_dia'
            || $sesion->eventos->contains(fn ($evento) => in_array($evento->paso, [
                'saldo_consultado_exitoso',
                'valores_a_pagar_mostrados',
            ], true));
    }

}
