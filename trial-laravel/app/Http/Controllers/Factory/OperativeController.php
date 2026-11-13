<?php

namespace App\Http\Controllers\Factory;

use App\Http\Controllers\Controller;
use App\Models\Operative;

class OperativeController extends Controller
{
    /**
     * List all operatives, alphabetically by name.
     */
    public function index()
    {
        return response()->json(Operative::orderBy('name')->orderBy('id')->get(), 200);
    }
}
