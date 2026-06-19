@extends('layouts.app')

@section('title', 'Registrar Animal')
@section('header_title', 'Registrar Animal')
@section('header_subtitle', 'Agregue un nuevo animal al inventario')
@section('back_url', route('animales.index'))

@section('content')
<div class="row justify-content-center">
    <div class="col-lg-8">
        <div class="card border-0 shadow-sm">
            <div class="card-body p-4">
                @if ($errors->any())
                    <div class="alert alert-danger border-0 shadow-sm mb-4 rounded-4">
                        <div class="fw-bold mb-1"><i class="bi bi-exclamation-circle-fill me-2"></i>Corrige los siguientes errores:</div>
                        <ul class="mb-0 small">
                            @foreach ($errors->all() as $error)
                                <li>{{ $error }}</li>
                            @endforeach
                        </ul>
                    </div>
                @endif
                <form action="{{ route('animales.store') }}" method="POST">
                    @csrf
                    @include('animales._form', ['animale' => null])
                    <div class="d-flex justify-content-end gap-3 mt-4">
                        <a href="{{ route('animales.index') }}" class="btn btn-light px-4">Cancelar</a>
                        <button type="submit" class="btn btn-primary px-5">Guardar Animal</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
@endsection
