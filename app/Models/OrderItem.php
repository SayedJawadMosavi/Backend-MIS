<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class OrderItem extends Model
{
    use HasFactory, SoftDeletes;
    protected $fillable = ['name', 'type', 'weight',  'order_id', 'car_id', 'price_per_killo', 'count', 'created_at', 'created_by'];
}
