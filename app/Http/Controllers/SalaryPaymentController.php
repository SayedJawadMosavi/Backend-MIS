<?php

namespace App\Http\Controllers;

use App\Models\Employee;
use App\Models\IncomingOutgoing;

use App\Models\SalaryPayment;
use Carbon\Carbon;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;


class SalaryPaymentController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        try {
            $query = new SalaryPayment();
            $searchCol = ['employee_id', 'created_at', 'employee.first_name', 'employee.last_name', "paid", "salary"];
            $query = $this->search($query, $request, $searchCol);
            $query = $query->with('employee:id,first_name,last_name,salary,job_title');
            $trashTotal = clone $query;
            $trashTotal = $trashTotal->onlyTrashed()->count();

            $allTotal = clone $query;
            $allTotal = $allTotal->count();
            if ($request->tab == 'trash') {
                $query = $query->onlyTrashed();
            }
            $query = $query->latest()->paginate($request->itemPerPage);
            $results = $query->items();
            $total = $query->total();
            return response()->json(["data" => $results, 'total' => $total, "extraTotal" => ['salaries' => $allTotal, 'trash' => $trashTotal]]);
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
            DB::beginTransaction();
            $dateString = $request->created_at;
            $date = Carbon::parse($dateString);
            $year = $date->year;
            $month = $date->month;
            $check =  SalaryPayment::whereYear('created_at', $year)
                ->whereMonth('created_at', $month)->where('employee_id', $request->employee_id)
                ->count();
            if ($check > 0) {
                return response()->json(' در یک ماه نمیتوانید دو بار پرداخت کنید', 500);
            }
            $this->storeValidation($request);
            $salary = new SalaryPayment();
            $user_id = Auth::user()->id;
            $attributes = $request->only($salary->getFillable());
            $attributes['created_by'] = $user_id;
            $attributes['salary'] = $request->employee['salary'];
            $salary =  $salary->create($attributes);
            $employee = Employee::find($request->employee_id);
            $name = $employee->first_name . ' ' . $employee->last_name;
            IncomingOutgoing::create(['table' => "salary", 'table_id' => $salary->id, 'type' => 'outgoing', 'name' => 'پرداخت معاش ' . $name, 'amount' => $request->paid, 'created_by' => $user_id, 'created_at' => $request->created_at,]);
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
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request)
    {
        $this->updateValidation($request);
        try {
            DB::beginTransaction();
            $salary = SalaryPayment::find($request->id);
            $salary->paid = $request->paid;
            $salary->created_at = $request->created_at;
            $salary->save();

            $income = IncomingOutgoing::withTrashed()->where(['table' => 'salary', 'table_id' => $request->id])->first();
            if ($income) {
                $income->amount = $request->paid;
                $income->save();
            }
            DB::commit();
            return response()->json($salary, 202);
        } catch (\Throwable $th) {
            DB::rollBack();
            return response()->json($th->getMessage(), 500);
        }
    }

    /**
     * Remove the specified resource from storage.
     */
    public function restore(string $id)
    {
        try {
            $ids = explode(",", $id);
            $model = new  SalaryPayment();
            IncomingOutgoing::withTrashed()->where(['table' => 'salary'])->whereIn('table_id', $ids)->restore();
            $model->whereIn('id', $ids)->withTrashed()->restore();
            return response()->json(true, 203);
        } catch (\Throwable $th) {
            return response()->json($th->getMessage(), 500);
        }
    }

    public function forceDelete(string $id)
    {
        try {
            $ids = explode(",", $id);
            $model = new  SalaryPayment();
            IncomingOutgoing::withTrashed()->where(['table' => 'salary'])->whereIn('table_id', $ids)->forceDelete();
            $model->whereIn('id', $ids)->withTrashed()->forceDelete();
            return response()->json(true, 203);
        } catch (\Throwable $th) {
            return response()->json($th->getMessage(), 500);
        }
    }


    public function destroy(string $id)
    {
        try {
            DB::beginTransaction();
            $ids = explode(",", $id);
            $model = new  SalaryPayment();
            $result =  $model->whereIn('id', $ids)->delete();
            $income = IncomingOutgoing::withTrashed()->where(['table' => 'salary'])->whereIn('table_id', $ids)->delete();
            DB::commit();
            return response()->json($result, 206);
        } catch (\Throwable $th) {
            DB::rollBack();
            return response()->json($th->getMessage(), 500);
        }
    }

    public function storeValidation($request)
    {
        return $request->validate([
            'employee_id' => ['required', 'exists:employees,id'],
            'created_at' => ['required', 'date'],
            'paid' => 'numeric:min:0',
        ], $this->validationTranslation());
    }

    public function updateValidation($request)
    {
        return $request->validate(
            [
                'created_at' => ['required', 'date'],
                'paid' => 'numeric:min:0|max:' . $request->salary,
            ],
            $this->validationTranslation(),
        );
    }
}
