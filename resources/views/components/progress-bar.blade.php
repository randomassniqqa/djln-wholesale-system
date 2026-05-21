@props(['percentage' => 0, 'color' => 'bg-indigo-600'])

@php
// Defensive null and type checks
$percentage = (float) ($percentage ?? 0);
$percentage = max(0, min($percentage, 100)); // Clamp between 0-100
$color = !empty($color) ? $color : 'bg-indigo-600';
@endphp

<div class="flex items-center space-x-3">
    <div class="flex-1 bg-slate-200 rounded-full h-2 overflow-hidden">
        <div class="{{ $color }} h-full transition-all duration-300" style="width: {{ round($percentage) }}%"></div>
    </div>
    <span class="text-sm font-medium text-slate-600 min-w-12 text-right">{{ round($percentage) }}%</span>
</div>
