<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;

class UsernameLoginTest extends TestCase
{
    use DatabaseTransactions;

    public function test_users_can_authenticate_using_username()
    {
        $user = User::factory()->create([
            'username' => 'testuser',
            'password' => bcrypt('password'),
        ]);

        $response = $this->post('/login', [
            'email' => 'testuser',
            'password' => 'password',
        ]);

        $this->assertAuthenticatedAs($user);
        $response->assertRedirect('/dashboard');
    }

    public function test_users_can_authenticate_using_email()
    {
        $user = User::factory()->create([
            'email' => 'test@example.com',
            'username' => 'testuser_email',
            'password' => bcrypt('password'),
        ]);

        $response = $this->post('/login', [
            'email' => 'test@example.com',
            'password' => 'password',
        ]);

        $this->assertAuthenticatedAs($user);
        $response->assertRedirect('/dashboard');
    }

    public function test_users_cannot_authenticate_using_space_username()
    {
        $user = User::factory()->create([
            'username' => ' ',
            'password' => bcrypt('password'),
        ]);

        $response = $this->post('/login', [
            'email' => ' ',
            'password' => 'password',
        ]);

        $this->assertGuest();
    }
}
