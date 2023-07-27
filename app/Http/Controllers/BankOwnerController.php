<?php

namespace App\Http\Controllers;

use App\Models\Bank_owner;
use App\Models\BankOwner;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class BankOwnerController extends Controller
{
    public function getBankOwner(Request $request)
    {
        $BankOwner = Bank_owner::with('bank');

        $BankOwner = $BankOwner->get()
            ->toarray();

        if (!empty($BankOwner)) {

            for ($i = 0; $i < count($BankOwner); $i++) {
                $BankOwner[$i]['No'] = $i + 1;
            }
        }

        return $this->returnSuccess('เรียกดูข้อมูลสำเร็จ', $BankOwner);
    }

    public function BankOwnerPage(Request $request)
    {
        $columns = $request->columns;
        $length = $request->length;
        $order = $request->order;
        $search = $request->search;
        $start = $request->start;
        $page = $start / $length + 1;

        $loginBy = $request->login_by;

        if (!isset($loginBy)) {
            return $this->returnErrorData('ไม่พบข้อมูลผู้ใช้งาน กรุณาเข้าสู่ระบบใหม่อีกครั้ง', 404);
        }

        $status = $request->status;

        $col = array('id', 'bank_id', 'account_number', 'account_name', 'status', 'created_at', 'updated_at');

        $orderby = array('', 'bank_id', 'account_number', 'account_name', 'status', 'created_at', 'updated_at');

        $d = Bank_owner::select($col)
            ->with('bank');

        //if

        if (isset($status)) {
            $d->where('status', $status);
        }

        if ($orderby[$order[0]['column']]) {
            $d->orderby($orderby[$order[0]['column']], $order[0]['dir']);
        }
        if ($search['value'] != '' && $search['value'] != null) {

            $d->Where(function ($query) use ($search, $col) {

                //search datatable
                $query->where(function ($query) use ($search, $col) {
                    foreach ($col as &$c) {
                        $query->orWhere($c, 'like', '%' . $search['value'] . '%');
                    }
                });

                //search with
                $query = $this->withAsset($query, $search);
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

        return $this->returnSuccess('เรียกดูข้อมูลสำเร็จ', $d);
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

        if (!isset($request->bank_id)) {
            return $this->returnErrorData('กรุณาระบุเลือกธนาคารให้เรียบร้อย', 404);
        } else if (!isset($request->account_name)) {
            return $this->returnErrorData('กรุณาระบุชื่อให้เรียบร้อย', 404);
        }  else if (!isset($request->account_number)) {
            return $this->returnErrorData('กรุณาระบุเลขบัญชีธนาคารเรียบร้อย', 404);
        } else if (!isset($loginBy)) {
            return $this->returnErrorData('ไม่พบข้อมูลผู้ใช้งาน กรุณาเข้าสู่ระบบใหม่อีกครั้ง', 404);
        }

        DB::beginTransaction();

        try {

            $BankOwner = new Bank_owner();
            $BankOwner->bank_id = $request->bank_id;
            $BankOwner->account_name = $request->account_name;
            $BankOwner->account_number = $request->account_number;

            $BankOwner->updated_at = Carbon::now()->toDateTimeString();

            $BankOwner->save();

            DB::commit();

            return $this->returnSuccess('ดำเนินการสำเร็จ', $BankOwner);
        } catch (\Throwable $e) {

            DB::rollback();

            return $this->returnErrorData('เกิดข้อผิดพลาด กรุณาลองใหม่อีกครั้ง ', 404);
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
        $BankOwner = Bank_owner::with('bank')
            ->find($id);

        if ($BankOwner) {
            //
        }
        return $this->returnSuccess('เรียกดูข้อมูลสำเร็จ', $BankOwner);
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

        if (!isset($id)) {
            return $this->returnErrorData('ไม่พบข้อมูล id', 404);
        } else if (!isset($loginBy)) {
            return $this->returnErrorData('ไม่พบข้อมูลผู้ใช้งาน กรุณาเข้าสู่ระบบใหม่อีกครั้ง', 404);
        }

        DB::beginTransaction();

        try {

            $BankOwner = Bank_owner::find($id);
            $BankOwner->bank_id = $request->bank_id;
            $BankOwner->account_name = $request->account_name;
            $BankOwner->account_number = $request->account_number;

            $BankOwner->updated_at = Carbon::now()->toDateTimeString();

            $BankOwner->save();

            DB::commit();

            return $this->returnSuccess('ดำเนินการสำเร็จ', $BankOwner);
        } catch (\Throwable $e) {

            DB::rollback();

            return $this->returnErrorData('เกิดข้อผิดพลาด กรุณาลองใหม่อีกครั้ง ', 404);
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
            return $this->returnErrorData('ไม่พบข้อมูลผู้ใช้งาน กรุณาเข้าสู่ระบบใหม่อีกครั้ง', 404);
        }

        DB::beginTransaction();

        try {

            $BankOwner = Bank_owner::find($id);
            $BankOwner->delete();

            DB::commit();

            return $this->returnUpdate('ดำเนินการสำเร็จ');
        } catch (\Throwable $e) {

            DB::rollback();

            return $this->returnErrorData('เกิดข้อผิดพลาด กรุณาลองใหม่อีกครั้ง ', 404);
        }
    }
}
