@props(['status'])

{{-- Consistent order-status pill used across history and tracking. --}}
@php
    $map = [
        'Delivered'  => 'bg-green-100 text-green-700',
        'In Transit' => 'bg-blue-100 text-blue-700',
        'Picked'     => 'bg-purple-100 text-purple-700',
        'Packing'    => 'bg-yellow-100 text-yellow-700',
        'Cancelled'  => 'bg-red-100 text-red-600',
    ];
    $classes = $map[$status] ?? 'bg-gray-100 text-gray-600';
    $label = $status === 'Delivered' ? 'Completed' : $status;
@endphp

<span {{ $attributes->class(['inline-block px-3 py-1 rounded-full text-[10px] font-bold uppercase tracking-wide', $classes]) }}>{{ $label }}</span>
