<?php

namespace Tests\Feature;

use Tests\TestCase;

class ExampleTest extends TestCase
{
    public function test_la_pagina_de_inicio_pide_iniciar_sesion(): void
    {
        $this->get('/')->assertRedirect(route('login'));
    }
}
