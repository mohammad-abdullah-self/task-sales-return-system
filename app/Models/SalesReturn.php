<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class SalesReturn extends Model
{
    protected $fillable = [
        'return_number',
        'invoice_id',
        'return_date',
        'refund_method',
        'return_reason',
        'taxable_amount',
        'discount_amount',
        'vat_amount',
        'total_amount',
    ];

    protected $casts = [
        'id' => 'string',
        'return_number' => 'string',
        'invoice_id' => 'string',
        'return_date' => 'datetime',
        'taxable_amount' => 'decimal:2',
        'discount_amount' => 'decimal:2',
        'vat_amount' => 'decimal:2',
        'total_amount' => 'decimal:2',
        'refund_method' => 'string',
        'return_reason' => 'string',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    public function invoice(): BelongsTo
    {
        return $this->belongsTo(Invoice::class, 'invoice_id', 'id');
    }

    public function salesReturnItems(): HasMany
    {
        return $this->hasMany(SalesReturnItem::class, 'sales_return_id', 'id');
    }
}
