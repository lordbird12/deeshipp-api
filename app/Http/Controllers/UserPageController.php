<?php

namespace App\Http\Controllers;

use App\Models\User_page;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class UserPageController extends Controller
{
    public function getUserPage(Request $request)
    {

        $userId = $request->user_id;

        $User_page = User_page::with('user');

        if ($userId) {
            $User_page->where('user_id', $userId);
        }
        $User_page = $User_page->get()
            ->toarray();

        if (!empty($User_page)) {

            for ($i = 0; $i < count($User_page); $i++) {
                $User_page[$i]['No'] = $i + 1;
            }
        }

        return $this->returnSuccess('เรียกดูข้อมูลสำเร็จ', $User_page);
    }

    public function UserPagePage(Request $request)
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

        $col = array('id', 'user_id', 'page_id', 'name', 'token', 'image', 'created_at', 'updated_at');

        $orderby = array('', 'user_id', 'page_id', 'name', 'token', 'image', 'created_at', 'updated_at');

        $d = User_page::select($col)
            ->with('user');

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
        } else  if (!isset($request->page_id)) {
            return $this->returnErrorData('กรุณาระบุ facebook เพจ id ให้เรียบร้อย', 404);
        } else if (!isset($request->name)) {
            return $this->returnErrorData('กรุณาระบุชื่อเพจให้เรียบร้อย', 404);
        } else if (!isset($request->token)) {
            return $this->returnErrorData('กรุณาระบุ token เพจเรียบร้อย', 404);
        } else if (!isset($request->image)) {
            return $this->returnErrorData('กรุณาระบุ url image เพจ เรียบร้อย', 404);
        } else if (!isset($loginBy)) {
            return $this->returnErrorData('ไม่พบข้อมูลผู้ใช้งาน กรุณาเข้าสู่ระบบใหม่อีกครั้ง', 404);
        }

        DB::beginTransaction();

        try {

            $User_page = new User_page();
            $User_page->user_id = $request->user_id;
            $User_page->page_id = $request->page_id;
            $User_page->name = $request->name;
            $User_page->token = $request->token;
            $User_page->image = $request->image;

            $User_page->updated_at = Carbon::now()->toDateTimeString();

            $User_page->save();

            DB::commit();

            return $this->returnSuccess('ดำเนินการสำเร็จ', $User_page);
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
        $User_page = User_page::with('user')
            ->find($id);

        if ($User_page) {
            //
        }
        return $this->returnSuccess('เรียกดูข้อมูลสำเร็จ', $User_page);
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

            $User_page = User_page::find($id);
            $User_page->page_id = $request->page_id;
            $User_page->name = $request->name;
            $User_page->token = $request->token;
            $User_page->image = $request->image;

            $User_page->updated_at = Carbon::now()->toDateTimeString();

            $User_page->save();

            DB::commit();

            return $this->returnSuccess('ดำเนินการสำเร็จ', $User_page);
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

            $User_page = User_page::find($id);
            $User_page->delete();

            DB::commit();

            return $this->returnUpdate('ดำเนินการสำเร็จ');
        } catch (\Throwable $e) {

            DB::rollback();

            return $this->returnErrorData('เกิดข้อผิดพลาด กรุณาลองใหม่อีกครั้ง ', 404);
        }
    }
}
