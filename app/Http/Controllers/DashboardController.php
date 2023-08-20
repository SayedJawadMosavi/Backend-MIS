<?php

namespace App\Http\Controllers;

use App\Models\Car;
use App\Models\ExchangeMoney;
use App\Models\IncomingOutgoing;
use App\Models\Order;
use App\Models\SalaryPayment;
use Carbon\Carbon;
use DateTime;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class DashboardController extends Controller
{
    //

    public function index()
    {
        $transactions = $this->transactions();
        $accountMoney = $this->AccountPayment();


        return response()->json([
            'transactions' => $transactions, 'account_money' => $accountMoney,

            'analytics' => $this->ordersAnalytics(),
            'incomes' => $this->incomings(),
            'salaries' => $this->salaries(),
            'orders' => $this->orders(),
        ]);
    }
    public function transactions()
    {;
        return [Order::count(), Car::count(), IncomingOutgoing::count(), ExchangeMoney::count()];
    }

    public function AccountPayment()
    {
        $query = new IncomingOutgoing();

        $totalIncomes = clone $query;
        $totalIncomes = $totalIncomes->whereType('incoming')->sum('amount');

        $totalOutGoing = clone $query;
        $totalOutGoing = $totalOutGoing->whereType('outgoing')->sum('amount');
        return $totalIncomes - $totalOutGoing;
    }

    public function ordersAnalytics()
    {
        $query = new Order();
        $startDate = Carbon::now()->subDays(7);
        $records = $query
            ->select(DB::raw('DAYNAME(created_at) as day'), DB::raw('COUNT(*) as count'))
            ->where('created_at', '>=', $startDate)
            ->groupBy('day')
            ->get();
        $records = array_map(function ($row) {
            $data = [];
            $row['day'] = $row['count'];
            return $data;
        }, $records->toArray());

        $days = [];

        for ($date = $startDate; $date <= Carbon::now(); $date->addDay()) {
            $dayName = $date->dayName;
            $days[] = $dayName;
        }

        foreach ($days as $day) {
            if (!isset($records[$day])) {
                $records[$day] = 0;
            }
        }

        return $records;
    }

    public function incomings()
    {
        # code...
        $income = IncomingOutgoing::where('type', 'incoming')->latest()->limit(5)->get();
        $outgoing = IncomingOutgoing::where('type', 'outgoing')->latest()->limit(5)->get();
        return ['incoming' => $income, 'outgoing' => $outgoing];
    }
    public function salaries()
    {
        $payments = SalaryPayment::with('employee')->latest()->limit(5)->get();
        return $payments;
    }

    public function orders()
    {
        try {
            $query = new Order();
            $searchCol = ['customer_name', 'customer_phone', 'receiver_name', 'receiver_phone', 'country', 'city', 'address', 'created_at'];

            $query = $query->withSum('payments', 'amount')->withSum('extraExpense', 'price')->withSum('items', 'count')->withSum('items', 'weight');

            $results = $query->latest()->limit(8)->get();
            $results = collect($results);
            $results = $results->map(function ($result) {
                $result->total_price = $result->items_sum_weight * $result->price_per_killo + $result->extra_expense_sum_price;
                $result->remainder = $result->total_price - $result->payments_sum_amount;
                return $result;
            });
            return  $results;
        } catch (\Throwable $th) {
            return response()->json($th->getMessage(), 500);
        }
    }
    public function reports(Request $request)
    {
        # code...
        $request->validate(
            [
                'start_date' => ['required', 'date', 'before_or_equal:' . $request->end_date],
            ],
            [
                "start_date.date" => "تاریخ شروع درست نمی باشد",
                "start_date.before_or_equal" => "تاریخ شروع بزرگتر از تاریخ ختم شده نمیتواند!",
            ]

        );
        try {
            $type = $request->type;
            $date1 = new DateTime($request->start_date);
            $startDate = $date1->format('Y-m-d');

            $date1 = new DateTime($request->end_date);
            $endDate = $date1->format('Y-m-d');


            if ($type == 'exchange') {
                $data = ExchangeMoney::whereBetween(DB::raw('DATE(created_at)'), [$startDate, $endDate])->get();
                return response()->json($data);
                // ExchangeMoney::where(DB::raw('Date(created_at)'), '>=', $request->start_date)->where(DB::raw('Date(created_at)'), '<=', $request->end)->get();
            }
            if ($type == 'incoming') {
                $data = IncomingOutgoing::whereBetween(DB::raw('DATE(created_at)'), [$startDate, $endDate])->where('type', 'incoming')->get();
                return response()->json($data);
            }
            if ($type == 'outgoing') {
                $data = IncomingOutgoing::whereBetween(DB::raw('DATE(created_at)'), [$startDate, $endDate])->where('type', 'outgoing')->get();
                return response()->json($data);
            }

            //code...
        } catch (\Throwable $th) {
            return response()->json($th->getMessage(), 500);
        }
    }
}
