<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class MarketData extends Model
{
    protected $fillable = [
        'currency_id',
        'symbol',
        'price',
        'pair_id',
        'last_price',
        'percent_change_1h',
        'percent_change_24h',
        'percent_change_7d',
        'last_percent_change_1h',
        'last_percent_change_24h',
        'last_percent_change_7d',
    ];

    protected $casts = [
        'html_classes' => 'object'
    ];
}
