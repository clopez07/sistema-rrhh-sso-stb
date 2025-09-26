@extends('layouts.prestamos')

@section('title', 'Previsualización de ajustes')

@section('content')
    @php
        $filtros = [
            'fecha_inicio' => $plan['inicio'] ?? null,
            'fecha_fin' => $plan['fin'] ?? null,
            'estado' => 'todas',
            'search' => request('search'),
        ];
    @endphp

    @include('prestamos.partials.ajustes-preview', [
        'ajustePlan' => $plan,
        'ajusteToken' => $token,
        'filtros' => $filtros,
    ])
@endsection
