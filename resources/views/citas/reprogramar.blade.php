@extends('layouts.app')
@section('title', 'Reprogramar cita')

{{-- Reprogramar una cita como administración o asistente (spec 028). Datos en CitasController::reprogramarForm. --}}
@section('content')
@include('citas.partials.reprogramar', [
    'quien' => ['etiqueta' => 'Paciente', 'nombre' => $cita->paciente],
])
@endsection
