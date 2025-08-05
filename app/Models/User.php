<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use App\Helpers\UtilityHelper;
use Exception;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Facades\Hash;
use Laravel\Sanctum\HasApiTokens;
use Ramsey\Uuid\Uuid;
use stdClass;

class User extends Authenticatable
{
    use HasApiTokens, HasFactory, Notifiable;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'uuid',
        'name',
        'email',
        'password',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var array<int, string>
     */
    protected $hidden = [
        'password',
        'remember_token',
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


    public static function validateUserEmail(string $email, $id = 0)
    {
        return User::where('email', $email)->when($id > 0, function ($query) use ($id) {
            $query->where('id', $id);
        })->first();
    }

    public static function ValidateEmail($email, $id = 0)
    {
        return User::where('email', trim(strtolower($email)))
            ->when($id > 0, function ($q) use ($id) {
                $q->where('id', '<>', $id);
            })
            ->first();
    }

    public static function SetInfo($request, $id = 0)
    {
        if ($id > 0) {
            $user = User::where('id', $id)->first();
            if (!$user) {
                throw new Exception(trans('messages.RECORD_NOT_FOUND'));
            }
            $message = trans("messages.RECORD_UPDATED_SUCCESSFULLY");
        } else {
            $user = new User;
            $user->uuid = Uuid::uuid4();
            $user->password = Hash::make($request->input('password'));
            $message = trans("messages.RECORD_ADDED_SUCCESSFULLY");
        }

        $user->name = $request->input('name');
        $user->email = $request->input('email');
        $user->save();

        $response = new stdClass();
        $response->uuid = $user->uuid;
        $response->message = $message;

        return $response;
    }
}
