<?php

namespace Modules\QualityCheck\Http\Controllers;

use App\Http\Controllers\Controller;

class QualityCheckController extends Controller
{
    public function index()
    {
        return view('module.quality-check::index');
    }
}
