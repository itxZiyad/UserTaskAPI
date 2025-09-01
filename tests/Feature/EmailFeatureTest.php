<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use App\Mail\WelcomeMail;

class EmailFeatureTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    public function welcome_email_is_sent_on_registration()
    {
        Mail::fake();

        $userData = [
            'name' => 'John Doe',
            'email' => 'john@example.com',
            'password' => 'password123',
            'password_confirmation' => 'password123'
        ];

        $response = $this->postJson('/api/register', $userData);

        $response->assertStatus(201);

        // Assert that welcome email was sent
        Mail::assertSent(WelcomeMail::class, function ($mail) use ($userData) {
            return $mail->hasTo($userData['email']);
        });

        // Assert that only one email was sent
        Mail::assertSent(WelcomeMail::class, 1);
    }

    /** @test */
    public function welcome_email_contains_correct_content()
    {
        Mail::fake();

        $userData = [
            'name' => 'Jane Smith',
            'email' => 'jane@example.com',
            'password' => 'password123',
            'password_confirmation' => 'password123'
        ];

        $response = $this->postJson('/api/register', $userData);

        $response->assertStatus(201);

        Mail::assertSent(WelcomeMail::class, function ($mail) use ($userData) {
            // Check if email is sent to correct recipient
            $hasCorrectRecipient = $mail->hasTo($userData['email']);
            
            // Check if email has correct subject
            $hasCorrectSubject = $mail->envelope()->subject === 'Welcome Mail';
            
            return $hasCorrectRecipient && $hasCorrectSubject;
        });
    }

    /** @test */
    public function welcome_email_is_not_sent_on_failed_registration()
    {
        Mail::fake();

        // Try to register with invalid data
        $userData = [
            'name' => '',
            'email' => 'invalid-email',
            'password' => '123',
            'password_confirmation' => 'different'
        ];

        $response = $this->postJson('/api/register', $userData);

        $response->assertStatus(422);

        // Assert that no welcome email was sent
        Mail::assertNotSent(WelcomeMail::class);
    }

    /** @test */
    public function welcome_email_is_not_sent_on_duplicate_email()
    {
        Mail::fake();

        // Create existing user
        User::factory()->create(['email' => 'existing@example.com']);

        // Try to register with same email
        $userData = [
            'name' => 'New User',
            'email' => 'existing@example.com',
            'password' => 'password123',
            'password_confirmation' => 'password123'
        ];

        $response = $this->postJson('/api/register', $userData);

        $response->assertStatus(422);

        // Assert that no welcome email was sent
        Mail::assertNotSent(WelcomeMail::class);
    }

    /** @test */
    public function multiple_registrations_send_multiple_emails()
    {
        Mail::fake();

        $users = [
            [
                'name' => 'User One',
                'email' => 'user1@example.com',
                'password' => 'password123',
                'password_confirmation' => 'password123'
            ],
            [
                'name' => 'User Two',
                'email' => 'user2@example.com',
                'password' => 'password123',
                'password_confirmation' => 'password123'
            ],
            [
                'name' => 'User Three',
                'email' => 'user3@example.com',
                'password' => 'password123',
                'password_confirmation' => 'password123'
            ]
        ];

        foreach ($users as $userData) {
            $response = $this->postJson('/api/register', $userData);
            $response->assertStatus(201);
        }

        // Assert that 3 welcome emails were sent
        Mail::assertSent(WelcomeMail::class, 3);

        // Assert each email was sent to correct recipient
        foreach ($users as $userData) {
            Mail::assertSent(WelcomeMail::class, function ($mail) use ($userData) {
                return $mail->hasTo($userData['email']);
            });
        }
    }

    /** @test */
    public function welcome_email_uses_correct_mail_class()
    {
        Mail::fake();

        $userData = [
            'name' => 'Test User',
            'email' => 'test@example.com',
            'password' => 'password123',
            'password_confirmation' => 'password123'
        ];

        $response = $this->postJson('/api/register', $userData);

        $response->assertStatus(201);

        // Assert that WelcomeMail class is used
        Mail::assertSent(WelcomeMail::class);
    }

    /** @test */
    public function welcome_email_handles_special_characters_in_name()
    {
        Mail::fake();

        $userData = [
            'name' => 'José María O\'Connor-Smith',
            'email' => 'jose@example.com',
            'password' => 'password123',
            'password_confirmation' => 'password123'
        ];

        $response = $this->postJson('/api/register', $userData);

        $response->assertStatus(201);

        Mail::assertSent(WelcomeMail::class, function ($mail) use ($userData) {
            return $mail->hasTo($userData['email']);
        });
    }

    /** @test */
    public function welcome_email_handles_international_email_addresses()
    {
        Mail::fake();

        $userData = [
            'name' => 'Test User',
            'email' => 'test+tag@example.co.uk',
            'password' => 'password123',
            'password_confirmation' => 'password123'
        ];

        $response = $this->postJson('/api/register', $userData);

        $response->assertStatus(201);

        Mail::assertSent(WelcomeMail::class, function ($mail) use ($userData) {
            return $mail->hasTo($userData['email']);
        });
    }
}