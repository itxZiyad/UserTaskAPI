<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\User;
use App\Models\Task;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;

class TaskFeatureTest extends TestCase
{
    use RefreshDatabase;

    protected $user;
    protected $admin;
    protected $otherUser;

    protected function setUp(): void
    {
        parent::setUp();
        
        $this->user = User::factory()->create(['role' => 'user']);
        $this->admin = User::factory()->create(['role' => 'admin']);
        $this->otherUser = User::factory()->create(['role' => 'user']);
    }

    protected function getAuthToken($user)
    {
        $response = $this->postJson('/api/login', [
            'email' => $user->email,
            'password' => 'password' // Default factory password
        ]);
        
        return $response->json('token');
    }

    /** @test */
    public function user_can_list_own_tasks()
    {
        // Create tasks for different users
        Task::factory()->count(3)->create(['user_id' => $this->user->id]);
        Task::factory()->count(2)->create(['user_id' => $this->otherUser->id]);

        $token = $this->getAuthToken($this->user);

        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $token
        ])->getJson('/api/tasks');

        $response->assertStatus(200);
        
        $tasks = $response->json();
        $this->assertCount(3, $tasks); // Only user's own tasks
        
        foreach ($tasks as $task) {
            $this->assertEquals($this->user->id, $task['user_id']);
        }
    }

    /** @test */
    public function admin_can_list_all_tasks()
    {
        // Create tasks for different users
        Task::factory()->count(2)->create(['user_id' => $this->user->id]);
        Task::factory()->count(3)->create(['user_id' => $this->otherUser->id]);
        Task::factory()->count(1)->create(['user_id' => $this->admin->id]);

        $token = $this->getAuthToken($this->admin);

        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $token
        ])->getJson('/api/tasks');

        $response->assertStatus(200);
        
        $tasks = $response->json();
        $this->assertCount(6, $tasks); // All tasks
    }

    /** @test */
    public function user_can_create_task()
    {
        $token = $this->getAuthToken($this->user);

        $taskData = [
            'title' => 'New Task',
            'description' => 'This is a new task',
            'status' => 'pending'
        ];

        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $token
        ])->postJson('/api/tasks', $taskData);

        $response->assertStatus(201)
                ->assertJsonStructure([
                    'id', 'title', 'description', 'status', 'user_id', 'created_at', 'updated_at'
                ])
                ->assertJson([
                    'title' => 'New Task',
                    'description' => 'This is a new task',
                    'status' => 'pending',
                    'user_id' => $this->user->id
                ]);

        $this->assertDatabaseHas('tasks', [
            'title' => 'New Task',
            'user_id' => $this->user->id
        ]);
    }

    /** @test */
    public function task_creation_fails_with_invalid_data()
    {
        $token = $this->getAuthToken($this->user);

        $taskData = [
            'title' => 'ab', // Too short
            'description' => str_repeat('a', 2001), // Too long
            'status' => 'invalid_status'
        ];

        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $token
        ])->postJson('/api/tasks', $taskData);

        $response->assertStatus(422)
                ->assertJsonValidationErrors(['title', 'description', 'status']);
    }

    /** @test */
    public function user_can_update_own_task()
    {
        $task = Task::factory()->create([
            'user_id' => $this->user->id,
            'title' => 'Original Title',
            'status' => 'pending'
        ]);

        $token = $this->getAuthToken($this->user);

        $updateData = [
            'title' => 'Updated Title',
            'status' => 'completed'
        ];

        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $token
        ])->putJson("/api/tasks/{$task->id}", $updateData);

        $response->assertStatus(200)
                ->assertJson([
                    'id' => $task->id,
                    'title' => 'Updated Title',
                    'status' => 'completed'
                ]);

        $this->assertDatabaseHas('tasks', [
            'id' => $task->id,
            'title' => 'Updated Title',
            'status' => 'completed'
        ]);
    }

    /** @test */
    public function admin_can_update_any_task()
    {
        $task = Task::factory()->create([
            'user_id' => $this->user->id,
            'title' => 'Original Title'
        ]);

        $token = $this->getAuthToken($this->admin);

        $updateData = [
            'title' => 'Admin Updated Title'
        ];

        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $token
        ])->putJson("/api/tasks/{$task->id}", $updateData);

        $response->assertStatus(200)
                ->assertJson([
                    'id' => $task->id,
                    'title' => 'Admin Updated Title'
                ]);
    }

    /** @test */
    public function user_cannot_update_others_task()
    {
        $task = Task::factory()->create(['user_id' => $this->otherUser->id]);

        $token = $this->getAuthToken($this->user);

        $updateData = [
            'title' => 'Unauthorized Update'
        ];

        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $token
        ])->putJson("/api/tasks/{$task->id}", $updateData);

        $response->assertStatus(403)
                ->assertJson([
                    'message' => 'Forbidden'
                ]);

        // Verify task wasn't updated
        $this->assertDatabaseMissing('tasks', [
            'id' => $task->id,
            'title' => 'Unauthorized Update'
        ]);
    }

    /** @test */
    public function user_can_delete_own_task()
    {
        $task = Task::factory()->create(['user_id' => $this->user->id]);

        $token = $this->getAuthToken($this->user);

        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $token
        ])->deleteJson("/api/tasks/{$task->id}");

        $response->assertStatus(204);
        
        $this->assertDatabaseMissing('tasks', ['id' => $task->id]);
    }

    /** @test */
    public function admin_can_delete_any_task()
    {
        $task = Task::factory()->create(['user_id' => $this->user->id]);

        $token = $this->getAuthToken($this->admin);

        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $token
        ])->deleteJson("/api/tasks/{$task->id}");

        $response->assertStatus(204);
        
        $this->assertDatabaseMissing('tasks', ['id' => $task->id]);
    }

    /** @test */
    public function user_cannot_delete_others_task()
    {
        $task = Task::factory()->create(['user_id' => $this->otherUser->id]);

        $token = $this->getAuthToken($this->user);

        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $token
        ])->deleteJson("/api/tasks/{$task->id}");

        $response->assertStatus(403)
                ->assertJson([
                    'message' => 'Forbidden'
                ]);

        // Verify task still exists
        $this->assertDatabaseHas('tasks', ['id' => $task->id]);
    }

    /** @test */
    public function unauthenticated_user_cannot_access_tasks()
    {
        $response = $this->getJson('/api/tasks');
        $response->assertStatus(401);

        $response = $this->postJson('/api/tasks', ['title' => 'Test']);
        $response->assertStatus(401);

        $task = Task::factory()->create();
        $response = $this->putJson("/api/tasks/{$task->id}", ['title' => 'Test']);
        $response->assertStatus(401);

        $response = $this->deleteJson("/api/tasks/{$task->id}");
        $response->assertStatus(401);
    }

    /** @test */
    public function task_update_fails_with_invalid_data()
    {
        $task = Task::factory()->create(['user_id' => $this->user->id]);
        $token = $this->getAuthToken($this->user);

        $updateData = [
            'title' => 'ab', // Too short
            'status' => 'invalid_status'
        ];

        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $token
        ])->putJson("/api/tasks/{$task->id}", $updateData);

        $response->assertStatus(422)
                ->assertJsonValidationErrors(['title', 'status']);
    }

    /** @test */
    public function tasks_are_returned_in_latest_order()
    {
        // Create tasks with different timestamps
        $task1 = Task::factory()->create([
            'user_id' => $this->user->id,
            'title' => 'First Task',
            'created_at' => now()->subMinutes(10)
        ]);
        
        $task2 = Task::factory()->create([
            'user_id' => $this->user->id,
            'title' => 'Second Task',
            'created_at' => now()->subMinutes(5)
        ]);
        
        $task3 = Task::factory()->create([
            'user_id' => $this->user->id,
            'title' => 'Third Task',
            'created_at' => now()
        ]);

        $token = $this->getAuthToken($this->user);

        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $token
        ])->getJson('/api/tasks');

        $response->assertStatus(200);
        
        $tasks = $response->json();
        $this->assertCount(3, $tasks);
        
        // Verify order (latest first)
        $this->assertEquals('Third Task', $tasks[0]['title']);
        $this->assertEquals('Second Task', $tasks[1]['title']);
        $this->assertEquals('First Task', $tasks[2]['title']);
    }
}