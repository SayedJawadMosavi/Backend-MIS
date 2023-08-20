<?php

namespace App\Http\Controllers;

use App\Models\Car;
use App\Models\Order;
use App\Models\OrderExtraExpense;
use App\Models\OrderItem;
use DateTime;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

use function PHPUnit\Framework\returnSelf;

class CarController extends Controller
{
    /**
     * Display a listing of the resource.
     */

    public function __construct()
    {
        $this->middleware('permissions:car_view')->only('index');
    }

    public function index(Request $request)
    {
        try {
            $query = new Car();
            $searchCol = ["id", "payments.amount", "extraExpense.price", "items.count", 'items.weight', "carExpense.price"];
            $query = $this->search($query, $request, $searchCol);
            $query = $query->withSum('payments', 'amount')
                ->withSum('extraExpense', 'price')
                ->withSum('items', 'count')
                ->withSum('items', 'weight')
                ->withSum('carExpense', 'price')
                // ->whereHas('items')
                ->withCount('items')
                ->withCount('orders')
                ->with($this->getRelations());
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
                $result->total_price = 0;
                $result->start_date = null;
                $result->end_date = null;
                if ($result->items_count > 0) {
                    $items = $result->items[0];
                    $result->total_price =  $items->total_price + $result->extra_expense_sum_price;
                    $result->start_date = $items->start_date;
                    $result->end_date = $items->end_date;
                    unset($result->items);
                }
                $result->benefits = $result->total_price - $result->car_expense_sum_price;
                $result->remainder = $result->total_price - $result->payments_sum_amount;
                return $result;
            });
            return response()->json(["data" => $results, 'total' => $total, "extraTotal" => ['cars' => $allTotal, 'trash' => $trashTotal]]);
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

    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        //
    }



    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
    }

    public function getRelations()
    {
        return   [
            'items' => function ($query) {
                $query->select('car_id', DB::raw('SUM(weight * price_per_killo) as total_price'), DB::raw('MAX(created_at) as start_date'), DB::raw('MIN(created_at) as end_date'))->groupBy('car_id');
            },

        ];
    }

    public function storeValidation($request)
    {

        return $request->validate([]);
    }

    public function getCurrentCar()
    {
        try {
            //code...
            $car = Car::whereStatus(true)->first();
            if (!$car) {
                $car = Car::create(['status' => true]);
            }
            $group_number = Order::where('car_id', $car->id)->max('group_number');
            return response()->json(['group_number' => $group_number + 1, 'car_id' => $car->id], 200);
        } catch (\Throwable $th) {
            return response()->json($th, 500);
        }
    }

    public function changeStatus(Request $request)
    {
        try {
            $car = Car::find($request->id);
            Car::query()->where('status', true)->update(['status' => false]);
            $car->status = !$car->status;
            $car->save();
            return response()->json($car, 202);
        } catch (\Throwable $th) {
            return response()->json($th->getMessage(), 500);
        }
    }

    public function carOrders(Request $request)
    {
        try {
            $query = new Order();
            $query = $query->whereCarId($request->id)->withSum('payments', 'amount')->withSum('extraExpense', 'price')->withSum('items', 'count')->withSum('items', 'weight');

            $results = $query->latest()->get();
            $results = collect($results);
            $results = $results->map(function ($result) {
                $result->total_price = $result->items_sum_weight * $result->price_per_killo + $result->extra_expense_sum_price;
                $result->remainder = $result->total_price - $result->payments_sum_amount;
                return $result;
            });
            return response()->json($results);
        } catch (\Throwable $th) {
            return response()->json($th->getMessage(), 500);
        }
    }
}
