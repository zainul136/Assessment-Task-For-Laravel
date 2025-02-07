<?php
namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;
use App\Models\Merchant;
use App\Models\User;
use App\Services\OrderService;
use Illuminate\Support\Facades\Validator;

class OrderWebhookTest extends TestCase
{
    use RefreshDatabase;

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
        $this->mock(OrderService::class)
            ->shouldReceive('processOrder')->with($data)->once();
        $response = $this->post(route('webhook'), $data);
        $response->assertStatus(201);
    }
}
