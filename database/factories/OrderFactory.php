<?php

namespace Database\Factories;

use App\Models\Affiliate;
use App\Models\Merchant;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Order>
 */
class OrderFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'subtotal' => $this->faker->randomFloat(2, 10, 500),
            'commission_owed' => $this->faker->randomFloat(2, 1, 50),
            'commission' => $this->faker->randomFloat(2, 1, 50),
            'merchant_id' => Merchant::factory(),
            'affiliate_id' => Affiliate::factory(),
            'payout_status' => 'unpaid',
        ];
    }

}
