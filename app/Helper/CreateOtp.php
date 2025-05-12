<?php

namespace App\Helper;

use Illuminate\Support\Carbon;
use Tymon\JWTAuth\Facades\JWTAuth;
use Tymon\JWTAuth\Facades\JWTFactory;

class CreateOtp
{
    public static function createOtp(string $id, string $type) : array
    {
        $otp = str_pad(mt_rand(0, 999999), 6, '0', STR_PAD_LEFT);
        $expiresAt = Carbon::now()->addMinutes(5); // OTP valid for 5 minutes
        $payload = [
            'sub' => $id,
            'type' => $type,
            'exp' => $expiresAt->timestamp,
        ];
        $jwtFactory = JWTFactory::customClaims($payload)->expires_at($expiresAt)->make();
        $token = JWTAuth::encode($jwtFactory);

        // create json object
        return [
            'id' => $id,
            'otp' => $otp,
            'expires_at' => $expiresAt,
            'token' => $token->get(),
            'type' => $type,
        ];
    }
}
