<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * A type of package orders are packed into, limited by how many units and how much weight it holds.
 */
class Packaging extends Model
{
    /** @use HasFactory<\Database\Factories\PackagingFactory> */
    use HasFactory;

    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'packaging';

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'max_units',
        'max_weight',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'max_units' => 'integer',
            'max_weight' => 'integer',
        ];
    }
}
