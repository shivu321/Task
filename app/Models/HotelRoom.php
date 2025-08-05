<?php

namespace App\Models;

use App\Helpers\UtilityHelper;
use Exception;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Log;
use stdClass;

class HotelRoom extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<string>
     */
    protected $fillable = ['hotel_id', 'no_of_guest', 'title', 'description', 'price', 'disc_price', 'tax', 'status'];


    /**
     * The attributes that should be mutated to dates.
     *
     * @var array
     */
    protected $dates = ['created_at', 'updated_at'];

    protected $appends = ['tax_amount'];

    public const PerPageRecord = 10;

    public function getTaxAmountAttribute()
    {
        if ($this->tax > 0 && $this->disc_price > 0) {
            return $this->disc_price * $this->tax / 100;
        }
        return 0;
    }

     public function bookings(){
        return $this->hasMany(Booking::class,"room_id","id");
    }

    public function images()
    {
        return $this->hasMany(RoomImage::class, 'room_id', 'id')->select("id", 'room_id', 'image');
    }


    public function amenities()
    {
        return $this->hasMany(RoomAmenitie::class, 'room_id', 'id')->select("id", 'room_id', 'title');
    }

    public static function getInfoById($id)
    {
        return self::where('id', $id)->with(['amenities', 'images'])->first();
    }

    public static function SetInfo($request, $uuid, $id = null)
    {

        $amenities = $request->input('amenities');
        $hotel = Hotel::where('uuid', $uuid)->first();
        if (!$hotel) {
            throw new Exception(trans('messages.RECORD_NOT_FOUND'));
        }
        if (!is_null($id)) {
            $room = HotelRoom::where('id', $id)->first();
            if (!$room) {
                throw new Exception(trans('messages.RECORD_NOT_FOUND'));
            }
            $message = trans("messages.RECORD_UPDATED_SUCCESSFULLY");
        } else {
            $room = new HotelRoom();
            $room->status = "VACANT";
            $message = trans("messages.RECORD_UPDATED_SUCCESSFULLY");
        }
        $room->hotel_id = $hotel->id;
        $room->no_of_guest = getValue($request->input("no_of_guest"));
        $room->title = getValue($request->input("title"));
        $room->description = getValue($request->input("description"));
        $room->price = getValue($request->input("price"));
        $room->disc_price = getValue($request->input("disc_price"));
        $room->tax = getValue($request->input("tax"));

        $room->save();

        $hotel->status = "ACTIVE";
        $hotel->save();


        Log::info("Room Id is" . $room);

        if (count($amenities) > 0) {
            RoomAmenitie::where('room_id', $room->id)->delete();
            foreach ($amenities as $amenity) {
                $setInfo = new RoomAmenitie();
                $setInfo->room_id = $room->id;
                $setInfo->title = $amenity;
                $setInfo->save();
            }
        }

        $res = new stdClass();
        $res->id = $room->id;
        $res->message = $message;

        return $res;
    }

    public static function GetList($uuid)
    {
        $getHotelInfo = Hotel::where("uuid", $uuid)->first();
        if (!$getHotelInfo) {
            throw new Exception(trans("messages.RECORD_NOT_FOUND"));
        }

        // $offset = getValue($request->input('offset'), 0);
        // $sort_by = getValue($request->input('sort_by'), 'created_at');
        // $order_by = getValue($request->input('order_by'), 'DESC');

        $query = self::query()->where('hotel_id', $getHotelInfo->id)->with(['images', 'amenities']);
        $data['count'] = count($query->get());
        if (isset($offset)) {
            $query->offset($offset * self::PerPageRecord);
            $query->limit(self::PerPageRecord);
        }
        $data['list'] = $query->get();
        $data['next_offset'] = ((count($data['list']) == self::PerPageRecord) ? (isset($offset) ? ((int) $offset + 1) : 0) : -1);
        return $data;
    }
}
