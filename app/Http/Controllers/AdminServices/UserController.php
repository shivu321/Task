<?php

namespace App\Http\Controllers\AdminServices;

use App\Helpers\UserAccessHelper;
use App\Http\Controllers\Controller;
use App\Models\AdminRole;
use App\Models\AdminUser;
use App\Models\User;
use App\Traits\UploadTrait;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Ramsey\Uuid\Uuid;

class UserController extends Controller
{
    use UploadTrait;
    protected $_userId;
    protected $access = null;
    public function __construct()
    {
        $this->_userId = ADMIN_USER_ID();
        $this->access = UserAccessHelper::getAccess($this->_userId, UserAccessHelper::$code_manage_user);
    }


    public function GetList(Request $request)
    {
        try {
            if ($this->access->can_read == 1) {
                $list = AdminUser::GetAdminUsers($request);
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
            $info = AdminUser::GetInfoByUuid($uuid);
            if(!$info){
                return $this->sendError(trans('messages.RECORD_NOT_FOUND'));
            }
            return $this->sendSuccess("success", Response::HTTP_OK, ['info' => $info, 'access' => $this->access]);
        } catch (Exception $e) {
            return $this->sendError($e->getMessage());
        }
    }


    public function SetInfo(Request $request, $uuid = null)
    {
        $rules = [
            'name' => 'required',
            'email' => 'required|email',
            "role_id" => "required",
        ];
        if(empty($uuid)) {
            $rules['password'] ='required';
        }
        $validator = Validator::make($request->all(), $rules);

        if ($validator->fails()) {
            return $this->sendError($validator->errors()->first(), Response::HTTP_FAILED_DEPENDENCY);
        }
        $email = $request->input("email");
        $checkEmail = AdminUser::ValidateEmail($email);
        if ($checkEmail && is_null($uuid)) {
            return $this->sendError(trans("messages.EMAIL_ALREADY_EXIST"));
        }

        try {
            DB::beginTransaction();
            $info = AdminUser::SetInfo($request, $uuid);
            DB::commit();
            return $this->sendSuccess($info->message, Response::HTTP_OK, ["id" => $info->id, 'uuid' => $info->uuid]);
        } catch (Exception $th) {
            DB::rollBack();
            return $this->sendError($th->getMessage());
        }
    }

    public function destroy(Request $request, $userId)
    {
        try {
            $info = AdminUser::where('uuid', $userId)->first();
            if ($info) {
                $info->delete();
                AdminRole::where('user_id', $userId)->delete();
                return $this->sendSuccess(['message' => trans('messages.USER_DELETED_SUCCESSFULLY')]);
            }

            return $this->sendSuccess(['message' => trans('messages.USER_INFO_NOT_FOUND')]);
        } catch (Exception $e) {
            return $this->sendError($e->getMessage());
        }
    }

    public function SetStatus(Request $request, $uuid)
    {
        $validator = Validator::make($request->all(), [
            'status' => 'required|in:ACTIVE,INACTIVE',
        ]);
        if ($validator->fails()) {
            return $this->sendError(['message' => $validator->errors()->first(), Response::HTTP_UNPROCESSABLE_ENTITY]);
        }
        $userInfo = AdminUser::GetInfoByUuid($uuid);
        if (!$userInfo) {
            return $this->sendError(trans("messages.USER_INFO_NOT_FOUND"));
        }

        $userInfo->status = $request->input("status");
        $userInfo->save();

        return $this->sendSuccess(["message" => "User status has been updated successfully."]);
    }

    public function SetPasswordReset(Request $request, $uuid)
    {

        $validator = Validator::make($request->all(), [
            "password" => "required",
        ]);
        if ($validator->fails()) {
            return $this->sendError($validator->errors()->first(), Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        $userInfo = AdminUser::GetInfoByUuid($uuid);
        if (!$userInfo) {
            return $this->sendError(trans("messages.USER_INFO_NOT_FOUND"));
        }
        try {
            $userInfo->password = Hash::make($request->input("password"));
            $userInfo->save();
            return $this->sendSuccess(trans("messages.PASSWORD_CHANGE_SUCCESSFULLY"));
        } catch (Exception $th) {
            return $this->sendError($th->getMessage());
        }
    }



    public function GetUserDD(Request $request)
    {
        try {
            $list = User::where('status', 'ACTIVE')->get();
            return $this->sendSuccess("success", ['list' => $list]);
        } catch (Exception $e) {
            return $this->sendError($e->getMessage());
        }
    }

    public function uploadLogo(Request $request, $id = null)
    {
        $validator = Validator::make($request->all(), [
            'image' => 'required|image|mimes:jpg,png,gif,jpeg'
        ]);

        if ($validator->fails()) {
            return $this->sendError($validator->errors()->first(), Response::HTTP_FAILED_DEPENDENCY);
        }

        try {
            $userInfo = AdminUser::where('id', $id)->first();
            if (!$userInfo) {
                return $this->sendError(trans('messages.RECORD_NOT_FOUND'));
            }
            if ($request->hasFile('image')) {
                $image = $request->file('image');
                $name = 'IMG_' . Uuid::uuid4() . '@' . time() . '.' . $image->getClientOriginalExtension();
                $folder = config('constants.AVATAR_FOLDER');
                $this->uploadOne($image, $folder, $name);
                $old_logo = $userInfo->profile_image;
                $userInfo->profile_image = $name;
                $userInfo->save();

                if ($old_logo) {
                    $this->deleteOne($folder, $old_logo);
                }
                return $this->sendSuccess(['status' => 'success', 'message' => trans('messages.IMAGE_UPLOADED_SUCCESSFULLY'), 'path' => $this->getURL($folder, $name), 'name' => $name]);
            }

            return $this->sendError(trans('messages.SOMETHING_WENT_WRONG'));
        } catch (Exception $e) {
            return $this->sendError($e->getMessage());
        }
    }
}
