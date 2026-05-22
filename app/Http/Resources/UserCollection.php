<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class UserCollection extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return array
     */
    public function toArray($request)
    {
        return [
            'id' => $this->id,
            'balance' => $this->balance,
            'name' => $this->name,
            'email' => $this->email,
            'phone' => $this->phone,
            'date_of_birth' => optional($this->date_of_birth)->format('Y-m-d'),
            'user_type' => $this->user_type,
            'club_points' => round((float) ($this->club_points ?? 0), 6),
            'avatar' => $this->avatar ? api_asset($this->avatar) : '',
        ];
    }
}
