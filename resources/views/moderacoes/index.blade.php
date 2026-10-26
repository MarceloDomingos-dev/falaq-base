@extends('layouts.app')

@section('title', 'Fila de Moderação — ' . $evento->titulo)

@section('content')
<div class="d-flex justify-content-between align-items-center mb-4">
    <h3 class="fw-bold m-0">🛡️ Fila de Moderação: {{ $evento->titulo }}</h3>
    <a href="{{ route('eventos.show', $evento->id) }}" class="btn btn-outline-light btn-sm">← Voltar ao evento</a>
</div>

@forelse($moderacoes as $moderacao)
    <div class="card mb-3 shadow-sm">
        <div class="card-body">
            <p class="fs-5 mb-1 text-white">{{ $moderacao->pergunta->texto }}</p>
            <p class="mb-2 text-secondary"><strong>Motivo:</strong> {{ $moderacao->motivo }}</p>

            <div class="d-flex justify-content-between align-items-center small">
                <span>
                    Status:
                    <span class="badge {{ $moderacao->status === 'aprovada' ? 'bg-success' : ($moderacao->status === 'rejeitada' ? 'bg-danger' : 'bg-warning text-dark') }}">
                        {{ $moderacao->status }}
                    </span>
                    <span class="text-secondary ms-2">{{ $moderacao->created_at->format('d/m/Y H:i') }}</span>
                </span>

                @if($moderacao->status === 'analisando')
                    <div class="d-flex gap-2">
                        <form action="{{ route('moderacoes.status', $moderacao) }}" method="POST">
                            @csrf
                            @method('PATCH')
                            <input type="hidden" name="status" value="aprovada">
                            <button type="submit" class="btn btn-success btn-sm">Aprovar</button>
                        </form>
                        <form action="{{ route('moderacoes.status', $moderacao) }}" method="POST">
                            @csrf
                            @method('PATCH')
                            <input type="hidden" name="status" value="rejeitada">
                            <button type="submit" class="btn btn-danger btn-sm">Rejeitar</button>
                        </form>
                    </div>
                @endif
            </div>
        </div>
    </div>
@empty
    <div class="alert alert-dark text-center p-4">Nenhuma denúncia para este evento. 🎉</div>
@endforelse

<div class="d-flex justify-content-center mt-4">
    {{ $moderacoes->links() }}
</div>
@endsection
