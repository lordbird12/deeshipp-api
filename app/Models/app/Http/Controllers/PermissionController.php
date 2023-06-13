<?php

namespace App\Http\Controllers;

use App\Models\Checkout;
use App\Models\Menu;
use App\Models\Permission;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class PermissionController extends Controller
{
   

    public function getPermissonMenu(Request $request)
    {

        $permissionId = $request->permission_id;

        $permission = Permission::find($permissionId);

      

        $menu = Menu::where('permission_id', $permissionId)->get();

        $data = [];

        //permision name
        $data['permission_name'] = $permission->name;

        //manu
        for ($i = 0; $i < count($menu); $i++) {
            $data['menu'][$i]['name'] = $menu[$i]->name;

        }

        return $this->returnSuccess('Successful', $data);

    }



    public function getPermission()
    {
        $Permission = Permission::get()->toarray();

        if (!empty($Permission)) {

            for ($i = 0; $i < count($Permission); $i++) {
                $Permission[$i]['No'] = $i + 1;

            }
        }

        return $this->returnSuccess('เรียกดูข้อมูลสำเร็จ', $Permission);
    }

    public function getPermissonUser()
    {

        //$LoginID = $request->login_id;

        // $user = DB::table('permission')
        //     ->join('users','users.id',"=",'permission.id')
        //     ->get();

            return DB::table('permission')
            ->join('users','users.id',"=",'permission.id',)
            //->where('users.id','2')
            ->get();


        // $data = [];

        // for ($i = 0; $i < count($user); $i++) {
        //     $data[] = $user[$i]->name;

        // }

        // return $this->returnSuccess('Successful', $data);

    }





    public function PermissionPage(Request $request)
    {

        $columns = $request->columns;
        $length = $request->length;
        $order = $request->order;
        $search = $request->search;
        $start = $request->start;
        $page = $start / $length + 1;

        $col = array('id', 'name', 'menu1', 'menu2', 'menu3', 'menu4', 'menu5', 'menu6', 'menu7', 'menu8'
            , 'create_by', 'update_by', 'created_at', 'updated_at');

            $orderby = array('', 'name', 'create_by', 'update_by', 'created_at', 'updated_at');


        $d = Permission::select($col);

        if ($orderby[$order[0]['column']]) {
            $d->orderby($orderby[$order[0]['column']], $order[0]['dir']);
        }
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

        if (!isset($request->name)) {
            return $this->returnErrorData('กรุณาระบุชื่อสิทธิ์การใช้งานระบบให้เรียบร้อย', 404);
        } else if (!isset($loginBy)) {
            return $this->returnErrorData('ไม่พบข้อมูลผู้ใช้งาน กรุณาเข้าสู่ระบบใหม่อีกครั้ง', 404);
        }

        $name = $request->name;

        $checkName = Permission::where('name', $name)->first();

        if ($checkName) {
            return $this->returnErrorData($name . ' มีข้อมูลในระบบแล้ว', 404);

        } else {

            DB::beginTransaction();

            try {

                $permission = new Permission();
                $permission->name = $name;
                $permission->menu1 = $request->menu1;
                $permission->menu2 = $request->menu2;
                $permission->menu3 = $request->menu3;
                $permission->menu4 = $request->menu4;
                $permission->menu5 = $request->menu5;
                $permission->menu6 = $request->menu6;
                $permission->menu7 = $request->menu7;
                $permission->menu8 = $request->menu8;

                $permission->create_by = $loginBy->user_id;
                $permission->updated_at = Carbon::now()->toDateTimeString();

                $permission->save();

                //log
                $userId = $loginBy->user_id;
                $type = 'เพิ่มสิทธิ์การใช้งาน';
                $description = 'ผู้ใช้งาน ' . $userId . ' ได้ทำการ ' . $type . ' ' . $name;
                $this->Log($userId, $description, $type);
                //

                DB::commit();

                return $this->returnSuccess('ดำเนินการสำเร็จ', $permission);

            } catch (\Throwable $e) {

                DB::rollback();

                return $this->returnErrorData('เกิดข้อผิดพลาด กรุณาลองใหม่อีกครั้ง '.$e , 404);
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
        $Permission = Permission::find($id);
        return $this->returnSuccess('เรียกดูข้อมูลสำเร็จ', $Permission);
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
        }
        //  else if (!isset($request->name)) {
        //     return $this->returnErrorData('กรุณาระบุชื่อสิทธิ์การใช้งานระบบให้เรียบร้อย', 404);
        // }
        else if (!isset($loginBy)) {
            return $this->returnErrorData('ไม่พบข้อมูลผู้ใช้งาน กรุณาเข้าสู่ระบบใหม่อีกครั้ง', 404);
        }

        DB::beginTransaction();

        try {

            $permission = Permission::find($id);
            $permission->menu1 = $request->menu1;
            $permission->menu2 = $request->menu2;
            $permission->menu3 = $request->menu3;
            $permission->menu4 = $request->menu4;
            $permission->menu5 = $request->menu5;
            $permission->menu6 = $request->menu6;
            $permission->menu7 = $request->menu7;
            $permission->menu8 = $request->menu8;

            $permission->update_by = $loginBy->user_id;
            $permission->updated_at = Carbon::now()->toDateTimeString();

            $permission->save();

            //log
            $userId = $loginBy->user_id;
            $type = 'แก้ไขสิทธิ์การใช้งาน';
            $description = 'ผู้ใช้งาน ' . $userId . ' ได้ทำการ ' . $type . ' ' . $permission->name;
            $this->Log($userId, $description, $type);
            //

            DB::commit();

            return $this->returnSuccess('ดำเนินการสำเร็จ', $permission);

        } catch (\Throwable $e) {

            DB::rollback();

            return $this->returnErrorData('เกิดข้อผิดพลาด กรุณาลองใหม่อีกครั้ง ' , 404);
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

        //check user in group
        $CheckUser = Permission::with('users')->where('id', $id)->first();

        if ($CheckUser->users->isEmpty()) {

            DB::beginTransaction();

            try {
              
                $Permission = Permission::find($id);

                $Permission->name =  $Permission->name. '_del_'.date('YmdHis');
                $Permission->save();

                //log
                $userId = $loginBy->user_id;
                $type = 'ลบสิทธิ์การใช้งาน';
                $description = 'ผู้ใช้งาน ' . $userId . ' ได้ทำการ ' . $type . ' ' . $Permission->name;
                $this->Log($userId, $description, $type);
                //
               
                $Permission->delete();

                DB::commit();

                return $this->returnUpdate('ดำเนินการสำเร็จ');

            } catch (\Throwable $e) {

                DB::rollback();

                return $this->returnErrorData('เกิดข้อผิดพลาด กรุณาลองใหม่อีกครั้ง ' , 404);
            }

        } else {

            return $this->returnErrorData('กรุณาย้ายเจ้าหน้าที่ออกจากกลุ่มสิทธิ์การใช้งานก่อนดำเนินการ', 404);
        }
    }
}
