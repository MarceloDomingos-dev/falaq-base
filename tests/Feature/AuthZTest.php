<?php

namespace Tests\Feature;

use App\Models\Evento;
use App\Models\Pergunta;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Gate;
use Tests\TestCase;

class AuthZTest extends TestCase
{
    use RefreshDatabase;

    private User $dono;

    private User $autor;

    private User $outro;

    private Evento $evento;

    private Pergunta $pergunta;

    protected function setUp(): void
    {
        parent::setUp();

        $this->withoutVite();
        $this->dono = User::factory()->create();
        $this->autor = User::factory()->create();
        $this->outro = User::factory()->create();
        $this->evento = Evento::create([
            'titulo' => 'Evento de teste',
            'user_id' => $this->dono->id,
        ]);
        $this->pergunta = Pergunta::create([
            'evento_id' => $this->evento->id,
            'user_id' => $this->autor->id,
            'texto' => 'Como funciona a autorização?',
        ]);
    }

    private function url(): string
    {
        return route('eventos.perguntas.destroy', [$this->evento->id, $this->pergunta->id]);
    }

    public function test_policy_permite_somente_autor_ou_dono_do_evento(): void
    {
        $this->assertTrue(Gate::forUser($this->autor)->allows('delete', $this->pergunta));
        $this->assertTrue(Gate::forUser($this->dono)->allows('delete', $this->pergunta));
        $this->assertFalse(Gate::forUser($this->outro)->allows('delete', $this->pergunta));
    }

    public function test_autor_pode_excluir_sua_pergunta(): void
    {
        $this->actingAs($this->autor)->delete($this->url())
            ->assertRedirect(route('eventos.show', $this->evento->id));

        $this->assertDatabaseMissing('perguntas', ['id' => $this->pergunta->id]);
    }

    public function test_dono_do_evento_pode_excluir_pergunta_de_outro_usuario(): void
    {
        $this->actingAs($this->dono)->delete($this->url())
            ->assertRedirect(route('eventos.show', $this->evento->id));

        $this->assertDatabaseMissing('perguntas', ['id' => $this->pergunta->id]);
    }

    public function test_outro_usuario_recebe_403_e_pergunta_permanece_no_banco(): void
    {
        $this->actingAs($this->outro)->delete($this->url())->assertForbidden();

        $this->assertDatabaseHas('perguntas', ['id' => $this->pergunta->id]);
    }

    public function test_visitante_precisa_fazer_login_para_excluir(): void
    {
        $this->delete($this->url())->assertRedirect(route('login.create'));

        $this->assertDatabaseHas('perguntas', ['id' => $this->pergunta->id]);
    }

    public function test_autor_e_dono_do_evento_veem_botao_de_exclusao(): void
    {
        foreach ([$this->autor, $this->dono] as $user) {
            $this->actingAs($user)->get(route('eventos.show', $this->evento->id))
                ->assertOk()
                ->assertSee('Excluir Pergunta')
                ->assertSee($this->url(), false)
                ->assertSee('bg-red-600');
        }
    }

    public function test_outro_usuario_e_visitante_nao_veem_botao_de_exclusao(): void
    {
        $this->get(route('eventos.show', $this->evento->id))
            ->assertOk()->assertDontSee('Excluir Pergunta')->assertDontSee($this->url(), false);

        $this->actingAs($this->outro)->get(route('eventos.show', $this->evento->id))
            ->assertOk()->assertDontSee('Excluir Pergunta')->assertDontSee($this->url(), false);
    }

    public function test_pergunta_de_outro_evento_nao_pode_ser_excluida_pela_url(): void
    {
        $outroEvento = Evento::create([
            'titulo' => 'Outro evento',
            'user_id' => $this->autor->id,
        ]);

        $this->actingAs($this->autor)
            ->delete(route('eventos.perguntas.destroy', [$outroEvento->id, $this->pergunta->id]))
            ->assertNotFound();

        $this->assertDatabaseHas('perguntas', ['id' => $this->pergunta->id]);
        $this->get(route('eventos.show', $outroEvento->id))
            ->assertOk()->assertDontSee($this->pergunta->texto);
    }

    public function test_pergunta_inexistente_retorna_404(): void
    {
        $this->actingAs($this->autor)
            ->delete(route('eventos.perguntas.destroy', [$this->evento->id, 999]))
            ->assertNotFound();
    }

    public function test_pergunta_sem_autor_so_pode_ser_excluida_pelo_dono_do_evento(): void
    {
        $this->pergunta->update(['user_id' => null]);

        $this->assertFalse(Gate::forUser($this->autor)->allows('delete', $this->pergunta));
        $this->assertTrue(Gate::forUser($this->dono)->allows('delete', $this->pergunta));
    }
}
