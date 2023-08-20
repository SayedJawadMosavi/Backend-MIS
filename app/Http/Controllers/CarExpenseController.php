<?php

namespace App\Http\Controllers;

use App\Models\CarExpense;
use App\Models\IncomingOutgoing;
use App\Models\Order;
use DateTime;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class CarExpenseController extends Controller
{
    /**
     * Display a listing of the resource.
     */

    public function __construct()
    {
        $this->middleware('permissions:car_create')->only('store');
        $this->middleware('permissions:car_force_delete')->only('forceDelete');
    }

    public function index()
    {
        //
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $this->storeValidation($request);
        try {
            DB::beginTransaction();
            $expense = new CarExpense();
            $user_id = Auth::user()->id;

            $attributes = $request->only($expense->getFillable());
            $attributes['created_by'] = $user_id;
            $expense =  $expense::create($attributes);
            IncomingOutgoing::create(['table' => "car_expense", 'table_id' => $expense->id, 'type' => 'outgoing', 'name' => $attributes['name'] . ' (موتر شماره  ' . $request->car_id . ')', 'amount' => $request->price, 'created_by' => $user_id, 'created_at' => $request->created_at,]);

            DB::commit();
            return response()->json(true, 201);
        } catch (\Exception $th) {
            DB::rollBack();
            return response()->json($th->getMessage(), 500);
        }
    }


    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        //
        try {
            $expenses = CarExpense::with('user:id,name')->withTrashed()->where('car_id', $id)->get();

            $query = new Order();
            $query = $query->whereCarId($id)->withSum('payments', 'amount')->withSum('extraExpense', 'price')->withSum('items', 'count')->withSum('items', 'weight');

            $orders = $query->latest()->get();
            $orders = collect($orders);
            $orders = $orders->map(function ($result) {
                $result->total_price = $result->items_sum_weight * $result->price_per_killo + $result->extra_expense_sum_price;
                $result->remainder = $result->total_price - $result->payments_sum_amount;
                return $result;
            });
            return response()->json(['orders' => $orders, 'expenses' => $expenses]);
        } catch (\Exception $th) {
            return response()->json($th->getMessage(), 500);
        }
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
        //
        try {

            $request->validate(
                [
                    'id' => ['required', 'exists:car_expenses,id'],
                    'created_at' => ['required', 'date', 'before_or_equal:' . now()],
                    'price' => 'required|numeric|min:1',
                ],
                [
                    'id.exists' => 'آی دی مصرف در سیستم موجود نیست!',
                    "created_at.required" => "تاریخ ثبت ضروری میباشد",
                    "created_at.date" => "تاریخ ثبت درست نمی باشد",
                    "created_at.before_or_equal" => "تاریخ ثبت بزرگتر از تاریخ فعلی شده نمیتواند!",

                ]

            );
            DB::beginTransaction();

            $expense = CarExpense::find($request->id);
            $expense->created_at = $request->created_at;
            $expense->price = $request->price;
            $expense->name = $request->name;
            $expense->save();

            $income = IncomingOutgoing::withTrashed()->where(['table' => 'car_expense', 'table_id' => $request->id])->first();
            if ($income) {
                $income->amount = $request->price;
                $income->save();
            }
            DB::commit();
            return response()->json($expense, 202);
        } catch (\Exception $th) {
            DB::rollBack();
            return response()->json($th->getMessage(), 500);
        }
    }

    public function restore(string $id)
    {
        try {
            $model = new CarExpense();
            $model->withTrashed()->find($id)->restore();
            IncomingOutgoing::withTrashed()->where(['table' => 'car_expense', 'table_id' => $id])->restore();

            return response()->json(true, 203);
        } catch (\Throwable $th) {
            return response()->json($th->getMessage(), 500);
        }
    }

    public function forceDelete(string $id)
    {
        try {
            $model = new CarExpense();
            $model->withTrashed()->find($id)->forceDelete();
            IncomingOutgoing::withTrashed()->where(['table' => 'car_expense', 'table_id' => $id])->forceDelete();
            return response()->json(true, 206);
        } catch (\Throwable $th) {
            return response()->json($th->getMessage(), 500);
        }
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        try {
            DB::beginTransaction();
            $model = new CarExpense();
            $result =  $model->find($id)->delete();
            IncomingOutgoing::withTrashed()->where(['table' => 'car_expense', 'table_id' => $id])->delete();
            DB::commit();
            return response()->json($result, 206);
        } catch (\Throwable $th) {
            //throw $th;
            DB::rollBack();
            return response()->json($th->getMessage(), 500);
        }
    }
    public function storeValidation($request)
    {

        return $request->validate(
            [
                'car_id' => ['required', 'exists:cars,id'],
                'created_at' => 'required',
                'price' => 'required|min:1',
                'name' => 'required|min:3',
            ],
            [
                'car_id.required' => 'نمبر موتر ضروری میباشد!',
                'car_id.exists' => 'نمبر موتر در سیستم موجود نیست!',
                "date.required" => "تاریخ ثبت ضروری میباشد",
                'expense.required' => 'هزینه ضروری می باشد',
                'expense.array' => 'هزینه باید لیست باشد',
                'expense.min' => 'طول لیست هزینه کمتر از یک شده نمی تواند',
                'expense.*.name.required' => 'نام هزینه ضروری می باشد',
                'expense.*.price.required' => 'قیمت هزینه ضروری می باشد',
                'expense.*.price.integer' => 'قیمت هزینه باید عدد باشد',
                'expense.*.price.min' => 'قیمت هزینه کمتر از یک شده نمی تواند',

            ]
        );
    }
}
