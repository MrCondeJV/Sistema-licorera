<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ExampleTest extends TestCase
{
    use RefreshDatabase;

    public function test_the_application_redirects_unauthenticated_to_login(): void
    {
        $response = $this->get('/');
        $response->assertRedirect(route('login'));

        $loginResponse = $this->get(route('login'));
        $loginResponse->assertStatus(200);
    }
}
