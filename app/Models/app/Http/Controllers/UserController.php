<?php

namespace App\Http\Controllers;

use App\Models\Employee_salary;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Maatwebsite\Excel\Facades\Excel;

class UserController extends Controller
{

    public function getUser()
    {
      
     // $User = User::get()->toarray();
       
        $User = User::with('Position')->get()->toarray();
        if (!empty($User)) {

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

        $type = $request->login_by->type;
        $agencyCommandId = $request->login_by->agency_command_id;
        $subAgencyCommandId = $request->login_by->sub_agency_command_id;
        $affiliationId = $request->login_by->affiliation_id;

        $Status = $request->status;

        $col = array(
            'id', 'permission_id', 'agency_command_id', 'sub_agency_command_id', 'affiliation_id', 'position_id', 'prefix_type_id', 'prefix_id', 'user_id', 'name', 'email', 'image', 'status', 'type', 'create_by', 'update_by', 'created_at', 'updated_at'
        );

        $orderby = array('', 'image', 'name', 'user_id', 'permission_id', 'create_by', 'status');

        $D = User::select($col)
            ->with('permission')
            ->with('agency_command')
            ->with('sub_agency_command')
            ->with('affiliation')
            ->with('position')
            ->with('prefix_type')
            ->with('prefix');

        if ($Status) {
            $D->where('status', $Status);
        }

        if ($type == 'agency_command') {

            if ($agencyCommandId) {
                $D->where('agency_command_id', $agencyCommandId);
            }
        }

        //
        if ($type == 'sub_agency_command') {

            if ($agencyCommandId && $subAgencyCommandId) {
                $D->where('agency_command_id', $agencyCommandId);
                $D->where('sub_agency_command_id', $subAgencyCommandId);
            }
        }

        if ($type == 'affiliation') {

            if ($agencyCommandId && $subAgencyCommandId && $affiliationId) {
                $D->where('agency_command_id', $agencyCommandId);
                $D->where('sub_agency_command_id', $subAgencyCommandId);
                $D->where('affiliation_id', $affiliationId);
            }
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

                //agency_command
                $query = $this->withAgencyCommand($query, $search);
                //

                //sub agency_command
                $query = $this->withSubAgencyCommand($query, $search);
                //

                //Affiliation
                $query = $this->withAffiliation($query, $search);
                //

                //Position
                $query = $this->withPosition($query, $search);
                //

                //Prefix type
                $query = $this->withPrefixType($query, $search);
                //

                //Prefix
                $query = $this->withPrefix($query, $search);
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
            return $this->returnErrorData('กรุณาระบุสิทธิ์การใช้งานให้เรียบร้อย', 404);
        } else if (!isset($request->user_id)) {
            return $this->returnErrorData('กรุณาระบุชื่อบัญชีผู้ใช้งานให้เรียบร้อย', 404);
        } else if (!isset($request->name)) {
            return $this->returnErrorData('กรุณาระบุชื่อเจ้าหน้าที่ให้เรียบร้อย', 404);
        } else if (!isset($request->email)) {
            return $this->returnErrorData('กรุณาระบุอีเมล์ให้เรียบร้อย', 404);
        } else if (!isset($request->password)) {
            return $this->returnErrorData('กรุณาระบุชื่อรหัสผ่านให้เรียบร้อย', 404);
        } else if (!isset($request->type)) {
            return $this->returnErrorData('กรุณาระบุประเภทเจ้าหน้าที่ให้เรียบร้อย', 404);
        } else if (!isset($request->position_id)) {
            return $this->returnErrorData('กรุณาเลือกตำแหน่งให้เรียบร้อย', 404);
        } else if (!isset($request->prefix_type_id)) {
            return $this->returnErrorData('กรุณาเลือกประเภทยศให้เรียบร้อย', 404);
        } else if (!isset($request->prefix_id)) {
            return $this->returnErrorData('กรุณาเลือกยศให้เรียบร้อย', 404);
        } else if (!isset($loginBy)) {
            return $this->returnErrorData('ไม่พบข้อมูลผู้ใช้งาน กรุณาเข้าสู่ระบบใหม่อีกครั้ง', 404);
        } else
            //
            if (!isset($request->agency_command_id)) {
                return $this->returnErrorData('กรุณาเลือกกองบัญชาการให้เรียบร้อย', 404);
            } else if (!isset($request->sub_agency_command_id) && $request->position_id > "007") {
                return $this->returnErrorData('กรุณาเลือกกองบังคับการให้เรียบร้อย', 404);
            } else if (!isset($request->affiliation_id) && $request->position_id > "007") {
                return $this->returnErrorData('กรุณาเลือกกองกำกับการให้เรียบร้อย', 404);
            } else
                //
                if (!isset($request->sub_agency_command_id) && $request->type == 'sub_agency_command') {
                    return $this->returnErrorData('กรุณาเลือกกองบังคับการให้เรียบร้อย', 404);
                } else if (!isset($request->affiliation_id) && $request->type == 'affiliation') {
                    return $this->returnErrorData('กรุณาเลือกกองกำกับการให้เรียบร้อย', 404);
                }

        if (strlen($request->password) < 6) {
            return $this->returnErrorData('กรุณาระบุรหัสผ่านอย่างน้อย 6 หลัก', 404);
        }

        $checkUserId = User::where('user_id', $request->user_id)->first();
        if ($checkUserId) {
            return $this->returnErrorData('มีชื่อบัญชีผู้ใช้งาน ' . $request->user_id . ' ในระบบแล้ว', 404);
        }

        $checkEmail = User::where('email', $request->email)->first();
        if ($checkEmail) {
            return $this->returnErrorData('มีอีเมล์ ' . $request->email . ' ในระบบแล้ว', 404);
        }

        DB::beginTransaction();

        try {

            $User = new User();
            $User->user_id = $request->user_id;
            $User->password = md5($request->password);
            $User->name = $request->name;
            $User->email = $request->email;

            if ($request->image && $request->image != null && $request->image != 'null') {
                $User->image = $this->uploadImage($request->image, '/images/users/');
            }

            $User->status = "Yes";
            $User->create_by = $loginBy->user_id;

            $User->permission_id = $request->permission_id;

            $User->agency_command_id = $request->agency_command_id;
            $User->sub_agency_command_id = $request->sub_agency_command_id;
            $User->affiliation_id = $request->affiliation_id;

            $User->position_id = $request->position_id;
            $User->prefix_type_id = $request->prefix_type_id;
            $User->prefix_id = $request->prefix_id;

            $User->type = $request->type;

            $User->save();
            $User->permission;
            $User->agency_command;
            $User->sub_agency_command;
            $User->affiliation;
            $User->position;
            $User->prefix_type;
            $User->prefix;

            //

            //log
            $userId = $loginBy->user_id;
            $type = 'เพิ่มเจ้าหน้าที่';
            $description = 'เจ้าหน้าที่ ' . $userId . ' ได้ทำการ ' . $type . ' ' . $request->user_id;
            $this->Log($userId, $description, $type);
            //

            DB::commit();

            return $this->returnSuccess('ดำเนินการสำเร็จ', $User);
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

        $User = User::with('permission')
            
            ->with('position')
           
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

        $loginBy = $request->login_by;

        if (!isset($request->id)) {
            return $this->returnErrorData('ไม่พบข้อมูล id', 404);
        }
        if (!isset($request->permission_id)) {
            return $this->returnErrorData('กรุณาระบุสิทธิ์การใช้งานให้เรียบร้อย', 404);
        } else if (!isset($request->user_id)) {
            return $this->returnErrorData('กรุณาระบุชื่อบัญชีผู้ใช้งานให้เรียบร้อย', 404);
        } else if (!isset($request->name)) {
            return $this->returnErrorData('กรุณาระบุชื่อเจ้าหน้าที่ให้เรียบร้อย', 404);
        } else if (!isset($request->email)) {
            return $this->returnErrorData('กรุณาระบุอีเมล์ให้เรียบร้อย', 404);
        } else if (!isset($request->type)) {
            return $this->returnErrorData('กรุณาระบุประเภทเจ้าหน้าที่ให้เรียบร้อย', 404);
        } else if (!isset($request->position_id)) {
            return $this->returnErrorData('กรุณาเลือกตำแหน่งให้เรียบร้อย', 404);
        } else if (!isset($request->prefix_type_id)) {
            return $this->returnErrorData('กรุณาเลือกประเภทยศให้เรียบร้อย', 404);
        } else if (!isset($request->prefix_id)) {
            return $this->returnErrorData('กรุณาเลือกยศให้เรียบร้อย', 404);
        } else if (!isset($loginBy)) {
            return $this->returnErrorData('ไม่พบข้อมูลเจ้าหน้าที่ กรุณาเข้าสู่ระบบใหม่อีกครั้ง', 404);
        } else
            //

            if (!isset($request->agency_command_id)) {
                return $this->returnErrorData('กรุณาเลือกกองบัญชาการให้เรียบร้อย', 404);
            } else if (!isset($request->sub_agency_command_id) && $request->position_id > "007") {
                return $this->returnErrorData('กรุณาเลือกกองบังคับการให้เรียบร้อย', 404);
            } else if (!isset($request->affiliation_id) && $request->position_id > "007") {
                return $this->returnErrorData('กรุณาเลือกกองกำกับการให้เรียบร้อย', 404);
            } else
                //
                if (!isset($request->sub_agency_command_id) && $request->type == 'sub_agency_command') {
                    return $this->returnErrorData('กรุณาเลือกกองบังคับการให้เรียบร้อย', 404);
                } else if (!isset($request->affiliation_id) && $request->type == 'affiliation') {
                    return $this->returnErrorData('กรุณาเลือกกองกำกับการให้เรียบร้อย', 404);
                }

        DB::beginTransaction();

        try {

            $id = $request->id;

            $checkUserId = User::where('user_id', $request->user_id)
                ->where('id', '!=', $id)
                ->first();

            if ($checkUserId) {
                return $this->returnErrorData('มีชื่อบัญชีผู้ใช้งาน ' . $request->user_id . ' ในระบบแล้ว', 404);
            }

            $checkName = User::where('email', $request->email)
                ->where('id', '!=', $id)
                ->first();

            if ($checkName) {
                return $this->returnErrorData('มีอีเมล์ ' . $request->email . ' ในระบบแล้ว', 404);
            }

            $User = User::find($id);

            $User->name = $request->name;
            $User->email = $request->email;

            if ($request->image && $request->image != null && $request->image != 'null') {
                $User->image = $this->uploadImage($request->image, '/images/users/');
            }

            $User->status = $request->status;
            $User->update_by = $loginBy->user_id;
            $User->updated_at = Carbon::now()->toDateTimeString();

            $User->permission_id = $request->permission_id;

            $User->agency_command_id = $request->agency_command_id;
            $User->sub_agency_command_id = $request->sub_agency_command_id;
            $User->affiliation_id = $request->affiliation_id;

            $User->position_id = $request->position_id;
            $User->prefix_type_id = $request->prefix_type_id;
            $User->prefix_id = $request->prefix_id;

            $User->type = $request->type;

            $User->save();
            $User->permission;
            $User->agency_command;
            $User->sub_agency_command;
            $User->affiliation;
            $User->position;
            $User->prefix_type;
            $User->prefix;

            //log
            $userId = $loginBy->user_id;
            $type = 'แก้ไขเจ้าหน้าที่';
            $description = 'เจ้าหน้าที่ ' . $userId . ' ได้ทำการ ' . $type . ' ' . $User->user_id;
            $this->Log($userId, $description, $type);
            //

            DB::commit();

            return $this->returnUpdate('ดำเนินการสำเร็จ');
        } catch (\Throwable $e) {

            DB::rollback();

            return $this->returnErrorData('เกิดข้อผิดพลาด กรุณาลองใหม่อีกครั้ง ', 404);
        }
    }

    public function getProfileUser(Request $request)
    {

        $User = User::with('permission')
             ->with('department')
             ->with('position')
             ->with('branch')
             ->with('Delivered_by')
            // ->with('position')
            // ->with('prefix_type')
            // ->with('prefix')
            ->where('id', $request->login_id)
            ->first();

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
           
            $User->email = $request->email;


            if ($request->image && $request->image != null && $request->image != 'null') {
                $User->image = $this->uploadImage($request->image, '/images/users/');
            }
            if ($request->image_signature && $request->image_signature != 'null' && $request->image_signature != null) {
                $User->image_signature= $this->uploadImage($request->image_signature, '/images/users_signature/');
            }
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

            return $this->returnErrorData('เกิดข้อผิดพลาด กรุณาลองใหม่อีกครั้ง '.$e, 404);
        }
    }



    public function updateUser (Request $request)
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
           
            $User->email = $request->email;
            $User->branch_id = $request->branch_id;
            $User->department_id = $request->department_id;
            $User->position_id = $request->position_id;
            $User->permission_id = $request->permission_id;


            if ($request->image && $request->image != null && $request->image != 'null') {
                $User->image = $this->uploadImage($request->image, '/images/users/');
            }
            if ($request->image_signature && $request->image_signature != 'null' && $request->image_signature != null) {
                $User->image_signature= $this->uploadImage($request->image_signature, '/images/users_signature/');
            }
            $User->update_by = $loginBy->user_id;
            $User->updated_at = Carbon::now()->toDateTimeString();

            $User->save();

            //log
            $userId = $loginBy->user_id;
            $type = 'แก้ไขเจ้าหน้าที่';
            $description = 'เจ้าหน้าที่ ' . $userId . ' ได้ทำการ ' . $type;
            $this->Log($userId, $description, $type);
            //

            DB::commit();

            return $this->returnUpdate('ดำเนินการสำเร็จ');
        } catch (\Throwable $e) {

            DB::rollback();

            return $this->returnErrorData('เกิดข้อผิดพลาด กรุณาลองใหม่อีกครั้ง '.$e, 404);
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
        } else if (!isset($request->image_signature)) {
            return $this->returnErrorData('กรุณาเพิ่มรูปลายเซ็น', 404);
        }

    else if (!isset($request->department_id)) {
        
        return $this->returnErrorData('กรุณาระบุแผนก', 404);
       
    }

     else if (!isset($request->position_id)) {
         return $this->returnErrorData('กรุณาระบุตำแหน่ง', 404);
     }

     else if (!isset($request->branch_id)) {
        return $this->returnErrorData('กรุณาระบุสาขา', 404);
    }
    else if (!isset($request->permission_id)) {
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


             


            //     //
                // $Branch = new Branch();
                // $Branch->name = 'Bangkok';
                // $Branch->create_by = 'admin';

                // $Branch->save();

               

            //     $Position = new Position();
            //     $Position->name = 'programmer';
            //    // $Position->name = $request->position;
            //    //$Position->employee_id =  $request->user_id;
                
            //     $Position->create_by = 'admin';
            //     $Position->save();

            //     $Department = new Department();
            //     $Department->name = 'SE';
            //     $Department->create_by = 'admin';

            //     $Department->save();

                //
                $User = new User();
                $User->user_id = $request->user_id;
               
                $User->password = md5($request->password);
                $User->first_name = $request->first_name;
                $User->last_name = $request->last_name;
                $User->email = $request->email;
                //$User->code_id = $request->code_id;
                $User->permission_id = $request->permission_id;
                $User->department_id = $request->department_id;
                $User->position_id = $request->position_id;
                $User->branch_id = $request->branch_id;
                $User->image = $this->uploadImage($request->image, '/images/users/');
                $User->image_signature = $this->uploadImage($request->image_signature, '/images/users_signature/');
                $User->status = 1;
                $User->create_by = "admin";

             

                 $User->save();



                //  $Employee_salary = new Employee_salary();
                //  $Employee_salary->user_id = $User->id;
                //  $Employee_salary->first_name = $request->first_name;
                //  $Employee_salary->last_name = $request->last_name;
                //  $Employee_salary->save();
                 //$User->user_id;
                // $User->branch;
                // $User->department;
                // $User->position;
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

        $columns = $request->columns;
        $length = $request->length;
        $order = $request->order;
        $search = $request->search;
        $start = $request->start;
        $page = $start / $length + 1;

        $col = array(
            
  'id' ,
  'branch_id' ,
  'department_id',
  'position_id' ,
  'permission_id' ,
  'user_id' ,
  'password' ,
  'first_name',
  'last_name' ,
  'email' ,
  'image' ,
  'image_signature' ,
  'status' ,
  'create_by' ,
  'update_by' ,
  'created_at' ,
  'updated_at' ,
  'deleted_at' ,

           );

        $d = User::select($col)
     
            ->with('permission')
            ->with('branch')
            ->with('department')
            ->with('position')
           // ->where('status', 'Request')

            ->orderby($col[$order[0]['column']], $order[0]['dir']);



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

                //image
                if ($d[$i]->image) {
                    $d[$i]->image = url($d[$i]->image);
                } else {
                    $d[$i]->image = null;
                }

                //signature
                if ($d[$i]->signature) {
                    $d[$i]->signature = url($d[$i]->signature);
                } else {
                    $d[$i]->signature = null;
                }

            }

        }

        return $this->returnSuccess('Successful', $d);

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

    

  

    // public function registerUser(Request $request)
    // {
    //     if (!isset($request->permission_id)) {
    //         return $this->returnErrorData('[permission_id] ไม่มีข้อมูล', 404);
    //     } else if (!isset($request->user_id)) {
    //         return $this->returnErrorData('[user_id] ไม่มีข้อมูล', 404);
    //     } else if (!isset($request->fname)) {
    //         return $this->returnErrorData('[fname] ไม่มีข้อมูล', 404);
    //     } else if (!isset($request->lname)) {
    //         return $this->returnErrorData('[lname] ไม่มีข้อมูล', 404);
    //     } else if (!isset($request->id_card)) {
    //         return $this->returnErrorData('[id_card] ไม่มีข้อมูล', 404);
    //     } else if (!isset($request->email)) {
    //         return $this->returnErrorData('[email] ไม่มีข้อมูล', 404);
    //     } else if (!isset($request->password)) {
    //         return $this->returnErrorData('[password] ไม่มีข้อมูล', 404);
    //     }

    //     $checkName = User::where(function ($query) use ($request) {
    //         $query->orwhere('email', $request->email)
    //             ->orWhere('user_id', $request->user_id);
    //     })
    //         ->first();

    //     if ($checkName) {
    //         return $this->returnErrorData('มีเจ้าหน้าที่นี้ในระบบแล้ว', 404);

    //     } else {

    //         DB::beginTransaction();

    //         try {

    //             $User = new User();
    //             $User->user_id = $request->user_id;
    //             $User->email = $request->email;
    //             $User->password = md5($request->password);
    //             $User->fname = $request->fname;
    //             $User->lname = $request->lname;
    //             $User->id_card = $request->id_card;
    //             $User->image = $this->uploadImage($request->image, '/images/users/');
    //             $User->status = "Request";

    //             $User->permission_id = $request->permission_id;

    //             $User->command_type_id = $request->command_type_id;
    //             $User->agency_command_id = $request->agency_command_id;
    //             $User->sub_agency_command_id = $request->sub_agency_command_id;
    //             $User->affiliation_id = $request->affiliation_id;
    //             $User->section_work_id = $request->section_work_id;
    //             $User->department_id = $request->department_id;

    //             $User->prefix_type_id = $request->prefix_type_id;
    //             $User->prefix_id = $request->prefix_id;

    //             $User->save();
    //             $User->permission;
    //             $User->command_type;
    //             $User->agency_command;
    //             $User->sub_agency_command;
    //             $User->affiliation;
    //             $User->section_work;
    //             $User->department;
    //             $User->prefix_type;
    //             $User->prefix;

    //             //

    //             DB::commit();

    //             return $this->returnSuccess('Successfully registered  Please wait for admin approval', []);

    //         } catch (\Throwable $e) {

    //             DB::rollback();

    //             return $this->returnErrorData('เกิดข้อผิดพลาด กรุณาลองใหม่อีกครั้ง ' . $e, 404);
    //         }
    //     }
    // }

    // public function ActivateUser(Request $request, $id)
    // {

    //     $loginBy = $request->login_by;

    //     if (!isset($id)) {
    //         return $this->returnErrorData('ไม่พบข้อมูล id', 404);
    //     } else if (!isset($request->status)) {
    //         return $this->returnErrorData('[status] ไม่มีข้อมูล', 404);
    //     } else if (!isset($loginBy)) {
    //         return $this->returnErrorData('ไม่พบข้อมูลเจ้าหน้าที่ กรุณาเข้าสู่ระบบใหม่อีกครั้ง', 404);
    //     }

    //     DB::beginTransaction();

    //     try {

    //         $User = User::find($id);

    //         $User->status = $request->status;
    //         $User->updated_at = Carbon::now()->toDateTimeString();
    //         $User->save();

    //         //log
    //         $userId = $loginBy->user_id;
    //         $type = 'Activate User';
    //         $description = 'User ' . $userId . ' has ' . $type . ' number ' . $User->user_id;
    //         $this->Log($userId, $description, $type);
    //         //

    //         DB::commit();

    //         return $this->returnUpdate('ดำเนินการสำเร็จ');

    //     } catch (\Throwable $e) {

    //         DB::rollback();

    //         return $this->returnErrorData('เกิดข้อผิดพลาด กรุณาลองใหม่อีกครั้ง ' . $e, 404);
    //     }
    // }

    // public function ActivateUserPage(Request $request)
    // {

    //     $columns = $request->columns;
    //     $length = $request->length;
    //     $order = $request->order;
    //     $search = $request->search;
    //     $start = $request->start;
    //     $page = $start / $length + 1;

    //     $col = array('id', 'permission_id', 'department_id', 'position_id', 'user_id', 'name', 'email', 'image', 'signature', 'line_token', 'status', 'create_by', 'update_by', 'created_at', 'updated_at');

    //     $d = User::select($col)
    //         ->with('permission')
    //         ->with('department')
    //         ->with('position')
    //         ->where('status', 'Request')

    //         ->orderby($col[$order[0]['column']], $order[0]['dir']);
    //     if ($search['value'] != '' && $search['value'] != null) {

    //         //search datatable
    //         $d->where(function ($query) use ($search, $col) {
    //             foreach ($col as &$c) {
    //                 $query->orWhere($c, 'like', '%' . $search['value'] . '%');
    //             }

    //         });
    //     }

    //     $d = $d->paginate($length, ['*'], 'page', $page);

    //     if ($d->isNotEmpty()) {

    //         //run no
    //         $No = (($page - 1) * $length);

    //         for ($i = 0; $i < count($d); $i++) {

    //             $No = $No + 1;
    //             $d[$i]->No = $No;

    //             //image
    //             if ($d[$i]->image) {
    //                 $d[$i]->image = url($d[$i]->image);
    //             } else {
    //                 $d[$i]->image = null;
    //             }

    //             //signature
    //             if ($d[$i]->signature) {
    //                 $d[$i]->signature = url($d[$i]->signature);
    //             } else {
    //                 $d[$i]->signature = null;
    //             }

    //         }

    //     }

    //     return $this->returnSuccess('เรียกดูข้อมูลสำเร็จ', $d);

    // }

}
