<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Http;

class AuthController extends Controller
{
    public function register(Request $request)
    {
        $validated = $request->validate([
            'name'                  => 'required|string|max:255',
            'phone_number'          => 'required|string|max:20',
            'email'                 => 'required|string|email|max:255|unique:users',
            'password'              => 'required|string|min:8|confirmed',
        ]);

        $user = User::create([
            'name'         => $validated['name'],
            'phone_number' => $validated['phone_number'],
            'email'        => $validated['email'],
            'password'     => $validated['password'],
            'role'         => 'passenger',
        ]);

        $token = $user->createToken('auth_token')->plainTextToken;

        return response()->json([
            'message' => 'User registered successfully',
            'user'    => $user->fresh(),
            'token'   => $token,
        ], 201);
    }

    public function login(Request $request)
    {
        $credentials = $request->validate([
            'email'    => 'required|string|email',
            'password' => 'required|string',
        ]);

        if (!Auth::attempt($credentials)) {
            return response()->json(['message' => 'Invalid credentials'], 401);
        }

        $token = $request->user()->createToken('auth_token')->plainTextToken;

        return response()->json([
            'message' => 'Login successful',
            'token'   => $token,
            'user'    => $request->user(),
        ]);
    }

    public function googleSignIn(Request $request)
    {
        $validated = $request->validate([
            'id_token' => 'required|string',
        ]);

        $googleResponse = Http::timeout(10)
            ->acceptJson()
            ->get('https://oauth2.googleapis.com/tokeninfo', [
                'id_token' => $validated['id_token'],
            ]);

        if (!$googleResponse->ok()) {
            return response()->json(['message' => 'Invalid Google token'], 422);
        }

        $payload = $googleResponse->json();
        $clientId = env('GOOGLE_CLIENT_ID');

        if ($clientId && ($payload['aud'] ?? null) !== $clientId) {
            return response()->json(['message' => 'Google token audience mismatch'], 422);
        }

        if (($payload['email_verified'] ?? 'false') !== 'true') {
            return response()->json(['message' => 'Google email is not verified'], 422);
        }

        $googleId = $payload['sub'] ?? null;
        $email = $payload['email'] ?? null;
        $name = $payload['name'] ?? 'Google User';

        if (!$googleId || !$email) {
            return response()->json(['message' => 'Google profile is incomplete'], 422);
        }

        $user = User::where('google_id', $googleId)
            ->orWhere('email', $email)
            ->first();

        if ($user) {
            if (!$user->google_id) {
                $user->google_id = $googleId;
                $user->save();
            }
        } else {
            $user = User::create([
                'name' => $name,
                'email' => $email,
                'phone_number' => null,
                'google_id' => $googleId,
                'password' => null,
                'role' => 'passenger',
            ]);
        }

        $token = $user->createToken('auth_token')->plainTextToken;

        return response()->json([
            'message' => 'Google sign in successful',
            'user' => $user,
            'token' => $token,
        ]);
    }

    public function logout(Request $request)
    {
        $request->user()->currentAccessToken()->delete();

        return response()->json(['message' => 'Logout successful']);
    }
}
