<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class CarExpense extends Model
{
    use HasFactory, SoftDeletes;
    protected $fillable = ['car_id', 'created_by', 'name', 'price', 'created_at'];
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
