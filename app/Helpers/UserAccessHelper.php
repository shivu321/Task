<?php

namespace App\Helpers;

use App\Models\RoleAccess;
use Illuminate\Support\Facades\DB;

class UserAccessHelper
{
    public static $code_dashboard = "dashboard";
    public static $code_manage_hotel = "manage_hotel";
    public static $code_manage_roles = "manage_role";
    public static $code_manage_transaction = "manage_transaction";
    public static $code_manage_booking = "manage_booking";
    public static $code_manage_user = "manage_user";


    public static function HashMap($list)
    {
        $arr = [];
        foreach ($list as $obj) {
            $arr[trim($obj->code)] = $obj;
        }
        return $arr;
    }

    public static function getAccess($user_id, $code)
    {


        $obj = RoleAccess::select("m.code", "ur.admin_id")
            ->addSelect(DB::raw("MAX(role_accesses.can_create) as can_create"))
            ->addSelect(DB::raw("MAX(role_accesses.can_read) as can_read"))
            ->addSelect(DB::raw("MAX(role_accesses.can_update) as can_update"))
            ->addSelect(DB::raw("MAX(role_accesses.can_delete) as can_delete"))
            ->addSelect(DB::raw("MAX(role_accesses.can_print) as can_print"))
            ->join('admin_roles as ur', function ($q) use ($user_id) {
                $q->on('ur.role_id', '=', 'role_accesses.role_id')
                    ->where('ur.admin_id', '=', $user_id);
            })
            ->join('menus as m', function ($q) use ($code) {
                $q->on('m.id', '=', 'role_accesses.menu_id')
                    ->where('code', '=', $code);
            })
            ->where('ur.admin_id', '=', $user_id)
            ->groupBy('m.code', 'ur.admin_id')
            ->first();

        if (!$obj) {
            return (object) [
                'id' => '-1',
                'code' => $code,
                'user_id' => $user_id,
                'can_create' => 0,
                'can_read' => 0,
                'can_update' => 0,
                'can_delete' => 0,
                'can_print' => 0,
                'can_view_sensitive_info' => 0
            ];
        }

        $obj->can_create = (int)$obj->can_create ?? 0;
        $obj->can_read = (int)$obj->can_read ?? 0;
        $obj->can_update =(int) $obj->can_update ?? 0;
        $obj->can_delete =(int) $obj->can_delete ?? 0;
        $obj->can_print =(int) $obj->can_print ?? 0;
        $obj->can_view_sensitive_info = (int)$obj->can_view_sensitive_info ?? 0;

        return $obj;

    }


    public static function getAccessByCode($user_id, $arr_codes)
    {
        $query = RoleAccess::select("m.code", "ur.admin_id")
            ->addSelect(DB::raw("MAX(role_accesses.can_create) as can_create"))
            ->addSelect(DB::raw("MAX(role_accesses.can_read) as can_read"))
            ->addSelect(DB::raw("MAX(role_accesses.can_update) as can_update"))
            ->addSelect(DB::raw("MAX(role_accesses.can_delete) as can_delete"))
            ->addSelect(DB::raw("MAX(role_accesses.can_print) as can_print"))
            ->join('admin_roles as ur', function ($q) use ($user_id) {
                $q->on('ur.role_id', '=', 'role_accesses.role_id')
                    ->where('ur.admin_id', '=', $user_id);
            })
            ->join('roles as mr', 'ur.role_id', '=', 'mr.id')
            ->join('menus as m', function ($q) use ($arr_codes) {
                $q->on('m.id', '=', 'role_accesses.menu_id')
                    ->whereIn('code', $arr_codes);
            })
            ->where('ur.admin_id', '=', $user_id)
            ->groupBy('m.code', 'ur.admin_id');

        $list = $query->get();
        $arr_access = [];

        foreach ($arr_codes as $role) {
            $item = $list->firstWhere('code', $role);
            if ($item) {
                $item->can_create = $item->can_create ?? 0;
                $item->can_read = $item->can_read ?? 0;
                $item->can_update = $item->can_update ?? 0;
                $item->can_delete = $item->can_delete ?? 0;
                $item->can_print = $item->can_print ?? 0;
                $item->can_view_sensitive_info = $item->can_view_sensitive_info ?? 0;
                $arr_access[] = $item;
            } else {
                $arr_access[] = (object) [
                    'code' => $role,
                    'user_id' => $user_id,
                    'can_create' => 0,
                    'can_read' => 0,
                    'can_update' => 0,
                    'can_delete' => 0,
                    'can_print' => 0,
                    'can_view_sensitive_info' => 0
                ];
            }
        }

        return $arr_access;
    }



    public static function RoleAccess()
    {
        $arr = [];
        $arr[] = self::$code_manage_roles;
        return $arr;
    }


    public static function getLeftMenus()
    {
        $arr = [];
        $arr[] = self::$code_dashboard;
        $arr[] = self::$code_manage_hotel;
        $arr[] = self::$code_manage_roles;
        $arr[] = self::$code_manage_transaction;
        $arr[] = self::$code_manage_user;
        $arr[] = self::$code_manage_booking;
       
        return $arr;
    }

    public static function getMenuObject($menu_name, $access, $icon_class, $url)
    {
        $obj = new \stdClass();
        $obj->name = $menu_name;
        $obj->icon_class = $icon_class;
        $obj->url = $url;
        $obj->access = $access;
        return $obj;
    }
}
