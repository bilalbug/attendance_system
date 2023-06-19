<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AttendanceCalController;
use App\Http\Controllers\IPAddressController;
use App\Http\Controllers\TimewithIPController;
use App\Http\Controllers\UserController;
use App\Http\Controllers\AuthController;

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

Route::post('/register', [UserController::class, 'store']); //register user
Route::get('/ip', [AttendanceCalController::class, 'showIPandRouter']); //show Ip

Route::group(['middleware' => 'api', 'prefix' => 'auth'], function ($router) {

    Route::apiResource('/ipaddresses', IPAddressController::class);// all operation of ips

    Route::post('login',[AuthController::class, 'login']); //login
    Route::get('logout',[AuthController::class, 'logout']); //logout



    Route::get('/start', [AttendanceCalController::class, 'startTimer']); //start timer
    Route::get('/stop', [AttendanceCalController::class, 'stopTimer']); //end timer
    Route::get('/status', [AttendanceCalController::class, 'attendanceStatus']); //attendance status
    Route::get('/times', [TimewithIPController::class, 'index']);//show all time sessions
});
