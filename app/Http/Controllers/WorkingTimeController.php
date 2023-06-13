<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\WorkingTime;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Maatwebsite\Excel\Facades\Excel;

class WorkingTimeController extends Controller
{

    public function getList()
    {
        $Item = WorkingTime::get()->toarray();

        if (!empty($Item)) {

            for ($i = 0; $i < count($Item); $i++) {
                $Item[$i]['No'] = $i + 1;
                $Item[$i]['path'] = url($Item[$i]['path']);
            }
        }

        return $this->returnSuccess('เรียกดูข้อมูลสำเร็จ', $Item);
    }

    public function getUserList($id)
    {
        $Item = WorkingTime::where('user_id', $id)->get();

        if (!empty($Item)) {

            for ($i = 0; $i < count($Item); $i++) {
                $Item[$i]['No'] = $i + 1;
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

        $user_id = $request->user_id;
        $month = $request->month;
        $year = $request->year;

        $col = array('id', 'user_id', 'date', 'machine', 'create_by', 'update_by', 'created_at', 'updated_at');

        $orderby = array('', 'user_id', 'date', 'machine', 'create_by');

        $D = WorkingTime::select($col);

        if ($user_id) {
            $D->where('user_id', $user_id);
        }

        if ($month) {
            $D->where('date', 'like', '%-' . $month . '-%');
        }

        if ($year) {
            $D->where('date', 'like', '%' . $year . '-%');
        }

        $D->groupBy('date');


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
                $d[$i]->time_start = WorkingTime::where('date', $d[$i]->date)->where('user_id',$d[$i]->user_id)->orderBy('time','asc')->first();
                $d[$i]->time_end = WorkingTime::where('date', $d[$i]->date)->where('user_id',$d[$i]->user_id)->orderBy('time','desc')->first();
                $d[$i]->user = User::where('id', $d[$i]->user_id)->get();
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
            return $this->returnErrorData('กรุณาใส่ user_id', 404);
        } else if (!isset($request->date)) {
            return $this->returnErrorData('กรุณาใส่ date', 404);
        } else if (!isset($request->time)) {
            return $this->returnErrorData('กรุณาใส่ time', 404);
        } else if (!isset($request->machine)) {
            return $this->returnErrorData('กรุณาใส่ machine ด้วย', 404);
        } else if (!isset($loginBy)) {
            return $this->returnErrorData('[login_by] Data Not Found', 404);
        }

        $check = User::where('user_id', $request->user_id)->first();
        if (!$check) {
            return $this->returnErrorData('ไม่มีชื่อ ' . $request->user_id . ' ในระบบ', 404);
        }

        DB::beginTransaction();

        try {

            $Item = new WorkingTime();
            $Item->user_id = $request->user_id;
            $Item->date = $request->date;
            $Item->time = $request->time;
            $Item->machine = $request->machine;

            $Item->create_by = $loginBy->user_id;


            $Item->save();

            //log
            $userId = $loginBy->user_id;
            $type = 'Add Item';
            $description = 'User ' . $userId . ' has ' . $type;
            $this->Log($userId, $description, $type);
            //

            DB::commit();

            return $this->returnSuccess('Successful operation', []);
        } catch (\Throwable $e) {

            DB::rollback();

            return $this->returnErrorData('Something went wrong Please try again' . $e, 404);
        }
    }

    /**
     * Display the specified resource.
     *
     * @param  \App\Models\WorkingTime  $workingTime
     * @return \Illuminate\Http\Response
     */
    public function show($id)
    {
        $Item = WorkingTime::find($id);

        return $this->returnSuccess('Successful', $Item);
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  \App\Models\WorkingTime  $workingTime
     * @return \Illuminate\Http\Response
     */
    public function edit(WorkingTime $workingTime)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \App\Models\WorkingTime  $workingTime
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, $date)
    {
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  \App\Models\WorkingTime  $workingTime
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

            $Item = WorkingTime::find($id);

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


    public function Import(Request $request)
    {
        ini_set('memory_limit', '4048M');

        $loginBy = $request->login_by;

        if (!isset($loginBy)) {
            return $this->returnErrorData('ไม่พบข้อมูลผู้ใช้งาน กรุณาเข้าสู่ระบบใหม่อีกครั้ง', 404);
        }


        $file = request()->file('file');

        $Data = Excel::toArray(new WorkingTime(), $file);

        $data = $Data[0];

        if (count($data) > 0) {

            $insert_data = [];


            for ($i = 1; $i < count($data); $i++) {
                $insert_data[] = array(
                    'user_id' => trim($data[$i][0]),
                    'date' => trim($data[$i][1]),
                    'time' => trim($data[$i][2]),
                    'machine' => trim($data[$i][3]),
                );
            }
        }

        if (!empty($insert_data)) {

            DB::beginTransaction();

            try {

                DB::table('working_times')->insert($insert_data);

                //log
                $userId = $loginBy->id;
                $type = 'นำเข้าข้อมูล';
                $description = 'ผู้ใช้งาน ' . $userId . ' ได้ทำการ ' . $type;
                $this->Log($userId, $description, $type);
                //

                DB::commit();

                return $this->returnSuccess('นำเข้าข้อมูลสำเร็จ', []);
            } catch (\Throwable $e) {

                DB::rollback();

                return $this->returnErrorData('นำเข้าข้อมูลผิดพลาด ', 404);
            }
        }
    }
}
