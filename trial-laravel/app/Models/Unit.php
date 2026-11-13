<?php

namespace App\Models;

use App\Enums\UnitStatus;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use InvalidArgumentException;

/**
 * A single physical item of an order line, tracked from its location to being packed.
 */
class Unit extends Model
{
    /** @use HasFactory<\Database\Factories\UnitFactory> */
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'order_item_id',
        'status',
        'location_id',
        'pick_list_id',
    ];

    /**
     * The model's default values for attributes.
     *
     * @var array<string, mixed>
     */
    protected $attributes = [
        'status' => 'waiting',
    ];

    /**
     * Register the model's event listeners.
     */
    protected static function booted(): void
    {
        // A unit can only be picked from a location that stocks its product.
        static::saving(function (Unit $unit) {
            if ($unit->location_id === null || ! $unit->isDirty(['location_id', 'order_item_id'])) {
                return;
            }

            $stockedProductId = Stock::query()
                ->where('location_id', $unit->location_id)
                ->value('product_id');

            $orderedProductId = OrderItem::query()
                ->whereKey($unit->order_item_id)
                ->value('product_id');

            if ($stockedProductId === null || $stockedProductId !== $orderedProductId) {
                throw new InvalidArgumentException(
                    "Location {$unit->location_id} does not stock the product of order item {$unit->order_item_id}."
                );
            }
        });
    }

    /**
     * Get the order line this unit belongs to.
     *
     * @return BelongsTo<OrderItem, $this>
     */
    public function orderItem(): BelongsTo
    {
        return $this->belongsTo(OrderItem::class);
    }

    /**
     * Get the location this unit is picked from, or null if its product isn't in stock.
     *
     * @return BelongsTo<Location, $this>
     */
    public function location(): BelongsTo
    {
        return $this->belongsTo(Location::class);
    }

    /**
     * Get the pick list this unit has been batched into, if any.
     *
     * @return BelongsTo<PickList, $this>
     */
    public function pickList(): BelongsTo
    {
        return $this->belongsTo(PickList::class);
    }

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'status' => UnitStatus::class,
        ];
    }
}
