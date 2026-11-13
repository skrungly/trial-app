<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * A person working on the factory floor, such as a picker.
 */
class Operative extends Model
{
    /** @use HasFactory<\Database\Factories\OperativeFactory> */
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
    ];

    /**
     * Get the pick lists this operative has taken.
     *
     * @return HasMany<PickList, $this>
     */
    public function pickLists(): HasMany
    {
        return $this->hasMany(PickList::class);
    }
}
