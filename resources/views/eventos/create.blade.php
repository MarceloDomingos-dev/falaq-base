@extends('layouts.app')

@section('title', 'Criar Evento — FalaQ')

@section('content')
<section class="max-w-2xl mx-auto rounded-lg bg-white p-6 text-gray-900 shadow-md" aria-labelledby="form-title">
    <h1 id="form-title" class="mb-2 text-2xl font-bold">Criar evento</h1>
    <p class="mb-6 text-gray-600">Preencha o título e a descrição para cadastrar seu evento. A data é opcional.</p>

    <form action="{{ route('eventos.store') }}" method="POST" class="space-y-6" novalidate>
        @csrf

        <div>
            <label for="titulo" class="mb-2 block font-medium">Título do evento (obrigatório)</label>
            <input
                type="text"
                name="titulo"
                id="titulo"
                value="{{ old('titulo') }}"
                maxlength="255"
                required
                aria-invalid="{{ $errors->has('titulo') ? 'true' : 'false' }}"
                @error('titulo') aria-describedby="titulo-error" @enderror
                class="w-full rounded-md border-[1px] border-solid bg-white px-3 py-2 text-gray-900 focus:outline-none focus:ring-2 @error('titulo') border-red-500 focus:border-red-500 focus:ring-red-500 @else border-gray-300 focus:border-blue-600 focus:ring-blue-600 @enderror"
            >
            @error('titulo')
                <p id="titulo-error" class="mt-1 text-sm text-red-500" role="alert">{{ $message }}</p>
            @enderror
        </div>

        <div>
            <label for="descricao" class="mb-2 block font-medium">Descrição do evento (obrigatória)</label>
            <textarea
                name="descricao"
                id="descricao"
                rows="5"
                required
                aria-invalid="{{ $errors->has('descricao') ? 'true' : 'false' }}"
                @error('descricao') aria-describedby="descricao-error" @enderror
                class="w-full resize-y rounded-md border-[1px] border-solid bg-white px-3 py-2 text-gray-900 focus:outline-none focus:ring-2 @error('descricao') border-red-500 focus:border-red-500 focus:ring-red-500 @else border-gray-300 focus:border-blue-600 focus:ring-blue-600 @enderror"
            >{{ old('descricao') }}</textarea>
            @error('descricao')
                <p id="descricao-error" class="mt-1 text-sm text-red-500" role="alert">{{ $message }}</p>
            @enderror
        </div>

        <div>
            <label for="data_evento" class="mb-2 block font-medium">Data do evento (opcional)</label>
            <input
                type="date"
                name="data_evento"
                id="data_evento"
                value="{{ old('data_evento') }}"
                aria-invalid="{{ $errors->has('data_evento') ? 'true' : 'false' }}"
                @error('data_evento') aria-describedby="data_evento-error" @enderror
                class="w-full rounded-md border-[1px] border-solid bg-white px-3 py-2 text-gray-900 focus:outline-none focus:ring-2 @error('data_evento') border-red-500 focus:border-red-500 focus:ring-red-500 @else border-gray-300 focus:border-blue-600 focus:ring-blue-600 @enderror"
            >
            @error('data_evento')
                <p id="data_evento-error" class="mt-1 text-sm text-red-500" role="alert">{{ $message }}</p>
            @enderror
        </div>

        <button type="submit" class="w-full cursor-pointer rounded-md bg-blue-600 px-4 py-2 font-semibold text-white transition-colors hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-blue-600 focus:ring-offset-2 sm:w-auto">
            Criar evento
        </button>
    </form>
</section>
@endsection
