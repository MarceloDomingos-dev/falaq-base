<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Moderacao extends Model
{
    /**
     * O plural automático do Laravel seria "moderacaos",
     * então apontamos explicitamente para a tabela da migration.
     */
    protected $table = 'moderacoes';

    protected $fillable = ['pergunta_id', 'motivo', 'status'];

    public function pergunta(): BelongsTo
    {
        return $this->belongsTo(Pergunta::class);
    }
}
