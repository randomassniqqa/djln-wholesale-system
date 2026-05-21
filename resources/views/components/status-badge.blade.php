@props(['status'])

@php
$statusClasses = [
    'pending' => 'bg-yellow-100 text-yellow-800 border-yellow-300',
    'in_progress' => 'bg-blue-100 text-blue-800 border-blue-300',
    'completed' => 'bg-green-100 text-green-800 border-green-300',
    'on_hold' => 'bg-gray-100 text-gray-800 border-gray-300',
    'cancelled' => 'bg-red-100 text-red-800 border-red-300',
];

$displayStatus = [
    'pending' => 'Pending',
    'in_progress' => 'In Progress',
    'completed' => 'Completed',
    'on_hold' => 'On Hold',
    'cancelled' => 'Cancelled',
];

// Defensive null check
if (empty($status)) {
    $status = 'pending';
}
@endphp

<span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium border {{ $statusClasses[$status] ?? 'bg-gray-100 text-gray-800 border-gray-300' }}">
    {{ $displayStatus[$status] ?? ucfirst(str_replace('_', ' ', $status)) }}
</span>
