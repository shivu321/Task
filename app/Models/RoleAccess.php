<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class RoleAccess extends Model
{
    use HasFactory;

     protected $fillable = ['role_id', 'menu_id', 'can_create', 'can_read', 'can_update', 'can_delete', 'can_print', 'created_at', 'updated_at'];
    protected $hidden = ['created_at', 'updated_at'];

    public function menus()
    {
        return $this->hasOne(Menu::class, 'id', 'menu_id');
    }

    public static function DeleteRoleAccess($role_id)
    {
        return RoleAccess::where('role_id', '=', $role_id)->delete();
    }
    public static function AssignRoleAccess($arr)
    {
        return RoleAccess::insert($arr);
    }

    public static function updateRoleAccess(
        $role_access_id,
        $role_id,
        $menu_id,
        $can_create,
        $can_read,
        $can_update,
        $can_delete,
        $can_print
    ) {
        return RoleAccess::where('id', '=', $role_access_id)
            ->update([
                'role_id' => $role_id,
                'menu_id' => $menu_id,
                'can_create' => $can_create,
                'can_read' => $can_read,
                'can_update' => $can_update,
                'can_delete' => $can_delete,
                'can_print' => $can_print,
            ]);
    }
}
