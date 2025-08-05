<?php

use App\Http\Controllers\WebServices\AuthController;
use App\Http\Controllers\WebServices\BookingController;
use App\Http\Controllers\WebServices\CommonController;
use App\Http\Controllers\WebServices\UserController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

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

Route::post('register', [AuthController::class, 'RegisterUser']);
Route::post('login', [AuthController::class, 'Login']);
Route::post('forgot-password', [AuthController::class, 'ForgetPassword']);
Route::post('reset-password/{token}', [AuthController::class, 'SetPassword']);
Route::post('forgot-password/verify', [AuthController::class, 'VerifyOTP']);
Route::post('otp/resend', [AuthController::class, 'ResendOTP']);

Route::group(['middleware' => ['auth:api']], function () {
    Route::get('me', [UserController::class, 'getMyProfile']);
    Route::post("update-password", [UserController::class, 'ChangePassword']);
    Route::post('upload/profile', [UserController::class, 'setProfileImage']);
    Route::put('update-profile', [UserController::class, 'UpdateUserInfo']);
    Route::delete('logout', [AuthController::class, 'Logout']);
    Route::post('register-device', [CommonController::class, 'RegisterDevice']);
    Route::post('unregister-device', [CommonController::class, 'UnregisterDevice']);

    Route::get('get-hotels', [BookingController::class,'GetHotels']);
    Route::get('hotel-detail/{uuid}', [BookingController::class,'GetDetailInfo']);

    Route::post('generate-transaction', [BookingController::class,'GenerateTransaction']);
    Route::post('do-booking', [BookingController::class,'DoBooking']);
    Route::get("get-bookings", [BookingController::class,"GetBookings"]);
    Route::get("booking-detail/{id}", [BookingController::class,"GetBookingInfo"]);



});
