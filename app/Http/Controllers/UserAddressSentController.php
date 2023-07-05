<?php

namespace App\Http\Controllers;

use App\Models\User_address_sent;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class UserAddressSentController extends Controller
{
    public function getUserAddressSent(Request $request)
    {

        $userId = $request->user_id;

        $User_address_sent = User_address_sent::with('user')
            ->with('user_page');

        if ($userId) {
            $User_address_sent->where('user_id', $userId);
        }
        $User_address_sent = $User_address_sent->get()
            ->toarray();

        if (!empty($User_address_sent)) {

            for ($i = 0; $i < count($User_address_sent); $i++) {
                $User_address_sent[$i]['No'] = $i + 1;
            }
        }

        return $this->returnSuccess('เรียกดูข้อมูลสำเร็จ', $User_address_sent);
    }

    public function UserAddressSentPage(Request $request)
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

        $col = array('id', 'user_id', 'user_page_id', 'name', 'address', 'tel', 'remark', 'status', 'create_by', 'update_by', 'created_at', 'updated_at');

        $orderby = array('id', 'user_id', 'user_page_id', 'name', 'address', 'tel', 'remark', 'status', 'create_by', 'update_by', 'created_at', 'updated_at');

        $d = User_address_sent::select($col)
            ->with('user')
            ->with('user_page');

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

        $userPageId = $request->user_page_id;
        $loginBy = $request->login_by;

        if (!isset($request->user_id)) {
            return $this->returnErrorData('กรุณาระบุ user_id ให้เรียบร้อย', 404);
        } else  if (empty($userPageId)) {
            return $this->returnErrorData('กรุณาระบุ facebook เพจ id ให้เรียบร้อย', 404);
        } else if (!isset($request->name)) {
            return $this->returnErrorData('กรุณาระบุชื่อผู้ส่งให้เรียบร้อย', 404);
        } else if (!isset($request->address)) {
            return $this->returnErrorData('กรุณาระบุที่อยู่ผู้ส่งให้เรียบร้อย', 404);
        } else if (!isset($request->tel)) {
            return $this->returnErrorData('กรุณาระบุเบอร์โทรศัพท์ผู้ส่งให้เรียบร้อย', 404);
        } else if (!isset($loginBy)) {
            return $this->returnErrorData('ไม่พบข้อมูลผู้ใช้งาน กรุณาเข้าสู่ระบบใหม่อีกครั้ง', 404);
        }

        DB::beginTransaction();

        try {

            for ($i = 0; $i < count($userPageId); $i++) {

                $User_address_sent = new User_address_sent();
                $User_address_sent->user_id = $request->user_id;
                $User_address_sent->user_page_id = $userPageId[$i];
                $User_address_sent->name = $request->name;
                $User_address_sent->address = $request->address;
                $User_address_sent->tel = $request->tel;
                $User_address_sent->remark = $request->remark;

                $User_address_sent->updated_at = Carbon::now()->toDateTimeString();

                $User_address_sent->save();
            }


            DB::commit();

            return $this->returnSuccess('ดำเนินการสำเร็จ', null);
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
        $User_address_sent = User_address_sent::with('user')
            ->with('user_page')
            ->find($id);

        if ($User_address_sent) {
            //
        }
        return $this->returnSuccess('เรียกดูข้อมูลสำเร็จ', $User_address_sent);
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

            $User_address_sent = User_address_sent::find($id);
            $User_address_sent->name = $request->name;
            $User_address_sent->address = $request->address;
            $User_address_sent->tel = $request->tel;
            $User_address_sent->remark = $request->remark;
            $User_address_sent->status = $request->status;

            $User_address_sent->updated_at = Carbon::now()->toDateTimeString();

            $User_address_sent->save();

            DB::commit();

            return $this->returnSuccess('ดำเนินการสำเร็จ', $User_address_sent);
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

            $User_address_sent = User_address_sent::find($id);
            $User_address_sent->delete();

            DB::commit();

            return $this->returnUpdate('ดำเนินการสำเร็จ');
        } catch (\Throwable $e) {

            DB::rollback();

            return $this->returnErrorData('เกิดข้อผิดพลาด กรุณาลองใหม่อีกครั้ง ', 404);
        }
    }
}
