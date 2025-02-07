<?php
namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use App\Services\OrderService;

class WebhookController extends Controller
{
    public function __construct(
        protected OrderService $orderService
    ) {}

    /**
     * Handle the incoming webhook and process the order.
     *
     * @param  Request $request
     * @return JsonResponse
     */
    public function __invoke(Request $request): JsonResponse
    {
        $validatedData = $request->validate([
            'order_id' => 'required|string|unique:orders,id',
            'subtotal_price' => 'required|numeric|min:0',
            'merchant_domain' => 'required|string|exists:merchants,domain',
            'discount_code' => 'nullable|string',
            'customer_email' => 'required|email|exists:users,email',
            'customer_name' => 'required|string|min:2',
        ]);
        $this->orderService->processOrder($validatedData);
        return response()->json(['message' => 'Order processed successfully'], 201);
    }
}
