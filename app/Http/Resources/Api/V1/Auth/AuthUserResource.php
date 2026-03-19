<?php

namespace App\Http\Resources\Api\V1\Auth;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class AuthUserResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'email' => $this->email,
            'phone' => $this->phone,
            'avatar' => $this->avatar ? api_asset($this->avatar) : null,
            'user_type' => $this->user_type ?? 'customer',
            'email_verified' => $this->email_verified_at !== null,
            'phone_verified' => $this->phone_verified_at !== null,
            'verification_status' => $this->email_verified_at !== null || $this->phone_verified_at !== null ? 'verified' : 'pending',
            'wallet_balance' => number_format((float) ($this->balance ?? 0), 2, '.', ''),
            'club_points' => (int) ($this->club_points ?? 0),
            'balance' => (float) ($this->balance ?? 0),
        ];
    }
}
