<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ExampleTest extends TestCase
{
    use RefreshDatabase;

    public function test_services_api_returns_a_successful_response(): void
    {
        $response = $this->getJson('/api/services');

        $response
            ->assertOk()
            ->assertJsonPath('status', true)
            ->assertJsonStructure([
                'status',
                'data',
            ]);
    }
}
