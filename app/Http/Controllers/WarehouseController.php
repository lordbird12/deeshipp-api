<?php

namespace App\Http\Controllers;

use App\Models\Warehouse;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class WarehouseController extends Controller
{
    public function getWarehouse()
    {
        $Warehouse = Warehouse::where('status', 1)->get()->toarray();

        if (!empty($Warehouse)) {

            for ($i = 0; $i < count($Warehouse); $i++) {
                $Warehouse[$i]['No'] = $i + 1;

            }
        }

        return $this->returnSuccess('Successful', $Warehouse);
    }

    public function WarehousePage(Request $request)
    {

        $columns = $request->columns;
        $length = $request->length;
        $order = $request->order;
        $search = $request->search;
        $start = $request->start;
        $page = $start / $length + 1;

        $col = array('id', 'code', 'name', 'status', 'create_by', 'update_by', 'created_at', 'updated_at');

        $d = Warehouse::select($col)->with('user_create')
            ->orderby($col[$order[0]['column']], $order[0]['dir']);
        if ($search['value'] != '' && $search['value'] != null) {

            //search datatable
            $d->where(function ($query) use ($search, $col) {
                foreach ($col as &$c) {
                    $query->orWhere($c, 'like', '%' . $search['value'] . '%');
                }

            });
        }

        $d = $d->paginate($length, ['*'], 'page', $page);

        if ($d->isNotEmpty()) {

            //run no
            $No = (($page - 1) * $length);

            for ($i = 0; $i < count($d); $i++) {

                $No = $No + 1;
                $d[$i]->No = $No;

            }

        }

        return $this->returnSuccess('Successful', $d);
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

        if (!isset($request->code)) {
            return $this->returnErrorData('กรุณาใส่รหัสคลังสินค้า', 404);
        } else if (!isset($request->name)) {
            return $this->returnErrorData('กรุณาใส่ชื่อคลังสินค้า', 404);
        }
            else if (!isset($request->wh_telephone)) {
                return $this->returnErrorData('กรุณาใส่เบอร์โทรด้วย', 404);
             
        } 
       
        else if (!isset($loginBy)) {
            return $this->returnErrorData('[login_by] Data Not Found', 404);
        }

        $name = $request->name;
        $code = $request->code;

        $checkName = Warehouse::where(function ($query) use ($code, $name) {
            $query->orwhere('code', $code)
                ->orwhere('name', $name);
        })
            ->first();

        if ($checkName) {
            return $this->returnErrorData('There is already this name in the system', 404);
        } else {

            DB::beginTransaction();

            try {

                $Warehouse = new Warehouse();
                $Warehouse->name = $name;
                $Warehouse->code = $code;
                $Warehouse->wh_address = $request->wh_address;
                $Warehouse->wh_telephone = $request->wh_telephone;
                $Warehouse->wh_description = $request->wh_description;
                $Warehouse->status = 1;

                $Warehouse->create_by = $loginBy->user_id;

                $Warehouse->save();

                //log
                $userId = $loginBy->user_id;
                $type = 'Add Warehouse';
                $description = 'User ' . $userId . ' has ' . $type . ' ' . $name;
                $this->Log($userId, $description, $type);
                //

                DB::commit();

                return $this->returnSuccess('Successful operation', []);
            } catch (\Throwable $e) {

                DB::rollback();

                return $this->returnErrorData('Something went wrong Please try again' . $e, 404);
            }
        }
    }

    /**
     * Display the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function show($id)
    {
        $Warehouse = Warehouse::find($id);
        return $this->returnSuccess('Successful', $Warehouse);
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function edit($id)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, $id)
    {
        $loginBy = $request->login_by;

        if (!isset($loginBy)) {
            return $this->returnErrorData('[login_by] Data Not Found', 404);
        }

        $name = $request->name;
        $code = $request->code;

        $checkName = Warehouse::where('id', '!=', $id)
            ->where(function ($query) use ($code, $name) {
                $query->orwhere('code', $code)
                    ->orWhere('name', $name);
            })
            ->first();

        if ($checkName) {
            return $this->returnErrorData('There is already this name in the system', 404);
        } else {

            DB::beginTransaction();

            try {

                $Warehouse = Warehouse::find($id);

                $Warehouse->name = $name;
                $Warehouse->code = $code;
                $Warehouse->wh_address = $request->wh_address;
                $Warehouse->wh_telephone = $request->wh_telephone;
                $Warehouse->wh_description = $request->wh_description;
                $Warehouse->status = $request->status;

                $Warehouse->update_by = $loginBy->user_id;
                $Warehouse->updated_at = Carbon::now()->toDateTimeString();

                $Warehouse->save();
                //log
                $userId = $loginBy->user_id;
                $type = 'Edit Warehouse';
                $description = 'User ' . $userId . ' has ' . $type . ' ' . $Warehouse->name;
                $this->Log($userId, $description, $type);
                //

                DB::commit();

                return $this->returnUpdate('Successful operation');
            } catch (\Throwable $e) {

                DB::rollback();

                return $this->returnErrorData('Something went wrong Please try again ' . $e, 404);
            }
        }
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $id
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

            $Warehouse = Warehouse::find($id);

            //log
            $userId = $loginBy->user_id;
            $type = 'Delete Warehouse';
            $description = 'User ' . $userId . ' has ' . $type . ' ' . $Warehouse->name;
            $this->Log($userId, $description, $type);
            //

            $Warehouse->delete();

            DB::commit();

            return $this->returnUpdate('Successful operation');
        } catch (\Throwable $e) {

            DB::rollback();

            return $this->returnErrorData('Something went wrong Please try again ' . $e, 404);
        }
    }
}
