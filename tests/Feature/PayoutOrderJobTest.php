<?php

namespace Tests\Feature;

use App\Jobs\PayoutOrderJob;
use App\Models\Affiliate;
use App\Models\Merchant;
use App\Models\Order;
use App\Models\User;
use App\Services\ApiService;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Illuminate\Support\Facades\Bus;
use RuntimeException;
use Tests\TestCase;

class PayoutOrderJobTest extends TestCase
{
    use RefreshDatabase, WithFaker;

    protected Order $order;

    public function setUp(): void
    {
        parent::setUp();
        $this->order = Order::factory()->for($merchant = Merchant::factory()->for(User::factory())->create())
            ->for(Affiliate::factory()->for($merchant)->for(User::factory()))
            ->create();
    }

//    public function test_calls_api()
//    {
//        // Prevent job from queuing asynchronously
//        Bus::fake();
//
//        // Mock the API service and define its expected behavior
//        $apiServiceMock = $this->mock(ApiService::class);
//        $apiServiceMock->shouldReceive('sendPayout')
//            ->once()
//            ->with($this->order->affiliate->user->email, $this->order->commission_owed)
//            ->andReturn(true); // Ensure API response is simulated properly
//
//        // Dispatch the job
//        dispatch_sync(new PayoutOrderJob($this->order));
//
//        // Refresh order from database to get the updated payout status
//        $this->order->refresh();
//
//        // Check if the job updated the payout status correctly
//        $this->assertSame(Order::STATUS_PAID, $this->order->payout_status, 'Payout status did not update.');
//
//        // Ensure the database reflects the updated payout status
//        $this->assertDatabaseHas('orders', [
//            'id' => $this->order->id,
//            'payout_status' => Order::STATUS_PAID,
//        ]);
//    }

    public function test_rolls_back_if_exception_thrown()
    {
        $this->mock(ApiService::class)
            ->shouldReceive('sendPayout')
            ->once()
            ->with($this->order->affiliate->user->email, $this->order->commission_owed)
            ->andThrow(RuntimeException::class);

        $this->expectException(RuntimeException::class);

        dispatch(new PayoutOrderJob($this->order));

        $this->assertDatabaseHas('orders', [
            'id' => $this->order->id,
            'payout_status' => Order::STATUS_UNPAID
        ]);
    }
}
