<?php

namespace App\Http\Controllers;

use App\Models\TimewithIP;
use Illuminate\Http\Request;

class TimewithIPController extends Controller
{
    public function index()
    {
        $sessions = TimewithIP::all();
        return response()->json($sessions);
    }
}
