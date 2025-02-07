<?php

namespace App\Services;

use App\Models\Affiliate;
use App\Models\Merchant;
use App\Models\Order;
use App\Models\User;
use Illuminate\Support\Facades\Log;

class OrderService
{
    protected ApiService $apiService;
    protected AffiliateService $affiliateService;

    public function __construct(ApiService $apiService, AffiliateService $affiliateService)
    {
        $this->apiService = $apiService;
        $this->affiliateService = $affiliateService;
    }
    /**
     * Process an order and create an affiliate if necessary.
     *
     * @param array $data
     * @return void
     */
    public function processOrder(array $data): void
    {
        Log::info("Processing order: " . json_encode($data));
        $existingOrder = Order::where('id', $data['order_id'])->first();
        if ($existingOrder) {
            Log::warning("Duplicate order ignored: " . $data['order_id']);
            return;
        }
        $merchant = Merchant::query()->where('domain', $data['merchant_domain'])->firstOrFail();
        $user = User::query()->firstOrCreate(
            ['email' => $data['customer_email']],
            ['name' => $data['customer_name'], 'type' => User::TYPE_MERCHANT]
        );
        $discountCodeData = $this->apiService->createDiscountCode($merchant);
        $discountCode = $discountCodeData['code'] ?? null;
        $affiliate = Affiliate::query()->firstOrCreate(
            ['email' => $data['customer_email']],
            [
                'merchant_id' => $merchant->id,
                'name' => $data['customer_name'],
                'commission_rate' => 10.0,
                'discount_code' => $discountCode,
                'user_id' => $user->id
            ]
        );
        Order::query()->firstOrCreate(
            ['id' => $data['order_id']],
            [
                'subtotal' => $data['subtotal_price'],
                'merchant_id' => $merchant->id,
                'affiliate_id' => $affiliate->id,
                'discount_code' => $data['discount_code'] ?? null,
            ]
        );

        Log::info("Order successfully processed: " . $data['order_id']);
    }


}
