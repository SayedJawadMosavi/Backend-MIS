<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\IncomingOutgoing;
use Exception;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use DateTime;

class IncomingOutgoingController extends Controller
{
    /**
     * Display a listing of the resource.
     */

    public function __construct()
    {
        $this->middleware('permissions:income_view')->only('index');
        $this->middleware('permissions:income_create')->only(['store', "update"]);
        $this->middleware('permissions:income_delete')->only(['destroy']);
        $this->middleware('permissions:income_restore')->only(['restore']);
        $this->middleware('permissions:income_force_delete')->only(['forceDelete']);
    }


    public function index(Request $request)
    {
        try {

            $query = new IncomingOutgoing();

            $totalIncomes = clone $query;
            $totalIncomes = $totalIncomes->whereType('incoming')->sum('amount');

            $totalOutGoing = clone $query;
            $totalOutGoing = $totalOutGoing->whereType('outgoing')->sum('amount');

            $searchCol = ['name', 'type', 'amount', 'created_by'];
            $query = $this->search($query, $request, $searchCol);

            $trashTotal = clone $query;
            $trashTotal = $trashTotal->onlyTrashed()->count();

            $allIncoming = clone $query;
            $allIncoming = $allIncoming->where('type', 'incoming')->count();

            $allOutgoing = clone $query;
            $allOutgoing = $allOutgoing->where('type', 'outgoing')->count();

            if ($request->tab == 'trash') {
                $query = $query->onlyTrashed();
            } else if ($request->tab == 'incoming') {
                $query = $query->where('type', 'incoming');
            } else {
                $query = $query->where('type', 'outgoing');
            }

            $query = $query->with(['user:id,name'])->latest()->paginate($request->itemPerPage);
            $results = collect($query->items());
            $total = $query->total();

            $result = [
                "data" => $results,
                "total" => $total,
                "extraTotal" => ['outgoing' => $allOutgoing, 'incoming' => $allIncoming, 'trash' => $trashTotal,],
                'extra' => ['total_income' => $totalIncomes, 'total_outgoing' => $totalOutGoing]
            ];
            return response()->json($result);
        } catch (Exception $th) {
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

            $user_id = Auth::user()->id;

            $incoming = new  IncomingOutgoing();

            $attributes = $request->only($incoming->getFillable());

            $attributes['created_by'] = $user_id;
            $incoming =  $incoming->create($attributes);
            DB::commit();

            return response()->json($incoming, 200,);
        } catch (Exception $e) {
            DB::rollBack();
            error_log($e);
            return response()->json($e, 500);
        }
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
        $this->storeValidation($request);
        try {
            $incomingOutgoing = IncomingOutgoing::find($request->id);
            $attributes = $request->only($incomingOutgoing->getFillable());
            $incomingOutgoing->update($attributes);
            DB::commit();
            return response()->json($incomingOutgoing);
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
            $ids  = explode(",", $id);
            IncomingOutgoing::whereIn("id", $ids)->delete();
            return response()->json(true);
        } catch (\Exception $th) {
            //throw $th;
            return response()->json($th->getMessage(), 500);
        }
    }
    public function storeValidation($request)
    {

        return $request->validate(
            [
                'name' => 'required|min:3',
                'type' => 'required',
                'amount' => 'required|min_digits:1',
            ],
            [
                'name.required' => 'نام ضروری میباشد',
                'name.min' => 'نام کمتر از سه شده نیتواند',
                'type.required' => 'نوعیت ضروری میباشد',
                'amount.required' => "مقدار ضروری می باشد",
                'amount.min_digits' => "مقدار از کمتر از یک شده نمی تواند",

            ]

        );
    }
    public function restore(string $id)
    {
        try {
            $ids = explode(",", $id);
            IncomingOutgoing::whereIn('id', $ids)->withTrashed()->restore();
            return response()->json(true, 203);
        } catch (\Throwable $th) {
            return response()->json($th->getMessage(), 500);
        }
    }

    public function forceDelete(string $id)
    {
        try {
            $ids = explode(",", $id);
            IncomingOutgoing::whereIn('id', $ids)->withTrashed()->forceDelete();
            return response()->json(true, 206);
        } catch (\Throwable $th) {
            return response()->json($th->getMessage(), 500);
        }
    }
}
