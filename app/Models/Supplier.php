<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Supplier extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'name',
        'contact_name',
        'email',
        'phone',
        'address',
        'lead_time_days',
        'notes',
    ];

    protected function casts(): array
    {
        return [
            'lead_time_days' => 'integer',
        ];
    }

    public function products(): HasMany
    {
        return $this->hasMany(Product::class);
    }

    public function purchaseOrders(): HasMany
    {
        return $this->hasMany(PurchaseOrder::class);
    }

    public function receivedPurchaseOrders(): HasMany
    {
        return $this->purchaseOrders()->where('status', 'received');
    }

    public function onTimePurchaseOrders(): HasMany
    {
        return $this->receivedPurchaseOrders()
            ->whereNotNull('expected_delivery_at')
            ->whereColumn('received_at', '<=', 'expected_delivery_at');
    }
}
