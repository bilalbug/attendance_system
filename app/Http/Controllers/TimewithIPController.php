<?php

namespace App\Http\Controllers;

use App\Models\TimewithIP;
use Illuminate\Http\Request;
use Tymon\JWTAuth\Facades\JWTAuth;

class TimewithIPController extends Controller
{
    public function index()
    {
        JWTAuth::parseToken()->authenticate();
        $sessions = TimewithIP::orderBy('id', 'desc')->get();
        return response()->json($sessions);
    }
}
