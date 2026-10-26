<?php

namespace App\Policies;

use App\Models\Evento;
use App\Models\User;

class EventoPolicy
{
    /**
     * TICKET #016: apenas o dono do evento modera as perguntas dele.
     */
    public function moderate(User $user, Evento $evento): bool
    {
        return $user->id === $evento->user_id;
    }
}
