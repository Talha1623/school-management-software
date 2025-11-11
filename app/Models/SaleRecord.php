<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SaleRecord extends Model
{
    use HasFactory;

    protected $fillable = [
        'product_id',
        'product_name',
        'category',
        'quantity',
        'unit_price',
        'total_amount',
        'method',
        'campus',
        'sale_date',
        'notes',
    ];

    protected $casts = [
        'quantity' => 'integer',
        'unit_price' => 'decimal:2',
        'total_amount' => 'decimal:2',
        'sale_date' => 'date',
    ];

    /**
     * Get the product that owns the sale record.
     */
    public function product()
    {
        return $this->belongsTo(Product::class);
    }
}

