@extends('layouts.app')
@section('title', 'Reprogramar cita')

{{-- Reprogramar una cita como paciente (spec 028). Datos en ClienteCitasController::reprogramarForm. --}}
@section('content')
@include('citas.partials.reprogramar', [
    'quien' => ['etiqueta' => 'Dentista', 'nombre' => $cita->dentista],
])
@endsection
