<?php

namespace App\Http\Controllers;

use App\Models\Customer;
use App\Models\User;
use App\Models\WorkTelesale;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class WorkTelesaleController extends Controller
{

    public function getList()
    {
        $Item = WorkTelesale::get()->toarray();

        if (!empty($Item)) {

            for ($i = 0; $i < count($Item); $i++) {
                $Item[$i]['No'] = $i + 1;
                $Item[$i]['path'] = url($Item[$i]['path']);
            }
        }

        return $this->returnSuccess('เรียกดูข้อมูลสำเร็จ', $Item);
    }

    public function getPage(Request $request)
    {
        $columns = $request->columns;
        $length = $request->length;
        $order = $request->order;
        $search = $request->search;
        $start = $request->start;
        $page = $start / $length + 1;

        $Status = $request->status;

        $col = array('id', 'user_id', 'customer_id', 'remark', 'path', 'create_by', 'update_by', 'created_at', 'updated_at');

        $orderby = array('', 'user_id', 'customer_id', 'remark', 'path', 'create_by', 'status');

        $D = WorkTelesale::select($col);

        if (isset($Status)) {
            $D->where('status', $Status);
        }

        if ($orderby[$order[0]['column']]) {
            $D->orderby($orderby[$order[0]['column']], $order[0]['dir']);
        }

        if ($search['value'] != '' && $search['value'] != null) {

            $D->Where(function ($query) use ($search, $col) {

                //search datatable
                $query->orWhere(function ($query) use ($search, $col) {
                    foreach ($col as &$c) {
                        $query->orWhere($c, 'like', '%' . $search['value'] . '%');
                    }
                });

                //search with
                $query = $this->withPermission($query, $search);
            });
        }

        $d = $D->paginate($length, ['*'], 'page', $page);

        if ($d->isNotEmpty()) {

            //run no
            $No = (($page - 1) * $length);

            for ($i = 0; $i < count($d); $i++) {

                $No = $No + 1;
                $d[$i]->No = $No;
                $d[$i]->Sale = User::where('id', $d[$i]->user_id)->get();
                $d[$i]->Customer = Customer::where('id', $d[$i]->customer_id)->get();
            }
        }

        return $this->returnSuccess('เรียกดูข้อมูลสำเร็จ', $d);
    }

    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        //
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {
        //
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        $loginBy = $request->login_by;

        if (!isset($request->user_id)) {
            return $this->returnErrorData('กรุณาใส่ user_id', 404);
        } else if (!isset($request->customer_id)) {
            return $this->returnErrorData('กรุณาใส่ customer_id', 404);
        } else if (!isset($request->remark)) {
            return $this->returnErrorData('กรุณาใส่ remark ด้วย', 404);
        } else if (!isset($loginBy)) {
            return $this->returnErrorData('[login_by] Data Not Found', 404);
        }

        $checkUser = User::where('id', $request->user_id)->first();

        if (!$checkUser) {
            return $this->returnErrorData('ไม่พบข้อมูลพนักงานในระบบ', 404);
        }

        $checkCustomer = Customer::where('id', $request->customer_id)->first();

        if (!$checkCustomer) {
            return $this->returnErrorData('ไม่พบข้อมูลลูกค้าในระบบ', 404);
        }

        DB::beginTransaction();

        try {

            $Item = new WorkTelesale();
            $Item->user_id = $request->user_id;
            $Item->customer_id = $request->customer_id;
            $Item->remark = $request->remark;

            if ($request->file && $request->file != null && $request->file != 'null') {
                $Item->path = $this->uploadFile2($request);
            }

            $Item->create_by = $loginBy->user_id;

            $Item->save();

            //log
            $userId = $loginBy->user_id;
            $type = 'Add Item';
            $description = 'User ' . $userId . ' has ' . $type;
            $this->Log($userId, $description, $type);
            //

            DB::commit();

            return $this->returnSuccess('Successful operation', []);
        } catch (\Throwable $e) {

            DB::rollback();

            return $this->returnErrorData('Something went wrong Please try again' . $e, 404);
        }
    }

    /**
     * Display the specified resource.
     *
     * @param  \App\Models\WorkTelesale  $workTelesale
     * @return \Illuminate\Http\Response
     */
    public function show($id)
    {
        $Item = WorkTelesale::find($id);

        if ($Item) {
            $Item->path = url($Item->path);
        }

        return $this->returnSuccess('Successful', $Item);
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  \App\Models\WorkTelesale  $workTelesale
     * @return \Illuminate\Http\Response
     */
    public function edit(WorkTelesale $workTelesale)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \App\Models\WorkTelesale  $workTelesale
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, WorkTelesale $workTelesale)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  \App\Models\WorkTelesale  $workTelesale
     * @return \Illuminate\Http\Response
     */
    public function destroy(Request $request, $id)
    {
        $loginBy = $request->login_by;

        if (!isset($loginBy)) {
            return $this->returnErrorData('[login_by] Data Not Found', 404);
        }

        DB::beginTransaction();

        try {

            $Item = WorkTelesale::find($id);

            //log
            $userId = $loginBy->user_id;
            $type = 'Delete Item';
            $description = 'User ' . $userId . ' has ' . $type . ' ' . $Item->name;
            $this->Log($userId, $description, $type);
            //

            $Item->delete();

            DB::commit();

            return $this->returnUpdate('Successful operation');
        } catch (\Throwable $e) {

            DB::rollback();

            return $this->returnErrorData('Something went wrong Please try again ' . $e, 404);
        }
    }
}
