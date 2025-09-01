<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use App\Mail\WelcomeMail;

class AuthFeatureTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    public function user_can_register_with_valid_data()
    {
        Mail::fake();

        $userData = [
            'name' => 'John Doe',
            'email' => 'john@example.com',
            'password' => 'password123',
            'password_confirmation' => 'password123',
            'role' => 'user'
        ];

        $response = $this->postJson('/api/register', $userData);

        $response->assertStatus(201)
                ->assertJsonStructure([
                    'user' => ['id', 'name', 'email', 'role', 'created_at', 'updated_at'],
                    'token'
                ])
                ->assertJson([
                    'user' => [
                        'name' => 'John Doe',
                        'email' => 'john@example.com',
                        'role' => 'user'
                    ]
                ]);

        $this->assertDatabaseHas('users', [
            'name' => 'John Doe',
            'email' => 'john@example.com',
            'role' => 'user'
        ]);

        // Verify welcome email was sent
        Mail::assertSent(WelcomeMail::class, function ($mail) use ($userData) {
            return $mail->hasTo($userData['email']);
        });
    }

    /** @test */
    public function user_can_register_as_admin()
    {
        Mail::fake();

        $userData = [
            'name' => 'Admin User',
            'email' => 'admin@example.com',
            'password' => 'password123',
            'password_confirmation' => 'password123',
            'role' => 'admin'
        ];

        $response = $this->postJson('/api/register', $userData);

        $response->assertStatus(201)
                ->assertJson([
                    'user' => [
                        'name' => 'Admin User',
                        'email' => 'admin@example.com',
                        'role' => 'admin'
                    ]
                ]);

        $this->assertDatabaseHas('users', [
            'name' => 'Admin User',
            'email' => 'admin@example.com',
            'role' => 'admin'
        ]);
    }

    /** @test */
    public function registration_fails_with_duplicate_email()
    {
        // Create existing user
        User::factory()->create(['email' => 'existing@example.com']);

        $userData = [
            'name' => 'New User',
            'email' => 'existing@example.com',
            'password' => 'password123',
            'password_confirmation' => 'password123'
        ];

        $response = $this->postJson('/api/register', $userData);

        $response->assertStatus(422)
                ->assertJsonValidationErrors(['email'])
                ->assertJson([
                    'errors' => [
                        'email' => ['This email address is already registered.']
                    ]
                ]);
    }

    /** @test */
    public function registration_fails_with_invalid_data()
    {
        $userData = [
            'name' => '',
            'email' => 'invalid-email',
            'password' => '123',
            'password_confirmation' => 'different'
        ];

        $response = $this->postJson('/api/register', $userData);

        $response->assertStatus(422)
                ->assertJsonValidationErrors(['name', 'email', 'password']);
    }

    /** @test */
    public function user_can_login_with_valid_credentials()
    {
        $user = User::factory()->create([
            'email' => 'test@example.com',
            'password' => Hash::make('password123')
        ]);

        $loginData = [
            'email' => 'test@example.com',
            'password' => 'password123'
        ];

        $response = $this->postJson('/api/login', $loginData);

        $response->assertStatus(200)
                ->assertJsonStructure([
                    'token',
                    'user' => ['id', 'name', 'email', 'role']
                ])
                ->assertJson([
                    'user' => [
                        'id' => $user->id,
                        'email' => 'test@example.com'
                    ]
                ]);

        $this->assertNotEmpty($response->json('token'));
    }

    /** @test */
    public function login_fails_with_invalid_credentials()
    {
        User::factory()->create([
            'email' => 'test@example.com',
            'password' => Hash::make('password123')
        ]);

        $loginData = [
            'email' => 'test@example.com',
            'password' => 'wrongpassword'
        ];

        $response = $this->postJson('/api/login', $loginData);

        $response->assertStatus(401)
                ->assertJson([
                    'message' => 'Invalid credentials'
                ]);
    }

    /** @test */
    public function login_fails_with_nonexistent_email()
    {
        $loginData = [
            'email' => 'nonexistent@example.com',
            'password' => 'password123'
        ];

        $response = $this->postJson('/api/login', $loginData);

        $response->assertStatus(401)
                ->assertJson([
                    'message' => 'Invalid credentials'
                ]);
    }

    /** @test */
    public function login_fails_with_invalid_data()
    {
        $loginData = [
            'email' => 'invalid-email',
            'password' => ''
        ];

        $response = $this->postJson('/api/login', $loginData);

        $response->assertStatus(422)
                ->assertJsonValidationErrors(['email', 'password']);
    }

    /** @test */
    public function registration_returns_jwt_token()
    {
        Mail::fake();

        $userData = [
            'name' => 'Token Test',
            'email' => 'token@example.com',
            'password' => 'password123',
            'password_confirmation' => 'password123'
        ];

        $response = $this->postJson('/api/register', $userData);

        $response->assertStatus(201);
        
        $token = $response->json('token');
        $this->assertNotEmpty($token);
        $this->assertIsString($token);
        
        // Verify token is valid JWT format (has 3 parts separated by dots)
        $tokenParts = explode('.', $token);
        $this->assertCount(3, $tokenParts);
    }

    /** @test */
    public function login_returns_jwt_token()
    {
        $user = User::factory()->create([
            'email' => 'logintoken@example.com',
            'password' => Hash::make('password123')
        ]);

        $loginData = [
            'email' => 'logintoken@example.com',
            'password' => 'password123'
        ];

        $response = $this->postJson('/api/login', $loginData);

        $response->assertStatus(200);
        
        $token = $response->json('token');
        $this->assertNotEmpty($token);
        $this->assertIsString($token);
        
        // Verify token is valid JWT format
        $tokenParts = explode('.', $token);
        $this->assertCount(3, $tokenParts);
    }
}