<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class OrderExtraExpense extends Model
{
    use HasFactory, SoftDeletes;
    protected $fillable = ['order_id', 'name',  'price', 'car_id', 'created_by'];
}
