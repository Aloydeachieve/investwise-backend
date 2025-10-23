<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class UserResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'email' => $this->email,
            'mobile' => $this->phone,
            'dob' => $this->dob,
            'gender' => $this->gender ?? 'N/A',
            'address' => $this->address ? [
                'street' => $this->address, // if address is a single string in DB
                'city' => $this->city ?? '',
                'state' => $this->state ?? '',
                'zip' => $this->zip ?? '',
                'country' => $this->country ?? '',
            ] : null,
            'joinDate' => $this->created_at,
            'registrationMethod' => $this->registration_method ?? 'Email',
            'lastLogin' => $this->last_login_at,
            'isVerified' => !is_null($this->email_verified_at),
            'status' => $this->status ?? 'active',
            'balances' => [
                'main' => $this->main_balance ?? 0,
                'investment' => $this->investment_wallet ?? 0,
                'locked' => $this->locked_profit ?? 0,
            ],
            // related data
            'transactions' => TransactionResource::collection($this->whenLoaded('transactions')),
            'investments' => InvestmentResource::collection($this->whenLoaded('investments')),
            'referrals' => ReferralResource::collection($this->whenLoaded('referralsMade')),
            'activities' => ActivityResource::collection($this->whenLoaded('activities')),
        ];
    }
}
