<?php

namespace Tests\Feature;

use Tests\TestCase;

class HomePageTest extends TestCase
{
    public function test_home_shows_tasks_screen(): void
    {
        $this->get('/')->assertOk()->assertSee('Tareas API')->assertSee('demo@example.com');
    }
}
