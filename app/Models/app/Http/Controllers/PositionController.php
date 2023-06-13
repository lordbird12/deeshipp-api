<?php

namespace App\Http\Controllers;

use App\Imports\PositionImport;
use App\Models\Position;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Maatwebsite\Excel\Facades\Excel;

class PositionController extends Controller
{

    public function getPosition()
    {
        $Position = Position::where('status', 1)->with('user_create')->get()->toarray();

        if (!empty($Position)) {

            for ($i = 0; $i < count($Position); $i++) {
                $Position[$i]['No'] = $i + 1;

            }
        }

        return $this->returnSuccess('Successful', $Position);
    }

    public function PositionPage(Request $request)
    {

        $columns = $request->columns;
        $length = $request->length;
        $order = $request->order;
        $search = $request->search;
        $start = $request->start;
        $page = $start / $length + 1;

        $col = array('id', 'name', 'status', 'create_by', 'update_by', 'created_at', 'updated_at');

        $d = Position::select($col)->with('user_create')

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
            return $this->returnErrorData('กรุณาใส่ชื่อตำแหน่ง', 404);
        } else if (!isset($loginBy)) {
            return $this->returnErrorData('[login_by] Data Not Found', 404);
        }

        $name = $request->name;

        $checkName = Position::where('name', $name)->first();

        if ($checkName) {
            return $this->returnErrorData('There is already this name in the system', 404);

        } else {

            DB::beginTransaction();

            try {

                $Position = new Position();
                $Position->name = $name;
                $Position->status = 1;

                $Position->create_by = $loginBy->user_id;
                $Position->save();

                //log
                $userId = $loginBy->user_id;
                $type = 'Add Position';
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
        $Position = Position::find($id)->with('user_create')->get();
        return $this->returnSuccess('Successful', $Position);
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

        $checkName = Position::where('id', '!=', $id)
            ->where('name', $name)
            ->first();

        if ($checkName) {
            return $this->returnErrorData('There is already this name in the system', 404);

        } else {

            DB::beginTransaction();

            try {

                $Position = Position::find($id);
                $Position->name = $name;
                $Position->status = $request->status;

                $Position->update_by = $loginBy->user_id;
                $Position->updated_at = Carbon::now()->toDateTimeString();
                $Position->save();

                //log
                $userId = $loginBy->user_id;
                $type = 'Edit Position';
                $description = 'User ' . $userId . ' has ' . $type . ' ' . $Position->name;
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

            $Position = Position::find($id);

            //log
            $userId = $loginBy->user_id;
            $type = 'Delete Position';
            $description = 'User ' . $userId . ' has ' . $type . ' ' . $Position->name;
            $this->Log($userId, $description, $type);
            //

            $Position->delete();

            DB::commit();

            return $this->returnUpdate('Successful operation');

        } catch (\Throwable $e) {

            DB::rollback();

            return $this->returnErrorData('Something went wrong Please try again ' . $e, 404);
        }
    }

    // public function ImportPosition(Request $request)
    // {

    //     $loginBy = $request->login_by;

    //     if (!isset($loginBy)) {
    //         return $this->returnErrorData('User information not found. Please login again', 404);
    //     }

    //     $file = request()->file('file');
    //     $fileName = $file->getClientOriginalName();

    //     $Data = Excel::toArray(new PositionImport(), $file);
    //     $data = $Data[0];

    //     if (count($data) > 0) {

    //         $insert_data = [];

    //         for ($i = 0; $i < count($data); $i++) {

    //             $name = trim($data[$i]['name']);

    //             $row = $i + 2;

    //             if ($name == '') {
    //                 return $this->returnErrorData('Row excel data ' . $row . 'please enter name', 404);
    //             }

    //             //check row sample
    //             if ($name == 'SIMPLE-000') {
    //                 //
    //             } else {

    //                 // //check name
    //                 // $Position = Position::where('name', $name)->first();
    //                 // if ($Position) {
    //                 //     return $this->returnErrorData('Position ' . $name . ' was information information is already in the system', 404);
    //                 // }

    //                 //check dupicate data form file import
    //                 for ($j = 0; $j < count($insert_data); $j++) {

    //                     if ($name == $insert_data[$j]['name']) {
    //                         return $this->returnErrorData('Position ' . $name . ' There is duplicate data in the import file', 404);
    //                     }
    //                 }
    //                 ///

    //                 $insert_data[] = array(
    //                     'name' => $name,
    //                     'status' => 1,
    //                     'created_at' => date('Y-m-d H:i:s'),
    //                     'updated_at' => date('Y-m-d H:i:s'),
    //                 );
    //             }

    //         }

    //         if (!empty($insert_data)) {

    //             DB::beginTransaction();

    //             try {

    //                 //updateOrInsert
    //                 for ($i = 0; $i < count($insert_data); $i++) {

    //                     DB::table('position')
    //                         ->updateOrInsert(
    //                             [
    //                                 'id' => trim($data[$i]['id']), //id
    //                             ],
    //                             $insert_data[$i]
    //                         );
    //                 }
    //                 //

    //                 DB::commit();

    //                 //log
    //                 $userId = $loginBy->user_id;
    //                 $type = 'Import Position';
    //                 $description = 'User ' . $userId . ' has ' . $type . ' ' . $name;
    //                 $this->Log($userId, $description, $type);
    //                 //

    //                 DB::commit();

    //                 return $this->returnSuccess('Successful operation', []);

    //             } catch (\Throwable $e) {

    //                 DB::rollback();

    //                 return $this->returnErrorData('Something went wrong Please try again ' . $e, 404);
    //             }

    //         } else {
    //             return $this->returnErrorData('Data Not Found', 404);
    //         }

    //     } else {
    //         return $this->returnErrorData('Data Not Found', 404);
    //     }
    // }
}
