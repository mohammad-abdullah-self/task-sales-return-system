<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Invoice extends Model
{
    /** @use HasFactory<\Database\Factories\InvoiceFactory> */
    use HasFactory;

    protected $fillable = [
        'customer_name',
        'invoice_date',
        'taxable_amount',
        'discount_amount',
        'vat_amount',
        'total_amount',
        'status',
    ];

    protected $casts = [
        'id' => 'integer',
        'customer_name' => 'string',
        'invoice_date' => 'datetime',
        'taxable_amount' => 'decimal:2',
        'discount_amount' => 'decimal:2',
        'vat_amount' => 'decimal:2',
        'total_amount' => 'decimal:2',
        'status' => 'string',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    public function invoiceItems(): HasMany
    {
        return $this->hasMany(InvoiceItem::class, 'invoice_id', 'id');
    }

    public function salesReturn(): HasOne
    {
        return $this->hasOne(SalesReturn::class, 'invoice_id', 'id');
    }
}
