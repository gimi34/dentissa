<?php

namespace App\Pacientes;

use App\Auditoria\Auditoria;
use Illuminate\Support\Facades\DB;

/**
 * Spec 051 (CA8, CA9, CA12): lleva a la lista las ciudades ya guardadas que coinciden con un nombre
 * y conserva las demás. Se puede repetir sin efecto.
 *
 * Compara en PHP y actualiza por id porque la columna no distingue mayúsculas ni tildes (D4). Si no
 * queda constancia en la auditoría, la transacción deshace la corrección (D7).
 */
class CorreccionCiudades
{
    public function __construct(private readonly Auditoria $auditoria) {}

    /** @return array{corregidos: int, fuera_de_lista: int} */
    public function ejecutar(): array
    {
        return DB::transaction(function (): array {
            $totales = ['corregidos' => 0, 'fuera_de_lista' => 0];

            DB::table('pacientes')->select('id', 'ciudad')->whereNotNull('ciudad')
                ->chunkById(500, function ($pacientes) use (&$totales): void {
                    foreach ($pacientes as $paciente) {
                        $this->corregir($paciente->id, $paciente->ciudad, $totales);
                    }
                });

            $this->auditoria->registrar(
                'pacientes_ciudades_corregidas',
                'exito',
                'pacientes',
                campos: ['ciudad'],
                detalle: $totales,
                critico: true,
            );

            return $totales;
        });
    }

    /** @param  array{corregidos: int, fuera_de_lista: int}  $totales */
    private function corregir(string $id, string $ciudad, array &$totales): void
    {
        if (trim($ciudad) === '' || Ciudades::contiene($ciudad)) {
            return;
        }

        $nombre = Ciudades::normalizar($ciudad);

        if ($nombre === null) {
            $totales['fuera_de_lista']++;

            return;
        }

        DB::table('pacientes')->where('id', $id)->update(['ciudad' => $nombre]);
        $totales['corregidos']++;
    }
}
