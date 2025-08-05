<?php

namespace App\Http\Controllers\WebServices;

use App\Http\Controllers\Controller;
use App\Models\UserDevice;
use Illuminate\Http\Request;

class CommonController extends Controller
{
   public function RegisterDevice(Request $request)
    {
        if ($request->has('token') && $request->has('type')) {
            UserDevice::where('type', $request->input('type'))
                ->where('token', $request->input('token'))
                ->delete();

            UserDevice::where("user_id", USER_ID())->delete();

            $device = new UserDevice();
            $device->user_id = USER_ID();
            $device->token = $request->input('token');
            $device->type = $request->input('type');
            $device->save();
        }

        return $this->sendResponse(trans('messages.DEVICE_REGISTERED_SUCCESSFULLY'));
    }


    public function UnregisterDevice(Request $request)
    {
        if ($request->has('token') && $request->has('type')) {
            $device = UserDevice::where('type', $request->input('type'))
                ->where('token', $request->token)->first();
            if ($device) {
                $device->delete();
            }
        }
        return $this->sendResponse(trans('messages.DEVICE_UNREGISTERED_SUCCESSFULLY'));
    }
}
