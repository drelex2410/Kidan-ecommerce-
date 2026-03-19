<?php

namespace App\Http\Resources\Api\V1\Auth;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class UserInfoResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'success' => true,
            'user' => new AuthUserResource($this->resource['user']),
            'followed_shops' => $this->resource['followed_shops'],
            'permissions' => $this->resource['permissions'],
            'profile' => $this->resource['profile'],
            'data' => [
                'is_authenticated' => true,
                'user' => new AuthUserResource($this->resource['user']),
                'followed_shops' => $this->resource['followed_shops'],
                'permissions' => $this->resource['permissions'],
                'profile' => $this->resource['profile'],
            ],
        ];
    }
}
