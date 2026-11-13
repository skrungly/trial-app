<?php

namespace App\Models;

use App\Enums\UnitStatus;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\DB;

/**
 * A batch of waiting units for a picker to collect in one trip.
 */
class PickList extends Model
{
    /** @use HasFactory<\Database\Factories\PickListFactory> */
    use HasFactory;

    /**
     * The most units a single pick list holds.
     */
    public const MAX_UNITS = 10;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'picker_name',
        'completed_at',
    ];

    /**
     * Batch the oldest waiting units that aren't on a pick list yet into a new pick list.
     *
     * Only units with a location are batched, as the picker needs somewhere to collect them from.
     * Returns null, creating nothing, when no such units are waiting.
     */
    public static function createForOldestWaitingUnits(): ?self
    {
        return DB::transaction(function () {
            // Locked so two pick lists being made at once can't claim the same units.
            $unitIds = Unit::query()
                ->where('status', UnitStatus::Waiting)
                ->whereNull('pick_list_id')
                ->whereNotNull('location_id')
                ->orderBy('created_at')
                ->orderBy('id')
                ->limit(self::MAX_UNITS)
                ->lockForUpdate()
                ->pluck('id');

            if ($unitIds->isEmpty()) {
                return null;
            }

            $pickList = static::create();

            Unit::whereKey($unitIds)->update(['pick_list_id' => $pickList->id]);

            return $pickList;
        });
    }

    /**
     * Get the units on this pick list.
     *
     * @return HasMany<Unit, $this>
     */
    public function units(): HasMany
    {
        return $this->hasMany(Unit::class);
    }

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'completed_at' => 'datetime',
        ];
    }
}
