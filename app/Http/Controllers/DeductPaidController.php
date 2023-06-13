<?php

namespace App\Http\Controllers;

use App\Models\DeductPaid;
use App\Models\DeductType;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class DeductPaidController extends Controller
{
    public function getList($id)
    {
        $Item = DeductPaid::where('user_id',$id)->get();

        if (!empty($Item)) {

            for ($i = 0; $i < count($Item); $i++) {
                $Item[$i]['No'] = $i + 1;
                $Item[$i]['deduct_type'] = DeductType::find($Item[$i]['deduct_type_id']);
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

        $col = array('id', 'user_id', 'deduct_type_id', 'price', 'type', 'description', 'create_by', 'update_by', 'created_at', 'updated_at');

        $orderby = array('', 'user_id', 'deduct_type_id', 'price', 'type', 'description', 'create_by');


        $D = DeductPaid::select($col);

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
                // $query = $this->withPermission($query, $search);
            });
        }

        $d = $D->paginate($length, ['*'], 'page', $page);

        if ($d->isNotEmpty()) {

            //run no
            $No = (($page - 1) * $length);

            for ($i = 0; $i < count($d); $i++) {

                $No = $No + 1;
                $d[$i]->No = $No;
                $d[$i]->income_type = DeductType::where('id', $d[$i]->deduct_type_id)->get();
                $d[$i]->user = User::where('id', $d[$i]->user_id)->get();
                $d[$i]->create = User::where('user_id', $d[$i]->create_by)->first();
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
            return $this->returnErrorData('กรุณาระบุ user_id ให้เรียบร้อย', 404);
        } else if (!isset($request->deduct_type_id)) {
            return $this->returnErrorData('กรุณาระบุ deduct_type_id ให้เรียบร้อย', 404);
        } else if (!isset($request->price)) {
            return $this->returnErrorData('กรุณาระบุ price ให้เรียบร้อย', 404);
        } else if (!isset($request->description)) {
            return $this->returnErrorData('กรุณาระบุ description ให้เรียบร้อย', 404);
        } else if (!isset($request->type)) {
            return $this->returnErrorData('กรุณาระบุ type ให้เรียบร้อย', 404);
        } else


        DB::beginTransaction();

        try {
            $Item = new DeductPaid();
            $Item->user_id = $request->user_id;
            $Item->deduct_type_id = $request->deduct_type_id;
            $Item->price = $request->price;
            $Item->description = $request->description;
            $Item->type = $request->type;

            $Item->create_by = $loginBy->user_id;

            $Item->save();
            //

            //log
            $userId = "admin";
            $type = 'เพิ่มรายการ';
            $description = 'ผู้ใช้งาน ' . $userId . ' ได้ทำการ ' . $type . ' ' . $request->name;
            $this->Log($userId, $description, $type);
            //

            DB::commit();

            return $this->returnSuccess('ดำเนินการสำเร็จ', $Item);
        } catch (\Throwable $e) {

            DB::rollback();

            return $this->returnErrorData('เกิดข้อผิดพลาด กรุณาลองใหม่อีกครั้ง ' . $e, 404);
        }
    }

    /**
     * Display the specified resource.
     *
     * @param  \App\Models\DeductPaid  $DeductPaid
     * @return \Illuminate\Http\Response
     */
    public function show($id)
    {
        $Item = DeductPaid::where('id', $id)
            ->first();


        return $this->returnSuccess('เรียกดูข้อมูลสำเร็จ', $Item);
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  \App\Models\DeductPaid  $DeductPaid
     * @return \Illuminate\Http\Response
     */
    public function edit(DeductPaid $DeductPaid)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \App\Models\DeductPaid  $DeductPaid
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, $id)
    {
        $loginBy = $request->login_by;

        if (!isset($id)) {
            return $this->returnErrorData('ไม่พบข้อมูล id', 404);
        }
        else
        //

        {
            DB::beginTransaction();
        }

        try {

            $Item = DeductPaid::find($id);
            if (!$Item) {
                return $this->returnErrorData('ไม่พบข้อมูลในระบบ', 404);
            }

            $Item->user_id = $request->user_id;
            $Item->deduct_type_id = $request->deduct_type_id;
            $Item->price = $request->price;
            $Item->description = $request->description;
            $Item->type = $request->type;

            $Item->create_by = $loginBy->user_id;

            $Item->save();

            //log
            $userId = "admin";
            $type = 'แก้ไขผู้ใช้งาน';
            $description = 'ผู้ใช้งาน ' . $userId . ' ได้ทำการ ' . $type . ' ' . $Item->username;
            $this->Log($userId, $description, $type);
            //

            DB::commit();

            return $this->returnUpdate('ดำเนินการสำเร็จ');
        } catch (\Throwable $e) {

            DB::rollback();

            return $this->returnErrorData('เกิดข้อผิดพลาด กรุณาลองใหม่อีกครั้ง ', 404);
        }
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  \App\Models\DeductPaid  $DeductPaid
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

            $Item = DeductPaid::find($id);

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
