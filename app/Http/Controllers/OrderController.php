<?php

namespace App\Http\Controllers;

use App\Models\IncomingOutgoing;
use App\Models\Order;
use App\Models\OrderExtraExpense;
use App\Models\OrderItem;
use App\Models\OrderPayment;
use DateTime;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

use function PHPUnit\Framework\returnSelf;

class OrderController extends Controller
{
    /**
     * Display a listing of the resource.
     */

    public function __construct()
    {

        $this->middleware('permissions:order_view')->only('index');
        $this->middleware('permissions:order_create')->only(['store', 'addItem', 'updateItem']);
        $this->middleware('permissions:order_delete')->only(['destroy']);
        $this->middleware('permissions:order_restore')->only(['restore']);
        $this->middleware('permissions:order_force_delete')->only(['forceDelete']);
    }


    public function index(Request $request)
    {
        try {
            $query = new Order();
            $searchCol = ['customer_name', 'group_number', 'customer_phone', "father_name", "grand_father_name", "tazkira_id", "delivary_type", 'receiver_name', 'receiver_phone', 'country', 'city', 'address', 'created_at'];

            $query = $this->search($query, $request, $searchCol);
            $query = $query->withSum('payments', 'amount')->withSum('extraExpense', 'price')->withSum('items', 'count')->withSum('items', 'weight');
            $trashTotal = clone $query;
            $trashTotal = $trashTotal->onlyTrashed()->count();

            $allTotal = clone $query;
            $allTotal = $allTotal->count();
            if ($request->tab == 'trash') {
                $query = $query->onlyTrashed();
            }
            $query = $query->latest()->paginate($request->itemPerPage);
            $results = collect($query->items());
            $total = $query->total();
            $results = $results->map(function ($result) {
                $result->total_price = $result->items_sum_weight * $result->price_per_killo + $result->extra_expense_sum_price;
                $result->remainder = $result->total_price - $result->payments_sum_amount;
                return $result;
            });
            return response()->json(["data" => $results, 'total' => $total, "extraTotal" => ['orders' => $allTotal, 'trash' => $trashTotal]]);
        } catch (\Throwable $th) {
            return response()->json($th->getMessage(), 500);
        }
    }


    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        //
        try {
            $this->storeValidation($request);


            $exist = Order::where('car_id', $request->car_id)->where('group_number', $request->group_number)->first();
            if ($exist) {
                return response()->json('نمبر گروپ قبلا استفاده شده', 500);
            }
            $car =  OrderItem::where('car_id', $request->car_id)->select(DB::raw('sum(weight) as total_weight'))->first();
            if ($car->total_weight + $request->weight > 21500) {
                return response()->json('ضرفیت بار موتر به ' . $car->total_weight . 'kg رسیده است نمیتواند اضافه بار کنید!', 500);
            }
            DB::beginTransaction();
            $order = new Order();
            $user_id = Auth::user()->id;

            $attributes = $request->only($order->getFillable());
            $attributes['created_by'] = $user_id;
            $attributes['created_at'] = $request->date;
            // $attributes['id'] = 2;
            $order =  $order->create($attributes);


            foreach ($request->items as $item) {
                $item['created_by'] = $user_id;
                $item['order_id'] = $order->id;
                $item['car_id'] = $order->car_id;
                $item['created_at'] = $request->date;
                $item['price_per_killo'] = $order->price_per_killo;
                OrderItem::create($item);
            }
            foreach ($request->extra_expense as $exp) {
                $exp['created_by'] = $user_id;
                $exp['order_id'] = $order->id;
                $exp['car_id'] = $order->car_id;
                $item['created_at'] = $request->date;
                OrderExtraExpense::create($exp);
            }

            if ($request->paid_amount > 0) {
                $payment = OrderPayment::create(['order_id' => $order->id, 'amount' => $request->paid_amount, 'created_by' => $user_id, 'created_at' => $request->date, 'car_id' => $request->car_id]);
                IncomingOutgoing::create(['table' => "order", 'table_id' => $payment->id, 'type' => 'incoming', 'name' => 'آمد از گروپ  ' . $order->group_number . ' (موتر ' . $order->car_id . ')', 'amount' => $request->paid_amount, 'created_by' => $user_id, 'created_at' => $payment->created_at,]);
            }

            DB::commit();
            return response()->json($order, 201);
        } catch (\Exception $th) {
            DB::rollBack();
            return response()->json($th->getMessage(), 500);
        }
    }

    public function addItem(Request $request)
    {
        try {
            $request->validate(
                [
                    'car_id' => ['required', 'exists:cars,id'],
                    'order_id' => ['required', 'exists:orders,id'],
                    'created_at' => ['required', 'date', 'before_or_equal:' . now()],
                    'name' => 'required',
                    'count' => 'required|numeric|min:1',
                    'type' => 'required',
                    'weight' => 'required|numeric|min:0.01',
                ],
                [
                    'car_id.required' => 'نمبر موتر ضروری میباشد!',
                    'car_id.exists' => 'نمبر موتر در سیستم موجود نیست!',
                    'order_id.required' => 'نمبر سفارش ضروری میباشد!',
                    'order_id.exists' => 'نمبر سفارش در سیستم موجود نیست!',
                    "created_at.required" => "تاریخ ثبت ضروری میباشد",
                    "created_at.date" => "تاریخ ثبت درست نمی باشد",
                    "created_at.before_or_equal" => "تاریخ ثبت بزرگتر از تاریخ فعلی شده نمیتواند!",
                    'name.required' => 'نام ضروری میباشد',
                    'type.required' => 'نوعیت ضروری میباشد',
                    'count.required' => 'تعداد ضروری میباشد ',
                    'count.numeric' => 'تعداد باید عدد باشد',
                    'count.min' => 'تعداد کمتر از یک شده نیتواند',
                    'weight.required' => 'وزن ضروری میباشد ',
                    'weight.numeric' => 'وزن باید عدد باشد',
                    'weight.min' => 'وزن کمتر از یک شده نیتواند',

                ],

            );
            DB::beginTransaction();
            $order = Order::find($request->order_id);
            $user_id = Auth::user()->id;

            $attributes = $request->all();

            $attributes['created_by'] = $user_id;
            $attributes['created_at'] = $attributes['created_at'];
            $attributes['order_id'] = $order->id;
            $attributes['car_id'] = $order->car_id;
            $attributes['price_per_killo'] = $order->price_per_killo;

            $item =  OrderItem::create($attributes);

            DB::commit();
            return response()->json($item, 201);
        } catch (\Exception $th) {
            DB::rollBack();
            return response()->json($th->getMessage(), 500);
        }
    }


    public function updateItem(Request $request)
    {
        try {

            $request->validate(
                [
                    'id' => ['required', 'exists:order_items,id'],
                    'car_id' => ['required', 'exists:cars,id'],
                    'order_id' => ['required', 'exists:orders,id'],
                    'created_at' => ['required', 'date', 'before_or_equal:' . now()],
                    'name' => 'required',
                    'count' => 'required|numeric|min:1',
                    'type' => 'required',
                    'weight' => 'required|numeric|min:0.01',
                ],
                [
                    'id.required' => 'نمبر موتر ضروری میباشد!',
                    'id.exists' => 'نمبر موتر در سیستم موجود نیست!',
                    'car_id.required' => 'نمبر موتر ضروری میباشد!',
                    'car_id.exists' => 'نمبر موتر در سیستم موجود نیست!',
                    'order_id.required' => 'نمبر سفارش ضروری میباشد!',
                    'order_id.exists' => 'نمبر سفارش در سیستم موجود نیست!',
                    "created_at.required" => "تاریخ ثبت ضروری میباشد",
                    "created_at.date" => "تاریخ ثبت درست نمی باشد",
                    "created_at.before_or_equal" => "تاریخ ثبت بزرگتر از تاریخ فعلی شده نمیتواند!",
                    'name.required' => 'نام ضروری میباشد',
                    'type.required' => 'نوعیت ضروری میباشد',
                    'count.required' => 'تعداد ضروری میباشد ',
                    'count.numeric' => 'تعداد باید عدد باشد',
                    'count.min' => 'تعداد کمتر از یک شده نیتواند',
                    'weight.required' => 'وزن ضروری میباشد ',
                    'weight.numeric' => 'وزن باید عدد باشد',
                    'weight.min' => 'وزن کمتر از یک شده نیتواند',
                ]

            );
            DB::beginTransaction();

            $expense = OrderItem::find($request->id);
            $expense->created_at = $request->created_at;
            $expense->name = $request->name;
            $expense->created_at = $request->created_at;
            $expense->count = $request->count;
            $expense->type = $request->type;
            $expense->weight = $request->weight;
            $expense->save();

            DB::commit();
            return response()->json($expense, 202);
        } catch (\Exception $th) {
            DB::rollBack();
            return response()->json($th->getMessage(), 500);
        }
    }
    public function addPayment(Request $request)
    {
        try {
            $request->validate(
                [
                    'car_id' => ['required', 'exists:cars,id'],
                    'order_id' => ['required', 'exists:orders,id'],
                    'created_at' => ['required', 'date', 'before_or_equal:' . now()],
                    'amount' => 'required|numeric|min:1',
                ],
                [
                    'car_id.required' => 'نمبر موتر ضروری میباشد!',
                    'car_id.exists' => 'نمبر موتر در سیستم موجود نیست!',
                    'order_id.required' => 'نمبر سفارش ضروری میباشد!',
                    'order_id.exists' => 'نمبر سفارش در سیستم موجود نیست!',
                    "created_at.required" => "تاریخ ثبت ضروری میباشد",
                    "created_at.date" => "تاریخ ثبت درست نمی باشد",
                    "created_at.before_or_equal" => "تاریخ ثبت بزرگتر از تاریخ فعلی شده نمیتواند!",
                    'amount.min' => 'مقدار پرداختی باید بزرگ از صفر باشد',
                    'amount.required' => 'مقدار پرداختی ضروری می باشد',
                    'amount.numeric' => 'مقدار پرداختی باید عدد باشد',

                ]
            );
            DB::beginTransaction();
            $order = Order::find($request->order_id);
            $user_id = Auth::user()->id;

            $attributes = $request->all();

            $attributes['created_by'] = $user_id;
            $attributes['created_at'] = $attributes['created_at'];
            $attributes['order_id'] = $order->id;
            $attributes['car_id'] = $order->car_id;
            $payment =  OrderPayment::create($attributes);
            IncomingOutgoing::create(['table' => "order", 'table_id' => $payment->id, 'type' => 'incoming', 'name' => 'آمد از گروپ  ' . $order->group_number . ' (موتر ' . $order->car_id . ')', 'amount' => $request->amount, 'created_by' => $user_id, 'created_at' => $payment->created_at,]);


            DB::commit();
            return response()->json($payment, 201);
        } catch (\Exception $th) {
            DB::rollBack();
            return response()->json($th->getMessage(), 500);
        }
    }

    public function updatePayment(Request $request)
    {
        try {
            $request->validate(
                [
                    'id' => ['required', 'exists:order_payments,id'],
                    'amount' => 'required|numeric|min:1',
                ],
                [
                    'id.required' => 'ای دی ضروری میباشد',
                    'id.exists' => 'آی دی در سیستم موجود نیست',
                    'amount.min' => 'مقدار پرداختی باید بزرگ از صفر باشد',
                    'amount.required' => 'مقدار پرداختی ضروری می باشد',
                    'amount.numeric' => 'مقدار پرداختی باید عدد باشد',
                ]
            );
            DB::beginTransaction();

            $payment = OrderPayment::find($request->id);
            if (!$payment)
                return response()->json('آی دی موجود نیست', 422);

            $order              = Order::withSum('payments', 'amount')->withSum('extraExpense', 'price')->withSum('items', 'weight')->find($payment->order_id);
            $total = $order->items_sum_weight * $order->price_per_killo + $order->extra_expense_sum_price;
            $paid = $order->payments_sum_amount - $payment->amount + $request->amount;

            if ($paid > $total) {
                return response()->json('نمیتواند بزرگتر از مجموع باشد', 422);
            }
            $payment->amount = $request->amount;
            $payment->save();
            $income = IncomingOutgoing::withTrashed()->where(['table' => 'order', 'table_id' => $request->id])->first();
            if ($income) {
                $income->amount = $request->amount;
                $income->save();
            }

            DB::commit();
            return response()->json($payment, 202);
        } catch (\Exception $th) {
            DB::rollBack();
            return response()->json($th->getMessage(), 500);
        }
    }




    public function addExpense(Request $request)
    {
        try {
            $request->validate(
                [
                    'car_id' => ['required', 'exists:cars,id'],
                    'order_id' => ['required', 'exists:orders,id'],
                    'created_at' => ['required', 'date', 'before_or_equal:' . now()],
                    'name' => 'required',
                    'price' => 'required|numeric|min:1',
                ],
                [
                    'car_id.required' => 'نمبر موتر ضروری میباشد!',
                    'car_id.exists' => 'نمبر موتر در سیستم موجود نیست!',
                    'order_id.required' => 'نمبر سفارش ضروری میباشد!',
                    'order_id.exists' => 'نمبر سفارش در سیستم موجود نیست!',
                    "created_at.required" => "تاریخ ثبت ضروری میباشد",
                    "created_at.date" => "تاریخ ثبت درست نمی باشد",
                    "created_at.before_or_equal" => "تاریخ ثبت بزرگتر از تاریخ فعلی شده نمیتواند!",
                    'name.required' => 'نام ضروری میباشد',
                    'price.required' => 'قیمت ضروری میباشد ',
                    'price.numeric' => 'قیمت باید عدد باشد',
                    'price.min' => 'قیمت کمتر از یک شده نیتواند',

                ]
            );
            DB::beginTransaction();
            $order = Order::find($request->order_id);
            $user_id = Auth::user()->id;

            $attributes = $request->all();

            $attributes['created_by'] = $user_id;
            $attributes['created_at'] = $attributes['created_at'];
            $attributes['order_id'] = $order->id;
            $attributes['car_id'] = $order->car_id;
            $expense =  OrderExtraExpense::create($attributes);

            DB::commit();
            return response()->json($expense, 201);
        } catch (\Exception $th) {
            DB::rollBack();
            return response()->json($th->getMessage(), 500);
        }
    }
    public function updateExpense(Request $request)
    {
        try {
            $request->validate(
                [
                    'id' => ['required', 'exists:order_extra_expenses,id'],
                    'created_at' => ['required', 'date', 'before_or_equal:' . now()],
                    'name' => 'required',
                    'price' => 'required|numeric|min:1',
                ],
                $this->validationTranslation()
            );
            DB::beginTransaction();

            $expense = OrderExtraExpense::find($request->id);
            $expense->created_at = $request->created_at;
            $expense->name = $request->name;
            $expense->price = $request->price;
            $expense->save();

            DB::commit();
            return response()->json($expense, 202);
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
        try {
            $order = new Order();
            $order = $order->with(['payments' => fn ($q) => $q->withTrashed(), 'items' => fn ($q) => $q->withTrashed(), 'extraExpense' => fn ($q) => $q->withTrashed()])->withTrashed()->withSum('payments', 'amount')->withSum('extraExpense', 'price')->withSum('items', 'weight')->find($id);
            $order->total_price = $order->items_sum_weight * $order->price_per_killo + $order->extra_expense_sum_price;
            $order->remainder  = $order->total_price - $order->payments_sum_amount;

            return response()->json($order);
        } catch (\Throwable $th) {
            //throw $th;
            return response()->json($th->getMessage(), 500);
        }
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
        try {
            $this->updateValidation($request);
            DB::beginTransaction();
            $order = Order::find($id);
            $attributes = $request->only($order->getFillable());
            if (isset($attributes['id']));
            unset($attributes['id']);
            $order->update($attributes);
            $order->items()->update(['price_per_killo' => $order->price_per_killo, 'car_id' => $order->car_id]);
            DB::commit();
            return response()->json($order, 202);
        } catch (\Throwable $th) {
            DB::rollBack();
            return response()->json($th->getMessage(), 500);
        }
    }

    /**
     * Remove the specified resource from storage.
     */
    public function restore(string $type, string $id)
    {
        try {
            DB::beginTransaction();
            $ids = explode(",", $id);

            if ($type == 'orders') {
                $model = new Order();
                foreach ($ids as $id) {
                    $trashed_order = Order::onlyTrashed()->find($id);
                    if ($trashed_order) {
                        $active_order = Order::where('car_id', $trashed_order->car_id)->where('group_number', $trashed_order->group_number)->first();
                        if ($active_order) {
                            $group_number = Order::where('car_id', $active_order->car_id)->max('group_number');
                            $trashed_order->group_number = $group_number + 1;
                            $trashed_order->save();
                            $payment_ids =  OrderPayment::withTrashed()->whereIn('order_id', $ids)->get()->pluck('id');
                            IncomingOutgoing::withTrashed()->where(['table' => 'order'])->whereIn('table_id', $payment_ids)
                                ->update(['name' =>  'آمد از گروپ  ' . $trashed_order->group_number . ' (موتر ' . $trashed_order->car_id . ')']);
                        }
                    }
                }
                $payment_ids =  OrderPayment::withTrashed()->whereIn('order_id', $ids)->get()->pluck('id');
                OrderPayment::withTrashed()->whereIn('order_id', $ids)->restore();
                OrderItem::withTrashed()->whereIn('order_id', $ids)->restore();
                OrderExtraExpense::withTrashed()->whereIn('order_id', $ids)->restore();
                IncomingOutgoing::withTrashed()->where(['table' => 'order'])->whereIn('table_id', $payment_ids)->restore();
            }
            if ($type == 'payments') {
                $model = new OrderPayment();
                IncomingOutgoing::withTrashed()->where(['table' => 'orders'])->whereIn('table_id', $ids)->restore();
            }
            if ($type == 'items')
                $model = new OrderItem();
            if ($type == 'expenses')
                $model = new OrderExtraExpense();

            $model->whereIn('id', $ids)->withTrashed()->restore();
            DB::commit();
            return response()->json(true, 203);
        } catch (\Throwable $th) {

            DB::rollBack();
            return response()->json($th->getMessage(), 500);
        }
    }

    public function destroy(string $type, string $id)
    {
        try {
            DB::beginTransaction();
            $ids = explode(",", $id);



            if ($type == 'orders') {
                $model = new Order();
                $payment_ids =  OrderPayment::whereIn('order_id', $ids)->get()->pluck('id');
                OrderPayment::whereIn('order_id', $ids)->delete();
                OrderItem::whereIn('order_id', $ids)->delete();
                OrderExtraExpense::whereIn('order_id', $ids)->delete();
                IncomingOutgoing::withTrashed()->where(['table' => 'order'])->whereIn('table_id', $payment_ids)->delete();
            }


            if ($type == 'payments') {
                $model = new OrderPayment();
                IncomingOutgoing::withTrashed()->where(['table' => 'order'])->whereIn('table_id', $ids)->delete();
            }
            if ($type == 'items')
                $model = new OrderItem();
            if ($type == 'expenses')
                $model = new OrderExtraExpense();

            $result =  $model->whereIn('id', $ids)->delete();
            DB::commit();
            return response()->json($result, 206);
        } catch (\Throwable $th) {
            DB::rollBack();
            return response()->json($th->getMessage(), 500);
        }
    }

    public function forceDelete(string $type, string $id)
    {
        try {
            DB::beginTransaction();
            $ids = explode(",", $id);

            if ($type == 'orders') {
                $model = new Order();
                $payment_ids =  OrderPayment::withTrashed()->whereIn('order_id', $ids)->get()->pluck('id');
                IncomingOutgoing::withTrashed()->where(['table' => 'order'])->whereIn('table_id', $payment_ids)->forceDelete();
            }


            if ($type == 'payments') {
                $model = new OrderPayment();
                IncomingOutgoing::withTrashed()->where(['table' => 'order'])->whereIn('table_id', $ids)->forceDelete();
            }
            if ($type == 'items') {
                $model = new OrderItem();
            }
            if ($type == 'expenses') {
                $model = new OrderExtraExpense();
            }

            $result =  $model->withTrashed()->whereIn('id', $ids)->forceDelete();
            DB::commit();
            return response()->json($result, 206);
        } catch (\Throwable $th) {
            DB::rollBack();
            return response()->json($th->getMessage(), 500);
        }
    }


    public function getRelations()
    {
        return   [
            'items' => function ($query) {
                $query->select('order_id', DB::raw('SUM(count * weight * price_per_killo) as total_price'))->groupBy('order_id');
            },


        ];
    }
    public function storeValidation($request)
    {
        return $request->validate(
            [
                'car_id' => ['required', 'exists:cars,id'],
                'group_number' => [
                    'required',
                ],
                'date' => ['required', 'date', 'before_or_equal:' . now()],
                'customer_name' => 'required',
                'customer_phone' => 'required',
                'receiver_name' => 'required',
                'receiver_phone' => 'required',
                'country' => 'required',
                'city' => 'required',
                'address' => 'required',
                'price_per_killo' => 'required|numeric|min:0',

                'paid_amount' => 'numeric:min:0',
                'items' => 'required|array|min:1',
                'items.*.name' => 'required',
                'items.*.count' => 'required|numeric|min:1',
                'items.*.type' => 'required',
                'items.*.weight' => 'required|numeric|min:0',
                'extra_expense' => 'sometimes|array',
                'extra_expense.*.name' => 'required_with:extra_expense|string',
                'extra_expense.*.price' => 'required_with:extra_expense|numeric|min:0',
            ],
            [
                'car_id.required' => 'نمبر موتر ضروری میباشد!',
                'group_number.required' => 'نمبر گروپ ضروری میباشد!',
                'car_id.exists' => 'نمبر موتر در سیستم موجود نیست!',
                "date.required" => "تاریخ ثبت ضروری میباشد",
                "date.date" => "تاریخ درست نمی باشد",
                "date.before_or_equal" => "تاریخ ثبت بزرگتر از تاریخ فعلی شده نمیتواند!",
                'customer_name.required' => 'اسم مشتری ضروری میباشد',
                'customer_phone.required' => 'شماره تماس مشتری ضروری میباشد',
                'receiver_name.required' => 'اسم گیرنده ضروری میباشد',
                'receiver_phone.required' => 'شماره تماس گیرنده ضروری میباشد',
                'country.required' => ' کشور ضروری میباشد',
                'city.required' => ' شهر ضروری میباشد',
                'address.required' => 'آدرس ضروری می باشد',
                'price_per_killo.required' => 'قیمت فی کیلو ضروری میباشد ',
                'price_per_killo.numeric' => 'قیمت فی کیلو باید عدد باشد',
                'price_per_killo.min' => 'قیمت فی کیلو کمتر از یک شده نمیتواند',
                'paid_amount.numeric' => 'مقدار پرداختی باید عدد باشد',
                'paid_amount.min' => 'مقدار پرداختی کمتر از یک شده نمی تواند',
                'items.required' => 'موارد ضروری می باشد',
                'items.array' => 'موارد باید لیست باشد',
                'items.min' => 'طول لیست موارد کمتر از یک شده نمی تواند',
                'items.*.name.required' => 'نام در موارید ضرور می باشد',
                'items.*.count.required' => 'شمارش در موارید ضرور می باشد',
                'items.*.count.numeric' => 'شمارش در موارید باید عدد باشد',
                'items.*.count.min' => 'شمارش در موارید از یک کمتر بوده نمی تواند',
                'items.*.type.required' => 'نوعیت در موارید ضروری می باشد',
                'items.*.weight.required' => 'وزن در موارید ضرور می باشد',
                'items.*.weight.numeric' => 'وزن در موارید عدد باید باشد',
                'items.*.weight.min' => 'وزن در موارید شده نمی تواند',
                'extra_expense.sometimes' => 'مصرف اضافه',
                'extra_expense.array' => 'مصرف اضافه باید لیست باشد',
                'extra_expense.*.name.required_with' => 'نام در مصرف اضافه ضرور می باشد',
                'extra_expense.*.name.string' => 'نام در مصرف اضافه باید کلمه باشد',
                'extra_expense.*.price.required_with' => 'قمیت در مصرف اضافه ضرور می باشد',
                'extra_expense.*.price.numeric' => 'قیمت در مصرف اضافه باید عدد باشد',
                'extra_expense.*.price.min' => 'قیمت در مصرف اضافه کمتر از یک شده نمی تواند',
            ]
        );
    }

    public function updateValidation($request)
    {
        return $request->validate(
            [
                'car_id' => ['required', 'exists:cars,id'],
                'created_at' => ['required', 'date', 'before_or_equal:' . now()],
                'customer_name' => 'required',
                'customer_phone' => 'required',
                'receiver_name' => 'required',
                'receiver_phone' => 'required',
                'country' => 'required',
                'city' => 'required',
                'address' => 'required',
                'price_per_killo' => 'required|numeric|min:1',
            ],
            [
                'car_id.required' => 'نمبر موتر ضروری میباشد!',
                'car_id.exists' => 'نمبر موتر در سیستم موجود نیست!',
                "created_at.required" => "تاریخ ثبت ضروری میباشد",
                "created_at.date" => "تاریخ ثبت درست نمی باشد",
                "created_at.before_or_equal" => "تاریخ ثبت بزرگتر از تاریخ فعلی شده نمیتواند!",
                'customer_name.required' => 'اسم مشتری ضروری میباشد',
                'customer_phone.required' => 'شماره تماس مشتری ضروری میباشد',
                'receiver_name.required' => 'اسم گیرنده ضروری میباشد',
                'receiver_phone.required' => 'شماره تماس گیرنده ضروری میباشد',
                'country.required' => ' کشور ضروری میباشد',
                'city.required' => ' شهر ضروری میباشد',
                'address.required' => 'آدرس ضروری می باشد',
                'price_per_killo.required' => 'قیمت فی کیلو ضروری میباشد ',
                'price_per_killo.numeric' => 'قیمت فی کیلو باید عدد باشد',
                'price_per_killo.min' => 'قیمت فی کیلو کمتر از یک شده نیتواند',
            ]
        );
    }
}
