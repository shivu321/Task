<?php

namespace App\Models;

use Exception;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use stdClass;

class HotelGuest extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<string>
     */
    protected $fillable = ['booking_id','name','phone_number','dial_code','country_code'];


    /**
     * The attributes that should be mutated to dates.
     *
     * @var array
     */
    protected $dates = ['created_at', 'updated_at'];


    public static function SetInfo($id,$request){
        $info = new self();
        $info->booking_id = $id;
        $info->name = $request->input('name');
        $info->mobile_number = $request->input('mobile_number');
        $info->dial_code = $request->input('dial_code');
        $info->country_code = $request->input('country_code');

        $info->save();
        $res = new stdClass();
        $res->id = $info->id;
        return $res;
    }
}
