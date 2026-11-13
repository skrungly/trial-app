<?php

namespace App\Models;

use App\Enums\UnitStatus;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * How many of a product are held at a location.
 */
class Stock extends Model
{
    /** @use HasFactory<\Database\Factories\StockFactory> */
    use HasFactory;

    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'stock';

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'product_id',
        'location_id',
        'quantity',
    ];

    /**
     * Register the model's event listeners.
     */
    protected static function booted(): void
    {
        // When stock arrives, hand it to units that have been waiting for their product to come back in.
        // Units only wait without a location when their product ran out, so in practice this is a restock from empty.
        static::created(fn (Stock $stock) => $stock->allocateToWaitingUnits());

        static::updated(function (Stock $stock) {
            if ($stock->wasChanged('quantity') && $stock->quantity > $stock->getOriginal('quantity')) {
                $stock->allocateToWaitingUnits();
            }
        });
    }

    /**
     * Give this stock's location to waiting units of its product that have none, oldest order first,
     * for as many units as there is stock. Each unit takes one from this stock as it's assigned.
     */
    public function allocateToWaitingUnits(): void
    {
        $available = static::whereKey($this->id)->value('quantity');

        if ($available < 1) {
            return;
        }

        $units = Unit::query()
            ->select('units.*')
            ->join('order_items', 'order_items.id', '=', 'units.order_item_id')
            ->join('orders', 'orders.id', '=', 'order_items.order_id')
            ->where('order_items.product_id', $this->product_id)
            ->where('units.status', UnitStatus::Waiting)
            ->whereNull('units.location_id')
            ->orderBy('orders.created_at')
            ->orderBy('orders.id')
            ->orderBy('units.id')
            ->limit($available)
            ->get();

        foreach ($units as $unit) {
            $unit->update(['location_id' => $this->location_id]);
        }
    }

    /**
     * Get the product this stock is of.
     *
     * @return BelongsTo<Product, $this>
     */
    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    /**
     * Get the location this stock is held at.
     *
     * @return BelongsTo<Location, $this>
     */
    public function location(): BelongsTo
    {
        return $this->belongsTo(Location::class);
    }

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'quantity' => 'integer',
        ];
    }
}
