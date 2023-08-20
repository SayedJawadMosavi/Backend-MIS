<?php

namespace App\Http\Controllers;

use App\Models\ExchangeMoney;
use DateTime;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ExchangeMoneyController extends Controller
{
    public function __construct()
    {
        $this->middleware('permissions:exchange_view')->only('index');
        $this->middleware('permissions:exchange_create')->only(['store', "update"]);
        $this->middleware('permissions:exchange_delete')->only(['destroy']);
        $this->middleware('permissions:exchange_restore')->only(['restore']);
        $this->middleware('permissions:exchange_force_delete')->only(['forceDelete']);
    }

    public function index(Request $request)
    {
        try {
            $query = new ExchangeMoney();
            $searchCol = ['sender_name', 'amount', 'exchange_id', 'currency', 'province', 'phone_number', 'receiver_name', 'created_at', 'receiver_father_name', 'receiver_id_no', 'date'];
            $query = $this->search($query, $request, $searchCol);
            $trashedTotal = clone $query;
            $trashedTotal = $trashedTotal->onlyTrashed()->count();
            $allTotal = clone $query;
            $allTotal = $allTotal->count();
            if ($request->tab == 'trash') {
                $query = $query->onlyTrashed();
            }
            $query = $query->latest()->paginate($request->itemsPerPage);
            $results = collect($query->items());
            $total = $query->total();
            $result = [
                "data" => $results,
                "total" => $total,
                "extraTotal" => ["all" => $allTotal, "trash" => $trashedTotal]
            ];
            return response()->json($result);
        } catch (\Exception $th) {
            return response()->json($th->getMessage(), 500);
        }
    }
    public function store(Request $request)
    {
        try {
            $this->storeValidation($request);
            DB::beginTransaction();
            $exchange_money = new ExchangeMoney();
            $attributes = $request->only($exchange_money->getFillable());
            $sendDate = $attributes['date'];
            $date1 = new DateTime($sendDate);
            $attributes['date'] = $date1->format('Y-m-d H:i');
            $exchange_money =  $exchange_money->create($attributes);
            DB::commit();
            return response()->json($exchange_money, 200);
        } catch (\Exception $th) {
            return response()->json($th->getMessage(), 500);
        }
    }

    public function storeValidation($request)
    {
        return $request->validate(
            [
                "sender_name" => "required",
                "amount" => "required",
                "currency"=> "required",
                'province' => 'required',
                'receiver_name' => 'required',
                'receiver_father_name' => 'required',
                'receiver_id_no' => 'required|unique:exchange_money',
                "exchange_id"=> "required",
                "date"=>'required'

            ],
            [
                'sender_name.required' => "نام فرستنده ضروری می باشد",
                'amount.required' => "مقدار ضروری می باشد",
                'province.required' => "ولایت ضروری می باشد",
                'receiver_name.required' => "نام فرستنده ضروری می باشد",
                'receiver_father_name.required' => "نام پدر فرستنده ضروری می باشد",
                'receiver_id_no.required' => "ای دی فرستنده ضروری می باشد",
                'receiver_id_no.unique' => "ای دی فرستنده موجود می باشد",

            ]
        );
    }
    public function update(Request $request, string $id)
    {
        try {
            $exchange_money = ExchangeMoney::find($id);
            $attributes = $request->only($exchange_money->getFillable());
            $exchange_money->update($attributes);
            DB::commit();
            return response()->json($exchange_money);
        } catch (\Throwable $th) {
            DB::rollBack();
            return response()->json($th->getMessage(), 500);
        }
    }

    function editExchange(Request $request)
    {

        $request->validate(
            [
                // 'id' => 'required|unique,id,' . $request->id,
                'sender_name' => ['required'],
                'amount' => ['required'],
                'currency' => 'required',
                'province' => 'required',
                'receiver_name' => 'required',
                'receiver_father_name' => 'required',
                'receiver_id_no' => 'required',
                'date' => 'required',

            ],
            [
                'sender_name.required' => 'نام فرستنده ضروری است',
                'phone_number.unique' => "شماره تلفن ذیل موجود می باشد",
                'phone_number.required' => "شماره تیلفون ضروری می باشد",
                'amount.required' => " مبلغ ضروری می باشد",
                'currency.required' => " واحد پول ضروری می باشد",
                'province.required' => " ولایت ضروری می باشد",
                'receiver_name.required' => " نام گیرنده ضروری می باشد",
                'receiver_father_name.required' => " نام پدر گیرنده ضروری می باشد",
                'receiver_id_no.required' => " آی دی گیرنده ضروری می باشد",
                'date.required' => " تاریخ ضروری می باشد",
            ]
        );
        try {
            DB::beginTransaction();
            $user = ExchangeMoney::find($request->id);
            $user->update([
                "sender_name" => $request->sender_name,
                "amount" => $request->amount,
                "currency" => $request->currency,
                "province" => $request->province,
                "receiver_name" => $request->receiver_name,
                "receiver_father_name" => $request->receiver_father_name,
                "phone_number" => $request->phone_number,
                "receiver_id_no" => $request->receiver_id_no,
                "date" => $request->date,

            ]);
            DB::commit();
            return  response()->json($user, 201);
        } catch (\Exception $e) {
            DB::rollback();
            return response()->json($e->getMessage(), 500);
        }
    }


    public function destroy(string $id)
    {
        try {
            $ids = explode(',', $id);
            ExchangeMoney::whereIn('id', $ids)->delete();
            return response()->json(true);
        } catch (\Exception $th) {
            //throw $th;
            return response()->json($th->getMessage(), 500);
        }
    }
    public function restore(string $id)
    {
        try {
            $ids = explode(",", $id);
            ExchangeMoney::whereIn('id', $ids)->withTrashed()->restore();
            return response()->json(true, 203);
        } catch (\Throwable $th) {
            return response()->json($th->getMessage(), 500);
        }
    }
    public function forceDelete(string $id)
    {
        try {
            $ids = explode(",", $id);
            ExchangeMoney::whereIn('id', $ids)->withTrashed()->forceDelete();
            return response()->json(true, 206);
        } catch (\Throwable $th) {
            return response()->json($th->getMessage(), 500);
        }
    }
}
