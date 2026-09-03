<?php

namespace App\Http\Controllers;

use App\Models\AuditoriaLog;
use App\Models\DocumentoIdentidad;
use App\Services\SecureLocalFile;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class DocumentoIdentidadImagenController extends Controller
{
    private const ALLOWED_ROOTS = [
        '/home/Tlsg_n8n/documentos_identidad',
    ];

    public function __construct(private readonly SecureLocalFile $files) {}

    public function show(DocumentoIdentidad $documento): BinaryFileResponse
    {
        abort_unless(auth()->user()->can('documentos_identidad.ver'), 403);

        $path = $this->resolve($documento);
        $this->audit($documento, 'visualizar_documento_identidad');

        return response()->file($path, $this->privateHeaders());
    }

    public function descargar(DocumentoIdentidad $documento): BinaryFileResponse
    {
        abort_unless(auth()->user()->can('documentos_identidad.descargar'), 403);

        $path = $this->resolve($documento);
        $this->audit($documento, 'descargar_documento_identidad');

        $name = basename($documento->nombre_archivo ?: "documento_identidad_{$documento->id}.jpg");

        return response()->download($path, $name, $this->privateHeaders());
    }

    private function resolve(DocumentoIdentidad $documento): string
    {
        $path = $this->files->resolve($documento->ruta_imagen, self::ALLOWED_ROOTS);
        abort_if($path === null, 404, 'La imagen del documento no está disponible.');

        return $path;
    }

    private function audit(DocumentoIdentidad $documento, string $action): void
    {
        AuditoriaLog::create([
            'usuario_id' => auth()->id(),
            'accion' => $action,
            'modulo' => 'Identidad',
            'entidad' => 'DocumentoIdentidad',
            'entidad_id' => $documento->id,
            'direccion_ip' => request()->ip(),
            'user_agent' => request()->userAgent(),
            'resultado' => 'exitoso',
            'descripcion' => "Acceso controlado al {$documento->lado} de un documento de identidad.",
        ]);
    }

    private function privateHeaders(): array
    {
        return [
            'Cache-Control' => 'private, no-store, max-age=0',
            'Pragma' => 'no-cache',
            'X-Content-Type-Options' => 'nosniff',
        ];
    }
}
