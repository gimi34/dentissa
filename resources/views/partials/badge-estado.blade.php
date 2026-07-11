@php
    $config = match($estado ?? '') {
        'agendada'   => ['class' => 'badge-pink',  'label' => 'Agendada'],
        'confirmada' => ['class' => 'badge-blue',  'label' => 'Confirmada'],
        'en_curso'   => ['class' => 'badge-amber', 'label' => 'En curso'],
        'completada' => ['class' => 'badge-green', 'label' => 'Completada'],
        'cancelada'  => ['class' => 'badge-red',   'label' => 'Cancelada'],
        'no_asistio' => ['class' => 'badge-gray',  'label' => 'No asistió'],
        default      => ['class' => 'badge-gray',  'label' => ucfirst($estado) ?: '—'],
    };
@endphp
<span class="badge {{ $config['class'] }}">{{ $config['label'] }}</span>