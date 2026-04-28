<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class CashRegister extends Model
{
    protected $fillable = [
        'user_id',
        'code',
        'status',
        'opening_amount',
        'expected_amount',
        'actual_amount',
        'difference_amount',
        'opened_at',
        'closed_at',
        'opening_notes',
        'closing_notes',
    ];

    protected function casts(): array
    {
        return [
            'opening_amount' => 'decimal:2',
            'expected_amount' => 'decimal:2',
            'actual_amount' => 'decimal:2',
            'difference_amount' => 'decimal:2',
            'opened_at' => 'datetime',
            'closed_at' => 'datetime',
            'created_at' => 'datetime',
            'updated_at' => 'datetime',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function orders(): HasMany
    {
        return $this->hasMany(Order::class);
    }

    public function expenses(): HasMany
    {
        return $this->hasMany(CashExpense::class);
    }

    public function validOrders(): HasMany
    {
        return $this->orders()->where('status', 'delivered');
    }

    public function getSalesTotalAttribute(): float
    {
        return (float) $this->validOrders()->sum('total');
    }

    public function getCashSalesTotalAttribute(): float
    {
        return (float) $this->validOrders()->where('payment_method', 'cash')->sum('total');
    }

    public function getQrSalesTotalAttribute(): float
    {
        return (float) $this->validOrders()->where('payment_method', 'qr')->sum('total');
    }

    public function getOrdersCountAttribute(): int
    {
        return $this->orders()->count();
    }

    public function getExpensesTotalAttribute(): float
    {
        return (float) $this->expenses()->sum('amount');
    }

    public function getCancelledOrdersCountAttribute(): int
    {
        return $this->orders()->where('status', 'cancelled')->count();
    }

    public function getExpectedClosingAmountAttribute(): float
    {
        return (float) $this->opening_amount + $this->cash_sales_total - $this->expenses_total;
    }
}
