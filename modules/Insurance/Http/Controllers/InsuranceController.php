<?php

namespace Modules\Insurance\Http\Controllers;

use App\Http\Controllers\Controller;

class InsuranceController extends Controller
{
    public function index()
    {
        return view('module.insurance::index');
    }
}
