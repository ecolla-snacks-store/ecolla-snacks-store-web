<?php

namespace App\Models;

use App\Traits\FormatDateToSerialize;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class OrderItem extends Model
{
    use HasFactory, FormatDateToSerialize;

    protected $fillable = [
        'order_id',
        'name',
        'name_en',
        'barcode',
        'price',
        'discount_rate',
        'quantity',
    ];

    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }
}
