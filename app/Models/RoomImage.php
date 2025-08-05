<?php

namespace App\Models;

use App\Helpers\UtilityHelper;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class RoomImage extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<string>
     */
    protected $fillable = ['room_id', 'image'];

    public function getImageAttribute($value)
    {
        $folder = "room";
        return UtilityHelper::GetDocumentURL($folder, $value);
    }
}
