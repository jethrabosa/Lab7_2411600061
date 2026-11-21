<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class PurchaseOrder extends Model
{
    public const ACTIVE_STATUSES = [
        'pending_approval',
        'approved',
        'ordered',
        'partially_received',
    ];

    protected $fillable = [
        'order_number',
        'supplier_id',
        'created_by',
        'approved_by',
        'status',
        'lead_time_days',
        'expected_delivery_at',
        'approved_at',
        'ordered_at',
        'received_at',
        'rejection_reason',
    ];

    protected function casts(): array
    {
        return [
            'lead_time_days' => 'integer',
            'expected_delivery_at' => 'datetime',
            'approved_at' => 'datetime',
            'ordered_at' => 'datetime',
            'received_at' => 'datetime',
        ];
    }

    public function supplier(): BelongsTo
    {
        return $this->belongsTo(Supplier::class)->withTrashed();
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function approver(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    public function items(): HasMany
    {
        return $this->hasMany(PurchaseOrderItem::class);
    }

    public function getTotalAttribute(): float
    {
        return (float) $this->items->sum(
            fn (PurchaseOrderItem $item) => $item->quantity * (float) $item->unit_cost
        );
    }
}
