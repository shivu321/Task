<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Ramsey\Uuid\Uuid;
use stdClass;

class Transaction extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<string>
     */
    protected $fillable = ['uuid', 'tax', 'tax_amount', 'discount', 'sub_total', 'grand_total', 'status', 'payment_intent', 'client_secrete', 'charges_intent'];

    /**
     * The attributes that should be mutated to dates.
     *
     * @var array
     */
    protected $dates = ['created_at', 'updated_at'];

    public const PerPageRecord = 10;

    public function booking(){
        return $this->hasOne(Booking::class,'transaction_id','id')->with(['room','room.images','hotel','user']);
    }

    public static function getTransactionExits($trxNo)
    {
        return self::where('transaction_no', $trxNo)->first();
    }

    public static function GetInfo($uuid){
        return self::where('uuid', $uuid)->with(['booking'])->first();
    }

    public static function SetTransaction($tax, $tax_amount, $discount, $sub_total, $total)
    {

        $generateTrx = generateTransactionId();
        if($generateTrx){
            $checkTrx = Transaction::getTransactionExits($generateTrx);
        }
        $data = new self();
        $data->uuid = Uuid::uuid4();
        $data->tax = $tax;
        $data->tax_amount = $tax_amount;
        $data->discount = $discount;
        $data->sub_total = $sub_total;
        $data->grand_total = $total;
        $data->save();

        $res = new stdClass();
        $res->message = trans('messages.OK');
        $res->uuid = $data->uuid;
        $res->id = $data->id;
        return $res;
    }

    public static function GetList($request)
    {
        $status = getValue($request->input('status'), "ALL");
        $offset = getValue($request->input('offset'), 0);
        $sort_by = getValue($request->input('sort_by'), 'created_at');
        $order_by = getValue($request->input('order_by'), 'DESC');

        $query = self::query()->with(['booking'])
            ->when($status != "ALL", function ($q) use ($status) {
                $q->where("status", $status);
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
