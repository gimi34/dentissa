@extends('layouts.app')
@section('title', 'Reprogramar cita')

{{-- Reprogramar una cita como paciente (spec 028). Datos en ClienteCitasController::reprogramarForm. --}}
@section('content')
{{-- Migas de pan (spec 046) --}}
@include('partials.migas', ['migas' => [
    ['texto' => 'Mis Citas', 'url' => route('cliente.citas')],
    ['texto' => 'Detalle', 'url' => $rutaDetalle],
    ['texto' => 'Reprogramar'],
]])
@include('citas.partials.reprogramar', [
    'quien' => ['etiqueta' => 'Dentista', 'nombre' => $cita->dentista],
])
@endsection
