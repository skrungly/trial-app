<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class OrderItem extends Model
{
    /** @use HasFactory<\Database\Factories\OrderItemFactory> */
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'product_id',
        'product_name',
        'unit_price',
        'quantity',
    ];

    /**
     * The accessors to append to the model's array form.
     *
     * @var list<string>
     */
    protected $appends = [
        'line_total',
    ];

    /**
     * Get the order this line belongs to.
     *
     * @return BelongsTo<Order, $this>
     */
    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }

    /**
     * Get the product this line was for, or null if it has since been deleted.
     *
     * @return BelongsTo<Product, $this>
     */
    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    /**
     * Get the physical units that make up this line.
     *
     * @return HasMany<Unit, $this>
     */
    public function units(): HasMany
    {
        return $this->hasMany(Unit::class);
    }

    /**
     * Create a waiting unit for each item on this line that doesn't have one yet,
     * so the line ends up with as many units as its quantity.
     *
     * Units start with no location; assigning stock to them is a separate step.
     *
     * @return Collection<int, Unit> The units created.
     */
    public function createUnits(): Collection
    {
        $missing = max(0, $this->quantity - $this->units()->count());

        return $this->units()->createMany(array_fill(0, $missing, []));
    }

    /**
     * The unit price multiplied by the quantity, to two decimal places.
     *
     * @return Attribute<string, never>
     */
    protected function lineTotal(): Attribute
    {
        return Attribute::get(fn () => bcmul((string) $this->unit_price, (string) $this->quantity, 2));
    }

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'unit_price' => 'decimal:2',
            'quantity' => 'integer',
        ];
    }
}
