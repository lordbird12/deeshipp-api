<?php

namespace App\Http\Controllers;

use App\Imports\CustomerImport;
use App\Models\Customer;
use App\Models\CustomerLine;
use App\Models\Sale_order;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Maatwebsite\Excel\Facades\Excel;

class CustomerController extends Controller
{

    public function getCustomer()
    {
        $Customer = Customer::where('status', 1)
            ->with('user_create')
            ->with('main_customerLine')->get()->toarray();

        if (!empty($Customer)) {

            for ($i = 0; $i < count($Customer); $i++) {
                $Customer[$i]['No'] = $i + 1;
            }
        }

        return $this->returnSuccess('Successful', $Customer);
    }

    public function getCustomerTelasale()
    {
        $Customer = Customer::where('status', 1)
            ->with('user_create')
            ->with('main_customerLine')->get()->toarray();

        if (!empty($Customer)) {

            for ($i = 0; $i < count($Customer); $i++) {
                $Customer[$i]['No'] = $i + 1;
            }
        }

        return $this->returnSuccess('Successful', $Customer);
    }

    public function CustomerPage(Request $request)
    {

        $columns = $request->columns;
        $length = $request->length;
        $order = $request->order;
        $search = $request->search;
        $start = $request->start;
        $page = $start / $length + 1;

        $col = array('id', 'name', 'contact', 'email', 'phone', 'status', 'create_by', 'update_by', 'created_at', 'updated_at');

        $d = Customer::select($col)
            ->with('main_customerLine')
            ->with('user_create');


        $d->orderby($col[$order[0]['column']], $order[0]['dir']);
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

    public function CustomerTelesalePage(Request $request)
    {

        $columns = $request->columns;
        $length = $request->length;
        $order = $request->order;
        $search = $request->search;
        $start = $request->start;
        $page = $start / $length + 1;

        $user_id = $request->user_id;

        $SaleList = User::where('position_id', 4)->orderBy('id', 'asc')->get();


        $date = date('Y-m-d', strtotime("-14 day", strtotime(date('Y-m-d'))));

        $saleorder = Sale_order::whereDate('date_time', '<', $date)
            ->groupBy('customer_id')
            ->orderBy('customer_id')
            ->get();

        $newCompete = array();
        for ($i = 0; $i < count($saleorder); $i++) {
            $check = Sale_order::where('customer_id', $saleorder[$i]->customer_id)->orderBy('created_at', 'desc')->first();
            $to = \Carbon\Carbon::createFromFormat('Y-m-d', date('Y-m-d'));
            $from = \Carbon\Carbon::createFromFormat('Y-m-d', $check->date_time);

            $diff_in_days = $to->diffInDays($from);
            if ($diff_in_days >= 14) {
                array_push($newCompete, $check->customer_id);
            }
        }

        $customerAll = Customer::all();

        for ($i = 0; $i < count($customerAll); $i++) {
            $check = Sale_order::where('customer_id', $customerAll[$i]->id)->first();
            if (!$check) {
                array_push($newCompete, $customerAll[$i]->id);
            }
        }



        $col = array('id', 'name', 'contact', 'call_date', 'call_by', 'contact', 'email', 'phone', 'status', 'create_by', 'update_by', 'created_at', 'updated_at');

        $d = Customer::select($col)
        ->whereIn('id', $newCompete);

        $d->orderby($col[$order[0]['column']], $order[0]['dir']);
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
                $d[$i]->call_status = $d[$i]->call_date == null ? 'false':'true';
                $d[$i]->call_name = User::where('user_id', $d[$i]->call_by)->first();

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
        $customer_line = $request->customer_line;
        //dd($loginBy);

        if (!isset($request->name)) {
            return $this->returnErrorData('กรุณาเพิ่มชื่อด้วย', 404);
        } else if (!isset($request->email)) {
            return $this->returnErrorData('กรุณาใส่อีเมล์ด้วย', 404);
        } else if (!isset($request->phone)) {
            return $this->returnErrorData('กรุณาใส่เบอร์โทรด้วย', 404);
        } else if (!isset($loginBy)) {
            return $this->returnErrorData('[login_by] Data Not Found', 404);
        }

        $name = $request->name;

        $checkName = Customer::where('name', $name)->first();

        if ($checkName) {
            return $this->returnErrorData('มีชื่ออยู่ในรายการลูกค้าอยู่แล้ว', 404);
        } else {

            DB::beginTransaction();

            try {

                $Customer = new Customer();

                $Customer->name = $name;
                $Customer->contact = $request->contact;
                $Customer->email = $request->email;
                $Customer->phone = $request->phone;
                // $Customer->address = $request->address;
                $Customer->status = 1;

                $Customer->create_by = $loginBy->user_id;
                $Customer->save();


                for ($i = 0; $i < count($customer_line); $i++) {


                    $customer_line[$i]['customer_id'] = $Customer->id;
                    //dd( $Customer->id);
                    $customer_line[$i]['created_at'] = Carbon::now()->toDateTimeString();
                    $customer_line[$i]['updated_at'] = Carbon::now()->toDateTimeString();
                }

                //add customer_line
                DB::table('customer_lines')->insert($customer_line);


                //log
                $userId = $loginBy->user_id;
                $type = 'Add Customer';
                $description = 'User ' . $userId . ' has ' . $type . ' ' . $name;
                $this->Log($userId, $description, $type);


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

        if (!isset($id)) {
            return $this->returnErrorData('[id] Data Not Found', 404);
        }
        $Customer = Customer::with('main_customerLine')
            ->find($id);
        return $this->returnSuccess('Successful', $Customer);
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
        $customer_line = $request->customer_line;

        if (!isset($loginBy)) {
            return $this->returnErrorData('[login_by] Data Not Found', 404);
        }

        $name = $request->name;
        $Customer = Customer::find($id);

        if (!isset($Customer)) {
            return $this->returnErrorData('ไม่พบIDผู้ใช้งาน', 404);
        }
        $checkName = Customer::where('id', '!=', $id)
            ->where('name', $name)
            ->first();

        if ($checkName) {
            return $this->returnErrorData('There is already this name in the system', 404);
        } else {

            DB::beginTransaction();

            try {


                $Customer->name = $name;
                $Customer->contact = $request->contact;
                $Customer->email = $request->email;
                $Customer->phone = $request->phone;
                //$Customer->address = $request->address;
                $Customer->status = $request->status;
                $Customer->update_by = $loginBy->user_id;
                $Customer->updated_at = Carbon::now()->toDateTimeString();

                $Customer->save();


                for ($i = 0; $i < count($customer_line); $i++) {

                    // "qty": 10,
                    // "price": 10,
                    // "total": 100

                    switch ($customer_line[$i]['action']) {
                        case 'insert':
                            $newCustomer_line = new CustomerLine();
                            $newCustomer_line->address = $customer_line[$i]['address'];

                            $newCustomer_line->customer_id = $Customer->id;


                            $newCustomer_line->save();
                            break;

                        case 'update':

                            $Customer_line = CustomerLine::find($customer_line[$i]['customer_line_id']);
                            //$Customer_line->id = $customer_line[$i]['id'];
                            $Customer_line->address = $customer_line[$i]['address'];

                            $Customer_line->save();
                            break;


                        case 'delete':
                            $Customer_line1 = CustomerLine::find($customer_line[$i]['customer_line_id']);
                            $Customer_line1->delete();
                            break;

                        default:
                            # code...
                            break;
                    }
                }


                //log
                $userId = $loginBy->user_id;
                $type = 'Edit Customer';
                $description = 'User ' . $userId . ' has ' . $type . ' ' . $Customer->name;
                $this->Log($userId, $description, $type);
                //

                DB::commit();

                return $this->returnUpdateReturnData('Successful operation', Customer::where('id', $id)->with('main_customerLine')->first());
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

            $Customer = Customer::find($id);

            //log
            $userId = $loginBy->user_id;
            $type = 'Delete Customer';
            $description = 'User ' . $userId . ' has ' . $type . ' ' . $Customer->name;
            $this->Log($userId, $description, $type);
            //

            $Customer->delete();

            DB::commit();

            return $this->returnUpdate('Successful operation');
        } catch (\Throwable $e) {

            DB::rollback();

            return $this->returnErrorData('Something went wrong Please try again ' . $e, 404);
        }
    }

    public function updateCall(Request $request)
    {
        $loginBy = $request->login_by;

        $customer_id = $request->customer_id;

        if (!isset($loginBy)) {
            return $this->returnErrorData('[login_by] Data Not Found', 404);
        }

        DB::beginTransaction();

        try {

            $Customer = Customer::find($customer_id);

            $Customer->call_date = date('Y-m-d');
            $Customer->call_by = $loginBy->user_id;

            //log
            $userId = $loginBy->user_id;
            $type = 'Call Customer';
            $description = 'User ' . $userId . ' has ' . $type . ' ' . $Customer->name;
            $this->Log($userId, $description, $type);
            //

            $Customer->save();

            DB::commit();

            return $this->returnUpdate('Successful operation');
        } catch (\Throwable $e) {

            DB::rollback();

            return $this->returnErrorData('Something went wrong Please try again ' . $e, 404);
        }
    }

    public function ImportCustomer(Request $request)
    {

        $loginBy = $request->login_by;

        if (!isset($loginBy)) {
            return $this->returnErrorData('User information not found. Please login again', 404);
        }

        $file = request()->file('file');
        $fileName = $file->getClientOriginalName();

        $Data = Excel::toArray(new CustomerImport(), $file);
        $data = $Data[0];

        if (count($data) > 0) {

            $insert_data = [];

            for ($i = 0; $i < count($data); $i++) {

                $name = trim($data[$i]['name']);
                $contact = trim($data[$i]['contact']);
                $email = trim($data[$i]['email']);
                $phone = trim($data[$i]['phone']);
                $adress = trim($data[$i]['adress']);

                $row = $i + 2;

                if ($name == '') {
                    return $this->returnErrorData('Row excel data ' . $row . 'please enter name', 404);
                } else if ($contact == '') {
                    return $this->returnErrorData('Row excel data ' . $row . ' please enter contact', 404);
                } else if ($email == '') {
                    return $this->returnErrorData('Row excel data ' . $row . ' please enter email', 404);
                } else if ($phone == '') {
                    return $this->returnErrorData('Row excel data ' . $row . ' please enter phone', 404);
                } else if ($adress == '') {
                    return $this->returnErrorData('Row excel data ' . $row . ' please enter adress', 404);
                }

                //check row sample
                if ($name == 'SIMPLE-000') {
                    //
                } else {

                    // //check name
                    // $Customer = Customer::where('name', $name)->first();
                    // if ($Customer) {
                    //     return $this->returnErrorData('Customer ' . $name . ' was information is already in the system', 404);
                    // }

                    //check dupicate data form file import
                    for ($j = 0; $j < count($insert_data); $j++) {

                        if ($name == $insert_data[$j]['name']) {
                            return $this->returnErrorData('Customer ' . $name . ' There is duplicate data in the import file', 404);
                        }
                    }
                    ///

                    $insert_data[] = array(
                        'name' => $name,
                        'contact' => $contact,
                        'email' => $email,
                        'phone' => $phone,
                        'adress' => $adress,
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

                        DB::table('customer')
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
                    $type = 'Import Customer';
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
        } else {
            return $this->returnErrorData('Data Not Found', 404);
        }
    }
}
