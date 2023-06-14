<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AttendanceCalController;
use App\Http\Controllers\IPAddressController;
use App\Http\Controllers\TimewithIPController;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| is assigned the "api" middleware group. Enjoy building your API!
|
*/

Route::middleware('auth:sanctum')->get('/user', function (Request $request) {
    return $request->user();
});

Route::get('/ip', [AttendanceCalController::class, 'showIPandRouter']); //show Ip
Route::get('/start', [AttendanceCalController::class, 'StartTime']); //start timer
Route::get('/end', [AttendanceCalController::class, 'EndTime']); //end timer

//Route::get('/', [IPAddressController::class, 'index']);

Route::get('/times', [TimewithIPController::class, 'index']);

Route::apiResource('/ipaddresses', IPAddressController::class);
