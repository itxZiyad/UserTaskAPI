<?php

namespace Tests\Unit;

use Tests\TestCase;
use App\Models\User;
use App\Models\Task;
use App\Http\Controllers\TaskController;
use App\Http\Requests\StoreTaskRequest;
use App\Http\Requests\UpdateTaskRequest;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Auth;

class TaskControllerTest extends TestCase
{
    use RefreshDatabase;

    protected $taskController;
    protected $user;
    protected $admin;

    protected function setUp(): void
    {
        parent::setUp();
        $this->taskController = new TaskController();
        
        $this->user = User::factory()->create(['role' => 'user']);
        $this->admin = User::factory()->create(['role' => 'admin']);
    }

    /** @test */
    public function it_can_list_tasks_for_regular_user()
    {
        Auth::shouldReceive('user')->andReturn($this->user);
        
        // Create tasks for the user
        Task::factory()->count(3)->create(['user_id' => $this->user->id]);
        Task::factory()->count(2)->create(['user_id' => $this->admin->id]); // Other user's tasks

        $response = $this->taskController->index();

        $this->assertEquals(200, $response->getStatusCode());
        
        $responseData = json_decode($response->getContent(), true);
        $this->assertCount(3, $responseData); // Only user's own tasks
    }

    /** @test */
    public function it_can_list_all_tasks_for_admin()
    {
        Auth::shouldReceive('user')->andReturn($this->admin);
        
        // Create tasks for different users
        Task::factory()->count(2)->create(['user_id' => $this->user->id]);
        Task::factory()->count(3)->create(['user_id' => $this->admin->id]);

        $response = $this->taskController->index();

        $this->assertEquals(200, $response->getStatusCode());
        
        $responseData = json_decode($response->getContent(), true);
        $this->assertCount(5, $responseData); // All tasks
    }

    /** @test */
    public function it_can_create_a_new_task()
    {
        Auth::shouldReceive('user')->andReturn($this->user);

        $taskData = [
            'title' => 'Test Task',
            'description' => 'This is a test task',
            'status' => 'pending'
        ];

        $request = StoreTaskRequest::create('/api/tasks', 'POST', $taskData);
        $request->setContainer(app());
        $request->validateResolved();

        $response = $this->taskController->store($request);

        $this->assertEquals(201, $response->getStatusCode());
        
        $responseData = json_decode($response->getContent(), true);
        $this->assertEquals('Test Task', $responseData['title']);
        $this->assertEquals('This is a test task', $responseData['description']);
        $this->assertEquals('pending', $responseData['status']);
        $this->assertEquals($this->user->id, $responseData['user_id']);

        $this->assertDatabaseHas('tasks', [
            'title' => 'Test Task',
            'user_id' => $this->user->id
        ]);
    }

    /** @test */
    public function it_can_update_own_task()
    {
        $task = Task::factory()->create(['user_id' => $this->user->id]);
        
        Auth::shouldReceive('user')->andReturn($this->user);
        Auth::shouldReceive('id')->andReturn($this->user->id);

        $updateData = [
            'title' => 'Updated Task Title',
            'status' => 'completed'
        ];

        $request = UpdateTaskRequest::create("/api/tasks/{$task->id}", 'PUT', $updateData);
        $request->setContainer(app());
        $request->validateResolved();

        $response = $this->taskController->update($request, $task);

        $this->assertEquals(200, $response->getStatusCode());
        
        $responseData = json_decode($response->getContent(), true);
        $this->assertEquals('Updated Task Title', $responseData['title']);
        $this->assertEquals('completed', $responseData['status']);

        $this->assertDatabaseHas('tasks', [
            'id' => $task->id,
            'title' => 'Updated Task Title',
            'status' => 'completed'
        ]);
    }

    /** @test */
    public function it_can_update_any_task_as_admin()
    {
        $task = Task::factory()->create(['user_id' => $this->user->id]);
        
        Auth::shouldReceive('user')->andReturn($this->admin);
        Auth::shouldReceive('id')->andReturn($this->admin->id);

        $updateData = [
            'title' => 'Admin Updated Task',
            'status' => 'completed'
        ];

        $request = UpdateTaskRequest::create("/api/tasks/{$task->id}", 'PUT', $updateData);
        $request->setContainer(app());
        $request->validateResolved();

        $response = $this->taskController->update($request, $task);

        $this->assertEquals(200, $response->getStatusCode());
        
        $responseData = json_decode($response->getContent(), true);
        $this->assertEquals('Admin Updated Task', $responseData['title']);
    }

    /** @test */
    public function it_returns_403_when_user_tries_to_update_others_task()
    {
        $otherUser = User::factory()->create();
        $task = Task::factory()->create(['user_id' => $otherUser->id]);
        
        Auth::shouldReceive('user')->andReturn($this->user);
        Auth::shouldReceive('id')->andReturn($this->user->id);

        $updateData = [
            'title' => 'Unauthorized Update'
        ];

        $request = UpdateTaskRequest::create("/api/tasks/{$task->id}", 'PUT', $updateData);
        $request->setContainer(app());
        $request->validateResolved();

        $response = $this->taskController->update($request, $task);

        $this->assertEquals(403, $response->getStatusCode());
        
        $responseData = json_decode($response->getContent(), true);
        $this->assertEquals('Forbidden', $responseData['message']);
    }

    /** @test */
    public function it_can_delete_own_task()
    {
        $task = Task::factory()->create(['user_id' => $this->user->id]);
        
        Auth::shouldReceive('user')->andReturn($this->user);
        Auth::shouldReceive('id')->andReturn($this->user->id);

        $response = $this->taskController->destroy($task);

        $this->assertEquals(204, $response->getStatusCode());
        $this->assertDatabaseMissing('tasks', ['id' => $task->id]);
    }

    /** @test */
    public function it_can_delete_any_task_as_admin()
    {
        $task = Task::factory()->create(['user_id' => $this->user->id]);
        
        Auth::shouldReceive('user')->andReturn($this->admin);
        Auth::shouldReceive('id')->andReturn($this->admin->id);

        $response = $this->taskController->destroy($task);

        $this->assertEquals(204, $response->getStatusCode());
        $this->assertDatabaseMissing('tasks', ['id' => $task->id]);
    }

    /** @test */
    public function it_returns_403_when_user_tries_to_delete_others_task()
    {
        $otherUser = User::factory()->create();
        $task = Task::factory()->create(['user_id' => $otherUser->id]);
        
        Auth::shouldReceive('user')->andReturn($this->user);
        Auth::shouldReceive('id')->andReturn($this->user->id);

        $response = $this->taskController->destroy($task);

        $this->assertEquals(403, $response->getStatusCode());
        
        $responseData = json_decode($response->getContent(), true);
        $this->assertEquals('Forbidden', $responseData['message']);
        
        $this->assertDatabaseHas('tasks', ['id' => $task->id]); // Task should still exist
    }
}