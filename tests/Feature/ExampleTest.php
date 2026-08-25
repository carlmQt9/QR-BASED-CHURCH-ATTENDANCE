<?php

namespace Tests\Feature;

// use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ExampleTest extends TestCase
{
    /**
     * A basic test example.
     */
    public function test_the_application_opens_at_login(): void
    {
        $response = $this->get('/');

        $response->assertRedirect('/login');
        $this->get('/login')->assertOk();
    }
}
