<?php

namespace Tests\Feature;

use App\Enums\Rol;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Feature\Concerns\DatosDelTaller;
use Tests\TestCase;

class AutenticacionTest extends TestCase
{
    use DatosDelTaller, RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->cargarCatalogos();
    }

    public function test_un_invitado_es_enviado_al_login(): void
    {
        $this->get('/')->assertRedirect('/login');
        $this->get('/ordenes')->assertRedirect('/login');
    }

    public function test_inicia_sesion_con_datos_correctos(): void
    {
        $user = $this->usuario(Rol::Recepcionista);

        $this->post('/login', ['email' => $user->email, 'password' => 'password'])
            ->assertRedirect(route('dashboard'));

        $this->assertAuthenticatedAs($user);
    }

    public function test_rechaza_contrasena_incorrecta(): void
    {
        $user = $this->usuario(Rol::Recepcionista);

        $this->post('/login', ['email' => $user->email, 'password' => 'otra'])
            ->assertSessionHasErrors('email');

        $this->assertGuest();
    }

    public function test_un_usuario_desactivado_no_puede_entrar(): void
    {
        $user = $this->usuario(Rol::Mecanico, ['activo' => false]);

        $this->post('/login', ['email' => $user->email, 'password' => 'password'])
            ->assertSessionHasErrors('email');

        $this->assertGuest();
    }

    public function test_cierra_sesion(): void
    {
        $this->actingAs($this->usuario(Rol::Administrador))
            ->post('/logout')
            ->assertRedirect('/login');

        $this->assertGuest();
    }

    public function test_el_tablero_carga_para_cada_rol(): void
    {
        foreach (Rol::cases() as $rol) {
            $this->actingAs($this->usuario($rol))->get('/')->assertOk()->assertSee('Vehículos en el taller');
        }
    }
}
