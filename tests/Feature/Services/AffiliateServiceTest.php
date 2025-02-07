<?php

namespace Tests\Feature\Services;

use App\Exceptions\AffiliateCreateException;
use App\Mail\AffiliateCreated;
use App\Models\Affiliate;
use App\Models\Merchant;
use App\Models\User;
use App\Services\AffiliateService;
use App\Services\ApiService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;
use Tests\TestCase;
use Faker\Factory as Faker;

class AffiliateServiceTest extends TestCase
{
    use RefreshDatabase;

    protected Merchant $merchant;
    protected AffiliateService $affiliateService;
    protected $faker;

    protected function setUp(): void
    {
        parent::setUp();
        $this->faker = Faker::create();
        $this->merchant = Merchant::factory()->for(User::factory())->create();
        $this->mock(ApiService::class, function ($mock) {
            $mock->shouldReceive('createDiscountCode')->zeroOrMoreTimes()->andReturn([
                'id' => -1,
                'code' => Str::random(8),
            ]);
        });
        $this->affiliateService = app(AffiliateService::class);
    }

    public function test_register_affiliate()
    {
        Mail::fake();
        $email = $this->faker->email();
        $name = $this->faker->name();
        $commissionRate = 0.1;
        $affiliate = $this->affiliateService->register($this->merchant, $email, $name, $commissionRate);
        $this->assertInstanceOf(Affiliate::class, $affiliate);

        Mail::assertQueued(AffiliateCreated::class, function ($mail) use ($affiliate) {
            return $mail->affiliate->is($affiliate);
        });

        $this->assertDatabaseHas('users', ['email' => $email]);

        $this->assertDatabaseHas('affiliates', [
            'user_id' => User::where('email', $email)->value('id'),
            'merchant_id' => $this->merchant->id,
            'commission_rate' => $commissionRate,
        ]);
    }

    public function test_register_affiliate_when_email_in_use_as_merchant()
    {
        $this->expectException(AffiliateCreateException::class);
        $this->expectExceptionMessage("Email already in use.");

        $this->affiliateService->register($this->merchant, $this->merchant->user->email, $this->faker->name(), 0.1);
    }

    public function test_register_affiliate_when_email_in_use_as_affiliate()
    {
        $this->expectException(AffiliateCreateException::class);

        $affiliate = Affiliate::factory()
            ->for($this->merchant)
            ->for(User::factory())
            ->create();

        $this->affiliateService->register($this->merchant, $affiliate->user->email, $this->faker->name(), 0.1);
    }
}
