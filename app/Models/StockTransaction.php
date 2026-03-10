<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class StockTransaction extends Model
{
    public const TYPE_SALE = 'sale';

    public const TYPE_PURCHASE = 'purchase';

    public const TYPE_RETURN = 'return';

    protected $fillable = [
        'item_id',
        'type',
        'reference_id',
        'quantity',
        'stock_effect',
    ];

    protected $casts = [
        'id' => 'string',
        'item_id' => 'string',
        'type' => 'string',
        'reference_id' => 'string',
        'quantity' => 'integer',
        'stock_effect' => 'integer',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    public function item(): BelongsTo
    {
        return $this->belongsTo(Item::class, 'item_id', 'id');
    }
}
