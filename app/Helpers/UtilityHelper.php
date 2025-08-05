<?php

namespace App\Helpers;

use App\Models\Store;
use App\Models\StoreReservationSlot;
use App\Models\StoreReservationTiming;
use App\Models\UserOffers;
use App\Traits\UploadTrait;
use Carbon\Carbon;
use chillerlan\QRCode\Output\QRGdImagePNG;
use chillerlan\QRCode\Output\QROutputInterface;
use chillerlan\QRCode\QRCode;
use chillerlan\QRCode\QROptions;
use DateTime;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class UtilityHelper
{

    use UploadTrait;
    
    
    public static function verifyTestMobileNumber($phone): bool
    {
        if (
            (substr($phone, 0, 9) == '999999999')
            || (substr($phone, 0, 9) == '888888888')
            || (substr($phone, 0, 9) == '777777777')
            || (substr($phone, -5) == '00000')
            || (substr($phone, -5) == '12345')
            || (substr($phone, -5) == '11111')
        ) {
            return false;
        }
        return true;
    }

    public static function GetDocumentURL($folder, $value)
    {
        if ($value) {
            if (Str::startsWith("http", $value)) {
                return $value;
            } else {
                $disk = config("filesystems.default");
                Log::info("disk Info " . $disk);
                if (Storage::disk($disk)->exists($folder . '/' . $value)) {
                    if ($disk == 's3') {
                        return Storage::disk($disk)->temporaryUrl($folder . "/" . $value, now()->addMinutes(10));
                    } else {
                        return Storage::disk($disk)->url($folder . "/" . $value);
                    }
                }
            }
        }
        return $value;
    }
}
