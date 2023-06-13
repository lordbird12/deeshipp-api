<?php

namespace App\Http\Controllers;

use App\Imports\UnitConvertionImport;
use App\Models\Unit;
use App\Models\Unit_convertion;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Maatwebsite\Excel\Facades\Excel;

class UnitConvertionController extends Controller
{
    public function getUnitConvertion(Request $request)
    {

        $unitId = $request->unit_id;

        if (!isset($request->unit_id)) {
            return $this->returnErrorData('[unit_id] Data Not Found', 404);
        }

        $Unit_convertion = Unit_convertion::with('unit')
            ->where('unit_id', $unitId)
            ->where('status', 1)
            ->get()
            ->toarray();

        if (!empty($Unit_convertion)) {

            for ($i = 0; $i < count($Unit_convertion); $i++) {
                $Unit_convertion[$i]['No'] = $i + 1;

            }
        }

        return $this->returnSuccess('Successful', $Unit_convertion);
    }

    public function getAllUnitConvertion()
    {

        $Unit_convertion = Unit_convertion::with('unit')
            ->where('status', 1)
            ->get()
            ->toarray();

        if (!empty($Unit_convertion)) {

            for ($i = 0; $i < count($Unit_convertion); $i++) {
                $Unit_convertion[$i]['No'] = $i + 1;

            }
        }

        return $this->returnSuccess('Successful', $Unit_convertion);
    }

    public function UnitConvertionPage(Request $request)
    {

        $unitId = $request->unit_id;

        $columns = $request->columns;
        $length = $request->length;
        $order = $request->order;
        $search = $request->search;
        $start = $request->start;
        $page = $start / $length + 1;

        $col = array('id', 'unit_id', 'name', 'description', 'value', 'status', 'create_by', 'update_by', 'created_at', 'updated_at');

        $D = Unit_convertion::select($col)
            ->with('unit');

        if ($unitId) {
            $D->where('unit_id', $unitId);
        }

        $d = $D->orderby($col[$order[0]['column']], $order[0]['dir']);
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

        if (!isset($request->unit_id)) {
            return $this->returnErrorData('[unit_id] Data Not Found', 404);
        } else if (!isset($request->name)) {
            return $this->returnErrorData('[name] Data Not Found', 404);
        } else if (!isset($request->description)) {
            return $this->returnErrorData('[description] Data Not Found', 404);
        } else if (!isset($request->value)) {
            return $this->returnErrorData('[value] Data Not Found', 404);
        } else if (!isset($loginBy)) {
            return $this->returnErrorData('[login_by] Data Not Found', 404);
        }

        $name = $request->name;
        $description = $request->description;
        $unitId = $request->unit_id;

        $checkName = Unit_convertion::where(function ($query) use ($name, $description) {
            $query->orwhere('name', $name)
                ->orWhere('description', $description);
        })
            ->where('unit_id', $unitId)
            ->first();

        if ($checkName) {
            return $this->returnErrorData('There is already this name in the system', 404);

        } else {

            DB::beginTransaction();

            try {

                $Unit_convertion = new Unit_convertion();
                $Unit_convertion->name = $name;
                $Unit_convertion->description = $description;
                $Unit_convertion->value = $request->value;
                $Unit_convertion->create_by = $loginBy->user_id;
                $Unit_convertion->status = 1;

                $Unit_convertion->unit_id = $unitId;

                $Unit_convertion->save();
                $Unit_convertion->unit;

                //log
                $userId = $loginBy->user_id;
                $type = 'Add Unit Convertion';
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
        $Unit_convertion = Unit_convertion::with('unit')
            ->find($id);

        return $this->returnSuccess('Successful', $Unit_convertion);
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
        $unitId = $request->unit_id;

        $checkName = Unit_convertion::where('id', '!=', $id)
            ->where(function ($query) use ($name, $description) {
                $query->orwhere('name', $name)
                    ->orWhere('description', $description);
            })
            ->where('unit_id', $unitId)
            ->first();

        if ($checkName) {
            return $this->returnErrorData('There is already this name in the system', 404);

        } else {

            DB::beginTransaction();

            try {

                $Unit_convertion = Unit_convertion::find($id);
                $Unit_convertion->name = $name;
                $Unit_convertion->description = $description;
                $Unit_convertion->value = $request->value;
                $Unit_convertion->status = $request->status;

                $Unit_convertion->update_by = $loginBy->user_id;
                $Unit_convertion->updated_at = Carbon::now()->toDateTimeString();

                $Unit_convertion->unit_id = $unitId;

                $Unit_convertion->save();
                $Unit_convertion->unit;

                //log
                $userId = $loginBy->user_id;
                $type = 'Edit Unit Convertion';
                $description = 'User ' . $userId . ' has ' . $type . ' ' . $Unit_convertion->name;
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

            $Unit_convertion = Unit_convertion::find($id);

            //log
            $userId = $loginBy->user_id;
            $type = 'Delete Unit Convertion';
            $description = 'User ' . $userId . ' has ' . $type . ' ' . $Unit_convertion->name;
            $this->Log($userId, $description, $type);
            //

            $Unit_convertion->delete();

            DB::commit();

            return $this->returnUpdate('Successful operation');

        } catch (\Throwable $e) {

            DB::rollback();

            return $this->returnErrorData('Something went wrong Please try again ' . $e, 404);
        }
    }

    public function ImportUnitConvertion(Request $request)
    {
        $loginBy = $request->login_by;

        if (!isset($loginBy)) {
            return $this->returnErrorData('[login_by] Data Not Found', 404);
        }

        $file = request()->file('file');
        $fileName = $file->getClientOriginalName();

        $Data = Excel::toArray(new UnitConvertionImport(), $file);
        $data = $Data[0];

        if (count($data) > 0) {

            $insert_data = [];

            for ($i = 0; $i < count($data); $i++) {

                $unitid = trim($data[$i]['unitid']);
                $name = trim($data[$i]['name']);
                $description = trim($data[$i]['description']);
                $value = trim($data[$i]['value']);

                $row = $i + 2;

                if ($unitid == '') {
                    return $this->returnErrorData('Row excel data ' . $row . ' please enter unit id', 404);
                } else if ($name == '') {
                    return $this->returnErrorData('Row excel data ' . $row . ' please enter name', 404);
                } else if ($description == '') {
                    return $this->returnErrorData('Row excel data ' . $row . ' Please enter description', 404);
                } else if ($value == '') {
                    return $this->returnErrorData('Row excel data ' . $row . ' Please enter value', 404);
                }

                //check row sample
                if ($name == '0') {
                    //
                } else {

                    //check unit id
                    $checkUnit = Unit::where('id', $unitid)->first();
                    if (!$checkUnit) {
                        return $this->returnErrorData('Unit Id' . $unitid . '  was not found in the system', 404);
                    }

                    $unitId = $checkUnit->id;

                    $insert_data[] = array(
                        'unit_id' => $unitId,
                        'name' => $name,
                        'description' => $description,
                        'value' => intval($value),

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

                        DB::table('unit_convertion')
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
                    $type = 'Import Unit Convertion';
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
