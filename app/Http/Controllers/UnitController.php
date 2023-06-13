<?php

namespace App\Http\Controllers;

use App\Imports\UnitImport;
use App\Models\Unit;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Maatwebsite\Excel\Facades\Excel;

class UnitController extends Controller
{

    public function getUnit()
    {
        $Unit = Unit::where('status', 1)->get()->toarray();

        if (!empty($Unit)) {

            for ($i = 0; $i < count($Unit); $i++) {
                $Unit[$i]['No'] = $i + 1;

            }
        }

        return $this->returnSuccess('Successful', $Unit);
    }

    public function UnitPage(Request $request)
    {

        $columns = $request->columns;
        $length = $request->length;
        $order = $request->order;
        $search = $request->search;
        $start = $request->start;
        $page = $start / $length + 1;

        $col = array('id', 'name', 'description', 'status', 'create_by', 'update_by', 'created_at', 'updated_at');

        $d = Unit::select($col)

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
            return $this->returnErrorData('[name] Data Not Found', 404);
        } else if (!isset($request->description)) {
            return $this->returnErrorData('[description] Data Not Found', 404);
        } else if (!isset($loginBy)) {
            return $this->returnErrorData('[login_by] Data Not Found', 404);
        }

        $name = $request->name;
        $description = $request->description;

        $checkName = Unit::where(function ($query) use ($name, $description) {
            $query->orwhere('name', $name)
                ->orWhere('description', $description);
        })
            ->first();

        if ($checkName) {
            return $this->returnErrorData('There is already this name in the system', 404);

        } else {

            DB::beginTransaction();

            try {

                $Unit = new Unit();
                $Unit->name = $name;
                $Unit->description = $description;
                $Unit->status = 1;

                $Unit->create_by = $loginBy->user_id;

                $Unit->save();

                //log
                $userId = $loginBy->user_id;
                $type = 'Add Unit';
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
        $Unit = Unit::find($id);
        return $this->returnSuccess('Successful', $Unit);
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
        $description = $request->description;
        //dd($request->description);

        $checkName = Unit::where('id', '!=', $id)
            ->where(function ($query) use ($name, $description) {
                $query->orwhere('name', $name)
                    ->orWhere('description', $description);
            })
            ->first();

        if ($checkName) {
            return $this->returnErrorData('There is already this name in the system', 404);

        } else {

            DB::beginTransaction();

            try {

                $Unit = Unit::find($id);
                $Unit->name = $name;
                $Unit->description = $description;
                $Unit->status = $request->status;

                $Unit->update_by = $loginBy->user_id;
                $Unit->updated_at = Carbon::now()->toDateTimeString();

                $Unit->save();

                //log
                $userId = $loginBy->user_id;
                $type = 'Edit Unit';
                $description = 'User ' . $userId . ' has ' . $type . ' ' . $Unit->name;
                $this->Log($userId, $description, $type);
                //

                DB::commit();

                return $this->returnUpdate('Successful operation');

            } catch (\Throwable $e) {

                DB::rollback();

                return $this->returnErrorData('Something went wrong Please try again' . $e, 404);
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

            $Unit = Unit::find($id);

            //log
            $userId = $loginBy->user_id;
            $type = 'Delete Unit';
            $description = 'User ' . $userId . ' has ' . $type . ' ' . $Unit->name;
            $this->Log($userId, $description, $type);
            //

            $Unit->delete();

            DB::commit();

            return $this->returnUpdate('Successful operation');

        } catch (\Throwable $e) {

            DB::rollback();

            return $this->returnErrorData('Something went wrong Please try again ' . $e, 404);
        }
    }

    public function ImportUnit(Request $request)
    {
        $loginBy = $request->login_by;

        if (!isset($loginBy)) {
            return $this->returnErrorData('[login_by] Data Not Found', 404);
        }

        $file = request()->file('file');
        $fileName = $file->getClientOriginalName();

        $Data = Excel::toArray(new UnitImport(), $file);
        $data = $Data[0];

        if (count($data) > 0) {

            $insert_data = [];

            for ($i = 0; $i < count($data); $i++) {

                $name = trim($data[$i]['name']);
                $description = trim($data[$i]['description']);

                $row = $i + 2;

                if ($name == '') {
                    return $this->returnErrorData('Row excel data ' . $row . ' please enter name', 404);
                } else if ($description == '') {
                    return $this->returnErrorData('Row excel data ' . $row . ' Please enter description', 404);
                }

                //check row sample
                if ($name == 'SIMPLE-000') {
                    //
                } else {

                    //check dupicate data form file import
                    for ($j = 0; $j < count($insert_data); $j++) {

                        if ($name == $insert_data[$j]['name']) {
                            return $this->returnErrorData('Name ' . $name . ' There is duplicate data in the import file', 404);
                        }
                    }
                    ///

                    $insert_data[] = array(
                        'name' => $name,
                        'description' => $description,
                        'status' => 1,
                        'create_by' => $loginBy->user_id,
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

                        DB::table('unit')
                            ->updateOrInsert(
                                [
                                    'id' => trim($data[$i]['id']), //id
                                ],
                                $insert_data[$i]
                            );
                    }
                    //

                    //log
                    $userId = $loginBy->user_id;
                    $type = 'Import Unit';
                    $description = 'User ' . $userId . ' has ' . $type;
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
