<?php

namespace App\Enums;

/**
 * The stages an order moves through, from payment to leaving the warehouse.
 */
enum OrderStatus: string
{
    case Paid = 'paid';
    case Picking = 'picking';
    case Consolidated = 'consolidated';
    case Packed = 'packed';
    case Dispatched = 'dispatched';
}
