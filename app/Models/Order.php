<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Order extends Model
{
    protected $table = 'orders';
    
    protected $fillable = [
        'g_number',
        'date',
        'last_change_date',
        'supplier_article',
        'tech_size',
        'barcode',
        'total_price',
        'discount_percent',
        'warehouse_name',
        'oblast',
        'income_id',
        'odid',        // Это уникальный идентификатор
        'nm_id',
        'subject',
        'category',
        'brand',
        'is_cancel',
        'cancel_dt',
    ];
    
    protected $casts = [
        'date' => 'datetime',
        'last_change_date' => 'date',
        'is_cancel' => 'boolean',
        'cancel_dt' => 'datetime',
        'total_price' => 'decimal:2',
    ];
}