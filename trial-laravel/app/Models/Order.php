<?php

namespace App\Models;

use App\Enums\OrderStatus;
use App\Exceptions\NoPigeonholeAvailableException;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

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
     * Get the pigeonhole this order is held in, if any.
     *
     * @return HasOne<Pigeonhole, $this>
     */
    public function pigeonhole(): HasOne
    {
        return $this->hasOne(Pigeonhole::class);
    }

    /**
     * Get the pigeonhole this order is held in, claiming a free one if it isn't in one yet.
     *
     * Free pigeonholes are tried in aisle then shelf order, so orders fill the warehouse from the front.
     *
     * @throws NoPigeonholeAvailableException When every pigeonhole is holding another order.
     */
    public function claimPigeonhole(): Pigeonhole
    {
        if ($existing = $this->pigeonhole()->first()) {
            return $existing;
        }

        $freeIds = Pigeonhole::query()
            ->whereNull('order_id')
            ->orderBy('aisle')
            ->orderBy('shelf')
            ->orderBy('id')
            ->pluck('id');

        foreach ($freeIds as $id) {
            try {
                // Only claims it if it's still free, so two orders can't take the same pigeonhole.
                $claimed = Pigeonhole::whereKey($id)->whereNull('order_id')->update(['order_id' => $this->id]);
            } catch (UniqueConstraintViolationException) {
                // Another unit of this order claimed a pigeonhole for it at the same moment; use that one.
                return $this->pigeonhole()->firstOrFail();
            }

            if ($claimed > 0) {
                $this->unsetRelation('pigeonhole');

                return Pigeonhole::findOrFail($id);
            }
        }

        throw NoPigeonholeAvailableException::forOrder($this);
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
