<?php

namespace App\Http\Controllers\AdminServices;

use App\Helpers\UserAccessHelper;
use App\Http\Controllers\Controller;
use App\Models\Booking;
use App\Models\Transaction;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

class BookingController extends Controller
{
    protected $_userId;
    protected $access = null;
    public function __construct()
    {
        $this->_userId = ADMIN_USER_ID();
        $this->access = UserAccessHelper::getAccess($this->_userId, UserAccessHelper::$code_manage_booking);
    }

    public function GetBookings(Request $request)
    {
        try {
            if ($this->access->can_read == 1) {
                $list = Booking::GetList($request);
                $list['access'] = $this->access;
                return $this->sendSuccess("success", Response::HTTP_OK, $list);
            }
            return $this->sendSuccess("success", Response::HTTP_OK, ['list' => [], 'access' => $this->access]);
        } catch (Exception $e) {
            return $this->sendError($e->getMessage());
        }
    }

    public function GetBookingIfo($uuid)
    {
        try {
            $info = Booking::GetInfoByUuid($uuid);
            if (!$info) {
                return $this->sendError(trans('messages.RECORD_NOT_FOUND'));
            }
            return $this->sendSuccess("success", Response::HTTP_OK, ['info' => $info, 'access' => $this->access]);
        } catch (Exception $e) {
            return $this->sendError($e->getMessage());
        }
    }

    public function UpdateBookingStatus(Request $request,$id){
        try {
            $info = Booking::where('id', $id)->first();
            if(!$info){
                return $this->sendError(trans('messages.RECORD_NOT_FOUND'));
            }
            if($info->status === 'PENDING'){
                $info->status = $request->input('status');
                $info->save();
                return $this->sendSuccess(trans('messages.STATUS_UPDATED_SUCCESSFULLY'));
            }
            return $this->sendError(trans('messages.SOMETHING_WENT_WRONG'));
        } catch (Exception $e) {
            return $this->sendError($e->getMessage());
        }
    }

    
}
