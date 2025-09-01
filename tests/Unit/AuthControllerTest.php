<?php

namespace Tests\Unit;

use Tests\TestCase;
use App\Models\User;
use App\Http\Controllers\AuthController;
use App\Http\Requests\RegisterRequest;
use App\Http\Requests\LoginRequest;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use App\Mail\WelcomeMail;
use PHPOpenSourceSaver\JWTAuth\Facades\JWTAuth;

class AuthControllerTest extends TestCase
{
    use RefreshDatabase;

    protected $authController;

    protected function setUp(): void
    {
        parent::setUp();
        $this->authController = new AuthController();
    }

    /** @test */
    public function it_can_register_a_new_user()
    {
        Mail::fake();

        $userData = [
            'name' => 'John Doe',
            'email' => 'john@example.com',
            'password' => 'password123',
            'password_confirmation' => 'password123',
            'role' => 'user'
        ];

        $request = RegisterRequest::create('/api/register', 'POST', $userData);
        $request->setContainer(app());
        $request->validateResolved();

        $response = $this->authController->register($request);

        $this->assertEquals(201, $response->getStatusCode());
        
        $responseData = json_decode($response->getContent(), true);
        $this->assertArrayHasKey('user', $responseData);
        $this->assertArrayHasKey('token', $responseData);
        $this->assertEquals('John Doe', $responseData['user']['name']);
        $this->assertEquals('john@example.com', $responseData['user']['email']);

        // Verify user was created in database
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
    public function it_can_register_an_admin_user()
    {
        Mail::fake();

        $userData = [
            'name' => 'Admin User',
            'email' => 'admin@example.com',
            'password' => 'password123',
            'password_confirmation' => 'password123',
            'role' => 'admin'
        ];

        $request = RegisterRequest::create('/api/register', 'POST', $userData);
        $request->setContainer(app());
        $request->validateResolved();

        $response = $this->authController->register($request);

        $this->assertEquals(201, $response->getStatusCode());
        
        $responseData = json_decode($response->getContent(), true);
        $this->assertEquals('admin', $responseData['user']['role']);

        $this->assertDatabaseHas('users', [
            'name' => 'Admin User',
            'email' => 'admin@example.com',
            'role' => 'admin'
        ]);
    }

    /** @test */
    public function it_can_login_with_valid_credentials()
    {
        $user = User::factory()->create([
            'email' => 'test@example.com',
            'password' => Hash::make('password123')
        ]);

        $loginData = [
            'email' => 'test@example.com',
            'password' => 'password123'
        ];

        $request = LoginRequest::create('/api/login', 'POST', $loginData);
        $request->setContainer(app());
        $request->validateResolved();

        $response = $this->authController->login($request);

        $this->assertEquals(200, $response->getStatusCode());
        
        $responseData = json_decode($response->getContent(), true);
        $this->assertArrayHasKey('token', $responseData);
        $this->assertArrayHasKey('user', $responseData);
        $this->assertEquals($user->id, $responseData['user']['id']);
    }

    /** @test */
    public function it_returns_401_for_invalid_credentials()
    {
        User::factory()->create([
            'email' => 'test@example.com',
            'password' => Hash::make('password123')
        ]);

        $loginData = [
            'email' => 'test@example.com',
            'password' => 'wrongpassword'
        ];

        $request = LoginRequest::create('/api/login', 'POST', $loginData);
        $request->setContainer(app());
        $request->validateResolved();

        $response = $this->authController->login($request);

        $this->assertEquals(401, $response->getStatusCode());
        
        $responseData = json_decode($response->getContent(), true);
        $this->assertEquals('Invalid credentials', $responseData['message']);
    }

    /** @test */
    public function it_returns_401_for_nonexistent_user()
    {
        $loginData = [
            'email' => 'nonexistent@example.com',
            'password' => 'password123'
        ];

        $request = LoginRequest::create('/api/login', 'POST', $loginData);
        $request->setContainer(app());
        $request->validateResolved();

        $response = $this->authController->login($request);

        $this->assertEquals(401, $response->getStatusCode());
        
        $responseData = json_decode($response->getContent(), true);
        $this->assertEquals('Invalid credentials', $responseData['message']);
    }
}

