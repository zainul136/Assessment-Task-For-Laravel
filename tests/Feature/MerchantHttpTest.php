<?php

namespace Tests\Feature;

use App\Jobs\PayoutOrderJob;
use App\Models\Affiliate;
use App\Models\Merchant;
use App\Models\Order;
use App\Models\User;
use App\Services\MerchantService;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use Tests\TestCase;

class MerchantHttpTest extends TestCase
{
    use RefreshDatabase;

    protected MerchantService $merchantService;

    protected function setUp(): void
    {
        parent::setUp();
        $this->merchantService = app(MerchantService::class);
    }

    /** @test */
    public function it_validates_order_stats_request()
    {
        $this->json('GET', '/api/merchant/order-stats')
            ->assertStatus(422)
            ->assertJsonValidationErrors(['from_date', 'to_date']);
    }

    /** @test */
    /** @test */
    /** @test */
    /** @test */
    public function it_returns_correct_order_statistics()
    {
        // Create test data
        $merchant = Merchant::factory()->create();
        $affiliate = Affiliate::factory()->create();

        Order::factory()->create([
            'subtotal' => 200,
            'commission_owed' => 20.07,
            'commission' => 20,
            'merchant_id' => $merchant->id,
            'affiliate_id' => $affiliate->id,
            'payout_status' => Order::STATUS_UNPAID,
            'created_at' => now()->subDays(2),
        ]);
        $fromDate = Carbon::now()->subDays(5)->toDateString();
        $toDate = Carbon::now()->toDateString();
        $response = $this->getJson(route('merchant.orderStats', [
            'from_date' => $fromDate,
            'to_date' => $toDate,
        ]));
//        $response->dump();
        $response->assertOk()
            ->assertJson([
                'count' => 1,
                'revenue' => 200,
                'commission_owed' => 20.07,
            ]);
    }

    /** @test */
    public function it_registers_a_merchant_successfully()
    {
        $data = [
            'domain' => 'example.com',
            'name' => 'Test Merchant',
            'email' => 'merchant@example.com',
            'api_key' => 'secret123',
        ];
        $merchant = $this->merchantService->register($data);
        $this->assertDatabaseHas('users', ['email' => $data['email']]);
        $this->assertDatabaseHas('merchants', ['domain' => $data['domain']]);
        $this->assertEquals($data['domain'], $merchant->domain);
    }

    /** @test */
    public function it_updates_merchant_details()
    {
        $user = User::factory()->create();
        $merchant = Merchant::factory()->create(['user_id' => $user->id]);
        $data = [
            'domain' => 'updated.com',
            'name' => 'Updated Merchant',
            'email' => 'updated@example.com',
            'api_key' => 'newsecret',
        ];
        $this->merchantService->updateMerchant($user, $data);
        $this->assertDatabaseHas('users', ['email' => $data['email']]);
        $this->assertDatabaseHas('merchants', ['domain' => $data['domain']]);
    }

    /** @test */
    public function it_finds_a_merchant_by_email()
    {
        $user = User::factory()->create(['email' => 'merchant@example.com']);
        $merchant = Merchant::factory()->create(['user_id' => $user->id]);
        $foundMerchant = $this->merchantService->findMerchantByEmail($user->email);
        $this->assertNotNull($foundMerchant);
        $this->assertEquals($merchant->id, $foundMerchant->id);
    }

    /** @test */
    public function it_dispatches_payout_jobs_for_affiliate_orders()
    {
        Queue::fake();
        $affiliate = Affiliate::factory()->create();
        Order::factory()->count(3)->create([
            'affiliate_id' => $affiliate->id,
            'payout_status' => Order::STATUS_UNPAID,
        ]);
        $this->merchantService->payout($affiliate);
        Queue::assertPushed(PayoutOrderJob::class, 3);
    }
}
