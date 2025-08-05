<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Role extends Model
{
    use HasFactory;
        protected $fillable = ['role_name', 'is_editable', 'status'];

    /**
     * The attributes that should be mutated to dates.
     *
     * @var array
     */
    protected $dates = ['created_at', 'updated_at'];

    public const PerPageRecord = 10;

    public static function GetRoleInfo($id)
    {
        return self::where('id', $id)->first();
    }

    public static function checkRoleExists($role, $id = 0)
    {
        return self::select('id')->where('role_name', '=', $role)
            ->when($id > 0, function ($q) use ($id) {
                $q->where('id', '<>', $id);
            })
            ->first();
    }


    public static function updateRole($role, $status, $role_id)
    {
        self::where('id', '=', $role_id)
            ->update([
                'role_name' => $role,
                'status' => $status,
            ]);
    }


    public static function getRoleList($request)
    {
        $keyword = getValue($request->input('keyword'));
        $offset = getValue($request->input('offset'), 0);
        $is_active = getValue($request->input('status'), 'ALL');

        $sort_by = getValue($request->input('sort_by'), 'roles.created_at');
        $sort_order = getValue($request->input('order_by'), 'DESC');


        $query = self::select('roles.id', 'roles.is_editable', 'roles.status', 'roles.role_name as role')->where("role_type","ADMIN")
            ->when($is_active != 'ALL', function ($q) use ($is_active) {
                $q->where('roles.status', $is_active);
            })
            ->when($keyword, function ($q) use ($keyword) {
                $parts = explode(' ', $keyword);
                $parts = array_filter($parts);
                foreach ($parts as $part) {
                    $q->orWhere('roles.role_name', 'like', '%' . $part . '%');
                }
            })
            ->orderBy($sort_by, $sort_order);

        $data['count'] = count($query->get());
        if (isset($offset)) {
            $query->offset($offset * self::PerPageRecord);
            $query->limit(self::PerPageRecord);
        }
        $data['list'] = $query->get();
        $data['next_offset'] = ((count($data['list']) == self::PerPageRecord) ? (isset($offset) ? ((int) $offset + 1) : 0) : -1);

        return $data;
    }

    public static function addRole($role, $status)
    {
        return self::insertGetId([
            'role_name' => $role,
            'is_editable' => "Y",
            'status' => $status,
        ]);
    }
}
