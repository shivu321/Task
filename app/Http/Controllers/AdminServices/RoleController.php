<?php

namespace App\Http\Controllers\AdminServices;

use App\Helpers\UserAccessHelper;
use App\Http\Controllers\Controller;
use App\Models\AdminRole;
use App\Models\AdminUser;
use App\Models\Menu;
use App\Models\Role;
use App\Models\RoleAccess;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Validator;

class RoleController extends Controller
{
    protected $_userId;
    protected $access = null;
    public function __construct()
    {
        $this->_userId = ADMIN_USER_ID();
        $this->access = UserAccessHelper::getAccess($this->_userId, UserAccessHelper::$code_manage_roles);
    }



    public function GetLeftMenu(Request $request)
    {
        $user = AdminUser::GetInfoById($this->_userId);
        if (!$user) {
            return $this->sendError(trans("messages.USER_INFO_NOT_FOUND"));
        }

        $left_menus = UserAccessHelper::getLeftMenus();
        $access = UserAccessHelper::getAccessByCode($user->id, $left_menus);
        $access = UserAccessHelper::HashMap($access);

        $arr = [];
        if (!empty($access)) {
            if ($access['dashboard']->can_read == 1) {
                $arr[] = UserAccessHelper::getMenuObject(trans('menus.MANAGE_DASHBOARD'), $access['dashboard'], "home-icon.png", "/dashboard");
            }
            if ($access['manage_user']->can_read == 1) {
                $arr[] = UserAccessHelper::getMenuObject(trans('menus.MANAGE_USERS'), $access['manage_user'], "users.png", "/manage-users");
            }
            if ($access['manage_role']->can_read == 1) {
                $arr[] = UserAccessHelper::getMenuObject(trans('menus.MANAGE_ROLES'), $access['manage_role'], "roles.png", "/manage-roles");
            }
            if ($access['manage_hotel']->can_read == 1) {
                $arr[] = UserAccessHelper::getMenuObject(trans('menus.MANAGE_HOTEL'), $access['manage_hotel'], "hotel.png", "/manage-hotel");
            }
            if ($access['manage_booking']->can_read == 1) {
                $arr[] = UserAccessHelper::getMenuObject(trans('menus.MANAGE_BOOKING'), $access['manage_booking'], "bookings.png", "/manage-booking");
            }
            if ($access['manage_transaction']->can_read == 1) {
                $arr[] = UserAccessHelper::getMenuObject(trans('menus.MANAGE_TRANSACTION'), $access['manage_transaction'], "transactions.png", "/manage-transaction");
            }
        }

        return response()->json(['message' => trans('messages.OK'), 'status' => trans('messages.SUCCESS'), 'list' => $arr]);
    }


    public function GetRoles(Request $request)
    {
        $user = AdminUser::GetInfoById($this->_userId);
        if (!$user) {
            return $this->sendError(trans("messages.USER_INFO_NOT_FOUND"));
        }

        $roleIds = [];
        if ($user->roles) {
            foreach ($user->roles as $userRole) {
                $roleIds[] = $userRole->role_id;
            }
        }

        if ($this->access->can_read == 1) {
            $data = Role::GetRoleList($request);
            foreach ($data['list'] as $item) {
                if ($item->is_editable == "Y") {
                    if (count($roleIds) > 0 && in_array($item->id, $roleIds)) {
                        $item->is_editable = "N";
                    }
                }
            }

            $data['status'] = trans('messages.SUCCESS');
            $data['access'] = $this->access;
            return $this->sendSuccess("success", Response::HTTP_OK,$data);
        } else {
            return response()->json(['message' => trans('messages.SOMETHING_WENT_WRONG'), 'status' => trans('messages.ERROR'), "access" => $this->access, 'list' => []]);
        }
    }
    public function getMenus(Request $request, $roleId = null)
    {
        $user = AdminUser::getInfoById($this->_userId);
        if (!$user) {
            return $this->sendError(trans("messages.USER_INFO_NOT_FOUND"));
        }


        if ($roleId > 0) {
            $roleInfo = Role::getRoleInfo($roleId);
            if (!$roleInfo) {
                return $this->sendError(trans("messages.ROLE_INFO_NOT_FOUND"));
            }
        }

        $list = Menu::getMenus($roleId);
        if (!empty($list) && count($list) > 0) {
            if (!empty($roleInfo)) {
                return $this->sendError(trans("messages.OK"),Response::HTTP_OK,[ "id" => $roleInfo->id, 'role_name' => $roleInfo->role_name, 'is_editable' => $roleInfo->is_editable, 'list' => $list]);
                // return response()->json(['message' => trans('messages.OK'), 'status' => $roleInfo->status,]);
            } else {
                return response()->json(['message' => trans('messages.OK'), 'status' => trans('messages.SUCCESS'), 'list' => $list]);
            }
        } else {
            return response()->json(['message' => trans('messages.NO_RECORD_FOUND'), 'status' => trans('messages.SUCCESS'), 'list' => []]);
        }
    }

    public function setRoleAccess(Request $request, $role_id = 0)
    {
        $validator = Validator::make($request->all(), [
            'role_name' => 'required|string',
            'status' => 'required|in:ACTIVE,INACTIVE',
            'role_access' => 'required'
        ]);

        if ($validator->fails()) {
            return $this->sendError(["message" => $validator->errors()->first()], Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        $user = AdminUser::getInfoById($this->_userId);
        if (!$user) {
            return $this->sendError(trans("messages.USER_INFO_NOT_FOUND"));
        }

        if ($role_id > 0) {
            $role_ids = [];
            if ($user->user_roles) {
                foreach ($user->user_roles as $userRole) {
                    $role_ids[] = $userRole->role->id;
                }
            }

            if (count($role_ids) && in_array($role_id, $role_ids)) {
                return $this->sendError("Sorry, You don't have permission to edit this role.");
            }
        }

        $role_name = $request->input('role_name');
        $type = $request->input('type');
        $status = $request->input('status');
        $role_access = $request->input('role_access');
        if (($this->access->can_read == 0 && $role_id == 0) || ($this->access->can_update == 0 && $role_id > 0)) {
            return $this->sendError(trans("messages.YOU_DONT_HAVE_PERMISSION_TO_ACCESS_THIS_FUNCTION"));
        }

        if (empty($role_name)) {
            return $this->sendError(['message' => trans('messages.ENTER_ROLE_NAME')], 403);
        }

        $objRole = Role::checkRoleExists($role_name, $role_id);
        if (!empty($objRole)) {
            return $this->sendError(trans('messages.ROLE_WITH_THIS_NAME_ALREDY_EXISTS', ['name' => $role_name]),Response::HTTP_FORBIDDEN);
        }

        if ($role_id == 0) {
            $role_id = Role::addRole($role_name, $status);
        } else {
            Role::updateRole($role_name, $status, $role_id);
        }

        $arrAccess = [];
        for ($i = 0; $i < count($role_access); $i++) {
            $menuInfo = Menu::find($role_access[$i]['id']);
            if ($menuInfo) {
                $cls = [];
                $cls['role_id'] = $role_id;
                $cls['menu_id'] = $role_access[$i]['id'];

                if (!is_null($menuInfo->can_create)) {
                    $cls['can_create'] = is_null($role_access[$i]['can_create']) ? "0" : "" . $role_access[$i]['can_create'];
                } else {
                    $cls['can_create'] = NULL;
                }

                if (!is_null($menuInfo->can_read)) {
                    $cls['can_read'] = is_null($role_access[$i]['can_read']) ? "0" : "" . $role_access[$i]['can_read'];
                } else {
                    $cls['can_read'] = NULL;
                }

                if (!is_null($menuInfo->can_update)) {
                    $cls['can_update'] = is_null($role_access[$i]['can_update']) ? "0" : "" . $role_access[$i]['can_update'];
                } else {
                    $cls['can_update'] = NULL;
                }

                if (!is_null($menuInfo->can_delete)) {
                    $cls['can_delete'] = is_null($role_access[$i]['can_delete']) ? "0" : "" . $role_access[$i]['can_delete'];
                } else {
                    $cls['can_delete'] = NULL;
                }

                if (!is_null($menuInfo->can_print)) {
                    $cls['can_print'] = is_null($role_access[$i]['can_print']) ? "0" : "" . $role_access[$i]['can_print'];
                } else {
                    $cls['can_print'] = NULL;
                }

                $arrAccess[] = $cls;
            }
        }

        RoleAccess::DeleteRoleAccess($role_id);
        RoleAccess::AssignRoleAccess($arrAccess);
        return $this->sendSuccess(trans('messages.ROLE_HAS_SAVED_SUCCESSFULLY'));
    }

    function deleteRole(Request $request, $role_id)
    {
        $user = AdminUser::getInfoById($this->_userId);
        if (!$user) {
            return $this->sendError(trans("messages.USER_INFO_NOT_FOUND"));
        }

        if ($role_id > 0) {
            $role_ids = [];
            if ($user->user_roles) {
                foreach ($user->user_roles as $userRole) {
                    $role_ids[] = $userRole->role->id;
                }
            }

            if (count($role_ids) && in_array($role_id, $role_ids)) {
                return $this->sendError("Sorry, You don't have permission to delete this role.");
            }
        }

        $info = Role::getRoleInfo($role_id);
        if (!$info) {
            return $this->sendError(trans("messages.ROLE_NOT_FOUND"));
        }

        if ($info->is_editable == "N") {
            return $this->sendError(trans("messages.YOU_CANT_DELETE_THIS_RECORD"));
        }

        $checkAssignedRole = AdminRole::where("role_id", $role_id)->count();
        if ($checkAssignedRole == 0) {
            RoleAccess::DeleteRoleAccess($role_id);
            $info->delete();
            return $this->sendSuccess(trans("messages.ROLE_HAS_DELETED_SUCCESSFULLY"),Response::HTTP_OK);
        }

        return $this->sendError(trans("messages.ROLE_HAS_IN_USED"));
    }

    public function GetRolesForDropdown(Request $request)
    {
        try {
            $roles = Role::where("role_type", "ADMIN")->get();
            return $this->sendSuccess(trans('messages.OK'), Response::HTTP_OK,['list' => $roles]);
        } catch (\Throwable $th) {
            return $this->sendError($th->getMessage());
        }
    }
}
