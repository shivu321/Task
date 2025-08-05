<?php

namespace App\Http\Controllers\WebServices;

use App\Helpers\EmailHelper;
use App\Http\Controllers\Controller;
use App\Models\UserOtp;
use Illuminate\Http\Request;
use App\Http\Requests\LoginRequest;
use App\Http\Requests\RegisterRequest;
use App\Models\User;
use Exception;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;

class AuthController extends Controller
{
    public function Login(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'email' => 'required|email',
            'password' => 'required|min:6|max:20'
        ]);
        if ($validator->fails()) {
            return $this->sendError($validator->errors()->first(), Response::HTTP_FAILED_DEPENDENCY);
        }
        $email = $request->input('email');
        $password = $request->input('password');
        $checkUser = User::validateUserEmail($email);
        if (! $checkUser) {
            return $this->sendError(trans("messages.EMAIL_NOT_FOUND"), Response::HTTP_FAILED_DEPENDENCY);
        }
        try {
            if (Hash::check($password, $checkUser->password)) {
                $token = $checkUser->createToken("hotel_management_task")->plainTextToken;
                return $this->sendSuccess(trans("messages.LOGIN_SUCCESSFULLY"), Response::HTTP_OK, ["access_token" => $token]);
            } else {
                return $this->sendError(trans("messages.INCORRECT_PASSWORD"), Response::HTTP_FAILED_DEPENDENCY);
            }
        } catch (Exception $e) {
            return $this->sendError($e->getMessage());
        }
    }

    public function RegisterUser(Request $request)
    {
        $validator = Validator::make($request->all(), [
            "name" => "required",
            "email" => "required",
            "password" => "required",
        ]);

        if ($validator->fails()) {
            return $this->sendError($validator->errors()->first(), Response::HTTP_BAD_REQUEST);
        }
        $checkEmail = User::validateEmail($request->input("email"));
        if ($checkEmail) {
            return $this->sendError(trans("messages.EMAIL_ALREADY_EXIST"), Response::HTTP_BAD_REQUEST);
        }
        try {
            DB::beginTransaction();
            $setInfo = User::SetInfo($request);
            $userInfo = User::where("uuid", $setInfo->uuid)->first();
            if (!$userInfo) {
                return $this->sendError(trans("messages.RECORD_NOT_FOUND"));
            }
            if ($setInfo) {
                $token = $userInfo->createToken("hotel_management")->plainTextToken;
                DB::commit();
                return $this->sendSuccess($setInfo->message, Response::HTTP_OK, ['uuid' => $setInfo->uuid, 'access_token' => $token]);
            }
            return $this->sendError(trans('messages.SOMETHING_WENT_WRONG'), Response::HTTP_BAD_REQUEST);
        } catch (Exception $th) {
            DB::rollBack();
            return $this->sendError($th->getMessage(), $th->getCode());
        }
    }

    public function VerifyOTP(Request $request)
    {
        $validator = Validator::make(
            $request->all(),
            [
                'otp' => "required|string|min:6",
                'email' => 'required|email:rfc,dns',
            ]
        );

        if ($validator->fails()) {
            return $this->sendError($validator->errors()->first());
        }

        try {
            $email = $request->input('email');
            $userInfo = User::where('email', $email)->first();
            if (!$userInfo) {
                return $this->sendError(trans("messages.INCORRECT_EMAIL"));
            }
            $validate_otp = UserOtp::where('otp', $request->input('otp'))
                ->where('user_id', $userInfo->id)
                ->orderBy('id', 'desc')
                ->first();

            if ($validate_otp) {
                $userInfo->save();
                if ($validate_otp->expired_at >= date("Y-m-d H:i:s")) {
                    return $this->sendSuccess(trans('messages.OK'), Response::HTTP_OK, [
                        "message" =>  trans("messages.OTP_HAS_BEEN_VERIFIED_SUCCESSFULLY"),
                        "has_verified" => true,
                        "remember_token" => $userInfo->remember_token,
                        "name" => $userInfo->name
                    ]);
                } else {
                    throw new Exception(trans("messages.OTP_HAS_BEEN_EXPIRED"));
                }
            }
            throw new Exception(trans("messages.INVALID_OTP_ENTERED"));
        } catch (Exception $e) {
            return $this->sendError($e->getMessage());
        }
    }

    public function Logout(Request $request)
    {
        if (Auth::guard('api')->check()) {
            $token = $request->user()->tokens();
            $token->delete();
        }

        return $this->sendSuccess(trans('messages.OK'));
    }

    public function ForgetPassword(Request $request)
    {

        $validator = Validator::make($request->all(), [
            "email" => "required|email:rfc,dns"
        ]);

        if ($validator->fails()) {
            return $this->sendError($validator->errors()->first());
        }
        try {
            $email = $request->input("email");
            $checkEmail = User::ValidateEmail($email);
            if (!$checkEmail) {
                return $this->sendError(trans("messages.USER_INFO_NOT_FOUND"));
            }
            $remember_token = Str::random(15);
            $checkEmail->remember_token = $remember_token;
            $checkEmail->save();


            $otp = UserOtp::SendOTP(
                $checkEmail->id,
                $email,
            );
            $data = [
                'user_name' => $checkEmail->name,
                "email" => $checkEmail->email,
                'otp' => $otp,
            ];

            EmailHelper::sendOTP($data,"Forget Password");
            return $this->sendSuccess(trans("messages.OTP_SUCCESSFULLY_SEND_EMAIL"), Response::HTTP_OK, ["has_send_otp" => true]);
        } catch (Exception $e) {
            return $this->sendError($e->getMessage());
        }
    }


    public function ResetPassword(Request $request, $token)
    {
        $validator = Validator::make($request->all(), [
            "password" => "required"
        ]);

        if ($validator->fails()) {
            return $this->sendError($validator->errors()->first());
        }
        try {
            $userInfo = User::where("remember_token", $token)->first();
            if (!$userInfo) {
                return $this->sendError(trans("messages.USER_INFO_NOT_FOUND"));
            }

            if (!is_null($userInfo->password) || !empty($userInfo->password)) {
                if (Hash::check($request->input("password"), $userInfo->password)) {
                    return $this->sendError(trans("messages.PASSWORD_SHOULD_NOT_SAME"));
                }
            }
            $userInfo->password = Hash::make($request->input("password"));
            $userInfo->remember_token = null;
            $userInfo->save();

            return $this->sendSuccess(trans("messages.PASSWORD_UPDATED_SUCCESSFULLY"));
        } catch (Exception $e) {
            return $this->sendError($e->getMessage());
        }
    }

     public function SetPassword(Request $request, $token)
    {
        $validator = Validator::make($request->all(), [
            'password' => 'required'
        ]);

        if ($validator->fails()) {
            return $this->sendError($validator->errors()->first(), Response::HTTP_FAILED_DEPENDENCY);
        }
        try {
            $adminInfo = User::where('remember_token', $token)->first();
            if (!$adminInfo) {
                return $this->sendError(trans("messages.LINK_EXPIRED"), Response::HTTP_BAD_REQUEST);
            }

            $adminInfo->remember_token = null;
            $adminInfo->password = Hash::make($request->input("password"));
            $adminInfo->save();

            return $this->sendSuccess(trans("messages.PASSWORD_HAS_BEEN_SET"));
        } catch (Exception $e) {
            return $this->sendError($e->getMessage());
        }
    }
}
