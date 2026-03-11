<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SalesReturnItem extends Model
{
    protected $fillable = [
        'sales_return_id',
        'invoice_id',
        'invoice_item_id',
        'item_id',
        'quantity',
        'unit_price',
        'taxable_price',
        'discount',
        'tax_rate',
        'total',
    ];

    protected $casts = [
        'id' => 'string',
        'sales_return_id' => 'string',
        'invoice_id' => 'string',
        'invoice_item_id' => 'string',
        'item_id' => 'string',
        'quantity' => 'integer',
        'unit_price' => 'decimal:2',
        'taxable_price' => 'decimal:2',
        'discount' => 'decimal:2',
        'tax_rate' => 'decimal:4',
        'total' => 'decimal:2',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    public function salesReturn(): BelongsTo
    {
        return $this->belongsTo(SalesReturn::class, 'sales_return_id', 'id');
    }

    public function invoice(): BelongsTo
    {
        return $this->belongsTo(Invoice::class, 'invoice_id', 'id');
    }

    public function invoiceItem(): BelongsTo
    {
        return $this->belongsTo(InvoiceItem::class, 'invoice_item_id', 'id');
    }

    public function item(): BelongsTo
    {
        return $this->belongsTo(Item::class, 'item_id', 'id');
    }
}
