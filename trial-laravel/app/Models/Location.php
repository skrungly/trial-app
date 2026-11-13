<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

/**
 * A place in the warehouse where stock is kept, identified by aisle, bay and shelf.
 */
class Location extends Model
{
    /** @use HasFactory<\Database\Factories\LocationFactory> */
    use HasFactory;

    /**
     * Separates the aisle, bay and shelf in a location code, e.g. "A-03-2".
     */
    public const CODE_SEPARATOR = '-';

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'aisle',
        'bay',
        'shelf',
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
     * Find a location by its code, e.g. "A-03-2".
     */
    public static function findByCode(string $code): ?self
    {
        $parts = explode(self::CODE_SEPARATOR, $code);

        if (count($parts) !== 3) {
            return null;
        }

        [$aisle, $bay, $shelf] = $parts;

        return static::query()
            ->where('aisle', $aisle)
            ->where('bay', $bay)
            ->where('shelf', $shelf)
            ->first();
    }

    /**
     * Get the stock held at this location, if any.
     *
     * @return HasOne<Stock, $this>
     */
    public function stock(): HasOne
    {
        return $this->hasOne(Stock::class);
    }

    /**
     * Get the units to be picked from this location.
     *
     * @return HasMany<Unit, $this>
     */
    public function units(): HasMany
    {
        return $this->hasMany(Unit::class);
    }

    /**
     * The location's code, made from its aisle, bay and shelf, e.g. "A-03-2".
     *
     * @return Attribute<string, never>
     */
    protected function code(): Attribute
    {
        return Attribute::get(fn () => implode(self::CODE_SEPARATOR, [$this->aisle, $this->bay, $this->shelf]));
    }
}
