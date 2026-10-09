<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SessionSecurityTest extends TestCase
{
    use RefreshDatabase;

    public function test_mutating_request_without_csrf_token_expires(): void
    {
        $this->app->instance('env', 'local');
        $this->post('/login', ['email' => 'user@example.test', 'password' => 'invalid'])->assertStatus(419);
    }

    public function test_valid_csrf_token_allows_validation(): void
    {
        $this->app->instance('env', 'local');
        $this->withSession(['_token' => 'test-csrf-token'])->post('/register', ['_token' => 'test-csrf-token'])->assertSessionHasErrors(['name', 'email', 'password']);
    }
}
