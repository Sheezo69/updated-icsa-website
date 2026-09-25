<?php

namespace Tests\Feature;

// use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ExampleTest extends TestCase
{
    /**
     * A basic test example.
     */
    public function test_the_application_returns_a_successful_response(): void
    {
        $response = $this->get('/');

        $response->assertStatus(200)
            ->assertSee('ICSA learning network')
            ->assertSee('Interactive map of ICSA learning paths')
            ->assertSee('Public learning paths · no personal data')
            ->assertSee('data-learning-network', false);
    }
}
