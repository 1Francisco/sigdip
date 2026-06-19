@extends('layouts.app')

@section('title', 'Editar Arete')
@section('header_title', 'Editar Arete del Censo')
@section('header_subtitle', 'Modifique los datos del arete')
@section('back_url', route('aretes-censo.index'))

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
                <form action="{{ route('aretes-censo.update', $aretes_censo->id) }}" method="POST">
                    @csrf
                    @method('PUT')
                    @include('aretes-censo._form', ['aretes_censo' => $aretes_censo])
                    <div class="d-flex justify-content-end gap-3 mt-4">
                        <a href="{{ route('aretes-censo.index') }}" class="btn btn-light px-4">Cancelar</a>
                        <button type="submit" class="btn btn-primary px-5">Actualizar Arete</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
@endsection
