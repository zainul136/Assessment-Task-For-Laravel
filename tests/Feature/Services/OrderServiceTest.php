<?php

namespace Tests\Feature\Services;
use App\Models\Merchant;
use App\Models\Order;
use App\Models\User;
use App\Services\OrderService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use Tests\TestCase;

class OrderServiceTest extends TestCase
{
    use RefreshDatabase, WithFaker;

    protected Merchant $merchant;

    public function setUp(): void
    {
        parent::setUp();
        $this->merchant = Merchant::factory()
            ->for(User::factory()->create())
            ->create();
    }

    protected function getOrderService(): OrderService
    {
        return $this->app->make(OrderService::class);
    }

    public function test_process_order()
    {
        $merchant = Merchant::factory()->create();
        $customer = User::factory()->create();

        $data = [
            'order_id' => (string) Str::uuid(),
            'subtotal_price' => round(rand(100, 999) / 3, 2),
            'merchant_domain' => $merchant->domain,
            'discount_code' => (string) Str::uuid(),
            'customer_email' => $customer->email,
            'customer_name' => 'John Doe',
        ];

        $validator = Validator::make($data, [
            'order_id' => 'required|string|unique:orders,order_id',
            'subtotal_price' => 'required|numeric|min:0',
            'merchant_domain' => 'required|string|exists:merchants,domain',
            'discount_code' => 'nullable|string',
            'customer_email' => 'required|email|exists:users,email',
            'customer_name' => 'required|string|min:2',
        ]);

        if ($validator->fails()) {
            dd($validator->errors()->toArray());
        }

        $this->mock(OrderService::class)->shouldReceive('processOrder')
            ->with($data)->once();
        $response = $this->post(route('webhook'), $data);
        $response->assertStatus(201);
    }

    public function test_process_duplicate_order()
    {
        Order::truncate();
        $order = Order::factory()->for($this->merchant)->create();
        $data = [
            'order_id' => $order->id,
            'subtotal_price' => round(rand(100, 999) / 3, 2),
            'merchant_domain' => $this->merchant->domain,
            'discount_code' => $this->faker->uuid(),
            'customer_email' => $this->faker->email(),
            'customer_name' => $this->faker->name(),
        ];
        $this->getOrderService()->processOrder($data);
        $this->assertDatabaseCount('orders', 1);
    }


}
