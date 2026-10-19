@extends('layouts.app')

@section('title', $evento->titulo . ' — FalaQ')

@section('content')
<div class="row">
    <div class="flex items-center gap-4 my-4">
        <span class="text-sm font-semibold bg-blue-100 text-blue-800 px-3 py-1 rounded-full">
            👥 {{ $evento->participantes->count() }} participante(s)
        </span>
    
        @auth
            <form action="{{ route('eventos.participar', $evento) }}" method="POST">
                @csrf
                <button type="submit" class="px-4 py-2 text-sm font-medium rounded-md {{ $evento->participantes->contains(auth()->user()) ? 'bg-red-600 hover:bg-red-700 text-white' : 'bg-green-600 hover:bg-green-700 text-white' }}">
                    {{ $evento->participantes->contains(auth()->user()) ? 'Cancelar Inscrição' : 'Inscrever-se no Evento' }}
                </button>
            </form>
        @endauth
    </div>
    <!-- Formularço de envio de Pergunta -->
    <div class="col-md-5 mb-4">
        <div class="card shadow-sm p-3">
            <h4 class="fw-bold mb-3">💬 Faça sua Pergunta</h4>
            <form action="{{ route('eventos.perguntas.store', $evento->id) }}" method="POST">
                @csrf
                <div class="mb-3">
                    <label for="texto" class="form-label text-secondary">Texto da Pergunta</label>

                    <textarea name="texto" id="texto" rows="4" 
                              class="form-control bg-dark text-white border-secondary @error('texto') is-invalid @enderror"
                              placeholder="Digite sua dúvida ou comentário para o palestrante..."></textarea>

                    @error('texto')
                        <div class="invalid-feedback fw-bold">
                            {{ $message }}
                        </div>
                    @enderror
                </div>
                <button type="submit" class="btn btn-primary w-100 fw-bold">Enviar Pergunta</button>
            </form>
        </div>
    </div>

    <!-- Lista de Perguntas (TICKET #002) -->
    <div class="col-md-7">
        <div class="d-flex justify-content-between align-items-center mb-3">
            <h4 class="fw-bold m-0">📋 Perguntas do Evento</h4>
            <span class="text-secondary small">Total no Banco: {{ $evento->perguntas->count() }}</span>
        </div>

        @forelse($perguntas as $pergunta)
            <div class="card mb-3 shadow-sm border-start border-4 border-primary">
                <div class="card-body">
                    <div class="flex items-start gap-3">
                        <!-- TICKET #012: Upvote -->
                        <div class="flex flex-col items-center min-w-12">
                            @auth
                                <form action="{{ route('perguntas.votar', $pergunta) }}" method="POST">
                                    @csrf
                                    <button type="submit"
                                            title="{{ $pergunta->votada_pelo_usuario ? 'Clique para remover seu voto' : 'Votar nesta pergunta' }}"
                                            class="px-3 py-1 rounded-md text-sm font-bold border {{ $pergunta->votada_pelo_usuario ? 'bg-blue-600 border-blue-600 text-white hover:bg-blue-700' : 'bg-transparent border-gray-400 text-gray-300 hover:border-blue-500 hover:text-blue-400' }}">
                                        ▲ {{ $pergunta->votada_pelo_usuario ? 'Votado' : 'Votar' }}
                                    </button>
                                </form>
                            @else
                                <a href="{{ route('login.create') }}" class="px-3 py-1 rounded-md text-sm border border-gray-400 text-gray-300" title="Entre para votar">▲</a>
                            @endauth
                            <span class="mt-1 font-bold text-white" data-votos>{{ $pergunta->votos_count }}</span>
                            <span class="text-xs text-secondary">voto(s)</span>
                        </div>
                        <p class="fs-5 mb-2 text-white">{{ $pergunta->texto }}</p>
                    </div>
                    <div class="d-flex justify-content-between align-items-center text-secondary small">
                        <span>Status: <span class="badge bg-success">{{ $pergunta->status }}</span></span>
                        <span>{{ $pergunta->created_at->format('d/m/Y H:i') }}</span>
                    </div>
                </div>
            </div>
        @empty
            <div class="alert alert-dark text-center p-4">
                Nenhuma pergunta enviada ainda. Seja o primeiro!
            </div>
        @endforelse

        <!-- TICKET #002: Renderização dos Botões de Paginação -->
        @if(method_exists($perguntas, 'links'))
            <div class="d-flex justify-content-center mt-4">
                
            </div>
        @endif
    </div>
</div>
@endsection
