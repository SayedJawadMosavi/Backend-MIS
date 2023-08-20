<?php

namespace App\Http\Controllers;

use App\Models\Employee;
use App\Models\IncomingOutgoing;
use App\Models\SalaryPayment;
use DateTime;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use LDAP\Result;

class EmployeeController extends Controller
{
    /**
     * Display a listing of the resource.
     */

    public function __construct()
    {
        $this->middleware('permissions:employee_view')->only('index');
        $this->middleware('permissions:employee_create')->only(['store', 'update']);
        $this->middleware('permissions:employee_delete')->only(['destroy']);
        $this->middleware('permissions:employee_restore')->only(['restore']);
        $this->middleware('permissions:employee_force_delete')->only(['forceDelete']);
    }
    public $path = "images/employees";



    public function index(Request $request)
    {
        try {
            $query = new Employee();
            $searchCol = ['first_name', 'last_name', 'email', 'phone_number', 'current_address', 'permenent_address', 'created_at', 'employee_id_number', 'employment_start_date', 'employment_end_date', "job_title"];
            $query = $this->search($query, $request, $searchCol);
            $query = $query->with('payments');
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
            return response()->json(["data" => $results, 'total' => $total, "extraTotal" => ['employees' => $allTotal, 'trash' => $trashTotal]]);
        } catch (\Throwable $th) {
            return response()->json($th->getMessage(), 500);
        }
    }


    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $this->storeValidation($request);
        try {
            DB::beginTransaction();
            $employee = new Employee();
            $attributes = $request->only($employee->getFillable());
            $attributes['created_at'] = $request->date;
            $employmentStartDate  = $attributes['employment_start_date'];
            $employmentEndDate  = $attributes['employment_end_date'];
            $date1 = new DateTime($employmentStartDate);
            $date2 = new DateTime($employmentEndDate);
            $attributes['employment_start_date'] = $date1->format("Y-m-d");
            $attributes['employment_end_date'] = $date2->format("Y-m-d");
            if ($request->hasFile('profile')) {
                $attributes['profile']  = $this->storeFile($request->file('profile'), $this->path);
            }
            $employee =  $employee->create($attributes);

            DB::commit();
            return response()->json($employee, 200);
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
            $employee = new Employee();
            $employee = $employee->withTrashed()->with('payments', fn ($q) => $q->withTrashed())->find($id);
            return response()->json($employee);
        } catch (\Throwable $th) {
            //throw $th;
            return response()->json($th->getMessage(), 500);
        }
    }


    public function getEmployees(Request $request)
    {
        try {
            $employee = Employee::select(['id', 'salary', 'first_name', 'last_name'])->latest()->get();
            return response()->json($employee);
        } catch (\Throwable $th) {
            return response()->json($th->getMessage(), 500);
        }
    }
    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
        $this->storeValidation($request);
        try {
            DB::beginTransaction();
            $employee = Employee::find($id);
            $attributes = $request->only($employee->getFillable());
            $employmentStartDate  = $attributes['employment_start_date'];
            $employmentEndDate  = $attributes['employment_end_date'];
            $date1 = new DateTime($employmentStartDate);
            $date2 = new DateTime($employmentEndDate);
            $attributes['employment_start_date'] = $date1->format("Y-m-d");
            $attributes['employment_end_date']   = $date2->format("Y-m-d");
            $employee->update($attributes);
            DB::commit();
            return response()->json($employee, 202);
        } catch (\Throwable $th) {
            DB::rollBack();
            return response()->json($th->getMessage(), 500);
        }
    }

    public function restore(string $id)
    {
        try {
            $ids = explode(",", $id);
            Employee::whereIn('id', $ids)->withTrashed()->restore();
            $salary_ids =  SalaryPayment::withTrashed()->whereIn('employee_id', $ids)->get()->pluck('id');
            SalaryPayment::withTrashed()->whereIn("employee_id", $ids)->restore();
            IncomingOutgoing::withTrashed()->where(['table' => 'salary'])->whereIn('table_id', $salary_ids)->restore();
            return response()->json(true, 203);
        } catch (\Throwable $th) {
            return response()->json($th->getMessage(), 500);
        }
    }

    public function forceDelete(string $id)
    {
        try {
            DB::beginTransaction();
            $ids = explode(",", $id);
            $salary_ids =  SalaryPayment::withTrashed()->whereIn('employee_id', $ids)->get()->pluck('id');
            Employee::whereIn('id', $ids)->withTrashed()->forceDelete();
            IncomingOutgoing::withTrashed()->where(['table' => 'salary'])->whereIn('table_id', $salary_ids)->forceDelete();
            DB::commit();
            return response()->json(true, 203);
        } catch (\Throwable $th) {
            DB::rollBack();
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
            $ids  = explode(",", $id);
            $result = Employee::whereIn("id", $ids)->delete();
            $salary_ids =  SalaryPayment::whereIn('employee_id', $ids)->get()->pluck('id');
            SalaryPayment::whereIn("employee_id", $ids)->delete();
            IncomingOutgoing::withTrashed()->where(['table' => 'salary'])->whereIn('table_id', $salary_ids)->delete();
            DB::commit();
            return response()->json($result, 206);
        } catch (\Exception $th) {
            //throw $th;
            DB::rollBack();
            return response()->json($th->getMessage(), 500);
        }
    }

    public function storeValidation($request)
    {
        return $request->validate(
            [
                'first_name' => 'required',
                'last_name' => 'required',
                'phone_number' => 'required',
                'current_address' => 'required',
                'permenent_address' => 'required',
                'employment_start_date' => 'required:date',
                'job_title' => 'required',
            ],
            [
                'first_name.required' => "نام ضروری می باشد",
                'last_name.required' => "تخلص ضروری می باشد",
                'email.required' => "ایمیل ضروری می باشد",
                'phone_number.required' => "شماره تیلفون ضروری می باشد",
                'phone_number.unique' => "شماره تیلفون ذیل موجود می باشد",
                'current_address.required' => "آدرس فعلی ضروری می باشد",
                'permenent_address.required' => "آدرس دایمی ضروری می باشد",
                'employee_start_date.required' => "شروع کارمند ضروری می باشد",
                'job_title.required' => "عنوان وظیفه ضروری می باشد",
            ]

        );
    }
}
