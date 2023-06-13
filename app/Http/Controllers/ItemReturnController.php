<?php

namespace App\Http\Controllers;

use App\Models\Customer;
use App\Models\ItemReturn;
use App\Models\Sale_order;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ItemReturnController extends Controller
{
    public function getList()
    {
        $Item = ItemReturn::get()->toarray();

        if (!empty($Item)) {

            for ($i = 0; $i < count($Item); $i++) {
                $Item[$i]['No'] = $i + 1;
                $Item[$i]['image'] = url($Item[$i]['image']);
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

        $col = array('id', 'customer_phone', 'description', 'image', 'create_by', 'update_by', 'created_at', 'updated_at');

        $orderby = array('', 'customer_phone', 'description', 'image', 'create_by');

        $D = ItemReturn::select($col);

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
                $d[$i]->image = url($d[$i]->image);
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

        if (!isset($request->order_id) && !isset($request->customer_phone)) {
            return $this->returnErrorData('กรุณาใส่ order_id หรือ customer phone', 404);
        } else if (!isset($request->description)) {
            return $this->returnErrorData('กรุณาใส่ description ด้วย', 404);
        } else if (!isset($loginBy)) {
            return $this->returnErrorData('[login_by] Data Not Found', 404);
        }

        if ($request->order_id != "") {
            $check = Sale_order::where('order_id', $request->order_id)->first();

            if (!$check) {
                return $this->returnErrorData('ไม่พบข้อมูลรายการขายในระบบ', 404);
            }
        }

        if ($request->customer_phone != "") {
            $check = Customer::where('phone', $request->customer_phone)->first();

            if (!$check) {
                return $this->returnErrorData('ไม่พบข้อมูลเบอร์ลูกค้าในระบบ', 404);
            }
        }

        DB::beginTransaction();

        try {

            $Item = new ItemReturn();
            $Item->order_id = $request->order_id;
            $Item->customer_phone = $request->customer_phone;
            $Item->description = $request->description;

            if ($request->image && $request->image != null && $request->image != 'null') {
                $Item->image = $this->uploadImage($request->image, '/images/item_return/');
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
     * @param  \App\Models\ItemReturn  $itemReturn
     * @return \Illuminate\Http\Response
     */
    public function show($id)
    {
        $Item = ItemReturn::find($id);

        if ($Item) {
            $Item->image = url($Item->image);
        }

        return $this->returnSuccess('Successful', $Item);
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  \App\Models\ItemReturn  $itemReturn
     * @return \Illuminate\Http\Response
     */
    public function edit(ItemReturn $itemReturn)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \App\Models\ItemReturn  $itemReturn
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, ItemReturn $itemReturn)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  \App\Models\ItemReturn  $itemReturn
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

            $Item = ItemReturn::find($id);

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
