<?php

namespace App\Http\Controllers;

use App\Models\Task;
use App\Http\Requests\StoreTaskRequest;
use App\Http\Requests\UpdateTaskRequest;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Gate;

class TaskController extends Controller
{
	public function index()
	{
		$user = Auth::user();
		$tasks = $user->role === 'admin' ? Task::query()->latest()->get() : $user->tasks()->latest()->get();
		return response()->json($tasks);
	}

	public function store(StoreTaskRequest $request)
	{
		$task = Auth::user()->tasks()->create([
			'title' => $request->string('title'),
			'description' => $request->input('description'),
			'status' => $request->input('status', 'pending'),
		]);

		return response()->json($task, 201);
	}

	public function update(UpdateTaskRequest $request, Task $task)
	{
		if (Auth::user()->role !== 'admin' && Auth::id() !== $task->user_id) {
			return response()->json(['message' => 'Forbidden'], 403);
		}

		$task->update($request->validated());
		return response()->json($task);
	}

	public function destroy(Task $task)
	{
		if (Auth::user()->role !== 'admin' && Auth::id() !== $task->user_id) {
			return response()->json(['message' => 'Forbidden'], 403);
		}
		$task->delete();
		return response()->json(null, 204);
	}
}
