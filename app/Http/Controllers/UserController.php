<?php

namespace App\Http\Controllers;

use App\Models\DeductPaid;
use App\Models\Employee_salary;
use App\Models\IncomePaid;
use App\Models\Order;
use App\Models\Transection;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Maatwebsite\Excel\Facades\Excel;
use Illuminate\Support\Facades\Http;

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
            ->with('delivered_by')
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
            ->with('delivered_by')
            ->where('id', $request->login_id)
            ->first();


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

                $d[$i]->total = ($d[$i]->salary + $d[$i]->total_income) - $d[$i]->total_deduct;

                $d[$i]->user = User::where('id', $d[$i]->user_id)->get();
            }
        }

        return $this->returnSuccess('เรียกดูข้อมูลสำเร็จ', $d);
    }


    public function userTransection(Request $request)
    {
        $loginBy = $request->login_by;
        $qty = $request->qty;

        if (!isset($qty)) {
            return $this->returnErrorData('กรุณาระบุจำนวนเงิน', 404);
        } else if (!isset($loginBy)) {
            return $this->returnErrorData('ไม่พบข้อมูลเจ้าหน้าที่ กรุณาเข้าสู่ระบบใหม่อีกครั้ง', 404);
        }

        DB::beginTransaction();

        try {

            //     $response = Http::withHeaders([
            //         'Content-Type' => 'application/json; charset=utf-8',
            //         'Authorization' => 'Bearer eyJ0eXAiOiJKV1QiLCJhbGciOiJIUzI1NiJ9.eyJpc3MiOiJwb2xpY2UiLCJhdWQiOjEsImx1biI6eyJpZCI6MSwicGVybWlzc2lvbl9pZCI6MSwidXNlcl9yZWZfaWQiOm51bGwsInVzZXJfaWQiOiIwMDAwMSIsImZpcnN0X25hbWUiOiJcdTBlMWJcdTBlMjdcdTBlMzRcdTBlMGFcdTBlMGRcdTBlMzIiLCJsYXN0X25hbWUiOiJcdTBlMTlcdTBlMzFcdTBlMTlcdTBlMTdcdTBlMWVcdTBlMzRcdTBlMjdcdTBlMzFcdTBlMTJcdTBlMTlcdTBlNGMiLCJlbWFpbCI6IkFkbWluMkBnbWFpbC5jb20iLCJpbWFnZSI6Imh0dHA6XC9cL2xvY2FsaG9zdFwvYXNoYVwvZGVzaGlwXC9kZWVzaGlwcC1hcGlcL3B1YmxpY1wvaW1hZ2VzXC91c2Vyc1wvMzU1OGM2Yzg3ZDFmNDRkMzVjYmE5YjJiZDFhMTVmY2YuanBnIiwidGVsIjpudWxsLCJ0ZWwyIjpudWxsLCJzaG9wX25hbWUiOm51bGwsInNob3BfYWRkcmVzcyI6bnVsbCwid2FsbGV0IjoiMC4wMCIsInN0YXR1cyI6MSwiY3JlYXRlX2J5IjoiMDAwMDEiLCJ1cGRhdGVfYnkiOiIwMDAwMSIsImNyZWF0ZWRfYXQiOiIxOVwvMTBcLzIwMjIgMTI6MzE6NDQiLCJ1cGRhdGVkX2F0IjoiMTVcLzA2XC8yMDIzIDE5OjUxOjE0IiwicGVybWlzc2lvbiI6eyJpZCI6MSwibmFtZSI6Ilx1MGU0MFx1MGUwOFx1MGU0OVx1MGUzMlx1MGUwMlx1MGUyZFx1MGUwN1x1MGU0MVx1MGUxZVx1MGUyNVx1MGUxNVx1MGUxZlx1MGUyZFx1MGUyM1x1MGU0Y1x1MGUyMSIsInN0YXR1cyI6MSwiY3JlYXRlX2J5IjoiMDAwMDciLCJ1cGRhdGVfYnkiOiIwMDAwMSIsImNyZWF0ZWRfYXQiOiIxOVwvMTBcLzIwMjIgMTI6MTM6MjgiLCJ1cGRhdGVkX2F0IjoiMTRcLzA2XC8yMDIzIDE1OjAyOjExIn0sInVzZXJfcmVmIjpudWxsfSwiaWF0IjoxNjg3MTYzOTYxLCJleHAiOjE2ODcyNTAzNjEsIm5iZiI6MTY4NzE2Mzk2MX0.gCtwkXYXzXQZiEuaS6aRt5vB3NFovBRkCQVsdfn8mnE',
            //     ])->get('http://localhost/asha/deship/deeshipp-api/public/api/get_user'
            // );

            //     return $response->json();
            ////////////

            $emv_qrcode = null;
            $randomTransection = strval(rand(100000000000, 999999999999));

            // $accessToken = $this->getTokenAPIPayment();

            // $body = [
            //     'requestId' => $randomTransection,
            //     'partnerId' => "MIXAY",
            //     'mechantId' => "G9OV179BERQ2P9LBI9IJQUM3O",
            //     'txnAmount' => $qty,
            //     'billNumber' => "DS" . $randomTransection,
            //     'terminalId' => $loginBy->id,
            //     'terminalLabel' => $loginBy->user_id,
            //     'mobileNo' => "0",
            // ];

            // $key = '4C/xiGWUAB2G0+xGbKq9ECvEHAnrRbMd68Wp+pigzCwKpfNeU/Vr8USJvQbdlZGYnfL3CWe1xUB+AOeEl62WPh3sW/fJ6lGl2xOS0pXu6uzbj5yJRymfFbnlvg99nlx1';

            // $hash = hash_hmac('sha256', json_encode($body), $key);

            // $response = Http::withHeaders([
            //     'Content-Type' => 'application/json; charset=utf-8',
            //     'SignedHash' => strval($hash),
            //     'Authorization' => 'Bearer ' + $accessToken,
            // ])->post('https://dynamicqr.jdbbank.com.la:12014/api/pro/dynamic/autenticate', $body);

            // $data = $response->json();
            // $emv_qrcode = $data->data->emv;

            //add order
            $order = new Order();
            $order->user_id =  $loginBy->id;
            $order->code =  'OR' . $randomTransection;
            $order->date =  date('Y-m-d');
            $order->time =  date('H:i:s');
            $order->type =  'deposit';
            $order->qty =  1;
            $order->price =  $qty;
            $order->discount =  0.00;
            $order->total =  $qty;
            $order->status = false;
            $order->payment = false;
            $order->remark = null;
            $order->save();
            //

            //add trans

            //get user wallet
            $getUser =  User::find($loginBy->id);
            if ($getUser) {
                $wallet =  $getUser->wallet;
            } else {
                $wallet = 0.00;
            }
            //

            $Transection = new Transection();
            $Transection->user_id =  $loginBy->id;
            $Transection->order_id =  $order->id;
            $Transection->date =  date('Y-m-d');
            $Transection->time =  date('H:i:s');
            $Transection->refNo =  $randomTransection;
            $Transection->merchantId =  "G9OV179BERQ2P9LBI9IJQUM3O";
            $Transection->cardtype =  'qrcode';
            $Transection->cc =  'qrcode';
            $Transection->qrcode =  $emv_qrcode;
            $Transection->price =   $qty;
            $Transection->fee =  0.00;
            $Transection->total =   $qty;
            $Transection->pre_wallet = $wallet;

            $Transection->new_wallet = $wallet + $qty;
            $Transection->type =   'deposit';

            $Transection->status = false;

            $Transection->remark = null;
            $Transection->save();
            $Transection->order;
            $Transection->user;
            //

            DB::commit();

            return $this->returnSuccess('ดำเนินการสำเร็จ', ['data' => $Transection, 'emv_qrcode' => $emv_qrcode]);
        } catch (\Throwable $e) {

            DB::rollback();

            return $this->returnErrorData('เกิดข้อผิดพลาด กรุณาลองใหม่อีกครั้ง ', 404);
        }
    }

    public function callbackUserTransection(Request $request)
    {

        $refNo =  $request->ref_no;

        DB::beginTransaction();

        try {

            $Transection = Transection::with('user')
                ->with('order')
                ->where('refNo', $refNo)
                ->where('status', false)
                ->first();

            if ($Transection) {

                //update trans status
                $Transection->status = true;
                $Transection->save();

                //update order
                $getOrder =  Order::find($Transection->order_id);
                $getOrder->status = true;
                $getOrder->payment = true;
                $getOrder->save();


                //get user wallet
                $getUser =  User::find($Transection->user_id);
                $getUser->wallet = $getUser->wallet + $Transection->total;
                $getUser->save();
                //

            }
            DB::commit();

            return $this->returnSuccess('ดำเนินการสำเร็จ', null);
        } catch (\Throwable $e) {

            DB::rollback();

            return $this->returnErrorData('เกิดข้อผิดพลาด กรุณาลองใหม่อีกครั้ง ', 404);
        }
    }

    public function getTokenAPIPayment()
    {

        try {

            $body = [
                'requestId' => strval(rand(100000000000, 999999999999)),
                'partnerId' => "MIXAY",
                'clientId' => "MIXAY",
                'clientScret' => "KTU8KQ1KDAI7RE4ML5KDHEOQKYV7Y84TIP9S0DZ41LX10F4UO33C0SF5SQFJZ6EV0SZSR3QNRHEITTL92ZJZ1KZBGPBZJE02EWTS",
            ];

            $key = '4C/xiGWUAB2G0+xGbKq9ECvEHAnrRbMd68Wp+pigzCwKpfNeU/Vr8USJvQbdlZGYnfL3CWe1xUB+AOeEl62WPh3sW/fJ6lGl2xOS0pXu6uzbj5yJRymfFbnlvg99nlx1';

            $hash = hash_hmac('sha256', json_encode($body), $key);

            $response = Http::withHeaders([
                'Content-Type' => 'application/json; charset=utf-8',
                'SignedHash' => strval($hash),
            ])->post('https://dynamicqr.jdbbank.com.la:12014/api/pro/dynamic/autenticate', $body);

            $data = $response->json();
            return $data->data->accessToken;
        } catch (\Throwable $e) {
            return $e;
        }
    }


    public function updateDeliveryUser(Request $request)
    {

        // $deliveredById = $request->delivered_by_id;
        $deliveredFee = $request->delivered_fee;

        $loginBy = $request->login_by;

        // if (!isset($deliveredById)) {
        //     return $this->returnErrorData('กรุณาระบุรหัสขนส่งให้เรียบร้อย', 404);
        // } else
        if (!isset($deliveredFee)) {
            return $this->returnErrorData('กรุณาระบุจำนวนค่าขนส่งที่ต้องการให้เรียบร้อย', 404);
        } else if (!isset($loginBy)) {
            return $this->returnErrorData('ไม่พบข้อมูลเจ้าหน้าที่ กรุณาเข้าสู่ระบบใหม่อีกครั้ง', 404);
        }


        DB::beginTransaction();

        try {

            $User = User::find($loginBy->id);
            // $User->delivered_by_id = $deliveredById;
            $User->delivered_fee = $deliveredFee;

            $User->save();

            //log
            $userId = $loginBy->user_id;
            $type = 'แก้ไขช่องขนส่ง';
            $description = 'เจ้าหน้าที่ ' . $userId . ' ได้ทำการ ' . $type . ' ของ ' . $User->user_id;
            $this->Log($userId, $description, $type);
            //

            DB::commit();

            return $this->returnSuccess('ดำเนินการสำเร็จ', $User);
        } catch (\Throwable $e) {

            DB::rollback();

            return $this->returnErrorData('เกิดข้อผิดพลาด กรุณาลองใหม่อีกครั้ง ', 404);
        }
    }
}
