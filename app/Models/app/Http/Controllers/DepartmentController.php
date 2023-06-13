<?php

namespace App\Http\Controllers;

use App\Imports\DepartmentImport;
use App\Models\Department;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Maatwebsite\Excel\Facades\Excel;

class DepartmentController extends Controller
{

    public function getDepartment()
    {
        $Department = Department::where('status', 1)->with('user_create')->get()->toarray();

        if (!empty($Department)) {

            for ($i = 0; $i < count($Department); $i++) {
                $Department[$i]['No'] = $i + 1;

            }
        }

        return $this->returnSuccess('Successful', $Department);
    }

    public function DepartmentPage(Request $request)
    {

        $columns = $request->columns;
        $length = $request->length;
        $order = $request->order;
        $search = $request->search;
        $start = $request->start;
        $page = $start / $length + 1;

        $col = array('id', 'name', 'status', 'create_by', 'update_by', 'created_at', 'updated_at');

        $d = Department::select($col)->with('user_create')
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
            return $this->returnErrorData('กรุณาใส่ชื่อแผนก', 404);
        } else if (!isset($loginBy)) {
            return $this->returnErrorData('[login_by] Data Not Found', 404);
        }
        $name = $request->name;

        $checkName = Department::where('name', $name)->first();

        if ($checkName) {
            return $this->returnErrorData('There is already this name in the system', 404);

        } else {

            DB::beginTransaction();

            try {

                $Department = new Department();
                $Department->name = $name;
                $Department->status = 1;

                $Department->create_by = $loginBy->user_id;

                $Department->save();

                //log
                $userId = $loginBy->user_id;
                $type = 'Add Department';
                $description = 'User ' . $userId . ' has ' . $type . ' ' . $name;
                $this->Log($userId, $description, $type);
                //

                DB::commit();

                return $this->returnSuccess('Successful operation', []);

            } catch (\Throwable $e) {

                DB::rollback();

                return $this->returnErrorData('Something went wrong Please try again ' . $e, 404);
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
        $Department = Department::find($id);
        return $this->returnSuccess('Successful', $Department);
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

        if (!isset($loginBy)) {
            return $this->returnErrorData('[login_by] Data Not Found', 404);
        }

        $name = $request->name;

        $checkName = Department::where('id', '!=', $id)
            ->where('name', $name)
            ->first();

        if ($checkName) {
            return $this->returnErrorData('There is already this name in the system', 404);

        } else {

            DB::beginTransaction();

            try {

                $Department = Department::find($id);

                $Department->name = $name;
                $Department->status = $request->status;

                $Department->update_by = $loginBy->user_id;
                $Department->updated_at = Carbon::now()->toDateTimeString();

                $Department->save();

                //log
                $userId = $loginBy->user_id;
                $type = 'Edit Department';
                $description = 'User ' . $userId . ' has ' . $type . ' ' . $Department->name;
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
     * @param  int  $id
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

            $Department = Department::find($id);

            //log
            $userId = $loginBy->user_id;
            $type = 'Delete Department';
            $description = 'User ' . $userId . ' has ' . $type . ' ' . $Department->name;
            $this->Log($userId, $description, $type);
            //

            $Department->delete();

            DB::commit();

            return $this->returnUpdate('Successful operation');

        } catch (\Throwable $e) {

            DB::rollback();

            return $this->returnErrorData('Something went wrong Please try again ' . $e, 404);
        }
    }

    public function ImportDepartment(Request $request)
    {

        $loginBy = $request->login_by;

        if (!isset($loginBy)) {
            return $this->returnErrorData('User information not found. Please login again', 404);
        }

        $file = request()->file('file');
        $fileName = $file->getClientOriginalName();

        $Data = Excel::toArray(new DepartmentImport(), $file);
        $data = $Data[0];

        if (count($data) > 0) {

            $insert_data = [];

            for ($i = 0; $i < count($data); $i++) {

                $name = trim($data[$i]['name']);

                $row = $i + 2;

                if ($name == '') {
                    return $this->returnErrorData('Row excel data ' . $row . 'please enter name', 404);
                }

                //check row sample
                if ($name == 'SIMPLE-000') {
                    //
                } else {

                    // //check name
                    // $Department = Department::where('name', $name)->first();
                    // if ($Department) {
                    //     return $this->returnErrorData('Department ' . $name . ' was information information is already in the system', 404);
                    // }

                    //check dupicate data form file import
                    for ($j = 0; $j < count($insert_data); $j++) {

                        if ($name == $insert_data[$j]['name']) {
                            return $this->returnErrorData('Department ' . $name . ' There is duplicate data in the import file', 404);
                        }
                    }
                    ///

                    $insert_data[] = array(
                        'name' => $name,
                        'status' => 1,
                        'created_at' => date('Y-m-d H:i:s'),
                        'updated_at' => date('Y-m-d H:i:s'),
                    );
                }

            }

            if (!empty($insert_data)) {

                DB::beginTransaction();

                try {

                    //updateOrInsert
                    for ($i = 0; $i < count($insert_data); $i++) {

                        DB::table('department')
                            ->updateOrInsert(
                                [
                                    'id' => trim($data[$i]['id']), //id
                                ],
                                $insert_data[$i]
                            );
                    }
                    //

                    DB::commit();

                    //log
                    $userId = $loginBy->user_id;
                    $type = 'Import Department';
                    $description = 'User ' . $userId . ' has ' . $type . ' ' . $name;
                    $this->Log($userId, $description, $type);
                    //

                    DB::commit();

                    return $this->returnSuccess('Successful operation', []);

                } catch (\Throwable $e) {

                    DB::rollback();

                    return $this->returnErrorData('Something went wrong Please try again ' . $e, 404);
                }

            } else {
                return $this->returnErrorData('Data Not Found', 404);
            }

        } else {
            return $this->returnErrorData('Data Not Found', 404);
        }
    }
}
