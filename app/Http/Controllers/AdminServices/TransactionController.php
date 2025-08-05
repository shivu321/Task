<?php

namespace App\Http\Controllers\AdminServices;

use App\Helpers\UserAccessHelper;
use App\Http\Controllers\Controller;
use App\Models\Transaction;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

class TransactionController extends Controller
{
    protected $_userId;
    protected $access = null;
    public function __construct()
    {
        $this->_userId = ADMIN_USER_ID();
        $this->access = UserAccessHelper::getAccess($this->_userId, UserAccessHelper::$code_manage_transaction);
    }

    public function GetTransaction(Request $request)
    {
        try {
            if ($this->access->can_read == 1) {
                $list = Transaction::GetList($request);
                $list['access'] = $this->access;
                return $this->sendSuccess("success", Response::HTTP_OK, $list);
            }
            return $this->sendSuccess("success", Response::HTTP_OK, ['list' => [], 'access' => $this->access]);
        } catch (Exception $e) {
            return $this->sendError($e->getMessage());
        }
    }


    public function GetTransactionInfo(Request $request,$uuid){
        try {
            $info = Transaction::GetInfo($uuid);
            if(!$info){
                return $this->sendError(trans('messages.RECORD_NOT_FOUND'));
            }
            return $this->sendSuccess(trans('messages.OK'), Response::HTTP_OK,['info'=> $info]);

         } catch (Exception $e) {
            return $this->sendError($e->getMessage());
        }
    }
}
