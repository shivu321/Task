<?php

namespace App\Models;

use App\Helpers\UtilityHelper;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class UserOtp extends Model
{
    use HasFactory;

    protected $fillable = ['user_id ','otp','email','expired_at'];


    /**
     * The attributes that should be mutated to dates.
     *
     * @var array
     */
    protected $dates = ['created_at', 'updated_at'];



    public const SignupOTP = "SIGNUP";
    public const LoginOTP = "LOGIN";
    public const CHANGE_MOBILE_NUMBER_OTP = "CHANGE_MOBILE_NUMBER";
    public const CHANGE_EMAIL_OTP = "CHANGE_EMAIL";
    public const FORGET_PASSWORD_OTP = "FORGET_PASSWORD";


    public static function SendOTP($user_id, $email)
    {
        // $otp = rand(100000, 999999);
        $otp = "123456";
        $user_otp = new self;
        $user_otp->user_id = $user_id;
        $user_otp->email = $email;
        $user_otp->expired_at = date("Y-m-d H:i:s", strtotime("+5 minutes"));
        $user_otp->otp = $otp;
        $user_otp->save();
        return  $user_otp->otp;
    }
}
