<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Ramsey\Uuid\Uuid;
use stdClass;

class AdminOtp extends Model
{
    use HasFactory;

    protected $fillable = ['admin_id', 'otp', 'expired_at'];

    /**
     * The attributes that should be hidden for arrays.
     *
     * @var array
     */
    protected $hidden = ['created_at', 'updated_at'];

    public static function SendOTP($adminId ,$otpType = null)
    {
        // $otp = rand(000000, 999999);
        $otp = "123456";
        $expired_at = date("Y-m-d H:i:s", strtotime("+10 minutes"));

        if ($adminId) {
            $otpInfo = AdminOtp::where("admin_id", $adminId)->first();
            if (!$otpInfo) {
                $otpInfo = new AdminOtp;
            }
        }
        $otpInfo->admin_id = $adminId;
        $otpInfo->ref_no = Uuid::uuid4();
        $otpInfo->otp = $otp;
        $otpInfo->otp_type = $otpType;
        $otpInfo->expired_at = $expired_at;
        $otpInfo->save();

        // EmailHelper::sendPasswordResetOtp($otp);

        $res = new stdClass;
        $res->id = $otpInfo->id;
        $res->otp = $otpInfo->otp;
        $res->message = trans("messages.OTP_SUCCESSFULLY_SEND");
        return $res;
    }
}
