@extends('layouts.app')
@section('title', 'Reprogramar cita')

{{-- Reprogramar una cita como administración o asistente (spec 028). Datos en CitasController::reprogramarForm. --}}
@section('content')
{{-- Migas de pan (spec 046): el controlador no pasa el prefijo del rol; se calcula como en citas/show. --}}
@php
    $rp = match(auth()->user()->rol) {
        'dentista'  => 'dentista',
        'asistente' => 'asistente',
        default     => 'admin',
    };
@endphp
@include('partials.migas', ['migas' => [
    ['texto' => auth()->user()->rol === 'dentista' ? 'Mis Citas' : 'Citas', 'url' => route("$rp.citas")],
    ['texto' => $cita->paciente, 'url' => $rutaDetalle],
    ['texto' => 'Reprogramar'],
]])
@include('citas.partials.reprogramar', [
    'quien' => ['etiqueta' => 'Paciente', 'nombre' => $cita->paciente],
])
@endsection
