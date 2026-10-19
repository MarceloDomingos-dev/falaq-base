<?php

namespace App\Http\Controllers;

use App\Models\Pergunta;

class PerguntaController extends Controller
{
    /**
     * Alterna o voto do usuário autenticado na pergunta (vota / remove o voto).
     */
    public function votar(Pergunta $pergunta)
    {
        $resultado = $pergunta->votos()->toggle(auth()->id());

        $mensagem = count($resultado['attached']) > 0
            ? 'Voto registrado!'
            : 'Voto removido.';

        return back()->with('status', $mensagem);
    }
}
