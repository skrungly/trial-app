<?php

namespace App\Enums;

/**
 * The stages a single unit moves through, from its pickface to being packed.
 */
enum UnitStatus: string
{
    case Waiting = 'waiting';
    case Picked = 'picked';
    case Consolidated = 'consolidated';
    case Packed = 'packed';
}
