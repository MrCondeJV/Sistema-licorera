<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ExampleTest extends TestCase
{
    use RefreshDatabase;

    public function test_the_application_redirects_unauthenticated_to_login(): void
    {
        $response = $this->get('/pos');
        $response->assertRedirect(route('login'));

        $rootResponse = $this->get('/');
        $rootResponse->assertRedirect(route('login'));

        $loginResponse = $this->get(route('login'));
        $loginResponse->assertStatus(200);
    }
}
