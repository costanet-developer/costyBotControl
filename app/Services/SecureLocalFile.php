<?php

namespace App\Services;

class SecureLocalFile
{
    /**
     * Resuelve un archivo únicamente cuando su ruta real está dentro de una raíz permitida.
     * Esto bloquea recorridos "../" y enlaces simbólicos que salgan de la carpeta autorizada.
     */
    public function resolve(?string $path, array $allowedRoots): ?string
    {
        if (! is_string($path) || trim($path) === '') {
            return null;
        }

        $realPath = realpath($path);
        if ($realPath === false || ! is_file($realPath)) {
            return null;
        }

        foreach ($allowedRoots as $root) {
            $realRoot = realpath($root);
            if ($realRoot !== false && str_starts_with($realPath, $realRoot.DIRECTORY_SEPARATOR)) {
                return $realPath;
            }
        }

        return null;
    }
}
