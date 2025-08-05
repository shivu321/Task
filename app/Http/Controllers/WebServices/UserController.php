<?php

namespace App\Http\Controllers\WebServices;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Traits\UploadTrait;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;

class UserController extends Controller
{
    use UploadTrait;
    public function ChangePassword(Request $request, $token)
    {
        $validator = Validator::make($request->all(), [
            "password" => "required",
        ]);
        if ($validator->fails()) {
            return $this->sendError(trans("messages.RECORD_NOT_FOUND"));
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
    public function GeInfo(Request $request, $uuid)
    {
        $userInfo = User::where("uuid", $uuid)->first();
        if ($userInfo) {
            return $this->sendSuccess(trans("messages.OK"), ['info' => $userInfo]);
        }

        return $this->sendError(trans("messages.USER_INFO_NOT_FOUND"));
    }

    public function setProfileImage(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'image' => 'required|image|mimes:jpg,png,gif,jpeg'
        ]);

        if ($validator->fails()) {
            return $this->sendError($validator->errors()->first(), Response::HTTP_FAILED_DEPENDENCY);
        }
        try {
            $userInfo = User::find(USER_ID());
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
                return $this->sendSuccess(trans('messages.IMAGE_UPLOADED_SUCCESSFULLY'), Response::HTTP_OK,['path' => $this->getURL($folder, $name), 'name' => $name]);
            }

            return $this->sendError(trans('messages.SOMETHING_WENT_WRONG'));
        } catch (Exception $e) {
            return $this->sendError($e->getMessage());
        }
    }

    public function DeleteAccount(Request $request)
    {
        try {
            $userInfo = User::where("id", USER_ID())->first();
            if ($userInfo) {
                $userInfo->delete();
                return $this->sendSuccess(trans("messages.ACCOUNT_DELETED"));
            }

            return $this->sendError(trans("messages.USER_INFO_NOT_FOUND"));
        } catch (Exception $e) {
            return $this->sendError($e->getMessage());
        }
    }



    public function ChangeCurrentUserPassword(Request $request)
    {

        $validator = Validator::make($request->all(), [
            'current_password' => "required",
            'password' => "required"
        ]);

        if ($validator->fails()) {
            return $this->sendError($validator->errors()->first(), Response::HTTP_BAD_REQUEST);
        }
        $current_password = $request->input("current_password");
        $password = $request->input("password");
        try {
            $userInfo = User::GetUserInfoById(USER_ID());
            if (!$userInfo) {
                return $this->sendError(trans("messages.USER_INFO_NOT_FOUND"));
            }

            if (Hash::check($current_password, $userInfo->password)) {
                $userInfo->password = Hash::make($password);
                $userInfo->save();
                return $this->sendSuccess(['message' => trans("messages.PASSWORD_UPDATED")]);
            }

            return $this->sendError(["message" => trans("messages.PASSWORD_NOT_MATCH")]);
        } catch (Exception $e) {
            return $this->sendError($e->getMessage());
        }
    }

    public function UpdateUserInfo(Request $request)
    {
        $validator = Validator::make($request->all(), [
            "name" => "required",
            'email' => 'required|email:rfc,dns',
        ]);

        if ($validator->fails()) {
            return $this->sendError($validator->errors()->first(), null, Response::HTTP_BAD_REQUEST);
        }
        try {
            $info = User::where("id", USER_ID())->first();
            if (!$info) {
                return $this->sendError(trans("messages.RECORD_NOT_FOUND"), null, Response::HTTP_BAD_REQUEST);
            }
            $info->name = $request->input("name");
            $info->email = $request->input("email");

            $info->save();


            return $this->sendSuccess(trans("messages.PROFILE_INFO_UPDATED_SUCCESSFULLY"));
        } catch (Exception $e) {
            return $this->sendError($e->getMessage());
        }
    }

    public function getMyProfile()
    {
        try {
            $info = User::where("id", USER_ID())->first();
            if (!$info) {
                return $this->sendError(trans("messages.RECORD_NOT_FOUND"));
            }
            return $this->sendSuccess(trans("messages.OK"), Response::HTTP_OK,["info" => $info]);
        } catch (Exception $e) {
            return $this->sendError($e->getMessage());
        }
    }
}
