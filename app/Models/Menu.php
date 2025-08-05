<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class Menu extends Model
{
    use HasFactory;

    protected $fillable = ['code', 'name', 'icon', 'url'];


    public static function getRoleColumns($query)
    {
        return $query->select("menus.menu", 'can_create', 'can_read', 'can_update', 'can_delete', 'can_print')
            ->leftjoin('role_accesses', function ($q) {
                $q->on('role_accesses.menu_id', '=', 'menus.id');
            });
    }


    public static function GetMenus($roleId = null)
    {
        // if($role_type == "ADMIN" ){
        //     $role_type = "ADMIN";
        // }
        $query = Menu::where('status', '=', "ACTIVE")
            ->where("menu_for", "ADMIN")
            ->orderBy('menus.ordering', 'asc');

        if (!$roleId) {
            $query = $query->select(
                "menus.id",
                "menus.menu",
                'menus.can_create',
                'menus.can_read',
                'menus.can_update',
                'menus.can_delete',
                'menus.can_print',
                "menus.parent_menu_id",
                "menus.ordering"
            );
        } else {

            $query = $query->join('role_accesses', function ($q) {
                $q->on('role_accesses.menu_id', '=', 'menus.id');
            })
                ->when($roleId, function ($q) use ($roleId) {
                    $q->where("role_accesses.role_id", $roleId);
                });

            $query = $query->select(
                "menus.id",
                "menus.menu",
                DB::RAW('(role_accesses.can_create) AS can_create'),
                DB::RAW('(role_accesses.can_read) AS can_read'),
                DB::RAW('(role_accesses.can_update) AS can_update'),
                DB::RAW('(role_accesses.can_delete) AS can_delete'),
                DB::RAW('(role_accesses.can_print) AS can_print'),
                "menus.parent_menu_id",
                "menus.ordering"
            );
        }
        // dump(\Str::replaceArray("?", $query->getBindings(), $query->toSql()));
        $list = $query->get();
        $collection = new Collection($list);
        $main_menus = $collection->where('parent_menu_id', '=', 0);
        foreach ($main_menus as $main_menu) {
            $submenus = $collection->where('parent_menu_id', '=', $main_menu->id);
            $main_menu->submenus = $collection->where('parent_menu_id', '=', $main_menu->id)->values()->all();
            if (!empty($submenus) && count($submenus) > 0) {
                foreach ($submenus as $submenu) {
                    $submenu->submenus = $collection->where('parent_menu_id', '=', $submenu->id)->values()->all();
                }
            }
        }

        return $main_menus->values()->all();
    }
}
