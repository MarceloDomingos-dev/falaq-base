<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreModeracaoRequest;
use App\Models\Evento;
use App\Models\Moderacao;
use App\Models\Pergunta;
use Illuminate\Http\Request;

class ModeracaoController extends Controller
{
    /**
     * Fila de Moderação do Evento (apenas o dono/moderador do evento).
     * Eager Loading com with('pergunta') evita o problema N+1.
     */
    public function index(Evento $evento)
    {
        $this->authorize('moderate', $evento);

        $moderacoes = Moderacao::with('pergunta')
            ->whereRelation('pergunta', 'evento_id', $evento->id)
            ->latest()
            ->paginate(15);

        return view('moderacoes.index', compact('evento', 'moderacoes'));
    }

    /**
     * Registra a denúncia de uma pergunta (qualquer participante autenticado).
     */
    public function store(StoreModeracaoRequest $request, Pergunta $pergunta)
    {
        $pergunta->moderacoes()->create([
            'motivo' => $request->validated('motivo'),
        ]);

        return back()->with('status', 'Denúncia enviada! A organização vai analisar a pergunta.');
    }

    /**
     * Aprova ou rejeita a denúncia — só o dono do evento decide.
     */
    public function updateStatus(Request $request, Moderacao $moderacao)
    {
        $this->authorize('moderate', $moderacao->pergunta->evento);

        $dados = $request->validate([
            'status' => ['required', 'in:aprovada,rejeitada'],
        ]);

        $moderacao->update(['status' => $dados['status']]);

        return back()->with('status', 'Moderação atualizada para "' . $dados['status'] . '".');
    }
}
