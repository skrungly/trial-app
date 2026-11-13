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
    // Released from its pigeonhole and taken away to be packed.
    case Packing = 'packing';
    case Packed = 'packed';
    case Dispatched = 'dispatched';
}
