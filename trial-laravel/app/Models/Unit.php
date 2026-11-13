<?php

namespace App\Models;

use App\Enums\OrderStatus;
use App\Enums\UnitStatus;
use App\Exceptions\InvalidUnitStatusChangeException;
use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOneThrough;
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
        // A unit's item is taken from stock as soon as it's given a location, so stock
        // levels only count items that haven't been promised to an order yet.
        // Save units in a transaction (as placing an order does) so the stock change
        // and the unit are saved together.
        static::saving(function (Unit $unit) {
            // A new unit with no location is given one from stock, if its product is in stock anywhere.
            if (! $unit->exists && $unit->location_id === null) {
                $unit->allocateFromStock();

                return;
            }

            $unit->ensureLocationStocksProduct();

            if ($unit->isDirty('location_id')) {
                $unit->moveStockToLocation($unit->getOriginal('location_id'), $unit->location_id);
            }
        });

        // Picking the last unit on a pick list completes it.
        // A consolidated unit is gathered with the rest of its order, so the order needs a pigeonhole.
        // If none is free the status change is refused. Save with updateOrFail() or saveOrFail()
        // so the claimed pigeonhole is released again if saving the unit then fails.
        static::updating(function (Unit $unit) {
            // Every unit has to go through consolidation, so it can't jump straight to packed.
            $from = $unit->getOriginal('status');

            if ($unit->isDirty('status') && $unit->status === UnitStatus::Packed && $from !== UnitStatus::Consolidated) {
                throw InvalidUnitStatusChangeException::mustBeConsolidatedBeforePacking($unit, $from);
            }

            if ($unit->isDirty('status') && $unit->status === UnitStatus::Consolidated) {
                // Queried rather than loaded as $unit->orderItem, so the order line and order
                // aren't cached on the unit and don't end up in its API response.
                $unit->orderItem()->firstOrFail()->order->claimPigeonhole();
                $unit->unsetRelation('pigeonhole');
            }
        });

        static::updated(function (Unit $unit) {
            if ($unit->wasChanged('status') && $unit->status !== UnitStatus::Waiting) {
                // Queried rather than loaded as $unit->pickList, so it isn't cached on the unit
                // and doesn't end up in its API response.
                $unit->pickList()->first()?->completeIfAllPicked();

                $unit->startPickingOrder();
                $unit->consolidateOrderIfAllConsolidated();
            }
        });
    }

    /**
     * Move this unit's order to consolidated once every unit of the order has been consolidated.
     *
     * Units already moved on to packed count as consolidated. Orders already further along
     * (packed or dispatched) are left as they are.
     */
    private function consolidateOrderIfAllConsolidated(): void
    {
        if (!in_array($this->status, [UnitStatus::Consolidated, UnitStatus::Packed], true)) {
            return;
        }

        $orderId = OrderItem::whereKey($this->order_item_id)->value('order_id');

        $stillToConsolidate = static::query()
            ->whereIn('order_item_id', OrderItem::where('order_id', $orderId)->select('id'))
            ->whereIn('status', [UnitStatus::Waiting, UnitStatus::Picked])
            ->exists();

        if ($stillToConsolidate) {
            return;
        }

        Order::whereKey($orderId)
            ->whereIn('status', [OrderStatus::Paid, OrderStatus::Picking])
            ->update(['status' => OrderStatus::Consolidated]);
    }

    /**
     * Move this unit's order from paid to picking, now one of its items has been picked.
     *
     * Units that skip straight past picked (to consolidated or packed) count too. Orders already
     * further along are left as they are, so only the first unit picked changes anything.
     */
    private function startPickingOrder(): void
    {
        $orderId = OrderItem::whereKey($this->order_item_id)->value('order_id');

        // Only changes it while still paid, so two units picked at once can't move it twice.
        Order::whereKey($orderId)
            ->where('status', OrderStatus::Paid)
            ->update(['status' => OrderStatus::Picking]);
    }

    /**
     * Give this unit the first location holding its product with stock left, taking one from that stock.
     *
     * Leaves the unit without a location when its product is out of stock everywhere.
     */
    private function allocateFromStock(): void
    {
        $productId = OrderItem::query()->whereKey($this->order_item_id)->value('product_id');

        if ($productId === null) {
            return;
        }

        $candidates = Stock::query()
            ->where('product_id', $productId)
            ->where('quantity', '>', 0)
            ->orderBy('id')
            ->pluck('location_id');

        foreach ($candidates as $locationId) {
            // Another unit may have just taken the last item here, so fall through to the next location.
            if (self::takeFromStock($locationId)) {
                $this->location_id = $locationId;

                return;
            }
        }
    }

    /**
     * A unit can only be at a location that stocks its order item's product.
     *
     * @throws InvalidArgumentException When the location stocks a different product, or nothing.
     */
    private function ensureLocationStocksProduct(): void
    {
        if ($this->location_id === null || ! $this->isDirty(['location_id', 'order_item_id'])) {
            return;
        }

        $stockedProductId = Stock::query()
            ->where('location_id', $this->location_id)
            ->value('product_id');

        $orderedProductId = OrderItem::query()
            ->whereKey($this->order_item_id)
            ->value('product_id');

        if ($stockedProductId === null || $stockedProductId !== $orderedProductId) {
            throw new InvalidArgumentException(
                "Location {$this->location_id} does not stock the product of order item {$this->order_item_id}."
            );
        }
    }

    /**
     * Take one item from the stock at the new location and return it to the stock at the old one.
     *
     * @throws InvalidArgumentException When the new location has no stock left.
     */
    private function moveStockToLocation(?int $from, ?int $to): void
    {
        if ($to !== null && ! self::takeFromStock($to)) {
            throw new InvalidArgumentException("Location {$to} has no stock left for order item {$this->order_item_id}.");
        }

        if ($from !== null) {
            Stock::query()->where('location_id', $from)->increment('quantity');
        }
    }

    /**
     * Take one item from the stock at a location, if it has any left.
     *
     * Only decrements while stock remains, so two units can't both take the last item.
     */
    private static function takeFromStock(int $locationId): bool
    {
        return Stock::query()
            ->where('location_id', $locationId)
            ->where('quantity', '>', 0)
            ->decrement('quantity') > 0;
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
     * Sort units in the order a picker walks the warehouse: by aisle, bay then shelf of their location.
     *
     * Units without a location come first. Location fields are compared as text,
     * so bays and shelves need consistent zero-padding (e.g. "03", not "3") to sort correctly.
     *
     * @param  Builder<Unit>  $query
     */
    #[Scope]
    protected function inPickingOrder(Builder $query): void
    {
        $query->select('units.*')
            ->leftJoin('locations', 'locations.id', '=', 'units.location_id')
            ->orderBy('locations.aisle')
            ->orderBy('locations.bay')
            ->orderBy('locations.shelf')
            ->orderBy('units.id');
    }

    /**
     * Get the pigeonhole this unit's order is being gathered in, if any.
     *
     * Reached through the order line: the pigeonhole whose order is the line's order.
     *
     * @return HasOneThrough<Pigeonhole, OrderItem, $this>
     */
    public function pigeonhole(): HasOneThrough
    {
        return $this->hasOneThrough(
            Pigeonhole::class,
            OrderItem::class,
            'id',            // order_items.id ...
            'order_id',      // pigeonholes.order_id ...
            'order_item_id', // ... matches units.order_item_id
            'order_id',      // ... matches order_items.order_id
        );
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
