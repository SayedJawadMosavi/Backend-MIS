<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class ExchangeMoney extends Model
{
    use HasFactory, SoftDeletes;
    protected $fillable = [
        'sender_name',
        'amount',
        'currency',
        'province',
        'phone_number',
        'receiver_name',
        'receiver_father_name',
        'receiver_id_no',
        'date',
        'exchange_id'
    ];

    use HasFactory;
}
