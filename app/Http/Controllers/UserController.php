<?php

namespace App\Http\Controllers;

use App\Models\DeductPaid;
use App\Models\Employee_salary;
use App\Models\IncomePaid;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Maatwebsite\Excel\Facades\Excel;

class UserController extends Controller
{

    public function getUser()
    {

        $User = User::with('permission')
            ->with('user_ref')
            ->with('user_create')
            ->get();

        if ($User->isNotEmpty()) {

            for ($i = 0; $i < count($User); $i++) {
                $User[$i]['No'] = $i + 1;
            }
        }

        return $this->returnSuccess('เรียกดูข้อมูลสำเร็จ', $User);
    }


    public function UserPage(Request $request)
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
         } else {
             $userId = $loginBy->id;
         }
         //

        $Status = $request->status;

        $col = array(
            'id', 'permission_id', 'user_ref_id', 'user_id', 'password', 'first_name', 'last_name', 'email', 'image', 'tel', 'tel2', 'shop_name', 'shop_address', 'create_by', 'update_by', 'created_at', 'updated_at'
        );

        $orderby = array('', 'permission_id', 'user_ref_id', 'user_id', 'password', 'first_name', 'last_name', 'email', 'image', 'tel', 'tel2', 'shop_name', 'shop_address', 'create_by', 'update_by', 'created_at', 'updated_at');

        $D = User::select($col)
            ->with('permission')
            ->with('user_ref')
            ->with('user_create');

        if ($userId) {
            $D->where('user_ref_id', $userId);
        }

        if ($Status) {
            $D->where('status', $Status);
        }

        //

        if ($orderby[$order[0]['column']]) {
            $D->orderby($orderby[$order[0]['column']], $order[0]['dir']);
        }

        if ($search['value'] != '' && $search['value'] != null) {

            $D->Where(function ($query) use ($search, $col) {

                //search datatable
                $query->where(function ($query) use ($search, $col) {
                    foreach ($col as &$c) {
                        $query->orWhere($c, 'like', '%' . $search['value'] . '%');
                    }
                });

                //permission
                $query = $this->withPermission($query, $search);
                //

            });
        }

        $d = $D->paginate($length, ['*'], 'page', $page);

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

        if (!isset($request->permission_id)) {
            return $this->returnErrorData('กรุณาเลือกสิทธิ์การใช้งาน', 404);
        } else if (!isset($request->user_id)) {
            return $this->returnErrorData('กรุณากรอกรหัสพนักงาน', 404);
        } else if (!isset($request->first_name)) {
            return $this->returnErrorData('กรุณาใส่ชื่อ', 404);
        } else if (!isset($request->last_name)) {
            return $this->returnErrorData('กรุณากรอกนามสกุล', 404);
        } else if (!isset($request->email)) {
            return $this->returnErrorData('กรุณากรอกอีเมล์', 404);
        } else if (!isset($request->password)) {
            return $this->returnErrorData('กรุณากรอกรหัสผ่าน', 404);
        }
        $checkName = User::where(function ($query) use ($request) {

            $query->orwhere('email', $request->email)
                ->orWhere('user_id', $request->user_id);
        })
            ->first();

        if ($checkName) {
            return $this->returnErrorData('มีเจ้าหน้าที่นี้ในระบบแล้ว', 404);
        } else {

            DB::beginTransaction();

            try {

                //
                $User = new User();
                $User->permission_id = $request->permission_id;
                $User->user_ref_id = $request->user_ref_id;

                $User->user_id = $request->user_id;

                $User->password = md5($request->password);
                $User->first_name = $request->first_name;
                $User->last_name = $request->last_name;
                $User->email = $request->email;

                if ($request->image && $request->image != null && $request->image != 'null') {
                    $User->image = $this->uploadImage($request->image, '/images/users/');
                }

                $User->tel = $request->tel;
                $User->tel2 = $request->tel2;
                $User->shop_name = $request->shop_name;
                $User->shop_address = $request->shop_address;

                $User->status = 1;
                $User->create_by = $loginBy->user_id;

                $User->save();


                //log
                $userId = $loginBy->user_id;
                $type = 'เพิ่มผู้ใช้งาน';
                $description = 'เจ้าหน้าที่ ' . $userId . ' ได้ทำการ ' . $type . ' ' . $request->user_id;
                $this->Log($userId, $description, $type);
                //

                DB::commit();

                return $this->returnSuccess('ดำเนินการสำเร็จ', []);
            } catch (\Throwable $e) {

                DB::rollback();

                return $this->returnErrorData('เกิดข้อผิดพลาด กรุณาลองใหม่อีกครั้ง ' . $e, 404);
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

        $User = User::with('permission')
            ->with('user_ref')
            ->with('user_create')
            ->where('id', $id)
            ->first();

        return $this->returnSuccess('เรียกดูข้อมูลสำเร็จ', $User);
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
    public function update(Request $request)
    {
        //
    }

    public function getProfileUser(Request $request)
    {

        $User = User::with('permission')
            ->with('user_ref')
            ->with('user_create')
            ->where('id', $request->login_id)
            ->first();

        $User->income = IncomePaid::where('user_id',  $User->id)->get();
        $User->deduct = DeductPaid::where('user_id',  $User->id)->get();

        $User->total_income = IncomePaid::where('user_id',  $User->id)->sum('price');
        $User->total_deduct = DeductPaid::where('user_id',  $User->id)->sum('price');

        $User->total = ($User->salary +  $User->total_income) - $User->total_deduct;

        return $this->returnSuccess('เรียกดูข้อมูลสำเร็จ', $User);
    }

    public function updateProfileUser(Request $request)
    {

        $loginBy = $request->login_by;

        if (!isset($request->id)) {
            return $this->returnErrorData('ไม่พบข้อมูล id', 404);
        } else if (!isset($loginBy)) {
            return $this->returnErrorData('ไม่พบข้อมูลเจ้าหน้าที่ กรุณาเข้าสู่ระบบใหม่อีกครั้ง', 404);
        }

        DB::beginTransaction();

        try {

            $id = $request->id;
            $User = User::find($id);



            $User->first_name = $request->first_name;
            $User->last_name = $request->last_name;

            if ($request->image && $request->image != null && $request->image != 'null') {
                $User->image = $this->uploadImage($request->image, '/images/users/');
            }

            $User->tel = $request->tel;
            $User->tel2 = $request->tel2;
            $User->shop_name = $request->shop_name;
            $User->shop_address = $request->shop_address;


            $User->update_by = $loginBy->user_id;
            $User->updated_at = Carbon::now()->toDateTimeString();

            $User->save();

            //log
            $userId = $loginBy->user_id;
            $type = 'แก้ไขโปรไฟล์เจ้าหน้าที่';
            $description = 'เจ้าหน้าที่ ' . $userId . ' ได้ทำการ ' . $type;
            $this->Log($userId, $description, $type);
            //

            DB::commit();

            return $this->returnUpdate('ดำเนินการสำเร็จ');
        } catch (\Throwable $e) {

            DB::rollback();

            return $this->returnErrorData('เกิดข้อผิดพลาด กรุณาลองใหม่อีกครั้ง ' . $e, 404);
        }
    }



    public function updateUser(Request $request)
    {

        $loginBy = $request->login_by;

        if (!isset($request->id)) {
            return $this->returnErrorData('ไม่พบข้อมูล id', 404);
        } else if (!isset($request->first_name)) {
            return $this->returnErrorData('กรุณาใส่ชื่อ', 404);
        } else if (!isset($request->last_name)) {
            return $this->returnErrorData('กรุณากรอกนามสกุล', 404);
        } else if (!isset($request->email)) {
            return $this->returnErrorData('กรุณากรอกอีเมล์', 404);
        } else if (!isset($request->image)) {
            return $this->returnErrorData('กรุณาเพิ่มรูป', 404);
        } else {

            DB::beginTransaction();

            try {

                //
                $id = $request->id;
                $User = User::find($id);

                $User->user_id = $request->user_id;

                $User->first_name = $request->first_name;
                $User->last_name = $request->last_name;
                $User->email = $request->email;

                if ($request->image && $request->image != null && $request->image != 'null') {
                    $User->image = $this->uploadImage($request->image, '/images/users/');
                }

                $User->tel = $request->tel;
                $User->tel2 = $request->tel2;
                $User->shop_name = $request->shop_name;
                $User->shop_address = $request->shop_address;

                $User->status = 1;
                $User->create_by = $loginBy->user_id;

                $User->save();


                //log
                $userId = $loginBy->user_id;
                $type = 'เพิ่ม admin';
                $description = 'เจ้าหน้าที่ ' . $userId . ' ได้ทำการ ' . $type . ' ' . $request->user_id;
                $this->Log($userId, $description, $type);
                //

                DB::commit();

                return $this->returnSuccess('ดำเนินการสำเร็จ', []);
            } catch (\Throwable $e) {

                DB::rollback();

                return $this->returnErrorData('เกิดข้อผิดพลาด กรุณาลองใหม่อีกครั้ง ' . $e, 404);
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
            return $this->returnErrorData('ไม่พบข้อมูลเจ้าหน้าที่ กรุณาเข้าสู่ระบบใหม่อีกครั้ง', 404);
        }

        DB::beginTransaction();

        try {

            $User = User::find($id);

            $User->user_id = $User->user_id . '_del_' . date('YmdHis');
            $User->save();

            //log
            $userId = $loginBy->user_id;
            $type = 'ลบเจ้าหน้าที่';
            $description = 'เจ้าหน้าที่ ' . $userId . ' ได้ทำการ ' . $type . ' ' . $User->user_id;
            $this->Log($userId, $description, $type);
            //

            $User->delete();

            DB::commit();

            return $this->returnUpdate('ดำเนินการสำเร็จ');
        } catch (\Throwable $e) {

            DB::rollback();

            return $this->returnErrorData('เกิดข้อผิดพลาด กรุณาลองใหม่อีกครั้ง ', 404);
        }
    }

    public function updatePasswordUser(Request $request, $id)
    {

        $password = $request->password;

        $loginBy = $request->login_by;

        if (!isset($id)) {
            return $this->returnErrorData('ไม่พบ id', 404);
        } else if (!isset($password)) {
            return $this->returnErrorData('กรุณาระบุรหัสผ่านใหม่ให้เรียบร้อย', 404);
        } else if (!isset($loginBy)) {
            return $this->returnErrorData('ไม่พบข้อมูลเจ้าหน้าที่ กรุณาเข้าสู่ระบบใหม่อีกครั้ง', 404);
        }

        if (strlen($password) < 6) {
            return $this->returnErrorData('กรุณาระบุรหัสผ่านอย่างน้อย 6 หลัก', 404);
        }

        DB::beginTransaction();

        try {

            $User = User::find($id);
            $User->password = md5($password);

            $User->save();

            //log
            $userId = $loginBy->user_id;
            $type = 'เปลื่ยนหรัสผ่าน';
            $description = 'เจ้าหน้าที่ ' . $userId . ' ได้ทำการ ' . $type . ' ของ ' . $User->user_id;
            $this->Log($userId, $description, $type);
            //

            DB::commit();

            return $this->returnUpdate('ดำเนินการสำเร็จ');
        } catch (\Throwable $e) {

            DB::rollback();

            return $this->returnErrorData('เกิดข้อผิดพลาด กรุณาลองใหม่อีกครั้ง ', 404);
        }
    }

    public function deleteUser(Request $request, $id)
    {


        $loginBy = $request->login_by;
        //dd($loginBy);
        if (!isset($loginBy)) {
            return $this->returnErrorData('[login_by] ไม่มีข้อมูล', 404);
        }

        DB::beginTransaction();

        try {

            $User = User::find($id);




            //log
            $userId = $loginBy->user_id;
            //$userId = $loginBy;
            $type = 'ลบUser';
            $description = 'เจ้าหน้าที่ ' . $userId . ' ได้ทำการ ' . $type . ' ลบ ' . $User->user_id;
            $this->Log($userId, $description, $type);
            //


            $User->delete();
            // $Position->delete();

            DB::commit();

            return $this->returnUpdate('ดำเนินการลบสำเร็จ');
        } catch (\Throwable $e) {

            DB::rollback();

            return $this->returnErrorData('ดำเนินการลบUserผิดพลาด ' . $e, 404);
        }
    }



    public function createUserAdmin(Request $request)
    {

        if (!isset($request->user_id)) {
            return $this->returnErrorData('กรุณากรอกรหัสพนักงาน', 404);
        } else if (!isset($request->first_name)) {
            return $this->returnErrorData('กรุณาใส่ชื่อ', 404);
        } else if (!isset($request->last_name)) {
            return $this->returnErrorData('กรุณากรอกนามสกุล', 404);
        } else if (!isset($request->email)) {
            return $this->returnErrorData('กรุณากรอกอีเมล์', 404);
        } else if (!isset($request->password)) {
            return $this->returnErrorData('กรุณากรอกรหัสผ่าน', 404);
        } else if (!isset($request->image)) {
            return $this->returnErrorData('กรุณาเพิ่มรูป', 404);
        } else if (!isset($request->permission_id)) {
            return $this->returnErrorData('กรุณาระบุสิทธิ์ผู้ใช้งาน', 404);
        }

        $checkName = User::where(function ($query) use ($request) {

            $query->orwhere('email', $request->email)
                ->orWhere('user_id', $request->user_id);
        })
            ->first();

        if ($checkName) {
            return $this->returnErrorData('มีเจ้าหน้าที่นี้ในระบบแล้ว', 404);
        } else {

            DB::beginTransaction();

            try {

                //
                $User = new User();
                $User->user_id = $request->user_id;

                $User->password = md5($request->password);
                $User->first_name = $request->first_name;
                $User->last_name = $request->last_name;
                $User->email = $request->email;
                $User->permission_id = $request->permission_id;

                $User->image = $this->uploadImage($request->image, '/images/users/');

                $User->status = 1;
                $User->create_by = "admin";

                $User->save();


                //log
                $userId = "admin";
                $type = 'เพิ่ม admin';
                $description = 'เจ้าหน้าที่ ' . $userId . ' ได้ทำการ ' . $type . ' ' . $request->user_id;
                $this->Log($userId, $description, $type);
                //

                DB::commit();

                return $this->returnSuccess('ดำเนินการสำเร็จ', []);
            } catch (\Throwable $e) {

                DB::rollback();

                return $this->returnErrorData('เกิดข้อผิดพลาด กรุณาลองใหม่อีกครั้ง ' . $e, 404);
            }
        }
    }

    public function ResetPasswordUser(Request $request, $id)
    {
        $loginBy = $request->login_by;


        if (!isset($id)) {


            return $this->returnErrorData('ไม่พบข้อมูล id', 404);
        } else if (!isset($request->password)) {
            return $this->returnErrorData('กรุณาระบุรหัสผ่านให้เรียบร้อย', 404);
        } else if (!isset($request->new_password)) {
            return $this->returnErrorData('กรุณาระบุรหัสผ่านใหม่ให้เรียบร้อย', 404);
        } else if (!isset($request->confirm_new_password)) {
            return $this->returnErrorData('กรุณาระบุรหัสผ่านใหม่อีกครั้ง', 404);
        } else if (!isset($loginBy)) {
            return $this->returnErrorData('ไม่พบข้อมูลเจ้าหน้าที่ กรุณาเข้าสู่ระบบใหม่อีกครั้ง', 404);
        }

        if (strlen($request->new_password) < 6) {
            return $this->returnErrorData('กรุณาระบุรหัสผ่านอย่างน้อย 6 หลัก', 404);
        }

        if ($request->new_password != $request->confirm_new_password) {
            return $this->returnErrorData('รหัสผ่านไม่ตรงกัน', 404);
        }

        DB::beginTransaction();

        try {

            $User = User::find($id);

            if ($User->password == md5($request->password)) {

                $User->password = md5($request->new_password);
                $User->updated_at = Carbon::now()->toDateTimeString();
                $User->save();

                DB::commit();

                return $this->returnUpdate('ดำเนินการสำเร็จ');
            } else {

                return $this->returnErrorData('รหัสผ่านไม่ถูกต้อง', 404);
            }
        } catch (\Throwable $e) {

            DB::rollback();

            return $this->returnErrorData('เกิดข้อผิดพลาด กรุณาลองใหม่อีกครั้ง ', 404);
        }
    }


    public function ActivateUserPage(Request $request)
    {

        // $columns = $request->columns;
        // $length = $request->length;
        // $order = $request->order;
        // $search = $request->search;
        // $start = $request->start;
        // $page = $start / $length + 1;

        // $col = array(

        //     'id',
        //     'branch_id',
        //     'position_id',
        //     'user_id',
        //     'password',
        //     'first_name',
        //     'last_name',
        //     'email',
        //     'image',
        //     'image_signature',
        //     'status',
        //     'create_by',
        //     'update_by',
        //     'created_at',
        //     'updated_at',
        //     'deleted_at',

        // );

        // $d = User::select($col)

        //     ->with('branch')
        //     ->with('position')
        //     // ->where('status', 'Request')

        //     ->orderby($col[$order[0]['column']], $order[0]['dir']);



        // if ($search['value'] != '' && $search['value'] != null) {

        //     //search datatable
        //     $d->where(function ($query) use ($search, $col) {
        //         foreach ($col as &$c) {
        //             $query->orWhere($c, 'like', '%' . $search['value'] . '%');
        //         }
        //     });
        // }

        // $d = $d->paginate($length, ['*'], 'page', $page);

        // if ($d->isNotEmpty()) {

        //     //run no
        //     $No = (($page - 1) * $length);

        //     for ($i = 0; $i < count($d); $i++) {

        //         $No = $No + 1;
        //         $d[$i]->No = $No;

        //         //image
        //         if ($d[$i]->image) {
        //             $d[$i]->image = url($d[$i]->image);
        //         } else {
        //             $d[$i]->image = null;
        //         }

        //         //signature
        //         if ($d[$i]->signature) {
        //             $d[$i]->signature = url($d[$i]->signature);
        //         } else {
        //             $d[$i]->signature = null;
        //         }
        //     }
        // }

        // return $this->returnSuccess('Successful', $d);
    }

    public function ForgotPasswordUser(Request $request)
    {

        $email = $request->email;


        $User = User::where('email', $email)->where('status', 'Yes')->first();

        if (!empty($User)) {

            //random string
            $length = 8;
            $characters = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';
            $charactersLength = strlen($characters);
            $randomString = '';
            for ($i = 0; $i < $length; $i++) {
                $randomString .= $characters[rand(0, $charactersLength - 1)];
            }
            //

            $newPasword = md5($randomString);

            DB::beginTransaction();

            try {

                $User->password = $newPasword;
                $User->save();

                $title = 'รหัสผ่านใหม่';
                $text = 'รหัสผ่านใหม่ของคุณคือ  ' . $randomString;
                $type = 'Forgot Password';

                // //send line
                // if ($User->line_token) {
                //     $this->sendLine($User->line_token, $text);
                // }

                //send email
                if ($User->email) {
                    $this->sendMail($User->email, $text, $title, $type);
                }

                DB::commit();

                return $this->returnUpdate('ดำเนินการสำเร็จ');
            } catch (\Throwable $e) {

                DB::rollback();

                return $this->returnErrorData('เกิดข้อผิดพลาด กรุณาลองใหม่อีกครั้ง ', 404);
            }
        } else {
            return $this->returnErrorData('ไม่พบอีเมล์ในระบบ ', 404);
        }
    }


    public function ActivateUser(Request $request, $id)
    {

        $loginBy = $request->login_by;

        if (!isset($id)) {
            return $this->returnErrorData('[id] Data Not Found', 404);
        } else if (!isset($request->status)) {
            return $this->returnErrorData('[status] Data Not Found', 404);
        } else if (!isset($loginBy)) {
            return $this->returnErrorData('[login_by] Data Not Found', 404);
        }

        DB::beginTransaction();

        try {

            $User = User::find($id);

            $User->status = $request->status;
            $User->updated_at = Carbon::now()->toDateTimeString();
            $User->save();

            //log
            $userId = $loginBy->user_id;
            //$userId = $loginBy;
            $type = 'Activate User';
            $description = 'User ' . $userId . ' has ' . $type . ' number ' . $User->user_id;
            $this->Log($userId, $description, $type);
            //

            DB::commit();

            return $this->returnUpdate('Successful operation');
        } catch (\Throwable $e) {

            DB::rollback();

            return $this->returnErrorData('Something went wrong Please try again ' . $e, 404);
        }
    }

    public function getLastUserID()
    {

        $User = User::latest()->first();
        $Item = array();
        if ($User) {
            $last = $User->user_id + 1;
            switch (strlen($last)) {
                case 1:
                    $Item["user_last_id"] = "0000" . $last;
                    break;
                case 2:
                    $Item["user_last_id"] = "000" . $last;
                    break;
                case 3:
                    $Item["user_last_id"] = "00" . $last;
                    break;
                case 4:
                    $Item["user_last_id"] = "0" . $last;
                    break;
                case 5:
                    $Item["user_last_id"] = $last;
                    break;
                default:
                    $Item["user_last_id"] = "000001";
            }
        } else {
            $Item["user_last_id"] = "000001";
        }
        return $this->returnSuccess('เรียกดูข้อมูลสำเร็จ', $Item);
    }

    public function getUserPayroll(Request $request)
    {
        $columns = $request->columns;
        $length = $request->length;
        $order = $request->order;
        $search = $request->search;
        $start = $request->start;
        $page = $start / $length + 1;

        $user_id = $request->user_id;
        $month = $request->month;
        $year = $request->year;

        $round = $year . "-" . $month;

        $col = array(
            'id', 'permission_id', 'user_ref_id', 'user_id', 'password', 'first_name', 'last_name', 'email', 'image', 'tel', 'tel2', 'shop_name', 'shop_address', 'create_by', 'update_by', 'created_at', 'updated_at'
        );

        $orderby = array('', 'permission_id', 'user_ref_id', 'user_id', 'password', 'first_name', 'last_name', 'email', 'image', 'tel', 'tel2', 'shop_name', 'shop_address', 'create_by', 'update_by', 'created_at', 'updated_at');

        $D = User::select($col);

        if ($user_id) {
            $uId = User::where('user_id', $user_id)->first();
            $D->where('user_id', $uId->user_id);
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
                $d[$i]->income = IncomePaid::where('user_id', $d[$i]->user_id)->get();
                $d[$i]->deduct = DeductPaid::where('user_id', $d[$i]->user_id)->get();

                $d[$i]->total_income = IncomePaid::where('user_id', $d[$i]->user_id)->sum('price');
                $d[$i]->total_deduct = DeductPaid::where('user_id', $d[$i]->user_id)->sum('price');

                $d[$i]->total = ($d[$i]->salary + $d[$i]->total_income) - $d[$i]->total_deduct;

                $d[$i]->user = User::where('id', $d[$i]->user_id)->get();
            }
        }

        return $this->returnSuccess('เรียกดูข้อมูลสำเร็จ', $d);
    }
}
