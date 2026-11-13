<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

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
     * The pigeonhole's code, made from its aisle and shelf, e.g. "A 02".
     *
     * @return Attribute<string, never>
     */
    protected function code(): Attribute
    {
        return Attribute::get(fn () => implode(self::CODE_SEPARATOR, [$this->aisle, $this->shelf]));
    }
}
