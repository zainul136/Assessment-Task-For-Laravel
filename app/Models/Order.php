<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property int $id
 * @property Merchant $merchant
 * @property Affiliate $affiliate
 * @property float $subtotal
 * @property float $commission_owed
 * @property string $payout_status
 */
class Order extends Model
{
    use HasFactory;

    const STATUS_UNPAID = 'unpaid';
    const STATUS_PAID = 'paid';

    protected $fillable = [
        'merchant_id',
        'affiliate_id',
        'subtotal',
        'commission_owed',
        'commission',
        'payout_status',
        'customer_email',
        'created_at'
    ];
    protected static function boot()
    {
        parent::boot();
        static::creating(function ($order) {
            if (Order::where('id', $order->id)->exists()) {
                throw new \Exception("Duplicate order detected: " . $order->id);
            }
        });
    }
    public function merchant(): BelongsTo
    {
        return $this->belongsTo(Merchant::class);
    }

    public function affiliate(): BelongsTo
    {
        return $this->belongsTo(Affiliate::class);
    }
}
