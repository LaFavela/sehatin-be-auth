<?php

namespace App\Http\Controllers;

use App\Http\Requests\SendOtpRequest;
use App\Http\Requests\VerifyOtpRequest;
use App\Http\Resources\MessageResource;
use App\Models\Otp;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Carbon;
use Tymon\JWTAuth\Facades\JWTAuth;
use Tymon\JWTAuth\Facades\JWTFactory;
use Tymon\JWTAuth\JWT;
use function Laravel\Prompts\error;

class OtpController
{
    /**
     * Send OTP to the user's email
     */
    public function send(SendOtpRequest $request): JsonResponse
    {
        // Generate OTP
        try {
            $otp = str_pad(mt_rand(0, 999999), 6, '0', STR_PAD_LEFT);
            $expiresAt = Carbon::now()->addMinutes(5); // OTP valid for 5 minutes
            $payload = JWTFactory::expires_at($expiresAt)->make();
            $token = JWTAuth::encode($payload);

//        error_log(JWTAuth::decode($token));
            // Simpan OTP ke OTP
            $otp = Otp::create([
                'otp' => $otp,
                'expires_at' => $expiresAt,
                'token' => $token->get(),
            ])->only(['token', 'expires_at']);


            // Kirim OTP via email
//        Mail::raw("Your OTP is: $otp", function ($message) use ($user) {
//            $message->to($user->email)
//                ->subject('Your OTP Code');
//        });

            return (new MessageResource($otp, true, 'OTP sent successfully'))->response()->setStatusCode(200);
        } catch (\Throwable $th) {
            return (new MessageResource(null, false, 'Failed to send OTP', $th->getMessage()))->response()->setStatusCode(500);
        }
    }

    public function verify(VerifyOtpRequest $request, $session_token): JsonResponse
    {

        try {
            if (!$session = Otp::where('token', $session_token)->first()) {
                return (new MessageResource(null, false, 'Token is invalid'))->response()->setStatusCode(401);
            }

            if (isset($request->validator) && $request->validator->fails()) {
                return (new MessageResource(null, false, 'Validation failed', $request->validator->messages()))->response()->setStatusCode(400);
            }

            // Verify OTP
            $validatedData = $request->validated();

            if (Carbon::now()->greaterThan(Carbon::parse($session->expires_at))) {
                return (new MessageResource(null, false, 'OTP expired'))->response()->setStatusCode(400);
            }


            if ($validatedData['otp'] != $session->otp) {
                return (new MessageResource(null, false, 'Invalid OTP'))->response()->setStatusCode(400);
            }
            // parse email from token
            $payload = JWTAuth::getPayload($session_token);

            error_log($payload['user']['id']);

            User::where('id', $payload['user']['id'])->update([
                'email_verified_at' => Carbon::now(),
            ]);

            return (new MessageResource(null, true, 'OTP verified successfully'))->response()->setStatusCode(200);


        } catch (\Throwable $e) {
            return (new MessageResource(null, false, "Failed to verify OTP", $e->getMessage()))->response()->setStatusCode(500);
        }
    }


}
