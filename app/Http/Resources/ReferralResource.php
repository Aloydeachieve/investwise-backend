<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ReferralResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
                return [
            'id' => $this->id,
            'referred_user_id' => $this->referred_user_id,
            'bonus_amount' => $this->bonus_amount,
            'status' => $this->status,
            'created_at' => $this->created_at,
        ];
    }
}
