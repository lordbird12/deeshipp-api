<?php

namespace App\Http\Controllers;

use App\Models\Item;
use App\Models\ProductLive;
use Illuminate\Http\Request;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class ProductLiveController extends Controller
{

    public function getList()
    {
        $Item = ProductLive::get()->toarray();

        if (!empty($Item)) {

            for ($i = 0; $i < count($Item); $i++) {
                $Item[$i]['No'] = $i + 1;
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

        //check user
        $loginBy = $request->login_by;

        if ($loginBy->permission->id == 1) {
            $userId = null;
        } else if ($loginBy->permission->id == 4) {
            $userId = $loginBy->user_ref_id; //ผู้ดูแลร้านค้า
        } else {
            $userId = $loginBy->id;
        }
        //

        $Status = $request->status;

        $col = array('id', 'item_id', 'code', 'qty', 'create_by', 'update_by', 'created_at', 'updated_at');

        $orderby = array('', 'item_id', 'code', 'qty', 'create_by', 'status');

        $D = ProductLive::select($col);

        if ($userId) {
            $D->where('user_id', $userId);
        }

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
                $d[$i]->item = Item::where('id', $d[$i]->item_id)->first();
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

        if (!isset($request->code)) {
            return $this->returnErrorData('[code] Data Not Found', 404);
        } else if (!isset($request->item_id)) {
            return $this->returnErrorData('[item_id] Data Not Found', 404);
        } else {

            $checkItem = Item::find($request->item_id);
            if (!$checkItem) {
                return $this->returnErrorData('ไม่มีรหัสสินค้า ' . $request->item_id . ' ในระบบ', 404);
            }

            $checkItem = ProductLive::where("code", $request->code)->first();
            if ($checkItem) {
                return $this->returnErrorData('มีรหัสสินค้า ' . $request->code . ' ในระบบอยู่แล้ว', 404);
            }

            DB::beginTransaction();

            try {

                $Product = new ProductLive();
                $Product->item_id = $request->item_id;
                $Product->code = $request->code;
                $Product->qty = $request->qty;

                $Product->save();
                $Product->item = Item::find($Product->item_id);
                //log
                $type = 'Add Product';
                $description = 'User  has ' . $type;
                $this->Log("admin", $description, $type);


                DB::commit();

                return $this->returnSuccess('Successful operation', $Product);
            } catch (\Throwable $e) {

                DB::rollback();

                return $this->returnErrorData('Something went wrong Please try again ' . $e, 404);
            }
        }
    }

    /**
     * Display the specified resource.
     *
     * @param  \App\Models\ProductLive  $productLive
     * @return \Illuminate\Http\Response
     */
    public function show($id)
    {
        $Item = ProductLive::find($id);
        $Item->product = Item::find($Item->item_id);
        return $this->returnSuccess('Successful', $Item);
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  \App\Models\ProductLive  $productLive
     * @return \Illuminate\Http\Response
     */
    public function edit(ProductLive $productLive)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \App\Models\ProductLive  $productLive
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, $id)
    {


        $item_id = $request->item_id;
        $code = $request->code;
        $qty = $request->qty;

        $check = ProductLive::find($id);

        if (!$check) {
            return $this->returnErrorData('ไม่พบรายการนี้ในระบบ', 404);
        } else {

            DB::beginTransaction();

            try {

                $Item = ProductLive::find($id);

                $Item->item_id = $item_id;
                $Item->code = $code;
                $Item->qty = $qty;

                $Item->update_by = "admin";
                $Item->updated_at = Carbon::now()->toDateTimeString();

                $Item->save();
                //log
                $userId = "admin";
                $type = 'Edit Branch';
                $description = 'User ' . $userId . ' has ' . $type . ' ' . $Item->code;
                $this->Log($userId, $description, $type);
                //

                DB::commit();

                return $this->returnSuccess('Successful operation', $Item);
            } catch (\Throwable $e) {

                DB::rollback();

                return $this->returnErrorData('Something went wrong Please try again ' . $e, 404);
            }
        }
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  \App\Models\ProductLive  $productLive
     * @return \Illuminate\Http\Response
     */
    public function destroy(Request $request, $id)
    {
        $check = ProductLive::find($id);

        if (!$check) {
            return $this->returnErrorData('ไม่พบรายการนี้ในระบบ', 404);
        } else {

            DB::beginTransaction();

            try {

                $Item = ProductLive::find($id);

                //log
                $userId = "admin";
                $type = 'Delete code';
                $description = 'User ' . $userId . ' has ' . $type;
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
}
