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

        return [
            'success' => true,
            'message' => $this->resource['message'],
            'verified' => $this->resource['verified'],
            'access_token' => $token,
            'token_type' => $token ? 'Bearer' : null,
            'expires_at' => null,
            'user' => new AuthUserResource($user),
            'followed_shops' => $followedShops,
            'data' => [
                'token' => $token,
                'token_type' => $token ? 'Bearer' : null,
                'expires_at' => null,
                'user' => new AuthUserResource($user),
                'followed_shops' => $followedShops,
            ],
        ];
    }
}
