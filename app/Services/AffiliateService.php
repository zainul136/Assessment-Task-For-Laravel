<?php

namespace App\Services;

use App\Exceptions\AffiliateCreateException;
use App\Mail\AffiliateCreated;
use App\Models\Affiliate;
use App\Models\Merchant;
use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Str;

class AffiliateService
{
    protected ApiService $apiService;

    public function __construct(ApiService $apiService)
    {
        $this->apiService = $apiService;
    }

    public function register(Merchant $merchant, string $email, string $name, float $commissionRate = 10.0): Affiliate
    {
        if (User::query()->where('email', $email)->whereHas('merchant')->exists()) {
            throw new AffiliateCreateException("Email already in use.");
        }
        if (Affiliate::query()->whereHas('user', fn($query) => $query->where('email', $email))->exists()) {
            throw new AffiliateCreateException("Email already in use as an affiliate.");
        }

        $user = User::query()->updateOrCreate(
            ['email' => $email],
            [
                'name' => $name,
                'password' => Hash::make(Str::random(12)),
                'type' => 'affiliate',
            ]
        );
        $discountCodeData = $this->apiService->createDiscountCode($merchant);
        $discountCode = $discountCodeData['code'] ?? null;
        if (!$discountCode) {
            throw new \Exception("Failed to generate discount code.");
        }

        $affiliate = Affiliate::updateOrCreate(
            ['user_id' => $user->id],
            [
                'merchant_id' => $merchant->id,
                'commission_rate' => $commissionRate,
                'discount_code' => $discountCode,
            ]
        );

        if ($affiliate->wasRecentlyCreated) {
            Mail::to($email)->queue(new AffiliateCreated($affiliate));
            $user->sendPasswordResetNotification(
                Password::broker()->createToken($user)
            );
        }

        return $affiliate;
    }
}
