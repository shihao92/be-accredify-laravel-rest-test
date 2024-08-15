<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;
use App\Models\User;

class AuthApiTest extends TestCase
{
    use RefreshDatabase;

    public function test_login_with_valid_credentials()
    {
        $user = User::factory()->create([
            'name' => 'Superadmin',
            'email' => 'superadmin@example.com',
            'password' => Hash::make('password'),
        ]);

        $response = $this->postJson('/api/login', [
            'email' => 'superadmin@example.com',
            'password' => 'password',
        ]);

        $response->assertStatus(200);

        $response->assertJsonStructure([
            'access_token',
        ]);
    }

    public function test_login_with_invalid_credentials()
    {
        $response = $this->postJson('/api/login', [
            'email' => 'superadmin@example.com',
            'password' => 'wrong-password',
        ]);

        $response->assertStatus(401);

        $response->assertJson([
            'error' => 'Unauthorized',
        ]);
    }

    public function test_access_protected_route_with_valid_token()
    {
        $user = User::factory()->create([
            'email' => 'superadmin@example.com',
            'password' => Hash::make('password'),
        ]);

        $token = $user->createToken('authToken')->plainTextToken;

        $response = $this->withHeader('Authorization', 'Bearer ' . $token)
                         ->getJson('/api/user');

        $response->assertStatus(200);

        $response->assertJson([
            'email' => 'superadmin@example.com',
        ]);
    }

    public function test_access_protected_route_without_token()
    {
        $response = $this->getJson('/api/user');

        $response->assertStatus(401);
    }
}
