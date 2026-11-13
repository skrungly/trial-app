<?php

namespace App\Models;

use App\Enums\OrderStatus;
use App\Enums\UnitStatus;
use App\Exceptions\PigeonholeCannotBeReleasedException;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\DB;

/**
 * A slot in the warehouse, identified by aisle and shelf, that can hold one order.
 */
class Pigeonhole extends Model
{
    /** @use HasFactory<\Database\Factories\PigeonholeFactory> */
    use HasFactory;

    /**
     * Separates the aisle and shelf in a pigeonhole code, e.g. "A 02".
     */
    public const CODE_SEPARATOR = ' ';

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'aisle',
        'shelf',
        'order_id',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * laravel_through_key is added by Eloquent when a pigeonhole is loaded through
     * another model (e.g. Unit::pigeonhole) and isn't part of the pigeonhole itself.
     *
     * @var list<string>
     */
    protected $hidden = [
        'laravel_through_key',
    ];

    /**
     * The accessors to append to the model's array form.
     *
     * @var list<string>
     */
    protected $appends = [
        'code',
    ];

    /**
     * Find a pigeonhole by its code, e.g. "A 02".
     */
    public static function findByCode(string $code): ?self
    {
        $parts = explode(self::CODE_SEPARATOR, $code);

        if (count($parts) !== 2) {
            return null;
        }

        [$aisle, $shelf] = $parts;

        return static::query()
            ->where('aisle', $aisle)
            ->where('shelf', $shelf)
            ->first();
    }

    /**
     * Get the order held in this pigeonhole, if any.
     *
     * @return BelongsTo<Order, $this>
     */
    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }

    /**
     * Release the order held here to be taken away for packing, freeing the pigeonhole for
     * another order. A consolidated order moves on to packing; any other status is left as it is.
     *
     * @throws PigeonholeCannotBeReleasedException When the pigeonhole is empty, or its order
     *                                             still has units that haven't been consolidated.
     */
    public function release(): void
    {
        if ($this->order_id === null) {
            throw PigeonholeCannotBeReleasedException::empty($this);
        }

        $unconsolidated = Unit::query()
            ->whereIn('order_item_id', OrderItem::where('order_id', $this->order_id)->select('id'))
            ->whereIn('status', [UnitStatus::Waiting, UnitStatus::Picked])
            ->count();

        if ($unconsolidated > 0) {
            throw PigeonholeCannotBeReleasedException::notFull($this, $unconsolidated);
        }

        $orderId = $this->order_id;

        DB::transaction(function () use ($orderId) {
            $this->update(['order_id' => null]);

            // Only from consolidated, so an order already further along is never moved back.
            Order::whereKey($orderId)
                ->where('status', OrderStatus::Consolidated)
                ->update(['status' => OrderStatus::Packing]);
        });

        $this->unsetRelation('order');
    }

    /**
     * The pigeonhole's code, made from its aisle and shelf, e.g. "A 02".
     *
     * @return Attribute<string, never>
     */
    protected function code(): Attribute
    {
        return Attribute::get(fn () => implode(self::CODE_SEPARATOR, [$this->aisle, $this->shelf]));
    }
}
