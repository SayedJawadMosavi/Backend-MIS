<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Car extends Model
{
    use HasFactory, SoftDeletes;
    protected $fillable = ['status', 'created_at'];


    public function payments(): HasMany
    {
        return $this->hasMany(OrderPayment::class, 'car_id');
    }

    public function extraExpense(): HasMany
    {
        return $this->hasMany(OrderExtraExpense::class, 'car_id');
    }
    public function items()
    {
        return $this->hasMany(OrderItem::class, 'car_id');
    }
    public function orders()
    {
        return $this->hasMany(Order::class, 'car_id');
    }

    public function carExpense()
    {
        return $this->hasMany(CarExpense::class, 'car_id');
    }
}
