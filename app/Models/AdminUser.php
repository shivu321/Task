<?php

namespace App\Models;

use App\Helpers\UtilityHelper;
use Exception;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Facades\Hash;
use Laravel\Sanctum\HasApiTokens;
use Ramsey\Uuid\Uuid;
use stdClass;

class AdminUser extends Model
{
    use HasFactory,HasApiTokens,Notifiable;

    protected $fillable = [
        'uuid',
        'name',
        'email',
        'password',
        'is_admin',
        'profile_image',
        'status'
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var array<int, string>
     */
    protected $hidden = [
        'password',
        'remember_token',
        'deleted_at',
        'updated_at',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'email_verified_at' => 'datetime',
    ];


    public function getProfileImageAttribute($value)
    {
        $folder = "avatar";
        return UtilityHelper::GetDocumentURL($folder, $value);
    }


    public function role()
    {
        return $this->hasOneThrough(
            Role::class,
            AdminRole::class,
            'admin_id',
            'id',
            'id',
            'role_id'
        )->select("role_name", "roles.role_name as role", "roles.is_editable", "admin_roles.admin_id", "admin_roles.role_id as id");
    }



    public static function GetInfoByUuid($id)
    {
        return self::where('uuid', $id)->with(['role'])->first();
    }

    public const PerPageRecord = 10;


    public static function FindById($id)
    {
        return self::where('id', $id)

            ->first();
    }

    public static function GetInfoById($id)
    {
        return self::where('id', $id)->with(['role'])->first();
    }

    public static function ValidateEmail($email, $id = null)
    {
        return self::where('email', trim(strtolower($email)))
            ->when($id, function ($q) use ($id) {
                $q->where('id', '<>', $id);
            })
            ->first();
    }

    public static function SetInfo($request, $id = null)
    {
        if ($id) {
            $data = self::where('uuid', $id)->first();
            if (!$data) {
                throw new Exception(trans("messages.USER_INFO_NOT_FOUND"));
            }
        } else {
            $data = new self;
            $data->uuid = Uuid::uuid4();
        }

        $data->status = getValue($request->input("status"), "ACTIVE");
        $data->name = getValue($request->input("name"));
        $data->password = Hash::make(getValue($request->input("password")));
        $data->email = getValue($request->input("email"));
        $data->save();

        if ($request->has("role_id")) {
            AdminRole::where("admin_id", $data->id)->delete();
            AdminRole::create(["admin_id" => $data->id, "role_id" => getValue($request->input('role_id'))]);
        }


        $response = new stdClass;
        $response->id = $data->id;
        $response->uuid = $data->uuid;
        $response->message = trans("messages.RECORDS_SAVED_SUCCESSFULLY");
        return $response;
    }

    public static function ChangePassword($request, $id)
    {
        $data = self::where("id", $id)->first();
        if (!$data) {
            throw new Exception(trans("messages.USER_INFO_NOT_FOUND"));
        }
        $old_password = $request->input('old_password');
        if (Hash::check($old_password, $data->password)) {
            $password = $request->input('new_password');
            $data->password = Hash::make(value: $password);
            $data->save();
        } else {
            throw new Exception("Sorry, Old Password is not matched");
        }
        $response = new stdClass;
        $response->message = "Password has been updated successfully.";

        return $response;
    }

    public static function GetAdminUsers($request)
    {
        $keyword = getValue($request->input('keyword'));
        $status = getValue($request->input('status'), "ALL");
        $offset = getValue($request->input('offset'), 0);
        $sort_by = getValue($request->input('sort_by'), 'created_at');
        $order_by = getValue($request->input('order_by'), 'DESC');

        $query = self::query()->with("role")->where("is_admin", 0)
            ->when($status != "ALL", function ($q) use ($status) {
                $q->where("status", $status);
            })

            ->when($keyword, function ($query) use ($keyword) {
                $query->Where('name', 'LIKE', '%' . $keyword . '%');
                $query->orWhere('email', 'LIKE', '%' . $keyword . '%');
            })
            ->orderBy($sort_by, $order_by);

        $data['count'] = count($query->get());
        if (isset($offset)) {
            $query->offset($offset * self::PerPageRecord);
            $query->limit(self::PerPageRecord);
        }
        $data['list'] = $query->get();
        $data['next_offset'] = ((count($data['list']) == self::PerPageRecord) ? (isset($offset) ? ((int) $offset + 1) : 0) : -1);
        return $data;
    }

    
}
