<?php

namespace Tests\Feature;

use App\Models\Evento;
use App\Models\Moderacao;
use App\Models\Pergunta;
use App\Models\User;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class ModeracaoPerguntaTest extends TestCase
{
    use RefreshDatabase;

    private function criarEvento(User $dono): Evento
    {
        return Evento::create(['titulo' => 'Palestra de Laravel', 'user_id' => $dono->id]);
    }

    private function criarPergunta(Evento $evento): Pergunta
    {
        return Pergunta::create([
            'evento_id' => $evento->id,
            'texto' => 'Pergunta fora do escopo da palestra',
            'status' => 'pendente',
        ]);
    }

    // Fase 1: Fundação de Dados
    public function test_tabela_moderacoes_com_fk_e_status_padrao(): void
    {
        $this->assertTrue(Schema::hasColumns('moderacoes', ['id', 'pergunta_id', 'motivo', 'status', 'created_at', 'updated_at']));

        $pergunta = $this->criarPergunta($this->criarEvento(User::factory()->create()));
        $moderacao = $pergunta->moderacoes()->create(['motivo' => 'Conteúdo ofensivo aqui']);

        $this->assertSame('analisando', $moderacao->fresh()->status);

        // cascadeOnDelete: apagar a pergunta apaga as denúncias
        DB::statement('PRAGMA foreign_keys = ON');
        $pergunta->delete();
        $this->assertDatabaseCount('moderacoes', 0);
    }

    public function test_relacionamentos_1_n(): void
    {
        $this->assertInstanceOf(HasMany::class, (new Pergunta)->moderacoes());
        $this->assertInstanceOf(BelongsTo::class, (new Moderacao)->pergunta());
        $this->assertSame(['pergunta_id', 'motivo', 'status'], (new Moderacao)->getFillable());
    }

    // Fase 2: Entrada Segura & Persistência
    public function test_participante_denuncia_pergunta(): void
    {
        $pergunta = $this->criarPergunta($this->criarEvento(User::factory()->create()));

        $this->actingAs(User::factory()->create())
            ->post(route('perguntas.moderar', $pergunta), ['motivo' => 'Pergunta com ofensas ao palestrante'])
            ->assertRedirect();

        $this->assertDatabaseHas('moderacoes', [
            'pergunta_id' => $pergunta->id,
            'motivo' => 'Pergunta com ofensas ao palestrante',
            'status' => 'analisando',
        ]);
    }

    public function test_validacao_do_motivo(): void
    {
        $pergunta = $this->criarPergunta($this->criarEvento(User::factory()->create()));
        $user = User::factory()->create();

        foreach (['', 'curto', str_repeat('a', 256)] as $motivo) {
            $this->actingAs($user)
                ->post(route('perguntas.moderar', $pergunta), ['motivo' => $motivo])
                ->assertSessionHasErrors('motivo');
        }

        $this->assertDatabaseCount('moderacoes', 0);
    }

    public function test_visitante_nao_pode_denunciar(): void
    {
        $pergunta = $this->criarPergunta($this->criarEvento(User::factory()->create()));

        $this->post(route('perguntas.moderar', $pergunta), ['motivo' => 'Pergunta com ofensas ao palestrante'])
            ->assertRedirect(route('login.create'));

        $this->assertDatabaseCount('moderacoes', 0);
    }

    // Fase 3: Autorização & Performance
    public function test_fila_de_moderacao_so_para_o_dono_e_sem_n_mais_1(): void
    {
        $dono = User::factory()->create();
        $evento = $this->criarEvento($dono);
        $outroEvento = $this->criarEvento(User::factory()->create());

        foreach (range(1, 3) as $i) {
            $this->criarPergunta($evento)->moderacoes()->create(['motivo' => "Denúncia número {$i} aqui"]);
        }
        $this->criarPergunta($outroEvento)->moderacoes()->create(['motivo' => 'Denúncia de outro evento']);

        $this->actingAs(User::factory()->create())
            ->get(route('eventos.moderacoes.index', $evento))
            ->assertForbidden();

        DB::enableQueryLog();
        $response = $this->actingAs($dono)->get(route('eventos.moderacoes.index', $evento));
        $consultasEmPerguntas = collect(DB::getQueryLog())
            ->filter(fn ($q) => str_contains($q['query'], 'from "perguntas"') && ! str_contains($q['query'], 'exists'))
            ->count();

        $response->assertOk()->assertDontSee('Denúncia de outro evento');
        $moderacoes = $response->viewData('moderacoes');
        $this->assertCount(3, $moderacoes);
        $this->assertTrue($moderacoes->first()->relationLoaded('pergunta'));
        $this->assertSame(1, $consultasEmPerguntas, 'As perguntas devem ser carregadas numa única query (eager loading).');
    }

    public function test_apenas_dono_do_evento_altera_status(): void
    {
        $dono = User::factory()->create();
        $moderacao = $this->criarPergunta($this->criarEvento($dono))
            ->moderacoes()->create(['motivo' => 'Conteúdo ofensivo aqui']);

        $this->actingAs(User::factory()->create())
            ->patch(route('moderacoes.status', $moderacao), ['status' => 'aprovada'])
            ->assertForbidden();
        $this->assertSame('analisando', $moderacao->fresh()->status);

        $this->actingAs($dono)
            ->patch(route('moderacoes.status', $moderacao), ['status' => 'qualquer'])
            ->assertSessionHasErrors('status');

        $this->actingAs($dono)
            ->patch(route('moderacoes.status', $moderacao), ['status' => 'rejeitada'])
            ->assertRedirect();
        $this->assertSame('rejeitada', $moderacao->fresh()->status);
    }
}
