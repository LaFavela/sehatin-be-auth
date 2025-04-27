<?php

namespace App\Http\Controllers;

use App\Http\Requests\LoginRequest;
use App\Http\Requests\LogoutRequest;
use App\Http\Resources\MessageResource;
use App\Http\Resources\UserResource;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;
use Tymon\JWTAuth\Facades\JWTAuth;
use Tymon\JWTAuth\Exceptions\JWTException;

class AuthController
{

    /**
     * Handle user login
     */
    public function login(LoginRequest $request): JsonResponse
    {
        if (isset($request->validator) && $request->validator->fails()) {
            return (new MessageResource(null, false, 'Validation failed', $request->validator->messages()))->response()->setStatusCode(400);
        }

        $validatedData = $request->validated();

        try {
            if (!$token = JWTAuth::attempt($validatedData)) {
                return (new MessageResource(null, false, 'Invalid Credentials'))->response()->setStatusCode(401);
            }

            $user = JWTAuth::claims(['user' => Auth::user()])->user();

            // create refresh token
            JWTAuth::setToken($token);
            $refreshToken = JWTAuth::refresh();

            // combine user and token to json
            $combined = [
                'user' => new UserResource($user),
                'access_token' => $token,
                'refresh_token' => $refreshToken,
                'expires_in' => config('jwt.ttl'),
                'token_type' => 'Bearer',
            ];

            return (new MessageResource($combined, true, 'Login Success'))->response()->setStatusCode(200);
        } catch (JWTException $e) {
            return (new MessageResource(null, false, 'Could not create token', $e->getMessage()))->response()->setStatusCode(500);
        }

    }

    /**
     * Handle refresh token
     *
     * @return JsonResponse
     */
    public function refresh(): JsonResponse
    {
        try {
            $newToken = JWTAuth::parseToken()->refresh();

            return (new MessageResource([
                'access_token' => $newToken,
                'token_type' => 'Bearer',
                'expires_in' => config('jwt.ttl'),
            ], true, 'Token refreshed'))->response()->setStatusCode(200);
        } catch (JWTException $e) {
            return (new MessageResource(null, false, 'Could not refresh token', $e->getMessage()))->response()->setStatusCode(500);
        }
    }

    /**
     * Verify JWT Token
     *
     * @return JsonResponse
     */
    public function verify(): JsonResponse
    {
        try {
            $user = JWTAuth::parseToken()->authenticate();

            if (!$user) {
                return (new MessageResource(null, false, 'User not found'))->response()
                    ->header('X-User-ID', $user->id)
                    ->header('X-User-Email', $user->email)
                    ->setStatusCode(401);
            }

            return (new MessageResource(new UserResource($user), true, 'Token verified'))->response()->setStatusCode(200);
        } catch (JWTException $e) {
            return (new MessageResource(null, false, 'Could not verify token', $e->getMessage()))->response()->setStatusCode(401);
        }

    }

    /**
     * Handle user logout
     */
    public function logout(LogoutRequest $request): JsonResponse
    {
        if (isset($request->validator) && $request->validator->fails()) {
            return (new MessageResource(null, false, 'Validation failed', $request->validator->messages()))->response()->setStatusCode(400);
        }

        // Revoke all tokens for the user jwtauth
        try {
            $forever = true;

            JWTAuth::parseToken()->invalidate($forever);

        } catch (JWTException $e) {
            return (new MessageResource(null, false, 'Failed to logout', $e->getMessage()))->response()->setStatusCode(500);
        }

        return (new MessageResource(null, true, 'Logout successful'))->response()->setStatusCode(200);
    }
}
