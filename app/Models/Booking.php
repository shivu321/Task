<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Booking extends Model
{
    use HasFactory;
    /**
     * The attributes that are mass assignable.
     *
     * @var array<string>
     */
    protected $fillable = ['transaction_id', 'hotel_id', 'room_id', 'user_id', 'from_date', 'to_date'];

    /**
     * The attributes that should be mutated to dates.
     *
     * @var array
     */
    protected $dates = ['created_at', 'updated_at'];


    public function room(){
        return $this->hasOne(HotelRoom::class,'id','room_id');
    }
    public function hotel(){
        return $this->hasOne(Hotel::class,'id','hotel_id');
    }

    public function user(){
        return $this->hasOne(User::class,'id','user_id');
    }

    public function transaction(){
        return $this->hasOne(Transaction::class,'id','transaction_id');
    }

    public const PENDING = "PENDING"; 
    public const BOOKED = "BOOKED";
    public const COMPLETED = "COMPLETED";
    public const CANCELLED = "CANCELLED";

    public const PerPageRecord = 10;

    public static function SetInfo($request, $id)
    {
        $res = new Booking();
        $res->transaction_id = $id;
        $res->user_id = USER_ID();
        $res->hotel_id = $request->input('hotel_id');
        $res->room_id = $request->input('room_id');
        $res->from_date = $request->input('from_date');
        $res->to_date = $request->input('to_date');
        $res->status = self::PENDING;

        $res->save();
    }

    public static function GetInfoByUuid($uuid){
        return Booking::where('uuid','=', $uuid)->with(['room', 'hotel','user'])->first();
    }
    public static function GetInfoById($id){
        return Booking::where('id','=', $id)->with(['room','room.images', 'hotel','user','transaction'])->first();
    }


    public static function GetList($request)
    {
        $status = getValue($request->input('status'), "ALL");
        $offset = getValue($request->input('offset'), 0);
        $sort_by = getValue($request->input('sort_by'), 'created_at');
        $order_by = getValue($request->input('order_by'), 'DESC');

        $query = self::query()->with(['room', 'hotel','user'])
            ->when($status != "ALL", function ($q) use ($status) {
                $q->where("status", $status);
            })
            ->orderBy($sort_by, $order_by);

        $data['count'] = count($query->get());
        if (isset($offset)) {
            $query->offset($offset * self::PerPageRecord);
            $query->limit(self::PerPageRecord);
        }
        $data['list'] = $query->get();
        $data['next_offset'] = ((count($data['list']) == self::PerPageRecord) ? (isset($offset) ? ((int) $offset + 1) : 0) : -1);
        return $data;
    }
    public static function GetMyBooking($request)
    {
        $status = getValue($request->input('status'), "ALL");
        $offset = getValue($request->input('offset'), 0);
        $sort_by = getValue($request->input('sort_by'), 'created_at');
        $order_by = getValue($request->input('order_by'), 'DESC');

        $query = self::query()->where("user_id",USER_ID())->with(['room', 'hotel','transaction'])
            ->when($status != "ALL", function ($q) use ($status) {
                $q->where("status", $status);
            })
            ->orderBy($sort_by, $order_by);

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
