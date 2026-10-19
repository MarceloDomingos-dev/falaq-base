<?php

namespace Tests\Feature;

use App\Models\Evento;
use App\Models\Pergunta;
use App\Models\User;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class UpvotePerguntaTest extends TestCase
{
    use RefreshDatabase;

    private function criarPergunta(Evento $evento, string $texto = 'Pergunta de teste'): Pergunta
    {
        return Pergunta::create([
            'evento_id' => $evento->id,
            'texto' => $texto,
            'status' => 'pendente',
        ]);
    }

    private function criarEvento(): Evento
    {
        return Evento::create(['titulo' => 'Palestra de Laravel']);
    }

    public function test_tabela_pivot_existe_com_chave_unica(): void
    {
        $this->assertTrue(Schema::hasTable('pergunta_user'));
        $this->assertTrue(Schema::hasColumns('pergunta_user', ['pergunta_id', 'user_id', 'created_at', 'updated_at']));

        $user = User::factory()->create();
        $pergunta = $this->criarPergunta($this->criarEvento());

        DB::table('pergunta_user')->insert(['pergunta_id' => $pergunta->id, 'user_id' => $user->id]);

        $this->expectException(QueryException::class);
        DB::table('pergunta_user')->insert(['pergunta_id' => $pergunta->id, 'user_id' => $user->id]);
    }

    public function test_relacionamentos_belongs_to_many(): void
    {
        $this->assertInstanceOf(BelongsToMany::class, (new Pergunta)->votos());
        $this->assertInstanceOf(BelongsToMany::class, (new User)->perguntasVotadas());
    }

    public function test_usuario_anonimo_nao_pode_votar(): void
    {
        $pergunta = $this->criarPergunta($this->criarEvento());

        $this->post(route('perguntas.votar', $pergunta))
            ->assertRedirect(route('login.create'));

        $this->assertDatabaseCount('pergunta_user', 0);
    }

    public function test_votar_insere_e_votar_de_novo_remove(): void
    {
        $user = User::factory()->create();
        $pergunta = $this->criarPergunta($this->criarEvento());

        $this->actingAs($user)->post(route('perguntas.votar', $pergunta))->assertRedirect();
        $this->assertDatabaseHas('pergunta_user', ['pergunta_id' => $pergunta->id, 'user_id' => $user->id]);
        $this->assertTrue($user->perguntasVotadas()->whereKey($pergunta->id)->exists());

        $this->actingAs($user)->post(route('perguntas.votar', $pergunta))->assertRedirect();
        $this->assertDatabaseMissing('pergunta_user', ['pergunta_id' => $pergunta->id, 'user_id' => $user->id]);
    }

    public function test_perguntas_do_evento_ordenadas_por_votos(): void
    {
        $evento = $this->criarEvento();
        $poucos = $this->criarPergunta($evento, 'Pergunta com um voto');
        $nenhum = $this->criarPergunta($evento, 'Pergunta sem votos');
        $muitos = $this->criarPergunta($evento, 'Pergunta mais votada');

        $users = User::factory()->count(3)->create();
        $muitos->votos()->attach($users->pluck('id'));
        $poucos->votos()->attach($users->first()->id);

        $this->withoutVite();
        $response = $this->get(route('eventos.show', $evento->id));

        $response->assertOk();
        $response->assertSeeInOrder(['Pergunta mais votada', 'Pergunta com um voto', 'Pergunta sem votos']);

        $perguntas = $response->viewData('perguntas');
        $this->assertEquals([3, 1, 0], $perguntas->pluck('votos_count')->all());
    }
}
