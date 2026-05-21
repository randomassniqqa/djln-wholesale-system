@props(['priority'])

@php
$priorityConfig = [
    'low' => [
        'icon' => '▼',
        'color' => 'text-green-600',
        'bg' => 'bg-green-50',
        'label' => 'Low'
    ],
    'medium' => [
        'icon' => '◆',
        'color' => 'text-yellow-600',
        'bg' => 'bg-yellow-50',
        'label' => 'Medium'
    ],
    'high' => [
        'icon' => '▲',
        'color' => 'text-orange-600',
        'bg' => 'bg-orange-50',
        'label' => 'High'
    ],
    'critical' => [
        'icon' => '🔴',
        'color' => 'text-red-600',
        'bg' => 'bg-red-50',
        'label' => 'Critical'
    ]
];

$config = $priorityConfig[$priority] ?? $priorityConfig['low'];
@endphp

<div class="inline-flex items-center px-2.5 py-1 rounded {{ $config['bg'] }} space-x-1">
    <span class="{{ $config['color'] }} font-bold text-sm">{{ $config['icon'] }}</span>
    <span class="{{ $config['color'] }} text-xs font-medium">{{ $config['label'] }}</span>
</div>
