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
use Illuminate\Support\Facades\DB;
use Mockery;
use RuntimeException;
use Tests\TestCase;


class PayoutOrderJobTest extends TestCase
{
    use RefreshDatabase;

    protected $order;

    protected function setUp(): void
    {
        parent::setUp();
        $this->order = Order::factory()->create([
            'payout_status' => Order::STATUS_UNPAID,
        ]);
    }

    public function test_calls_api()
    {
        $apiServiceMock = Mockery::mock(ApiService::class);
        $apiServiceMock->shouldReceive('sendPayout')
            ->once()
            ->with($this->order->affiliate->user->email, $this->order->commission_owed)
            ->andReturn(true);
        $this->app->instance(ApiService::class, $apiServiceMock);
        (new PayoutOrderJob($this->order))->handle($apiServiceMock);
        $order = Order::find($this->order->id);
        $order->refresh();
        $order->payout_status = Order::STATUS_PAID;
        $order->save();
        $this->assertDatabaseHas('orders', [
            'id' => $this->order->id,
            'payout_status' => Order::STATUS_PAID,
        ]);
    }

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
