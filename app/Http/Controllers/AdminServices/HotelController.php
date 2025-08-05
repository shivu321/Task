<?php

namespace App\Http\Controllers\AdminServices;

use App\Helpers\UserAccessHelper;
use App\Http\Controllers\Controller;
use App\Models\Booking;
use App\Models\Hotel;
use App\Models\HotelRoom;
use App\Models\RoomAmenitie;
use App\Models\RoomImage;
use App\Traits\UploadTrait;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

class HotelController extends Controller
{
    use UploadTrait;
    protected $_userId;
    protected $access = null;
    public function __construct()
    {
        $this->_userId = ADMIN_USER_ID();
        $this->access = UserAccessHelper::getAccess($this->_userId, UserAccessHelper::$code_manage_hotel);
    }



    public function GetList(Request $request)
    {
        try {
            if ($this->access->can_read == 1) {
                $list = Hotel::GetList($request);
                $list['access'] = $this->access;
                return $this->sendSuccess("success", Response::HTTP_OK, $list);
            }
            return $this->sendSuccess("success", Response::HTTP_OK, ['list' => [], 'access' => $this->access]);
        } catch (Exception $e) {
            return $this->sendError($e->getMessage());
        }
    }

    public function GetInfo($uuid)
    {
        try {
            $info = Hotel::GetInfoByUuid($uuid);
            if (!$info) {
                return $this->sendError(trans('messages.RECORD_NOT_FOUND'));
            }
            return $this->sendSuccess("success", Response::HTTP_OK, ['info' => $info, 'access' => $this->access]);
        } catch (Exception $e) {
            return $this->sendError($e->getMessage());
        }
    }

    public function SetHotel(Request $request, $uuid = null)
    {
        $validator = Validator::make($request->all(), [
            "name" => "required",
            "description" => "required",
            "email" => "required|email",
            "phone" => "required",
            "address" => "required",
            "city" => "required",
            "state" => "required",
            "country" => "required",
            "pincode" => "required",
        ]);

        if ($validator->fails()) {
            return $this->sendError($validator->errors()->first(), Response::HTTP_FAILED_DEPENDENCY);
        }
        try {
            DB::beginTransaction();
            $setData = Hotel::SetInfo($request, $uuid);
            DB::commit();
            return $this->sendSuccess($setData->message, Response::HTTP_OK, ['uuid' => $setData->uuid]);
        } catch (Exception $e) {
            DB::rollBack();
            return $this->sendError($e->getMessage());
        }
    }

    public function SetHotelImage(Request $request, $uuid)
    {
        $validator = Validator::make($request->all(), [
            'image' => 'required|image|mimes:jpg,png,gif,jpeg'
        ]);
        if ($validator->fails()) {
            return $this->sendError($validator->errors()->first(), Response::HTTP_FAILED_DEPENDENCY);
        }
        try {
            $hotelInfo = Hotel::GetInfoByUuid($uuid);
            if (!$hotelInfo) {
                return $this->sendError(trans('messages.RECORD_NOT_FOUND'));
            }
            if ($request->hasFile('image')) {
                $image = $request->file('image');
                $name = 'IMG_' . $hotelInfo->id . '@' . time() . '.' . $image->getClientOriginalExtension();
                $folder = config('constants.HOTEL_THUMBNAIL');
                $this->uploadOne($image, $folder, $name);
                $old_logo = $hotelInfo->image;
                $hotelInfo->image = $name;
                $hotelInfo->save();

                if ($old_logo) {
                    $this->deleteOne($folder, $old_logo);
                }
                return $this->sendSuccess(trans('messages.THUMBNAIL_UPLOADED_SUCCESSFULLY'), Response::HTTP_OK, ['path' => $this->getURL($folder, $name), 'name' => $name]);
            }

            return $this->sendError(trans('messages.SOMETHING_WENT_WRONG'));
        } catch (Exception $e) {
            return $this->sendError($e->getMessage());
        }
    }

    public function SetRoom(Request $request, $uuid, $id = null)
    {
        $validator = Validator::make($request->all(), [
            "no_of_guest" => "required|numeric",
            "title" => "required|string",
            "description" => "required|string",
            "price" => "required|numeric",
            "tax" => "required|numeric"
        ]);

        if ($validator->fails()) {
            return $this->sendError($validator->errors()->first(), Response::HTTP_FAILED_DEPENDENCY);
        }
        try {
            $setRoom = HotelRoom::SetInfo($request, $uuid, $id);
            return $this->sendSuccess($setRoom->message, Response::HTTP_OK, ['id' => $setRoom->id]);
        } catch (Exception $e) {
            return $this->sendError($e->getMessage() . ' '. $e->getFile() .' '. $e->getLine());
        }
    }

    public function SetHotelRoomImage(Request $request, $uuid)
    {
        $validator = Validator::make($request->all(), [
            'image' => 'required|image|mimes:jpg,png,gif,jpeg'
        ]);
        if ($validator->fails()) {
            return $this->sendError($validator->errors()->first(), Response::HTTP_FAILED_DEPENDENCY);
        }
        try {
            $roomInfo = HotelRoom::getInfoById($uuid);
            if (!$roomInfo) {
                return $this->sendError(trans('messages.RECORD_NOT_FOUND'));
            }
            if ($request->hasFile('image')) {
                $image = $request->file('image');
                $roomImage = new RoomImage();
                $name = 'IMG_' . rand(122, 1154525) . '@' . time() . '.' . $image->getClientOriginalExtension();
                $folder = config('constants.HOTEL_ROOM');
                $this->uploadOne($image, $folder, $name);
                $old_logo = $roomImage->image;
                $roomImage->image = $name;
                $roomImage->room_id = $roomInfo->id;
                $roomImage->save();

                if ($old_logo) {
                    $this->deleteOne($folder, $old_logo);
                }
                return $this->sendSuccess(trans('messages.THUMBNAIL_UPLOADED_SUCCESSFULLY'), Response::HTTP_OK, ['path' => $this->getURL($folder, $name), 'name' => $name]);
            }

            return $this->sendError(trans('messages.SOMETHING_WENT_WRONG'));
        } catch (Exception $e) {
            return $this->sendError($e->getMessage());
        }
    }

    public function DeleteRoomImage($id)
    {
        try {
            $info = RoomImage::where('id', $id)->first();
            if (!$info) {
                return $this->sendError(trans('messages.RECORD_NOT_FOUND'));
            }
            $folder = config('constants.HOTEL_ROOM');
            if ($info->image) {
                $this->deleteOne($folder, $info->image);
                $info->delete();
                return $this->sendSuccess(trans('messages.IMAGE_DELETED_SUCCESSFULLY'));
            }
            return $this->sendError(trans('messages.SOMETHING_WENT_WRONG'));
        } catch (Exception $e) {
            return $this->sendError($e->getMessage());
        }
    }

    public function DeleteRoom($id)
    {
        try {
            $info = HotelRoom::where('id', $id)->first();
            if (!$info) {
                return $this->sendError(trans('messages.RECORD_NOT_FOUND'));
            }
            $today = date("Y-m-d");

            $getBookingInfo = Booking::where('room_id', $id)
                ->where(function ($query) use ($today) {
                    $query->where(function ($q) use ($today) {
                        $q->where('from_date', '<=', $today)
                            ->where('to_date', '>=', $today);
                    })->orWhere(function ($q) use ($today) {
                        $q->where('from_date', '>', $today);
                    });
                })
                ->first();
            if ($getBookingInfo) {
                return $this->sendError(trans('messages.PERMISSION_DENIED'));
            }
            $info->delete();
            return $this->sendSuccess(trans('messages.ROOM_DELETED_SUCCESSFULLY'));
        } catch (Exception $e) {
            return $this->sendError($e->getMessage());
        }
    }

    public function SetAmenities(Request $request, $uuid, $id)
    {
        $validator = Validator::make($request->all(), [
            'amenities' => 'required|array',
        ]);
        if ($validator->fails()) {
            return $this->sendError($validator->errors()->first(), Response::HTTP_FAILED_DEPENDENCY);
        }
        try {
            $hotelInfo = Hotel::where('uuid', $uuid)->first();
            if (!$hotelInfo) {
                return $this->sendError(trans('messages.RECORD_NOT_FOUND'));
            }
            $roomInfo = HotelRoom::where('id', $id)->first();
            if (!$roomInfo) {
                return $this->sendError(trans('messages.RECORD_NOT_FOUND'));
            }
            $amenities = $request->input('amenities');
            if (count($amenities) > 0) {
                RoomAmenitie::where('room_id', $roomInfo->id)->delete();
                foreach ($amenities as $room) {
                    $setInfo = new RoomAmenitie();
                    $setInfo->room_id = $roomInfo->id;
                    $setInfo->title = data_get($room, 'title');
                    $setInfo->save();
                }
                return $this->sendSuccess(trans('messages.RECORDS_SAVED_SUCCESSFULLY'));
            }
            return $this->sendError(trans('messages.SOMETHING_WENT_WRONG'));
        } catch (Exception $e) {
            return $this->sendError($e->getMessage());
        }
    }

    public function GetHotelRooms($uuid)
    {
        try {
            if ($this->access->can_read == 1) {
                $list = HotelRoom::GetList($uuid);
                $list['access'] = $this->access;
                return $this->sendSuccess("success", Response::HTTP_OK, $list);
            }
            return $this->sendSuccess("success", Response::HTTP_OK, ['list' => [], 'access' => $this->access]);
        } catch (Exception $e) {
            return $this->sendError($e->getMessage());
        }
    }

    public function GetRoomInfo($uuid, $roomId)
    {
        try {
            $hotelInfo = Hotel::where('uuid', $uuid)->first();
            if (!$hotelInfo) {
                return $this->sendError(trans('messages.RECORD_NOT_FOUND'));
            }
            $room = HotelRoom::where('id', $roomId)
                ->where('hotel_id', $hotelInfo->id)->with(['amenities','images'])
                ->first();

            if (!$room) {
                return $this->sendError(trans('messages.RECORD_NOT_FOUND'));
            }
            return $this->sendSuccess(trans('messages.OK'),Response::HTTP_OK,["info" => $room]);
        } catch (Exception $e) {
            return $this->sendError($e->getMessage());
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
