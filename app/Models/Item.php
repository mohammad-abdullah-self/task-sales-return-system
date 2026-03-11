<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Item extends Model
{
    /** @use HasFactory<\Database\Factories\ItemFactory> */
    use HasFactory;

    protected $fillable = [
        'name',
        'sku',
        'stock',
        'price',
    ];

    protected $casts = [
        'id' => 'integer',
        'name' => 'string',
        'sku' => 'string',
        'stock' => 'integer',
        'price' => 'decimal:2',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    public function invoiceItems(): HasMany
    {
        return $this->hasMany(InvoiceItem::class, 'item_id', 'id');
    }

    public function stockTransactions(): HasMany
    {
        return $this->hasMany(StockTransaction::class, 'item_id', 'id');
    }
}
