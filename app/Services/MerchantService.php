<?php
namespace App\Services;

use App\Jobs\PayoutOrderJob;
use App\Models\Order;
use App\Models\Affiliate;
use App\Models\Merchant;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Queue;
use Carbon\Carbon;

class MerchantService
{
    public function register(array $data): Merchant
    {
        $user = User::query()->create([
            'name' => $data['name'],
            'email' => $data['email'],
            'password' => bcrypt($data['api_key']),
            'type' => User::TYPE_MERCHANT,
        ]);

        return $user->merchant()->create([
            'user_id' => $user->id,
            'domain' => $data['domain'],
            'display_name' => $data['name'],
        ]);
    }
    public function updateMerchant($user, array $data): void
    {
        if (!$user instanceof User) {
            $user = User::query()->findOrFail($user);
        }
        $user->update([
            'email' => $data['email'],
            'password' => bcrypt($data['api_key']),
        ]);
        if ($user->merchant) {
            $user->merchant->update([
                'domain' => $data['domain'],
                'display_name' => $data['name'],
            ]);
        } else {
            throw new \Exception('Merchant record not found for this user.');
        }
    }
    public function findMerchantByEmail(string $email): ?Merchant
    {
        return Merchant::whereHas('user', function ($query) use ($email) {
            $query->where('email', $email);
        })->first();
    }



    public function getOrderStatistics(Carbon $fromDate, Carbon $toDate): array
    {
        $orders = Order::query()->whereBetween('created_at', [$fromDate, $toDate])->get();

        return [
            'count' => $orders->count(),
            'revenue' => $orders->sum('subtotal'),
            'commission_owed' => $orders->whereNotNull('affiliate_id')
                ->where('payout_status', 'unpaid')
                ->sum('commission_owed'),
        ];
    }

    public function payout(Affiliate $affiliate)
    {
        $orders = Order::where('affiliate_id', $affiliate->id)->where('payout_status', Order::STATUS_UNPAID)->get();
        foreach ($orders as $order) {
            PayoutOrderJob::dispatch($order);
        }
    }
}
