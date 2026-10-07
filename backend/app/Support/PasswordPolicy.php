<?php

namespace App\Support;

/**
 * Política única de contraseñas para todo flujo que define una credencial
 * (registro, cambio de clave, alta de usuarios por el admin).
 */
class PasswordPolicy
{
    /** Reglas base: 8+ caracteres con mayúscula, minúscula y número. */
    public static function rules(): array
    {
        return ['string', 'min:8', 'max:128', 'regex:/^(?=.*[a-z])(?=.*[A-Z])(?=.*\d)/'];
    }

    /** Mensajes en español para el campo indicado. */
    public static function messages(string $campo = 'password'): array
    {
        return [
            "{$campo}.regex" => 'La contraseña debe contener al menos una mayúscula, una minúscula y un número.',
            "{$campo}.min" => 'La contraseña debe tener al menos 8 caracteres.',
            "{$campo}.max" => 'La contraseña no puede superar los 128 caracteres.',
        ];
    }
}
