<?php

use App\Http\Controllers\AdminServices\AuthController;
use App\Http\Controllers\AdminServices\BookingController;
use App\Http\Controllers\AdminServices\HotelController;
use App\Http\Controllers\AdminServices\RoleController;
use App\Http\Controllers\AdminServices\TransactionController;
use App\Http\Controllers\AdminServices\UserController;
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

Route::group(['middleware' => ['guest:admin']], function () {
    Route::post('login', [AuthController::class, 'Login']);
    Route::post('forgot-password', [AuthController::class, 'ForgetPassword']);
    Route::post('forgot-password/verify', [AuthController::class, 'VerifyOTP']);
    Route::post('reset-password/{token}', [AuthController::class, 'SetPassword']);
    Route::post('otp-resend', [AuthController::class, 'ForgetPassword']);
});

Route::group(['middleware' => ['auth:admin']], function () {

    Route::get('me', [AuthController::class, 'myProfile']);
    Route::put('update-profile', [AuthController::class, 'UpdateProfile']);
    Route::post('reset-password', [AuthController::class, 'ResetPassword']);
    Route::post('upload-profile-image', [AuthController::class, 'SetProfileImage']);
    Route::post('logout', [AuthController::class, 'Logout']);

    Route::get('menus', [RoleController::class, 'getMenus']);
    Route::get('roles', [RoleController::class, 'getRoles']);
    Route::get('role/{role_id}', [RoleController::class, 'getMenus']);
    Route::post('role', [RoleController::class, 'setRoleAccess']);
    Route::put('role/{role_id}', [RoleController::class, 'setRoleAccess']);
    Route::delete('role/{role_id}', [RoleController::class, 'deleteRole']);
    Route::get('left-menus', [RoleController::class, 'getLeftMenu']);
    Route::get('roles-dd', [RoleController::class, 'GetRolesForDropdown']);

    Route::get("users", [UserController::class, "GetList"]);
    Route::get("user/{uuid}", [UserController::class, "GetInfo"]);
    Route::post("user", [UserController::class, "SetInfo"]);
    Route::post("update-status", [UserController::class, "SetStatus"]);
    Route::put("user/{uuid}", [UserController::class, "SetInfo"]);
    Route::put("user/password/{uuid}/set", [UserController::class, "SetPasswordReset"]);
    Route::delete("user/{uuid}", [UserController::class, "destroy"]);


    Route::get("hotels", [HotelController::class, "GetList"]);
    Route::get("hotel/{uuid}", [HotelController::class, "GetInfo"]);
    Route::post("hotel", [HotelController::class, "SetHotel"]);
    Route::put("hotel/{uuid}", [HotelController::class, "SetHotel"]);
    Route::post('upload-thumbnail/{uuid}', [HotelController::class, 'SetHotelImage']);
    Route::delete("hotel/{uuid}", [HotelController::class, "destroy"]);
    Route::get("hotel/{uuid}/rooms", [HotelController::class,'GetHotelRooms']);
    Route::get('room/{uuid}/details/{id}', [HotelController::class,'GetRoomInfo']);
    Route::post("room/{uuid}/hotel", [HotelController::class, "SetRoom"]);
    Route::put("room/{uuid}/hotel/{id}", [HotelController::class, "SetRoom"]);
    Route::post('upload-room/{room_id}/image', [HotelController::class, 'SetHotelRoomImage']);
    Route::delete('delete/{id}/image', [HotelController::class,'DeleteRoomImage']);
    Route::delete('room/{room_id}/delete',[HotelController::class,'DeleteRoom']);
    Route::post('{uuid}/room/{id}/amenities',[HotelController::class, 'SetAmenities']);


    Route::get("get-bookings",[BookingController::class,"GetBookings"]);
    Route::get("booking-detail/{uuid}",[HotelController::class,"GetBookingInfo"]);
    Route::post("update/{bookingId}/status",[BookingController::class,"UpdateBookingStatus"]);

    Route::get("transactions",[TransactionController::class,"GetTransaction"]);
    Route::get("transaction/{uuid}/info",[TransactionController::class,"GetTransactionInfo"]);
    
});
