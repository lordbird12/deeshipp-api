<?php

namespace App\Http\Controllers;

use App\Models\User_bank;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class UserBankController extends Controller
{
    public function getUserBank(Request $request)
    {

        $userId = $request->user_id;

        $User_bank = User_bank::with('user')
            ->with('bank');

        if ($userId) {
            $User_bank->where('user_id', $userId);
        }
        $User_bank = $User_bank->get()
            ->toarray();

        if (!empty($User_bank)) {

            for ($i = 0; $i < count($User_bank); $i++) {
                $User_bank[$i]['No'] = $i + 1;
            }
        }

        return $this->returnSuccess('เรียกดูข้อมูลสำเร็จ', $User_bank);
    }

    public function UserBankPage(Request $request)
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

        $userId = $request->user_id;
        $status = $request->status;

        $col = array('id', 'user_id', 'bank_id', 'first_name', 'last_name', 'account_number', 'created_at', 'updated_at');

        $orderby = array('', 'user_id', 'bank_id', 'first_name', 'last_name', 'account_number', 'created_at', 'updated_at');

        $d = User_bank::select($col)
            ->with('user')
            ->with('bank');

        //if

        if ($userId) {
            $d->where('user_id', $userId);
        }

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

        if (!isset($request->user_id)) {
            return $this->returnErrorData('กรุณาระบุ user_id ให้เรียบร้อย', 404);
        } else  if (!isset($request->bank_id)) {
            return $this->returnErrorData('กรุณาระบุเลือกธนาคารให้เรียบร้อย', 404);
        } else if (!isset($request->first_name)) {
            return $this->returnErrorData('กรุณาระบุชื่อให้เรียบร้อย', 404);
        } else if (!isset($request->last_name)) {
            return $this->returnErrorData('กรุณาระบุนามสกุลเรียบร้อย', 404);
        } else if (!isset($request->account_number)) {
            return $this->returnErrorData('กรุณาระบุเลขบัญชีธนาคารเรียบร้อย', 404);
        } else if (!isset($loginBy)) {
            return $this->returnErrorData('ไม่พบข้อมูลผู้ใช้งาน กรุณาเข้าสู่ระบบใหม่อีกครั้ง', 404);
        }

        DB::beginTransaction();

        try {

            $User_bank = new User_bank();
            $User_bank->user_id = $request->user_id;
            $User_bank->bank_id = $request->bank_id;
            $User_bank->first_name = $request->first_name;
            $User_bank->last_name = $request->last_name;
            $User_bank->account_number = $request->account_number;

            $User_bank->updated_at = Carbon::now()->toDateTimeString();

            $User_bank->save();

            DB::commit();

            return $this->returnSuccess('ดำเนินการสำเร็จ', $User_bank);
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
        $User_bank = User_bank::with('user')
            ->with('bank')
            ->find($id);

        if ($User_bank) {
            //
        }
        return $this->returnSuccess('เรียกดูข้อมูลสำเร็จ', $User_bank);
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

            $User_bank = User_bank::find($id);
            $User_bank->bank_id = $request->bank_id;
            $User_bank->first_name = $request->first_name;
            $User_bank->last_name = $request->last_name;
            $User_bank->account_number = $request->account_number;

            $User_bank->updated_at = Carbon::now()->toDateTimeString();

            $User_bank->save();

            DB::commit();

            return $this->returnSuccess('ดำเนินการสำเร็จ', $User_bank);
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

            $User_bank = User_bank::find($id);
            $User_bank->delete();

            DB::commit();

            return $this->returnUpdate('ดำเนินการสำเร็จ');
        } catch (\Throwable $e) {

            DB::rollback();

            return $this->returnErrorData('เกิดข้อผิดพลาด กรุณาลองใหม่อีกครั้ง ', 404);
        }
    }
}
