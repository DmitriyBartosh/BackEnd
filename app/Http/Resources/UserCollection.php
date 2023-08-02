<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class UserCollection extends JsonResource
{
    /**
     * Transform the resource collection into an array.
     *
     * @return array<int|string, mixed>
     */

    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'email' => $this->email,
            'design_admin' => $this->hasRole('Design Expert'),
            'frontend_admin' => $this->hasRole('Frontend Expert'),
            'created_at' => $this->created_at->format('Y-m-d H:i:s'),
        ];
    }
}
