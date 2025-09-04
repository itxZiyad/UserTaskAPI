<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Http\Requests\RegisterRequest;
use App\Http\Requests\LoginRequest;
use Illuminate\Support\Facades\Hash;
use PHPOpenSourceSaver\JWTAuth\Facades\JWTAuth;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\DB; // <-- Add this
use App\Mail\WelcomeMail;

class AuthController extends Controller
{
	public function register(RegisterRequest $request)
	{
		$user = User::create([
			'name' => $request->string('name'),
			'email' => $request->string('email'),
			'password' => Hash::make($request->string('password')),
			'role' => $request->input('role', 'user'),
		]);

		Mail::to((string)$user->email)->send(new WelcomeMail());

		$token = JWTAuth::fromUser($user);

		// Example: Call stored procedure after registration
		// Suppose your SP is `sp_log_registration` and expects user_id & role
		DB::statement('CALL sp_log_registration(?, ?)', [$user->id, $user->role]);

		return response()->json([
			'user' => $user,
			'token' => $token,
		], 201);
	}

	public function login(LoginRequest $request)
	{
		$credentials = $request->only(['email', 'password']);

		if (!$token = auth()->attempt($credentials)) {
			return response()->json(['message' => 'Invalid credentials'], 401);
		}

		$user = auth()->user();

		// Example: Call stored procedure after login
		// Suppose your SP is `sp_log_login` and expects user_id
		DB::statement('CALL sp_log_login(?)', [$user->id]);

		return response()->json([
			'token' => $token,
			'user' => $user,
		]);
	}
}
