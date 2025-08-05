<?php

namespace App\Http\Controllers\WebServices;

use App\Http\Controllers\Controller;
use App\Models\Booking;
use App\Models\Hotel;
use App\Models\HotelRoom;
use App\Models\Transaction;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;

class BookingController extends Controller
{
    public  function GetHotels(Request $request)
    {
        try {
            $list = Hotel::GetHotelList($request);
            return $this->sendSuccess(trans("messages.OK"), Response::HTTP_OK, $list);
        } catch (Exception $th) {
            return $this->sendError($th->getMessage(), Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    public function GetDetailInfo(Request $request,$uuid)
    {
        try {
            $info = Hotel::GetInfoByUuid($uuid,$request->input("from_date"), $request->input("to_date"));
            if (!$info) {
                return $this->sendError(trans("messages.RECORD_NOT_FOUND"));
            }
            return $this->sendSuccess(trans("messages.OK"), Response::HTTP_OK, ["info" => $info]);
        } catch (Exception $th) {
            return $this->sendError($th->getMessage(), Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    public function GenerateTransaction(Request $request)
    {
        $validator = Validator::make($request->all(), [
            "hotel_id" => "required",
            "room_id" => "required",
            'no_of_night' => "required",
            "from_date" => "required|date|date_format:y-m-d",
            "to_date" => "required|date|date_format:y-m-d",
        ]);
        try {
            DB::beginTransaction();
            $getHotel = Hotel::where('id', $request->input('hotel_id'))->first();
            if (!$getHotel) {
                return $this->sendError(trans('messages.RECORD_NOT_FOUND'));
            }
            $getRoom = HotelRoom::where('id', $request->input('room_id'))->first();
            if (!$getRoom) {
                return $this->sendError(trans('messages.RECORD_NOT_FOUND'));
            }

            $priceBreakup = [];
            $no_of_night = $request->input('no_of_night');
            $priceBreakup['base_price'] = $getRoom->price * $no_of_night;
            $priceBreakup['disc_price'] = $getRoom->disc_price * $no_of_night;
            $priceBreakup['tax_amount'] = ($getRoom->tax_amount * $no_of_night);
            $priceBreakup['tax'] = $getRoom->tax;

            $transInfo =  Transaction::SetTransaction(
                $getRoom->tax,
                data_get($priceBreakup, 'tax_amount'),
                0,
                data_get($priceBreakup, 'disc_price'),
                data_get($priceBreakup, 'disc_price') + data_get($priceBreakup, 'tax_amount')
            );
            if ($transInfo->id) {
                Booking::SetInfo($request, $transInfo->id);
            }
            DB::commit();
            return $this->sendSuccess(trans('messages.BOOKING_SUCCESSFULLY'), Response::HTTP_OK, [
                'uuid' => $transInfo->uuid,
                "order_id" => "order_" . Str::random(10),
                "amount" => (data_get($priceBreakup, 'disc_price') + data_get($priceBreakup, 'tax_amount') ) * 100 ,
                "currency" => "INR",
                "razorpay_key" => "rzp_test_xxxxxxxx"
            ]);
        } catch (Exception $th) {
            DB::rollBack();
            return $this->sendError($th->getMessage(), Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    public function GetBookings(Request $request){
        try {
            $list = Booking::GetMyBooking($request);
            return $this->sendSuccess(trans("messages.OK"), Response::HTTP_OK, $list);
       } catch (Exception $th) {
            return $this->sendError($th->getMessage(), Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    public function GetBookingInfo(Request $request,$id){
        try {
            $info = Booking::GetInfoById($id);
            if(!$info){
                return $this->sendError(trans("messages.RECORD_NOT_FOUND"), Response::HTTP_FAILED_DEPENDENCY);
            }
            return $this->sendSuccess(trans("messages.OK"), Response::HTTP_OK, ['info' => $info]);
        } catch (Exception $th) {
            return $this->sendError($th->getMessage(), Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }
}
