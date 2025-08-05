<?php

namespace App\Http\Controllers\AdminServices;

use App\Helpers\EmailHelper;
use App\Http\Controllers\Controller;
use App\Models\AdminOtp;
use App\Models\AdminUser;
use App\Traits\UploadTrait;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;

class AuthController extends Controller
{
    use UploadTrait;
    public function login(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'email' => 'required|email',
            'password' => 'required|string',
        ], [
            'email.required' => trans('messages.ENTER_EMAIL'),
            'password.required' => trans('messages.ENTER_PASSWORD'),
        ]);

        if ($validator->fails()) {
            return $this->sendError($validator->errors()->first(), Response::HTTP_FAILED_DEPENDENCY);
        }

        try {
            $email = $request->input('email');

            $checkEmail = AdminUser::validateEmail($email);
            if (!$checkEmail) {
                return $this->sendError(trans('messages.INCORRECT_EMAIL'));
            }
            if ($checkEmail->status == "INACTIVE") {
                return $this->sendError(trans('messages.INACTIVE_ACCOUNT'));
            }

            if (Hash::check($request->password, $checkEmail->password)) {
                $token = $checkEmail->createToken("hotelManagement")->plainTextToken;
                return $this->sendSuccess(trans('messages.LOGIN_SUCCESSFULLY'), Response::HTTP_OK, ['access_token' => $token]);
            } else {
                return $this->sendError(trans('messages.INCORRECT_PASSWORD'), 422);
            }
        } catch (Exception $th) {
            return $this->sendError($th->getMessage(), $th->getCode());
        }
    }

    public function ForgetPassword(Request $request)
    {
        $validate = Validator::make($request->all(), [
            'email' => 'required|email'
        ], [
            'email.required' => trans("messages.EMAIL_REQUIRED"),
            'email.email' => trans("messages.EMAIL_VALID_EMAIL"),
        ]);

        if ($validate->fails()) {
            return $this->sendError($validate->errors()->first(), Response::HTTP_FAILED_DEPENDENCY);
        }

        try {
            DB::beginTransaction();
            $email = $request->input("email");
            $checkEmail = AdminUser::ValidateEmail($email);
            if (!$checkEmail) {
                return $this->sendError(trans("messages.RECORD_NOT_FOUND"));
            }

            $SendOTP = AdminOtp::SendOTP($checkEmail->id, "RESET_PASSWORD");
            if ($SendOTP->id) {
                $checkEmail->otp = $SendOTP->otp;
                 EmailHelper::sendOTP(['user_name' => $checkEmail->name,'otp' => $SendOTP->otp],"Forget Password");
            }

            DB::commit();
            return $this->sendSuccess(trans("messages.OTP_SUCCESSFULLY_SEND"), Response::HTTP_OK, ["has_send_otp" => true]);
        } catch (Exception $e) {
            DB::rollBack();
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
            $adminInfo = AdminUser::where('remember_token', $token)->first();
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

    public function VerifyOtp(Request $request)
    {
        $validate = Validator::make($request->all(), [
            'otp' => 'required|digits:6',
            'email' => "required|email"
        ], [
            'otp.required' => trans("messages.PLEASE_ENTER_OTP"),
            'otp.digits' => trans("messages.OTP_MUST_BE_6_DIGITS"),
        ]);

        if ($validate->fails()) {
            return $this->sendError($validate->errors()->first(), Response::HTTP_FAILED_DEPENDENCY);
        }

        try {
            $email = $request->input("email");
            $checkEmail = AdminUser::ValidateEmail($email);
            if (!$checkEmail) {
                throw new Exception(trans("messages.RECORD_NOT_FOUND"));
            }
            $otp = $request->input("otp");
            $checkOtp = AdminOtp::where('otp', $otp)
                ->where('admin_id', $checkEmail->id)
                ->first();

            if ($checkOtp) {
                if ($checkOtp->expired_at >= date("Y-m-d H:i:s")) {
                    $checkOtp->save();

                    $checkEmail->remember_token = Str::random(20);
                    $checkEmail->save();
                    return $this->sendSuccess(trans("messages.OTP_HAS_BEEN_VERIFIED_SUCCESSFULLY"), Response::HTTP_OK,["token" => $checkEmail->remember_token, "has_verified" => true]);
                }

                return $this->sendError(trans("messages.OTP_HAS_BEEN_EXPIRED"), Response::HTTP_FAILED_DEPENDENCY,["has_verified" => false]);
            }
            return $this->sendError(trans("messages.INCORRECT_OTP"), Response::HTTP_FAILED_DEPENDENCY,["has_verified" => false]);
        } catch (Exception $e) {
            return $this->sendError($e->getMessage());
        }
    }

    public function myProfile()
    {
        try {
            $user = AdminUser::GetInfoById(ADMIN_USER_ID());
            if (!$user) {
                return $this->sendError(trans("messages.USER_INFO_NOT_FOUND"));
            }
            return $this->sendSuccess("success", Response::HTTP_OK, ['info' => $user]);
        } catch (Exception $th) {
            return $this->sendError($th->getMessage());
        }
    }

    public function SetProfile(Request $request, $id = null)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'string|max:200',

            'email' => 'email:rfc,dns',

        ]);

        if ($validator->fails()) {
            return $this->sendError($validator->errors()->first(), Response::HTTP_FAILED_DEPENDENCY);
        }
        try {
            $email = getValue($request->input('email'));
            $UserInfo = AdminUser::GetInfoById(ADMIN_USER_ID());
            // $checkEmail = AdminUser::validateEmail($email, $UserInfo->id);
            // if ($checkEmail) {
            //     throw new Exception(trans('messages.EMAIL_ALREADY_EXISTS'));
            // }
            $info = AdminUser::SetInfo($request, $UserInfo->uuid);
            return $this->sendSuccess($info);
        } catch (Exception $e) {
            return $this->sendError($e->getMessage());
        }
    }

    public function SetProfileImage(Request $request, $id = null)
    {
        $validator = Validator::make($request->all(), [
            'image' => 'required|image|mimes:jpg,png,gif,jpeg'
        ]);
        if ($validator->fails()) {
            return $this->sendError($validator->errors()->first(), Response::HTTP_FAILED_DEPENDENCY);
        }
        try {
            if ($id > 0) {
                $userInfo = AdminUser::find($id);
            } else {
                $userInfo = AdminUser::find(ADMIN_USER_ID());
            }

            if ($request->hasFile('image')) {
                $image = $request->file('image');
                $name = 'IMG_' . $userInfo->id . '@' . time() . '.' . $image->getClientOriginalExtension();
                $folder = config('constants.AVATAR_FOLDER');
                $this->uploadOne($image, $folder, $name);
                $old_logo = $userInfo->profile_image;
                $userInfo->profile_image = $name;
                $userInfo->save();

                if ($old_logo) {
                    $this->deleteOne($folder, $old_logo);
                }
                return $this->sendSuccess(trans('messages.IMAGE_UPLOADED_SUCCESSFULLY'), Response::HTTP_OK, ['path' => $this->getURL($folder, $name), 'name' => $name]);
            }

            return $this->sendError(trans('messages.SOMETHING_WENT_WRONG'));
        } catch (Exception $e) {
            return $this->sendError($e->getMessage());
        }
    }

    public function ResetPassword(Request $request, $id = null)
    {
        $validator = Validator::make($request->all(), [
            'current_password' => 'required',
            'password' => 'required',
        ]);


        if ($validator->fails()) {
            return $this->sendError($validator->errors()->first(), Response::HTTP_FAILED_DEPENDENCY);
        }
        if (!$id) {
            $id = AdminUser::GetInfoById(ADMIN_USER_ID())->id;
        }

        try {
            $info = AdminUser::ChangePassword($request, $id);
            return $this->sendSuccess($info->message);
        } catch (Exception $e) {
            return $this->sendError($e->getMessage());
        }
    }

    public function Logout(Request $request)
    {
        if (Auth::guard('admin')->check()) {
            $user = $request->user();
            if ($user) {
                $user->tokens()->delete();
            }
        }
        return $this->sendSuccess(trans('messages.LOGOUT'));
    }

    public function UpdateProfile(Request $request)
    {

        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:200',
            'email' => 'email:rfc,dns',
        ]);
        if ($validator->fails()) {
            return $this->sendError($validator->errors()->first(), Response::HTTP_FAILED_DEPENDENCY);
        }

        try {
            $info = AdminUser::GetInfoById(ADMIN_USER_ID());
            if (!$info) {
                return $this->sendError(trans('messages.RECORD_NOT_FOUND'));
            }
            $info->name = getValue($request->input('name'));
            $info->email = getValue($request->input('email'));
            $info->save();
            return $this->sendSuccess(trans('messages.PROFILE_UPDATED_SUCCESSFULLY'));
        } catch (Exception $e) {
            return $this->sendError($e->getMessage());
        }
    }
}
