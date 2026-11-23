<?php

namespace App\Autorizacion;

use App\Models\Usuario;
use Illuminate\Auth\Access\Response;
use Illuminate\Support\Facades\DB;

/**
 * Quién puede ver y modificar fichas y expedientes, crear expedientes y agendar para un paciente (spec 014, ADR 0003).
 *
 * Respalda siete Gates. Fuera del alcance clínico (`AlcanceClinico`) la respuesta es `denyAsNotFound()`: un 404 que no
 * confirma que el paciente exista (D2). Dentro del alcance, lo que el rol no puede hacer es `deny()` (403). El admin
 * no tiene restricción. Quien no es personal de la clínica no pasa por aquí (sus rutas tienen otro rol).
 */
class AccesoClinico
{
    public function __construct(private AlcanceClinico $alcance) {}

    public function verPaciente(Usuario $usuario, string $pacienteId): Response
    {
        return $this->enAlcance($usuario, $pacienteId);
    }

    public function editarPaciente(Usuario $usuario, string $pacienteId): Response
    {
        return $this->conRol($usuario, $pacienteId, ['admin', 'dentista'], 'Como asistente no puedes modificar la ficha del paciente.');
    }

    /**
     * Cambiar el dentista asignado (CA15, D21): el admin o el dentista asignado. Quien tiene al paciente en su alcance
     * solo por una cita edita la ficha, pero no le cambia el doctor.
     */
    public function reasignarPaciente(Usuario $usuario, string $pacienteId): Response
    {
        $acceso = $this->conRol($usuario, $pacienteId, ['admin', 'dentista'], 'Como asistente no puedes modificar la ficha del paciente.');
        if ($acceso->denied() || $usuario->rol === 'admin') {
            return $acceso;
        }

        $asignado = DB::table('pacientes')->where('id', $pacienteId)->value('dentista_id');

        return $asignado !== null && $asignado === $this->alcance->personalId($usuario)
            ? Response::allow()
            : Response::deny('Solo el dentista asignado o la administración cambian el doctor del paciente.');
    }

    public function verExpediente(Usuario $usuario, object $expediente): Response
    {
        return $this->enAlcance($usuario, $expediente->paciente_id);
    }

    public function editarExpediente(Usuario $usuario, object $expediente): Response
    {
        $acceso = $this->conRol($usuario, $expediente->paciente_id, ['admin', 'dentista'], 'Como asistente no puedes modificar expedientes.');
        if ($acceso->denied() || $usuario->rol === 'admin') {
            return $acceso;
        }

        return $expediente->dentista_id === $this->alcance->personalId($usuario)
            ? Response::allow()
            : Response::deny('Solo quien firmó el expediente puede modificarlo.');
    }

    public function crearExpediente(Usuario $usuario, string $pacienteId): Response
    {
        return $this->conRol($usuario, $pacienteId, ['admin', 'dentista'], 'Como asistente no puedes registrar expedientes.');
    }

    public function agendarParaPaciente(Usuario $usuario, string $pacienteId): Response
    {
        return $this->enAlcance($usuario, $pacienteId);
    }

    /** Admin, o paciente del alcance; si no, 404. */
    private function enAlcance(Usuario $usuario, string $pacienteId): Response
    {
        if ($usuario->rol === 'admin') {
            return Response::allow();
        }

        return $this->alcance->pacienteEnAlcance($usuario, $pacienteId)
            ? Response::allow()
            : Response::denyAsNotFound();
    }

    /** Fuera del alcance, 404; dentro, 403 si el rol no está entre los permitidos. */
    private function conRol(Usuario $usuario, string $pacienteId, array $roles, string $mensaje): Response
    {
        $acceso = $this->enAlcance($usuario, $pacienteId);
        if ($acceso->denied()) {
            return $acceso;
        }

        return in_array($usuario->rol, $roles, true) ? Response::allow() : Response::deny($mensaje);
    }
}
