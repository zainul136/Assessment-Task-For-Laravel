<?php

namespace Database\Factories;

use App\Models\Affiliate;
use App\Models\Merchant;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Affiliate>
 */
class AffiliateFactory extends Factory
{
    protected $model = Affiliate::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'user_id' => User::factory(), // Creates a related User
            'merchant_id' => Merchant::factory(), // Creates a related Merchant
            'discount_code' => $this->faker->uuid(),
            'commission_rate' => $this->faker->randomFloat(2, 0.1, 0.5), // Generates between 0.1 - 0.5
        ];
    }
}
