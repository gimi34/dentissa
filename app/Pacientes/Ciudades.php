<?php

namespace App\Pacientes;

use Illuminate\Support\Str;

/**
 * Spec 051: la lista cerrada de ciudades del paciente, única fuente de los formularios, de la
 * validación y del filtro del directorio.
 *
 * Nombres oficiales del catálogo de municipios de INEGI (Marco Geoestadístico; claves 16 y 12),
 * en orden alfabético sin contar las tildes.
 */
final class Ciudades
{
    /** Los 113 municipios de Michoacán. */
    public const MICHOACAN = [
        'Acuitzio',
        'Aguililla',
        'Álvaro Obregón',
        'Angamacutiro',
        'Angangueo',
        'Apatzingán',
        'Aporo',
        'Aquila',
        'Ario',
        'Arteaga',
        'Briseñas',
        'Buenavista',
        'Carácuaro',
        'Charapan',
        'Charo',
        'Chavinda',
        'Cherán',
        'Chilchota',
        'Chinicuila',
        'Chucándiro',
        'Churintzio',
        'Churumuco',
        'Coahuayana',
        'Coalcomán de Vázquez Pallares',
        'Coeneo',
        'Cojumatlán de Régules',
        'Contepec',
        'Copándaro',
        'Cotija',
        'Cuitzeo',
        'Ecuandureo',
        'Epitacio Huerta',
        'Erongarícuaro',
        'Gabriel Zamora',
        'Hidalgo',
        'Huandacareo',
        'Huaniqueo',
        'Huetamo',
        'Huiramba',
        'Indaparapeo',
        'Irimbo',
        'Ixtlán',
        'Jacona',
        'Jiménez',
        'Jiquilpan',
        'José Sixto Verduzco',
        'Juárez',
        'Jungapeo',
        'La Huacana',
        'La Piedad',
        'Lagunillas',
        'Lázaro Cárdenas',
        'Los Reyes',
        'Madero',
        'Maravatío',
        'Marcos Castellanos',
        'Morelia',
        'Morelos',
        'Múgica',
        'Nahuatzen',
        'Nocupétaro',
        'Nuevo Parangaricutiro',
        'Nuevo Urecho',
        'Numarán',
        'Ocampo',
        'Pajacuarán',
        'Panindícuaro',
        'Paracho',
        'Parácuaro',
        'Pátzcuaro',
        'Penjamillo',
        'Peribán',
        'Purépero',
        'Puruándiro',
        'Queréndaro',
        'Quiroga',
        'Sahuayo',
        'Salvador Escalante',
        'San Lucas',
        'Santa Ana Maya',
        'Senguio',
        'Susupuato',
        'Tacámbaro',
        'Tancítaro',
        'Tangamandapio',
        'Tangancícuaro',
        'Tanhuato',
        'Taretan',
        'Tarímbaro',
        'Tepalcatepec',
        'Tingambato',
        'Tingüindín',
        'Tiquicheo de Nicolás Romero',
        'Tlalpujahua',
        'Tlazazalca',
        'Tocumbo',
        'Tumbiscatío',
        'Turicato',
        'Tuxpan',
        'Tuzantla',
        'Tzintzuntzan',
        'Tzitzio',
        'Uruapan',
        'Venustiano Carranza',
        'Villamar',
        'Vista Hermosa',
        'Yurécuaro',
        'Zacapu',
        'Zamora',
        'Zináparo',
        'Zinapécuaro',
        'Ziracuaretiro',
        'Zitácuaro',
    ];

    /** Los municipios de Guerrero cercanos a Lázaro Cárdenas (decisión del usuario, 2026-11-18). */
    public const GUERRERO = [
        'Coahuayutla de José María Izazaga',
        'La Unión de Isidoro Montes de Oca',
        'Petatlán',
        'Zihuatanejo de Azueta',
    ];

    /** Para quien vive fuera de la región. */
    public const OTRO_LUGAR = 'Otro lugar';

    /** Nombres cortos de uso común, por su clave (D5). Nada más: la corrección no adivina. */
    private const ALIAS = [
        'zihuatanejo' => 'Zihuatanejo de Azueta',
        'la union' => 'La Unión de Isidoro Montes de Oca',
        'coahuayutla' => 'Coahuayutla de José María Izazaga',
        'coalcoman' => 'Coalcomán de Vázquez Pallares',
        'cojumatlan' => 'Cojumatlán de Régules',
        'tiquicheo' => 'Tiquicheo de Nicolás Romero',
        'ciudad hidalgo' => 'Hidalgo',
        'los reyes de salgado' => 'Los Reyes',
    ];

    /** @return array<string, list<string>> municipios por estado */
    public static function grupos(): array
    {
        return ['Michoacán' => self::MICHOACAN, 'Guerrero' => self::GUERRERO];
    }

    /** @return list<string> todo lo que se puede elegir, con «Otro lugar» al final */
    public static function nombres(): array
    {
        return [...self::MICHOACAN, ...self::GUERRERO, self::OTRO_LUGAR];
    }

    /** Si el texto es exactamente una opción de la lista. */
    public static function contiene(?string $ciudad): bool
    {
        return $ciudad !== null && in_array($ciudad, self::nombres(), true);
    }

    /** La ciudad guardada, sin espacios sobrantes, si no es una opción de la lista (CA10). */
    public static function antigua(?string $guardada): ?string
    {
        $ciudad = trim((string) $guardada);

        return $ciudad === '' || self::contiene($ciudad) ? null : $ciudad;
    }

    /** El nombre de la lista al que corresponde un texto escrito a mano, o `null` si no coincide (CA8, CA9). */
    public static function normalizar(string $texto): ?string
    {
        $clave = self::clave($texto);

        foreach (self::nombres() as $nombre) {
            if (self::clave($nombre) === $clave) {
                return $nombre;
            }
        }

        return self::ALIAS[$clave] ?? null;
    }

    /** Sin mayúsculas ni tildes y con los espacios colapsados. */
    private static function clave(string $texto): string
    {
        return Str::lower(Str::ascii(Str::squish($texto)));
    }
}
