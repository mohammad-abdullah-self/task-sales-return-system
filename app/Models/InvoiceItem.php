<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class InvoiceItem extends Model
{
    protected $fillable = [
        'invoice_id',
        'item_id',
        'quantity',
        'unit_price',
        'taxable_price',
        'discount',
        'tax_rate',
        'total',
    ];

    protected $casts = [
        'id' => 'integer',
        'invoice_id' => 'integer',
        'item_id' => 'integer',
        'quantity' => 'integer',
        'unit_price' => 'decimal:2',
        'taxable_price' => 'decimal:2',
        'discount' => 'decimal:2',
        'tax_rate' => 'decimal:4',
        'total' => 'decimal:2',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    public function invoice(): BelongsTo
    {
        return $this->belongsTo(Invoice::class, 'invoice_id', 'id');
    }

    public function item(): BelongsTo
    {
        return $this->belongsTo(Item::class, 'item_id', 'id');
    }
}
