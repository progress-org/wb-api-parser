<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Income extends Model
{
    protected $table = 'incomes';
    
    protected $fillable = [
        'income_id',
        'number',
        'date',
        'last_change_date',
        'supplier_article',
        'tech_size',
        'barcode',
        'quantity',
        'total_price',
        'date_close',
        'warehouse_name',
        'nm_id',
    ];
    
    protected $casts = [
        'date' => 'date',
        'last_change_date' => 'date',
        'date_close' => 'date',
        'total_price' => 'decimal:2',
        'quantity' => 'integer',
    ];
    
    /**
     * Обработка специального значения даты "0001-01-01"
     */
    public function getDateCloseAttribute($value)
    {
        // Если дата "пустая" (0001-01-01), возвращаем null
        return $value === '0001-01-01' ? null : $value;
    }
}