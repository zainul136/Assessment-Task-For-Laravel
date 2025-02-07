<?php

namespace App\Http\Controllers;

use App\Models\Affiliate;
use App\Models\User;
use App\Services\MerchantService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;

class MerchantController extends Controller
{
    protected MerchantService $merchantService;

    public function __construct(MerchantService $merchantService)
    {
        $this->merchantService = $merchantService;
    }

    /**
     * Get order statistics for a merchant.
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function orderStats(Request $request): JsonResponse
    {
        $request->validate([
            'from_date' => 'required|date',
            'to_date' => 'required|date|after_or_equal:from_date',
        ]);
        $fromDate = Carbon::parse($request->from_date);
        $toDate = Carbon::parse($request->to_date);
        $stats = $this->merchantService->getOrderStatistics($fromDate, $toDate);

        return response()->json($stats);
    }

    /**
     * Register a new merchant.
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function registerMerchant(Request $request): JsonResponse
    {
        $data = $request->validate([
            'domain' => 'required|string|unique:merchants,domain',
            'name' => 'required|string|max:255',
            'email' => 'required|email|unique:users,email',
            'api_key' => 'required|string|min:8',
        ]);

        $merchant = $this->merchantService->register($data);

        return response()->json([
            'message' => 'Merchant registered successfully.',
            'merchant' => $merchant,
        ]);
    }

    /**
     * Update an existing merchant.
     *
     * @param Request $request
     * @param int $userId
     * @return JsonResponse
     */
    public function updateMerchant(Request $request, $userId): JsonResponse
    {
        $data = $request->validate([
            'domain' => 'required|string',
            'name' => 'required|string|max:255',
            'email' => 'required|email',
            'api_key' => 'required|string|min:8',
        ]);

        $this->merchantService->updateMerchant($userId, $data);

        return response()->json(['message' => 'Merchant updated successfully.']);
    }

    /**
     * Find a merchant by email.
     *
     * @param string $email
     * @return JsonResponse
     */
    public function findMerchantByEmail(string $email): JsonResponse
    {
        $merchant = $this->merchantService->findMerchantByEmail($email);

        if (!$merchant) {
            return response()->json(['message' => 'Merchant not found.'], 404);
        }

        return response()->json($merchant);
    }

    /**
     * Payout all orders for an affiliate.
     *
     * @param int $affiliateId
     * @return JsonResponse
     */
    public function payoutAffiliateOrders($affiliateId): JsonResponse
    {
        $affiliate = Affiliate::query()->findOrFail($affiliateId);

        $this->merchantService->payout($affiliate);

        return response()->json(['message' => 'Payout processed successfully.']);
    }
}
