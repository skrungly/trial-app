<?php

namespace App\Models;

use App\Enums\OrderStatus;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Order extends Model
{
    /** @use HasFactory<\Database\Factories\OrderFactory> */
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'total',
        'status',
        'paid_at',
        'customer_name',
        'address',
    ];

    /**
     * The model's default values for attributes.
     *
     * @var array<string, mixed>
     */
    protected $attributes = [
        'status' => 'paid',
    ];

    /**
     * Register the model's event listeners.
     */
    protected static function booted(): void
    {
        // Orders are paid for when they are placed, so for now paid_at matches created_at.
        static::creating(function (Order $order) {
            $order->created_at ??= $order->freshTimestamp();
            $order->paid_at ??= $order->created_at;
        });
    }

    /**
     * Get the lines in this order.
     *
     * @return HasMany<OrderItem, $this>
     */
    public function items(): HasMany
    {
        return $this->hasMany(OrderItem::class);
    }

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'total' => 'decimal:2',
            'status' => OrderStatus::class,
            'paid_at' => 'datetime',
        ];
    }
}
