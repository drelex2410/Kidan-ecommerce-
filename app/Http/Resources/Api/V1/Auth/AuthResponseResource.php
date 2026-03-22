<?php

namespace App\Http\Resources\Api\V1\Auth;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class AuthResponseResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $user = $this->resource['user'];
        $token = $this->resource['token'] ?? null;
        $followedShops = $this->resource['followed_shops'] ?? [];
        $verified = (bool) ($this->resource['verified'] ?? false);
        $requiresVerification = (bool) ($this->resource['requires_verification'] ?? !$verified);
        $verificationChannel = $this->resource['channel'] ?? null;
        $verificationTarget = $this->resource['target'] ?? null;

        return [
            'success' => true,
            'message' => $this->resource['message'],
            'verified' => $verified,
            'requires_verification' => $requiresVerification,
            'verification_channel' => $verificationChannel,
            'verification_target' => $verificationTarget,
            'access_token' => $token,
            'token_type' => $token ? 'Bearer' : null,
            'expires_at' => null,
            'user' => new AuthUserResource($user),
            'followed_shops' => $followedShops,
            'data' => [
                'token' => $token,
                'token_type' => $token ? 'Bearer' : null,
                'expires_at' => null,
                'verified' => $verified,
                'requires_verification' => $requiresVerification,
                'verification_channel' => $verificationChannel,
                'verification_target' => $verificationTarget,
                'user' => new AuthUserResource($user),
                'followed_shops' => $followedShops,
            ],
        ];
    }
}
