<?php

namespace App\Http\Controllers;

use App\Models\WorkTime;
use Carbon\Carbon;
use DateTime;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class WorkTimeController extends Controller
{
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
        $position_id = $request->position_id;
        $year = $request->year;
        $timeIn = $request->time_in;
        $timeOut = $request->time_out;

        $loginBy = $request->login_by;

        if (!isset($request->year)) {
            return $this->returnErrorData('[year] Data Not Found', 404);
        } else if (!isset($position_id)) {
            return $this->returnErrorData('[position_id] Data Not Found', 404);
        } else if (!isset($loginBy)) {
            return $this->returnErrorData('[login_by] Data Not Found', 404);
        }

        //check year
        $JobCalendar = WorkTime::where('date', 'like', '%' . $year . '%')
            ->where('position_id', $position_id)
            ->first();
        if ($JobCalendar) {
            return $this->returnErrorData('There is this year  information in the system', 404);
        }

        $countMonth = ['01', '02', '03', '04', '05', '06', '07', '08', '09', '10', '11', '12'];

        //check 366 day
        $isLeap = DateTime::createFromFormat('Y', $year)->format('L') === "1";

        $arrDay = [];

        if ($isLeap) {
            //366 day
            $countDay = ['31', '29', '31', '30', '31', '30', '31', '31', '30', '31', '30', '31'];

            for ($i = 0; $i < count($countMonth); $i++) {

                for ($j = 0; $j < intval($countDay[$i]); $j++) {

                    $day = $j + 1;

                    if (strlen($day) == 1) {
                        $day = '0' . $day;
                    }

                    $arrDay[] = $year . '-' . $countMonth[$i] . '-' . $day;
                }
            }
        } else {
            //365 day
            $countDay = ['31', '28', '31', '30', '31', '30', '31', '31', '30', '31', '30', '31'];

            for ($i = 0; $i < count($countMonth); $i++) {

                for ($j = 0; $j < intval($countDay[$i]); $j++) {

                    $day = $j + 1;

                    if (strlen($day) == 1) {
                        $day = '0' . $day;
                    }

                    $arrDay[] = $year . '-' . $countMonth[$i] . '-' . $day;
                }
            }
        }

        DB::beginTransaction();

        try {

            for ($i = 0; $i < count($arrDay); $i++) {

                //Job calendar
                $Job_calendar = new WorkTime();
                $Job_calendar->position_id = $position_id;
                $Job_calendar->date = $arrDay[$i];
                $Job_calendar->time_in = $timeIn;
                $Job_calendar->time_out = $timeOut;
                $Job_calendar->description = null;

                if (date('D', strtotime($arrDay[$i])) != 'Sun') {
                    $Job_calendar->type = 'Work';
                } else {
                    $Job_calendar->type = 'Holiday';
                }

                $Job_calendar->create_by = $loginBy->user_id;

                $Job_calendar->save();
            }

            //log
            $userId = $loginBy->user_id;
            $type = 'Add Calendar';
            $description = 'User ' . $userId . ' has ' . $type . ' ' . $year;
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
     * @param  \App\Models\WorkTime  $workTime
     * @return \Illuminate\Http\Response
     */
    public function show($id)
    {
        $Working_time = WorkTime::find($id);
        return $this->returnSuccess('Successful', $Working_time);
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  \App\Models\WorkTime  $workTime
     * @return \Illuminate\Http\Response
     */
    public function edit(WorkTime $workTime)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \App\Models\WorkTime  $workTime
     * @return \Illuminate\Http\Response
     */

    public function update(Request $request, $id)
    {
    }

    public function WorkingTimeType(Request $request)
    {
        $loginBy = $request->login_by;

        if (!isset($loginBy)) {
            return $this->returnErrorData('[login_by] Data Not Found', 404);
        }

        $date = $request->date;
        $position_id = $request->position_id;
        $description = $request->description;

        $Working_time = WorkTime::where('date', '=', $date)
            ->where('position_id', $position_id)
            ->first();

        if (!$Working_time) {
            return $this->returnErrorData('ไม่พบวันที่ ที่กำหนด', 404);
        } else {

            DB::beginTransaction();

            try {

                $Working_time->type = $request->type;
                $Working_time->time_in = $request->time_in;
                $Working_time->time_out = $request->time_out;
                $Working_time->description = $request->description;

                $Working_time->update_by = $loginBy->user_id;
                $Working_time->updated_at = Carbon::now()->toDateTimeString();

                $Working_time->save();

                //log
                $userId = $loginBy->user_id;
                $type = 'Edit Working Time';
                $description = 'User ' . $userId . ' has ' . $type . ' ' . $Working_time->date;
                $this->Log($userId, $description, $type);
                //

                DB::commit();

                return $this->returnUpdate('Successful operation');
            } catch (\Throwable $e) {

                DB::rollback();

                return $this->returnErrorData('Something went wrong Please try again ' . $e, 404);
            }
        }
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  \App\Models\WorkTime  $workTime
     * @return \Illuminate\Http\Response
     */
    public function destroy(Request $request, $id)
    {
        //
    }

    public function WorkingTimePage(Request $request)
    {

        $columns = $request->columns;
        $length = $request->length;
        $order = $request->order;
        $search = $request->search;
        $start = $request->start;
        $page = $start / $length + 1;

        $col = array('id', 'date', 'time_in', 'time_out', 'description', 'status', 'create_by', 'update_by', 'created_at', 'updated_at');

        $d = WorkTime::select($col)
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

    public function getWorkingTime(Request $request)
    {

        $position_id = $request->position_id;
        $year = $request->year;

        $Working_time = WorkTime::where('date', 'like', '%' . $year . '%')
            ->where('position_id', $position_id)
            ->get();


        if (!empty($Working_time)) {

            for ($i = 0; $i < count($Working_time); $i++) {
                $Working_time[$i]['No'] = $i + 1;
            }
        }

        return $this->returnSuccess('Successful', $Working_time);
    }

    public function deleteWorkTime(Request $request)
    {
        $loginBy = $request->login_by;

        $position_id = $request->position_id;
        $year = $request->year;

        if (!isset($loginBy)) {
            return $this->returnErrorData('[login_by] Data Not Found', 404);
        }

        DB::beginTransaction();

        try {

            $Working_time = WorkTime::where('date', 'like', '%' . $year . '%')
                ->where('position_id', $position_id)
                ->delete();


            //log
            $userId = $loginBy->user_id;
            $type = 'Delete Working Time';
            $description = 'User ' . $userId . ' has ' . $type;
            $this->Log($userId, $description, $type);
            //


            DB::commit();

            return $this->returnUpdate('Successful operation');
        } catch (\Throwable $e) {

            DB::rollback();

            return $this->returnErrorData('Something went wrong Please try again ' . $e, 404);
        }
    }
}
