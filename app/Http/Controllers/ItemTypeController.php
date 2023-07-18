<?php

namespace App\Http\Controllers;

use App\Imports\ItemTypeImport;
use App\Models\Item_type;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Maatwebsite\Excel\Facades\Excel;

class ItemTypeController extends Controller
{

    public function getItemType()
    {
        $Item_type = Item_type::where('status', 1)->with('user_create')->get()->toarray();

        if (!empty($Item_type)) {

            for ($i = 0; $i < count($Item_type); $i++) {
                $Item_type[$i]['No'] = $i + 1;

            }
        }

        return $this->returnSuccess('Successful', $Item_type);
    }

    public function ItemTypePage(Request $request)
    {

        $columns = $request->columns;
        $length = $request->length;
        $order = $request->order;
        $search = $request->search;
        $start = $request->start;
        $page = $start / $length + 1;

        $col = array('id', 'name', 'status', 'code', 'create_by', 'update_by', 'created_at', 'updated_at');

        $d = Item_type::select($col)->with('user_create')

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
            return $this->returnErrorData('กรุณาใส่ชื่อหมวดหมู่สินค้า', 404);
        } else if (!isset($request->code)) {
            return $this->returnErrorData('กรุณาใส่รหัสสินค้า', 404);

        } else if (!isset($loginBy)) {
            return $this->returnErrorData('[login_by] Data Not Found', 404);
        }

        $name = $request->name;
        $code = $request->code;

        $checkName = Item_type::where(function ($query) use ($name, $code) {
            $query->orwhere('name', $name)
                ->orWhere('code', $code);
        })
            ->first();

        if ($checkName) {
            return $this->returnErrorData('ชื่อหรือรห้สซำ้ที่มีอยู่แล้วกรุณาเลือกใหม่', 404);

        } else {

            DB::beginTransaction();

            try {

                $Item_type = new Item_type();
                $Item_type->name = $name;
                $Item_type->code = $code;
                $Item_type->status = 1;

                $Item_type->create_by = $loginBy->user_id;
                $Item_type->save();

                //log
                $userId = $loginBy->user_id;
                $type = 'Add Item Type';
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
        $Item_type = Item_type::find($id);
        return $this->returnSuccess('Successful', $Item_type);
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
        $code = $request->code;

        $checkName = Item_type::where('id', '!=', $id)
            ->where(function ($query) use ($name, $code) {
                $query->orwhere('name', $name)
                    ->orWhere('code', $code);
            })
            ->first();

        if ($checkName) {
            return $this->returnErrorData('There is already this name in the system', 404);

        } else {

            DB::beginTransaction();

            try {

                $Item_type = Item_type::find($id);
                $Item_type->name = $name;
                $Item_type->code = $code;
                $Item_type->status = $request->status;

                $Item_type->update_by = $loginBy->user_id;
                $Item_type->updated_at = Carbon::now()->toDateTimeString();
                $Item_type->save();

                //log
                $userId = $loginBy->user_id;
                $type = 'Edit Item Type';
                $description = 'User ' . $userId . ' has ' . $type . ' ' . $Item_type->name;
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

            $Item_type = Item_type::find($id);

            // //log
            // $userId = $loginBy->user_id;
            // $type = 'Delete Item Type';
            // $description = 'User ' . $userId . ' has ' . $type . ' ' . $Item_type->name;
            // $this->Log($userId, $description, $type);
            // //

            $Item_type->delete();

            DB::commit();

            return $this->returnUpdate('Successful operation');

        } catch (\Throwable $e) {

            DB::rollback();

            return $this->returnErrorData('Something went wrong Please try again ' . $e, 404);
        }
    }

    // public function ImportItemType(Request $request)
    // {
    //     $loginBy = $request->login_by;

    //     if (!isset($loginBy)) {
    //         return $this->returnErrorData('[login_by] Data Not Found', 404);
    //     }

    //     $file = request()->file('file');
    //     $fileName = $file->getClientOriginalName();

    //     $Data = Excel::toArray(new ItemTypeImport(), $file);
    //     $data = $Data[0];

    //     if (count($data) > 0) {

    //         $insert_data = [];

    //         for ($i = 0; $i < count($data); $i++) {

    //             $name = trim($data[$i]['name']);
    //             $initial = trim($data[$i]['initial']);

    //             $row = $i + 2;

    //             if ($name == '') {
    //                 return $this->returnErrorData('Row excel data ' . $row . ' please enter name', 404);
    //             } else if ($initial == '') {
    //                 return $this->returnErrorData('Row excel data ' . $row . ' Please enter initial', 404);
    //             }

    //             //check row sample
    //             if ($name == 'SIMPLE-000') {
    //                 //
    //             } else {

    //                 //check dupicate data form file import
    //                 for ($j = 0; $j < count($insert_data); $j++) {

    //                     if ($name == $insert_data[$j]['name']) {
    //                         return $this->returnErrorData('Name ' . $name . ' There is duplicate data in the import file', 404);
    //                     }
    //                 }
    //                 ///

    //                 $insert_data[] = array(
    //                     'name' => $name,
    //                     'initial' => $initial,
    //                     'status' => 1,
    //                     'create_by' => $loginBy->user_id,
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

    //                     DB::table('item_type')
    //                         ->updateOrInsert(
    //                             [
    //                                 'id' => trim($data[$i]['id']), //id
    //                             ],
    //                             $insert_data[$i]
    //                         );
    //                 }
    //                 //

    //                 //log
    //                 $userId = $loginBy->user_id;
    //                 $type = 'Import Item Type';
    //                 $description = 'User ' . $userId . ' has ' . $type;
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
