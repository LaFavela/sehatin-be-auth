<?php

namespace App\Http\Controllers;

use app\Enum\Otp as OtpEnum;
use App\Helper\CreateOtp;
use App\Http\Requests\LoginRequest;
use App\Http\Requests\LogoutRequest;
use app\Http\Requests\ResetPasswordRequest;
use App\Http\Resources\MessageResource;
use App\Http\Resources\UserResource;
use App\Models\Otp;
use GuzzleHttp\Exception\RequestException;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Mail;
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
                return (new MessageResource(null, false, 'User not found'))->response()->setStatusCode(401);
            }

            $user->load('roles');

            $roleName = $user->role ? $user->role->name : 'No Role Assigned';

            return (new MessageResource(null, true, 'Token verified'))->response()
                ->header('X-User-ID', $user->id)
                ->header('X-User-Role',$roleName)
                ->setStatusCode(200);
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

    /**
     * Handle user register
     */
    public function register(LoginRequest $request): JsonResponse
    {
        if (isset($request->validator) && $request->validator->fails()) {
            return (new MessageResource(null, false, 'Validation failed', $request->validator->messages()))->response()->setStatusCode(400);
        }

        $validatedData = $request->validated();

        try {
            $response = Http::post("http://user-service.default.svc.cluster.local:8000/api/users", [
                'email' => $validatedData['email'],
                'password' => $validatedData['password'],
            ]);

            error_log("User Service Response: " . $response->body());
            if (!$token = JWTAuth::attempt($validatedData)) {
                return (new MessageResource(null, false, 'Invalid Credentials'))->response()->setStatusCode(401);
            }

            $user = JWTAuth::claims(['user' => Auth::user()])->user();

            // create refresh token
            JWTAuth::setToken($token);
            $refreshToken = JWTAuth::refresh();


            $array = json_decode($response->body(), true);
            $data = $array['data'];

            $otpData = CreateOtp::createOtp($data['id'], OtpEnum::REGISTER->value);

            Otp::create([
                'otp' => $otpData['otp'],
                'expires_at' => $otpData['expires_at'],
                'token' => $otpData['token'],
            ])->only(['token', 'expires_at']);

            // combine user and token to json
            $combined = [
                'user' => new UserResource($user),
                'access_token' => $token,
                'refresh_token' => $refreshToken,
                'otp_token' => $otpData['token'],
            ];


            return (new MessageResource($combined, true, 'Register Success'))->response()->setStatusCode(200);
        } catch (JWTException $e) {
            return (new MessageResource(null, false, 'Could not create token', $e->getMessage()))->response()->setStatusCode(500);
        } catch (RequestException $e) {
            return (new MessageResource(null, false, 'Could not create user', $e->getMessage()))->response()->setStatusCode(500);
        } catch (\Exception $e) {
            return (new MessageResource(null, false, 'Could not create user', $e->getMessage()))->response()->setStatusCode(500);
        }
    }

    public function resetPassword(ResetPasswordRequest $request): JsonResponse
    {
        if (isset($request->validator) && $request->validator->fails()) {
            return (new MessageResource(null, false, 'Validation failed', $request->validator->messages()))->response()->setStatusCode(400);
        }

        $validatedData = $request->validated();

        try {
            $response = Http::get("http://user-service.default.svc.cluster.local:8000/api/users?email=" . $validatedData['email']);

            if ($response->notFound()) {
                return (new MessageResource(null, false, 'User not found'))->response()->setStatusCode(404);
            }

            if ($response->serverError()) {
                return (new MessageResource(null, false, 'Failed to reset password'))->response()->setStatusCode(500);
            }
            $array = json_decode($response->body(), true);
            $data = $array['data'][0];

            $otpData = CreateOtp::createOtp($data['id'], OtpEnum::FORGOT_PASSWORD->value);

            Otp::create([
                'otp' => $otpData['otp'],
                'expires_at' => $otpData['expires_at'],
                'token' => $otpData['token'],
            ])->only(['token', 'expires_at']);

//            Mail::raw("Your OTP is: $otp", function ($message) use ($user) {
//            $message->to($user->email)
//                ->subject('Your OTP Code');
//            });

            return (new MessageResource(null, true, 'Check email for reset OTP'))->response()->setStatusCode(200);
        } catch (RequestException $e) {
            return (new MessageResource(null, false, 'Could not reset password', $e->getMessage()))->response()->setStatusCode(500);
        } catch (\Exception $e) {
            return (new MessageResource(null, false, 'Could not reset password', $e->getMessage()))->response()->setStatusCode(500);
        }
    }

}
