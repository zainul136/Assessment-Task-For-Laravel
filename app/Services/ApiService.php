<?php

namespace App\Services;

use App\Models\Merchant;
use Illuminate\Support\Str;
use RuntimeException;

class ApiService
{
    /**
     * Generate a new discount code for an affiliate.
     *
     * @param Merchant $merchant
     * @return array{id: int, code: string}
     */
    public function createDiscountCode(Merchant $merchant): array
    {
        return [
            'id' => random_int(1000, 999999),
            'code' => strtoupper(Str::random(8)),
        ];
    }

    /**
     * Send a payout to an affiliate.
     *
     * @param  string  $email
     * @param  float  $amount
     * @return void
     * @throws RuntimeException
     */
    public function sendPayout(string $email, float $amount): void
    {
        if ($amount <= 0) {
            throw new RuntimeException("Invalid payout amount.");
        }

        // Implement payout logic (e.g., send request to payment gateway)
    }
}
