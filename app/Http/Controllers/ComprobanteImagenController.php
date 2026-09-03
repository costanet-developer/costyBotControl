<?php

namespace App\Http\Controllers;

use App\Models\AuditoriaLog;
use App\Models\Comprobante;
use App\Services\SecureLocalFile;

class ComprobanteImagenController extends Controller
{
    public function __construct(private readonly SecureLocalFile $files) {}

    protected function resolverRuta(Comprobante $comprobante): ?string
    {
        if (! $comprobante->ruta_imagen) {
            return null;
        }

        $permitidos = [
            '/home/Tlsg_n8n/whatsapp_imagenes',
            '/home/Tlsg_n8n/whatsapp_imagenes_no_detectadas_ocr',
        ];

        return $this->files->resolve($comprobante->ruta_imagen, $permitidos);
    }

    public function show(Comprobante $comprobante)
    {
        abort_unless(auth()->user()->can('comprobantes.ver'), 403);

        $ruta = $this->resolverRuta($comprobante);

        if (! $ruta) {
            abort(404, 'La imagen del comprobante no está disponible.');
        }

        $this->audit($comprobante, 'visualizar_comprobante');

        return response()->file($ruta, [
            'Cache-Control' => 'private, no-store, max-age=0',
            'X-Content-Type-Options' => 'nosniff',
        ]);
    }

    public function descargar(Comprobante $comprobante)
    {
        abort_unless(auth()->user()->can('comprobantes.descargar'), 403);

        $ruta = $this->resolverRuta($comprobante);

        if (! $ruta) {
            abort(404);
        }

        $this->audit($comprobante, 'descargar_comprobante');

        return response()->download(
            $ruta,
            basename($comprobante->nombre_archivo ?? "comprobante_{$comprobante->id}.jpg"),
            ['X-Content-Type-Options' => 'nosniff']
        );
    }

    private function audit(Comprobante $comprobante, string $action): void
    {
        AuditoriaLog::create([
            'usuario_id' => auth()->id(),
            'accion' => $action,
            'modulo' => 'Comprobantes',
            'entidad' => 'Comprobante',
            'entidad_id' => $comprobante->id,
            'direccion_ip' => request()->ip(),
            'user_agent' => request()->userAgent(),
            'resultado' => 'exitoso',
            'descripcion' => 'Acceso controlado a la evidencia de un comprobante.',
        ]);
    }
}
