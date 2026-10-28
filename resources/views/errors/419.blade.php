@extends('errors.minimal')

@section('title', 'Sesión vencida')
@section('code', '419')
@section('heading', 'Sesión vencida')
@section('message', 'Tu sesión venció. Volvé a iniciar sesión')

@section('extra')
    <p>Por seguridad, los formularios y las sesiones inactivas caducan después de un tiempo sin actividad.</p>
@endsection

@section('actions')
    <a
        href="{{ route('login') }}"
        class="inline-flex items-center justify-center px-5 py-2.5 rounded-full bg-brand-600 hover:bg-brand-hover text-white font-medium text-sm transition-colors duration-150 shadow-xs focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-brand-600 focus-visible:ring-offset-2 min-h-11 w-full sm:w-auto"
    >
        Iniciar sesión
    </a>
@endsection
