<?php

namespace App\Pacientes;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

/**
 * Spec 054 (CA3, D5): pacientes que podrían ser la misma persona que un alta nueva.
 *
 * Coinciden el nombre y el apellido (sin distinguir mayúsculas, tildes ni espacios sobrantes) y,
 * además, los dígitos del teléfono o la fecha de nacimiento. Cuenta toda ficha de `pacientes`, activa
 * o no y sea cual sea el rol de su cuenta (/analyze A5). Filtra en SQL por la primera palabra del
 * apellido con binding (la intercalación de la columna ya ignora mayúsculas y tildes) y compara el
 * resto en PHP, sin `whereRaw` (P4).
 */
class PosiblesDuplicados
{
    /** Coincidencias que se devuelven con su id; del resto solo se cuenta el total. */
    public const MAXIMO = 3;

    /** @return array{ids: list<string>, total: int} */
    public static function buscar(string $nombre, string $apellido, ?string $telefono, ?string $fechaNacimiento): array
    {
        $digitos = self::digitos($telefono);
        $fecha = blank($fechaNacimiento) ? null : (string) $fechaNacimiento;
        $primeraPalabra = Str::before(Str::squish($apellido), ' ');

        if (($digitos === '' && $fecha === null) || $primeraPalabra === '') {
            return ['ids' => [], 'total' => 0];
        }

        $nombreBuscado = self::clave($nombre);
        $apellidoBuscado = self::clave($apellido);

        $coincidencias = DB::table('pacientes')
            ->join('usuarios', 'pacientes.usuario_id', '=', 'usuarios.id')
            ->where('usuarios.apellido', 'like', self::escaparLike($primeraPalabra).'%')
            ->orderBy('usuarios.nombre')
            ->orderBy('usuarios.apellido')
            ->orderBy('pacientes.id')
            ->get(['pacientes.id', 'pacientes.fecha_nacimiento', 'usuarios.nombre', 'usuarios.apellido', 'usuarios.telefono'])
            ->filter(function (object $fila) use ($nombreBuscado, $apellidoBuscado, $digitos, $fecha): bool {
                if (self::clave($fila->nombre) !== $nombreBuscado || self::clave($fila->apellido) !== $apellidoBuscado) {
                    return false;
                }

                $mismoTelefono = $digitos !== '' && self::digitos($fila->telefono) === $digitos;
                $mismaFecha = $fecha !== null && $fila->fecha_nacimiento !== null
                    && substr((string) $fila->fecha_nacimiento, 0, 10) === $fecha;

                return $mismoTelefono || $mismaFecha;
            })
            ->pluck('id');

        return [
            'ids' => $coincidencias->take(self::MAXIMO)->values()->all(),
            'total' => $coincidencias->count(),
        ];
    }

    private static function clave(?string $texto): string
    {
        return Str::lower(Str::ascii(Str::squish((string) $texto)));
    }

    private static function digitos(?string $telefono): string
    {
        return preg_replace('/\D+/', '', (string) $telefono);
    }

    private static function escaparLike(string $texto): string
    {
        return str_replace(['\\', '%', '_'], ['\\\\', '\\%', '\\_'], $texto);
    }
}
