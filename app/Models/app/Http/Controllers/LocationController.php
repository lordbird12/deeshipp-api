<?php

namespace App\Http\Controllers;

use App\Imports\LocationImport;
use App\Models\Item_trans;
use App\Models\Location;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Maatwebsite\Excel\Facades\Excel;

class LocationController extends Controller
{
    public function getLocation()
    {

        // $Location = Location::with('warehouse')
        // ->with('items')
        // ->get();
       
        $Location = Location::where('status', 1)->get()->toarray();

        if (!empty($Location)) {

            for ($i = 0; $i < count($Location); $i++) {
                $Location[$i]['No'] = $i + 1;

            }
        }

         return $this->returnSuccess('Successful', $Location);
    }

    public function getLocationByItem(Request $request)
    {

        $itemId = $request->item_id;

        //dd($itemId);
        $Location = Item_trans::select('location_1_id')
        
            ->with('location')
            ->where('item_id', $itemId)
            ->where('status', 1)
            ->groupby('location_1_id')
            ->get()
            ->toarray();
//dd($Location);
        if (!empty($Location)) {

            for ($i = 0; $i < count($Location); $i++) {
                $Location[$i]['No'] = $i + 1;

            }
        }

        return $this->returnSuccess('Successful', $Location);
    }

    public function LocationPage(Request $request)
    {

        $columns = $request->columns;
        $length = $request->length;
        $order = $request->order;
        $search = $request->search;
        $start = $request->start;
        $page = $start / $length + 1;

        $warehouseId = $request->warehouse_id;

        // if (!isset($warehouseId)) {
        //     return $this->returnErrorData('[warehouse_id] Data Not Found', 404);
        // }

        $col = array('id', 'warehouse_id', 'code', 'name', 'status', 'create_by', 'update_by', 'created_at', 'updated_at');

        $d = Location::select($col)
            ->with('warehouse')
            ->with('user_create')

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

        //dd($loginBy);
        if (!isset($request->warehouse_id)) {
            return $this->returnErrorData('กรุณาเลือกสถานที่คลังสินค้า', 404);
        } else if (!isset($request->code)) {
            return $this->returnErrorData('กรุณาใส่รห้สสถานที่', 404);
        } else if (!isset($request->name)) {
            return $this->returnErrorData('กรุณาใส่ชื่อสถานที่จัดเก็บ', 404);
        } else if (!isset($loginBy)) {
            return $this->returnErrorData('[login_by] Data Not Found', 404);
        }

        $name = $request->name;
        $code = $request->code;

        $checkName = Location::where(function ($query) use ($code, $name) {
            $query->orwhere('code', $code)
                ->orwhere('name', $name);
        })
            ->first();

        if ($checkName) {
            return $this->returnErrorData('มีรหัสคลังสินค้าสถานที่อยู่ในระบบหรือมีชื่ออยู่ในระบบอยู่แล้วกรุณาเลือกใหม่', 404);
        } else {

            DB::beginTransaction();

            try {

                $Location = new Location();
                $Location->name = $name;
                $Location->code = $code;
                $Location->status = 1;

                $Location->create_by = $loginBy->user_id;

                $Location->warehouse_id = $request->warehouse_id;

                $Location->save();
                $Location->warehouse;

                //log
                $userId = $loginBy->user_id;
                $type = 'Add Location';
                $description = 'User ' . $userId . ' has ' . $type . ' ' . $name;
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

    /**
     * Display the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function show($id)
    {
        $Location = Location::find($id);
        return $this->returnSuccess('Successful', $Location);
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

        $checkName = Location::where('id', '!=', $id)
            ->where(function ($query) use ($code, $name) {
                $query->orwhere('code', $code)
                    ->orWhere('name', $name);
            })
            ->first();

        if ($checkName) {
            return $this->returnErrorData('There is already this name in the system', 404);
        } else {

            DB::beginTransaction();

            try {

                $Location = Location::find($id);
//dd($Location);
                $Location->name = $name;
                $Location->code = $code;
                $Location->status = $request->status;

                $Location->update_by = $loginBy->user_id;
                $Location->updated_at = Carbon::now()->toDateTimeString();

                $Location->warehouse_id = $request->warehouse_id;

                $Location->save();
                $Location->warehouse;

                //log
                $userId = $loginBy->user_id;
                $type = 'Edit Location';
                $description = 'User ' . $userId . ' has ' . $type . ' ' . $Location->name;
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

            $Location = Location::find($id);

            //log
            $userId = $loginBy->user_id;
            $type = 'Delete Location';
            $description = 'User ' . $userId . ' has ' . $type . ' ' . $Location->name;
            $this->Log($userId, $description, $type);
            //

            $Location->delete();

            DB::commit();

            return $this->returnUpdate('Successful operation');
        } catch (\Throwable $e) {

            DB::rollback();

            return $this->returnErrorData('Something went wrong Please try again ' . $e, 404);
        }
    }

    public function getLocationItem(Request $request)
    {

        $itemId = $request->item_id;
//dd($itemId);
        $Location = Location::get()->toarray();

        if (!empty($Location)) {

            for ($i = 0; $i < count($Location); $i++) {

                $Location[$i]['No'] = $i + 1;

                //item qty
                $itemTrans = Item_trans::where('item_id', $itemId)
                
                    ->where('status', 1)
                    ->where('location_1_id', $Location[$i]['id'])
                    ->sum('qty');

                $Location[$i]['item_qty'] = $itemTrans;

                

            $data = [];

            for ($i = 0; $i < count($Location); $i++) {

                if ($Location[$i]['item_qty'] > 0) {
                    $data[] = $Location[$i];
                }

            }
        }

        return $this->returnSuccess('Successful', $data);
    }
    }
    public function getLocationStockItem(Request $request)
    {
        $columns = $request->columns;
        $length = $request->length;
        $order = $request->order;
        $search = $request->search;
        $start = $request->start;
        $page = $start / $length + 1;

        $itemId = $request->item_id;

        if (!isset($itemId)) {
            return $this->returnErrorData('[item_id] Data Not Found', 404);
        }

        $col = array('id', 'warehouse_id', 'code', 'name', 'create_by', 'update_by', 'created_at', 'updated_at');

        $d = Location::select($col)

        
            ->with('warehouse')

            ->orderby($col[$order[0]['column']], $order[0]['dir']);
            
            
        if ($search['value'] != '' && $search['value'] != null) {
            foreach ($col as &$c) {
                $d->orWhere($c, 'LIKE', '%' . $search['value'] . '%');

                
            }
        }

        $d = $d->paginate($length, ['*'], 'page', $page);

        if ($d->isNotEmpty()) {

            //run no
            $No = (($page - 1) * $length);

            for ($i = 0; $i < count($d); $i++) {

                $No = $No + 1;
                $d[$i]->No = $No;

                //item qty
                $itemTrans = Item_trans::where('item_id', $itemId)
                    ->where('status', 1)
                    ->where('location_1_id', $d[$i]->id)
                    ->sum('qty');

                $d[$i]->item_qty = $itemTrans;

            }

        }

        return $this->returnSuccess('Successful', $d);
    }

    public function getLocationByWarehouse(Request $request)
    {

        $warehouseId = $request->warehouse_id;

        $Location = Location::where('warehouse_id', $warehouseId)->get()->toarray();

        if (!empty($Location)) {

            for ($i = 0; $i < count($Location); $i++) {
                $Location[$i]['No'] = $i + 1;

            }
        }

        return $this->returnSuccess('Successful', $Location);
    }

    // public function ImportLocation(Request $request)
    // {

    //     $loginBy = $request->login_by;

    //     if (!isset($loginBy)) {
    //         return $this->returnErrorData('User information not found. Please login again', 404);
    //     }

    //     $file = request()->file('file');
    //     $fileName = $file->getClientOriginalName();

    //     $Data = Excel::toArray(new LocationImport(), $file);
    //     $data = $Data[0];

    //     if (count($data) > 0) {

    //         $insert_data = [];

    //         for ($i = 0; $i < count($data); $i++) {

    //             $code = trim($data[$i]['code']);
    //             $name = trim($data[$i]['name']);

    //             $row = $i + 2;

    //             if ($code == '') {
    //                 return $this->returnErrorData('Row excel data ' . $row . 'please enter code', 404);
    //             } else if ($name == '') {
    //                 return $this->returnErrorData('Row excel data ' . $row . 'please enter name', 404);
    //             }

    //             //check row sample
    //             if ($name == 'SIMPLE-000') {
    //                 //
    //             } else {

    //                 // //check name
    //                 // $Location = Location::where('code', $code)->first();
    //                 // if ($Location) {
    //                 //     return $this->returnErrorData('Location ' . $code . ' was information information is already in the system', 404);
    //                 // }

    //                 //check dupicate data form file import
    //                 for ($j = 0; $j < count($insert_data); $j++) {

    //                     if ($code == $insert_data[$j]['code']) {
    //                         return $this->returnErrorData('Location ' . $code . ' There is duplicate data in the import file', 404);
    //                     }
    //                 }
    //                 ///

    //                 $insert_data[] = array(
    //                     'warehouse_id' => 1,
    //                     'code' => $code,
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

    //                     DB::table('location')
    //                         ->updateOrInsert(
    //                             [
    //                                 'code' => trim($data[$i]['code']), //id
    //                             ],
    //                             $insert_data[$i]
    //                         );
    //                 }
    //                 //

    //                 DB::commit();

    //                 //log
    //                 $userId = $loginBy->user_id;
    //                 $type = 'Import Location';
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

    // public function getLocationStockItem(Request $request)
    // {

    //     $itemId = $request->item_id;

    //     $Location = Location::get()->toarray();

    //     if (!empty($Location)) {

    //         for ($i = 0; $i < count($Location); $i++) {

    //             $Location[$i]['No'] = $i + 1;

    //             //item qty
    //             $itemTrans = Item_trans::where('item_id', $itemId)
    //                 ->where('status', 1)
    //                 ->where('location_1_id', $Location[$i]['id'])
    //                 ->sum('qty');

    //             $Location[$i]['item_qty'] = $itemTrans;

    //         }

    //         $data = [];

    //         for ($i = 0; $i < count($Location); $i++) {

    //             $data[] = $Location[$i];

    //         }
    //     }

    //     return $this->returnSuccess('Successful', $data);
    // }

}
