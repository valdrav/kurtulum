<?php

namespace Modules\Warehouse\Http\Controllers;

use App\Http\Controllers\Controller;

class WarehouseController extends Controller
{
    public function index()
    {
        return view('module.warehouse::index');
    }
}
