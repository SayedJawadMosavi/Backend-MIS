<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Order extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = ['id', 'car_id', 'customer_name', 'father_name', 'grand_father_name', 'tazkira_id', 'customer_phone', 'status', 'receiver_name', 'price_per_killo', 'receiver_phone', 'country', 'city', 'address', 'delivary_type', 'description', 'created_by', 'group_number'];


    public function payments(): HasMany
    {
        return $this->hasMany(OrderPayment::class);
    }

    public function extraExpense(): HasMany
    {
        return $this->hasMany(OrderExtraExpense::class);
    }
    public function items(): HasMany
    {
        return $this->hasMany(OrderItem::class);
    }
}
