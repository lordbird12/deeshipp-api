<?php

namespace App\Http\Controllers;

use App\Models\Employee_salary;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class EmployeeSalaryController extends Controller
{

    public function update(Request $request, $id)
    {
       
        $loginBy = $request->login_by;

        if (!isset($request->user_id)) {
            return $this->returnErrorData('ไม่พบข้อมูล user_id', 404);
        } else if (!isset($loginBy)) {
            return $this->returnErrorData('ไม่พบข้อมูลเจ้าหน้าที่ กรุณาเข้าสู่ระบบใหม่อีกครั้ง', 404);
        }

        DB::beginTransaction();

        try {

          //  $id = $request->id;
            $Employee_salary = Employee_salary::find($id);

            

            $Employee_salary->user_id = $request->user_id;
            $Employee_salary->hire_date = $request->hire_date;
            $Employee_salary->salary = $request->salary;
            $Employee_salary->commission = $request->commission;
            $Employee_salary->total = $request->total;
            //$Bank->update_by = $loginBy->user_id;
            $Employee_salary->updated_at = Carbon::now()->toDateTimeString();
            $Employee_salary->save();

            //log
            $userId = $loginBy->user_id;
            $type = 'แก้ไขเงินเดือน';
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


    public function salaryPage(Request $request)
    {

        $columns = $request->columns;
        $length = $request->length;
        $order = $request->order;
        $search = $request->search;
        $start = $request->start;
        $page = $start / $length + 1;

        $col = array('id', 'user_id','hire_date','salary','commission' ,'total', 'created_at', 'updated_at');

        $d = Employee_salary::select($col)
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

            }

        }

        return $this->returnSuccess('Successful', $d);
    }



    public function show($id)
    {

        if (!isset($id)) {
            return $this->returnErrorData('[id] Data Not Found', 404);
        }

        $Employee_salary = Employee_salary:: 

           // ->with('location')

            find($id);
  

        return $this->returnSuccess('Successful', $Employee_salary);
    }


    public function getsalary()
    {
       
        $Bank = Employee_salary::get()->toarray();

        if (!empty($Bank)) {

            for ($i = 0; $i < count($Bank); $i++) {
                $Bank[$i]['No'] = $i + 1;

            }
        }

        return $this->returnSuccess('Successful', $Bank);
    }



    public function store(Request $request)
    {
        $loginBy = $request->login_by;

        if (!isset($request->user_id)) {
            return $this->returnErrorData('กรุณาเลือกชื่อ', 404);
        } else if (!isset($request->hire_date)) {
            return $this->returnErrorData('กรุณาใส่วันเริ่มทำงาน', 404);
        } else if (!isset($request->salary)) {
            return $this->returnErrorData('กรุณาระบุเงินเดือน', 404);
        } else if (!isset($request->commission)) {
            return $this->returnErrorData('กรุณาใส่ค่า commission', 404);
        }
            else if (!isset($request->total)) {
                return $this->returnErrorData('กรุณาใส่ยอดรวม', 404);
     
        } else if (!isset($loginBy)) {
            return $this->returnErrorData('[login_by] Data Not Found', 404);
        }


            DB::beginTransaction();

            try {

                $Employee_salary = new Employee_salary();
                $Employee_salary->user_id = $request->user_id;
                $Employee_salary->hire_date = $request->hire_date;
                $Employee_salary->salary = $request->salary;
                $Employee_salary->commission = $request->commission;
                $Employee_salary->total = $request->total;
                $Employee_salary->create_by = $loginBy->user_id;

                $Employee_salary->save();

                //log
                $userId = $loginBy->user_id;
                $type = 'Add Salary';
                $description = 'User ' . $userId . ' has ' . $type . ' ' ;
                $this->Log($userId, $description, $type);
                //

                 DB::commit();

                return $this->returnSuccess('Successful operation', []);
            } catch (\Throwable $e) {

                DB::rollback();

                return $this->returnErrorData('Something went wrong Please try again' . $e, 404);
            }
        }
    }

