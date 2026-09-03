<?php

namespace App\Console\Commands;

use App\Services\ProcesarAlertasOperativas;
use Illuminate\Console\Command;

class ProcesarAlertasOperativasCommand extends Command
{
    protected $signature = 'costy:procesar-alertas-operativas';

    protected $description = 'Genera avisos internos y escalaciones idempotentes para casos operativos';

    public function handle(ProcesarAlertasOperativas $procesador): int
    {
        $resultado = $procesador->procesar();
        $this->info("Alertas nuevas: {$resultado['nuevas']}; notificaciones: {$resultado['notificaciones']}; emails: {$resultado['emails']}; errores: {$resultado['errores']}");
        $this->table(['Tipo', 'Nuevas'], collect($resultado['por_tipo'])->map(fn ($cantidad, $tipo) => [$tipo, $cantidad])->values()->all());

        return $resultado['errores'] ? self::FAILURE : self::SUCCESS;
    }
}
