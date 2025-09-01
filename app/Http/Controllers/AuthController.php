<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Http\Requests\RegisterRequest;
use App\Http\Requests\LoginRequest;
use Illuminate\Support\Facades\Hash;
use PHPOpenSourceSaver\JWTAuth\Facades\JWTAuth;
use Illuminate\Support\Facades\Mail;
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

		return response()->json([
			'token' => $token,
			'user' => auth()->user(),
		]);
	}
}
