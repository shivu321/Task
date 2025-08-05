<?php

namespace App\Models;

use App\Helpers\UtilityHelper;
use Exception;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Ramsey\Uuid\Uuid;
use stdClass;

class Hotel extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = ['uuid', 'added_by', 'updated_by', 'name', 'email', 'phone', 'address', 'city', 'state', 'country', 'pincode', 'description', 'image', 'status'];

    /**
     * The attributes that should be mutated to dates.
     *
     * @var array
     */
    protected $dates = ['created_at', 'updated_at'];

    public const PerPageRecord = 10;

    public function room()
    {
        return $this->hasMany(HotelRoom::class, "hotel_id", "id")->with(['images', 'amenities']);
    }

    public function added_us()
    {
        return $this->hasOne(AdminUser::class, 'id', 'added_by')->select("id", "name");
    }
    public function updated_us()
    {
        return $this->hasOne(AdminUser::class, 'id', 'updated_by')->select("id", "name");
    }

    public function getImageAttribute($value)
    {
        $folder = "thumbnail";
        return UtilityHelper::GetDocumentURL($folder, $value);
    }

    public function hotel_price()
    {
        return $this->hasOne(HotelRoom::class, "hotel_id", "id");
    }

   


    public static function GetInfoById($Id)
    {
        return self::where('id', '=', $Id)->with(['room', 'added_us', 'updated_us'])->first();
    }
    public static function GetInfoByUuid($uuid, $from_date = null, $to_date = null)
    {
        return self::where('uuid', $uuid)
            ->with([
                'room' => function ($query) use ($from_date, $to_date) {
                    if ($from_date && $to_date) {
                        $query->whereDoesntHave('bookings', function ($bookingQuery) use ($from_date, $to_date) {
                            $bookingQuery->where('status', 'COMPLETED')
                                ->where(function ($q) use ($from_date, $to_date) {
                                    $q->whereBetween('from_date', [$from_date, $to_date])
                                        ->orWhereBetween('to_date', [$from_date, $to_date])
                                        ->orWhere(function ($q2) use ($from_date, $to_date) {
                                            $q2->where('from_date', '<=', $from_date)
                                                ->where('to_date', '>=', $to_date);
                                        });
                                });
                        });
                    }
                },
                'added_us',
                'updated_us'
            ])
            ->first();
    }


    public static function GetList($request)
    {
        $keyword = getValue($request->input('keyword'));
        $status = getValue($request->input('status'), "ALL");
        $offset = getValue($request->input('offset'), 0);
        $sort_by = getValue($request->input('sort_by'), 'created_at');
        $order_by = getValue($request->input('order_by'), 'DESC');

        $query = self::query()->with(['room', 'added_us', 'updated_us'])
            ->when($status != "ALL", function ($q) use ($status) {
                $q->where("status", $status);
            })

            ->when($keyword, function ($query) use ($keyword) {
                $query->where(function ($q) use ($keyword) {
                    $q->where('name', 'LIKE', '%' . $keyword . '%')
                        ->orWhere('address', 'LIKE', '%' . $keyword . '%');
                });
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


    public static function SetInfo($request, $uuid = null)
    {
        if (!is_null($uuid)) {
            $hotel = self::where('uuid', $uuid)->first();
            if (!$hotel) {
                throw new Exception(trans("messages.RECORD_NOT_FOUND"));
            }
            $hotel->updated_by = ADMIN_USER_ID();
            $message = trans("messages.RECORD_UPDATED_SUCCESSFULLY");
        } else {
            $hotel = new Hotel();
            $hotel->added_by = ADMIN_USER_ID();
            $hotel->uuid = Uuid::uuid4();
            $hotel->status = "DRAFT";
            $message = trans("messages.RECORDS_SAVED_SUCCESSFULLY");
        }

        $hotel->name = getValue($request->input("name"));
        $hotel->address = getValue($request->input("address"));
        $hotel->city = getValue($request->input("city"));
        $hotel->email = getValue($request->input("email"));
        $hotel->state = getValue($request->input("state"));
        $hotel->country = getValue($request->input("country"));
        $hotel->phone = getValue($request->input("phone"));
        $hotel->description = getValue($request->input("description"));
        $hotel->pincode = getValue($request->input("pincode"));

        $hotel->save();

        $res = new stdClass();
        $res->message = $message;
        $res->uuid = $hotel->uuid;

        return $res;
    }

    public static function GetHotelList($request)
    {
        $keyword = getValue($request->input("keyword"));
        $offset = getValue($request->input("offset"), 0);
        $no_of_guest = getValue($request->input("no_of_guest"));
        $sort_by = getValue($request->input("sort_by"), 'hotels.created_at');
        $order_by = getValue($request->input("order_by"), 'DESC');
        $from_date = getValue($request->input("from_date"));
        $to_date = getValue($request->input("to_date"));

        $query = Hotel::with(['hotel_price'])->leftJoin("hotel_rooms", function ($qu) {
            $qu->on("hotels.id", "=", "hotel_rooms.hotel_id");
        })->leftJoin("bookings", function ($qu) use ($from_date, $to_date) {
            $qu->on("hotel_rooms.id", "=", "bookings.room_id")
                ->where("bookings.status", "COMPLETED")
                ->where("bookings.user_id", USER_ID());

            if (!empty($from_date) && !empty($to_date)) {
                $qu->where(function ($q) use ($from_date, $to_date) {
                    $q->whereDate("bookings.from_date", "<=", $to_date)
                        ->whereDate("bookings.to_date", ">=", $from_date);
                });
            }
        })
            ->when(!empty($keyword), function ($q) use ($keyword) {
                $q->where(function ($query) use ($keyword) {
                    $query->where("hotels.name", "LIKE", "%" . trim($keyword) . "%")
                        ->orWhere("hotels.state", "LIKE", "%" . trim($keyword) . "%")
                        ->orWhere("hotels.country", "LIKE", "%" . trim($keyword) . "%")
                        ->orWhere("hotels.city", "LIKE", "%" . trim($keyword) . "%");
                });
            })
            ->where(function ($q) {
                $q->whereNotNull("bookings.id")
                    ->orWhereNull("bookings.id");
            })->where("hotels.status", "ACTIVE")
            ->orderBy($sort_by, $order_by)
            ->groupBy("hotels.id");


        $data['count'] = count($query->get());
        if (isset($offset)) {
            $query->offset($offset * self::PerPageRecord);
            $query->limit(self::PerPageRecord);
        }
        $data['list'] = $query->select("hotels.*")->get();
        $data['next_offset'] = ((count($data['list']) == self::PerPageRecord) ? (isset($offset) ? ((int) $offset + 1) : 0) : -1);
        return $data;
    }
}
