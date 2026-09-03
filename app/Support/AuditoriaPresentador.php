<?php

namespace App\Support;

class AuditoriaPresentador
{
    private const SENSITIVE_KEY = '/password|contrasena|contraseña|authorization|access.?token|refresh.?token|secret|otp.?codigo|codigo.?otp|codigo.?enviado|codigo.?ingresado|base64|binary/i';

    public static function redactar(mixed $valor, ?string $clave = null): mixed
    {
        if ($clave !== null && preg_match(self::SENSITIVE_KEY, $clave)) {
            return '[PROTEGIDO]';
        }

        if (is_array($valor)) {
            $seguro = [];
            foreach ($valor as $k => $v) {
                $seguro[$k] = self::redactar($v, (string) $k);
            }

            return $seguro;
        }

        if (is_string($valor) && strlen($valor) > 500) {
            return mb_substr($valor, 0, 200).'… [contenido extenso oculto]';
        }

        return $valor;
    }

    public static function textoSeguro(mixed $valor): string
    {
        if ($valor === null || $valor === []) {
            return 'Sin datos';
        }

        return json_encode(self::redactar($valor), JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?: 'Sin datos';
    }

    public static function etiqueta(string $valor): string
    {
        return ucfirst(str_replace(['_', '-'], ' ', $valor));
    }
}
