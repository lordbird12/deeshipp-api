<?php

namespace App\Http\Controllers;

use App\Imports\VendorImport;
use App\Models\Vendor;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Maatwebsite\Excel\Facades\Excel;

class VendorController extends Controller
{

    public function getVendor()
    {
        $Vendor = Vendor::where('status', 1)->get()->toarray();


        if (!empty($Vendor)) {

            for ($i = 0; $i < count($Vendor); $i++) {
                $Vendor[$i]['No'] = $i + 1;

            }
        }

        return $this->returnSuccess('Successful', $Vendor);
    }

    public function VendorPage(Request $request)
    {

        $columns = $request->columns;
        $length = $request->length;
        $order = $request->order;
        $search = $request->search;
        $start = $request->start;
        $page = $start / $length + 1;

        $col = array('id', 'name', 'contact', 'email', 'phone', 'address', 'status', 'create_by', 'update_by', 'created_at', 'updated_at');

        $d = Vendor::select($col)->with('user_create')
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
            return $this->returnErrorData('กรุณาใส่ชื่อ', 404);
        }  else if (!isset($request->email)) {
            return $this->returnErrorData('กรุณาใส่อีเมล์', 404);
        } else if (!isset($request->phone)) {
            return $this->returnErrorData('กรุณาใส่เบอร์โทร', 404);
        } else if (!isset($request->address)) {
            return $this->returnErrorData('กรุณาใส่ที่อยู่', 404);

        } else if (!isset($loginBy)) {
            return $this->returnErrorData('[login_by] Data Not Found', 404);
        }

        $name = $request->name;

        $checkName = Vendor::where('name', $name)->first();

        if ($checkName) {
            return $this->returnErrorData('ชื่อบริษัทนี้มีอยู่แล้ว', 404);

        } else {

            DB::beginTransaction();

            try {

                $Vendor = new Vendor();


                $Vendor->name = $name;
                //$Vendor->delivered_by_id = $request->delivered_by_id;
                //$Vendor->warehouse_id = $request->warehouse_id;
                $Vendor->contact = $request->contact;
                $Vendor->email = $request->email;
                $Vendor->phone = $request->phone;
                $Vendor->address = $request->address;
//dd($Vendor->adress);

                // $Vendor->ordered_shipping_price = $request->ordered_shipping_price;

                // $Vendor->sub_total = $request->sub_total;

                // $Vendor->vat = $request->vat;
                // $Vendor->discount = $request->discount;
                // $Vendor->total = $request->total;
                // $Vendor->description = $request->description;

                $Vendor->status = 1;

                $Vendor->create_by = $loginBy->user_id;

                $Vendor->save();
               ;
                //log
                $userId = $loginBy->user_id;
                $type = 'Add Vendor';
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
        $Vendor = Vendor::with('delivered_by')
        ->with('warehouse')
        ->where('id', $id)
        ->first();


        return $this->returnSuccess('Successful', $Vendor);
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

        $checkName = Vendor::where('id', '!=', $id)
            ->where('name', $name)
            ->first();

        if ($checkName) {
            return $this->returnErrorData('There is already this name in the system', 404);

        } else {

            DB::beginTransaction();

            try {

                $Vendor = Vendor::find($id);

                $Vendor->name = $name;
                $Vendor->contact = $request->contact;
                $Vendor->email = $request->email;
                $Vendor->phone = $request->phone;
                $Vendor->address = $request->address;
                $Vendor->status = $request->status;

                $Vendor->update_by = $loginBy->user_id;
                $Vendor->updated_at = Carbon::now()->toDateTimeString();

                $Vendor->save();

                //log
                $userId = $loginBy->user_id;
                $type = 'Edit Vendor';
                $description = 'User ' . $userId . ' has ' . $type . ' ' . $Vendor->name;
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

            $Vendor = Vendor::find($id);

            //log
            $userId = $loginBy->user_id;
            $type = 'Delete Vendor';
            $description = 'User ' . $userId . ' has ' . $type . ' ' . $Vendor->name;
            $this->Log($userId, $description, $type);
            //

            $Vendor->delete();

            DB::commit();

            return $this->returnUpdate('Successful operation');

        } catch (\Throwable $e) {

            DB::rollback();

            return $this->returnErrorData('Something went wrong Please try again ' . $e, 404);
        }
    }

    // public function ImportVendor(Request $request)
    // {

    //     $loginBy = $request->login_by;

    //     if (!isset($loginBy)) {
    //         return $this->returnErrorData('User information not found. Please login again', 404);
    //     }

    //     $file = request()->file('file');
    //     $fileName = $file->getClientOriginalName();

    //     $Data = Excel::toArray(new VendorImport(), $file);
    //     $data = $Data[0];

    //     if (count($data) > 0) {

    //         $insert_data = [];

    //         for ($i = 0; $i < count($data); $i++) {

    //             $name = trim($data[$i]['name']);
    //             $contact = trim($data[$i]['contact']);
    //             $email = trim($data[$i]['email']);
    //             $phone = trim($data[$i]['phone']);
    //             $adress = trim($data[$i]['adress']);

    //             $row = $i + 2;

    //             if ($name == '') {
    //                 return $this->returnErrorData('Row excel data ' . $row . 'please enter name', 404);
    //             } else if ($contact == '') {
    //                 return $this->returnErrorData('Row excel data ' . $row . ' please enter contact', 404);
    //             } else if ($email == '') {
    //                 return $this->returnErrorData('Row excel data ' . $row . ' please enter email', 404);
    //             } else if ($phone == '') {
    //                 return $this->returnErrorData('Row excel data ' . $row . ' please enter phone', 404);
    //             } else if ($adress == '') {
    //                 return $this->returnErrorData('Row excel data ' . $row . ' please enter adress', 404);
    //             }

    //             //check row sample
    //             if ($name == 'SIMPLE-000') {
    //                 //
    //             } else {

    //                 // //check name
    //                 // $Vendor = Vendor::where('name', $name)->first();
    //                 // if ($Vendor) {
    //                 //     return $this->returnErrorData('Vendor ' . $name . ' was information is already in the system', 404);
    //                 // }

    //                 //check dupicate data form file import
    //                 for ($j = 0; $j < count($insert_data); $j++) {

    //                     if ($name == $insert_data[$j]['name']) {
    //                         return $this->returnErrorData('Vendor ' . $name . ' There is duplicate data in the import file', 404);
    //                     }
    //                 }
    //                 ///

    //                 $insert_data[] = array(
    //                     'name' => $name,
    //                     'contact' => $contact,
    //                     'email' => $email,
    //                     'phone' => $phone,
    //                     'adress' => $adress,
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

    //                     DB::table('vendor')
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
    //                 $type = 'Import Vendor';
    //                 $description = 'User ' . $userId . ' has ' . $type . ' ' . $name;
    //                 $this->Log($userId, $description, $type);
    //                 //

    //                 DB::commit();

    //                 return $this->returnSuccess('Successful operation', []);

    //             } catch (\Throwable $e) {

    //                 DB::rollback();

    //                 return $this->returnErrorData('Something went wrong Please try again ' . $e, 404);
    //             }

    //         }

    //     } else {
    //         return $this->returnErrorData('Data Not Found', 404);
    //     }
    // }
}
