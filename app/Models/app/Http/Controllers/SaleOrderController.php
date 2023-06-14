<?php

namespace App\Http\Controllers;

use App\Imports\SaleOrderImport;
use App\Models\Customer;
use App\Models\CustomerLine;
use App\Models\Item;
use App\Models\Item_line;
use App\Models\Item_trans;
use App\Models\Qty_sale_order_job;
use App\Models\Report_stock;
use App\Models\Sale_order;
use App\Models\Sale_order_line;
use App\Models\Unit_convertion;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Maatwebsite\Excel\Facades\Excel;

class SaleOrderController extends Controller
{
    public function getSaleOrder()
    {

        $Sale_order = Sale_order::with('user_create')
        ->get()->toarray();

        if (!empty($Sale_order)) {

            for ($i = 0; $i < count($Sale_order); $i++) {
                $Sale_order[$i]['No'] = $i + 1;
            }
        }

        return $this->returnSuccess('Successful', $Sale_order);
    }

    public function getSaleOrderApprove()
    {
        $Sale_order = Sale_order::where('status', 'confirm')->orderby('id', 'desc')->get()->toarray();

        if (!empty($Sale_order)) {

            $order = [];

            for ($i = 0; $i < count($Sale_order); $i++) {
                $Sale_order[$i]['No'] = $i + 1;

                $saleOrderLine = Sale_order_line::where('sale_order_id', $Sale_order[$i]['id'])->sum('qty');
                $Sale_order[$i]['qty_sale_order'] = intval($saleOrderLine);

                //$qtySaleOrderJob = Qty_sale_order_job::where('sale_order_id', $Sale_order[$i]['id'])->sum('qty');

               // $Sale_order[$i]['qty_sale_order_job'] = intval($qtySaleOrderJob);

               // $Sale_order[$i]['result'] = intval($saleOrderLine) - intval($qtySaleOrderJob);

                $Sale_order[$i]['result'] = intval($saleOrderLine);

                //check result > 0
                if ($Sale_order[$i]['result'] > 0) {
                    $order[] = $Sale_order[$i];
                }
            }
        }
        return $this->returnSuccess('Successful', $order);

    }

    public function getSaleOrderApprovePage(Request $request)
    {

        $columns = $request->columns;
        $length = $request->length;
        $order = $request->order;
        $search = $request->search;
        $start = $request->start;
        $page = $start / $length + 1;

        $col = array('id', 'order_id', 'item_id', 'del_date', 'customer_id', 'user_id');

        $D = Sale_order::select($col)
            ->with('item_code')
            ->with('customer')
            ->with('user')
            ->where('status', 'Approved');

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

            $order = [];

            for ($i = 0; $i < count($d); $i++) {

                $No = $No + 1;
                $d[$i]->No = $No;

                $saleOrderLine = Sale_order_line::where('sale_order_id', $d[$i]->id)->sum('qty');
                $d[$i]->qty_sale_order = intval($saleOrderLine);

                //$qtySaleOrderJob = Qty_sale_order_job::where('sale_order_id', $d[$i]->id)->sum('qty');
                //$d[$i]->qty_sale_order_job = intval($qtySaleOrderJob);

                //$d[$i]->result = intval($saleOrderLine) - intval($qtySaleOrderJob);
                $d[$i]->result = intval($saleOrderLine);
            }
        }

        return $this->returnSuccess('Successful', $d);
    }

    public function getSaleOrderLineByItem(Request $request)
    {

        $itemId = $request->item_id;

        $Sale_order = Sale_order_line::with('sale_order')
            ->where('item_id', $itemId)
            ->get()
            ->toarray();

        if (!empty($Sale_order)) {

            for ($i = 0; $i < count($Sale_order); $i++) {
                $Sale_order[$i]['No'] = $i + 1;
            }
        }

        return $this->returnSuccess('Successful', $Sale_order);
    }

    public function getSaleOrderOpenJob(Request $request)
    {

        $itemId = $request->item_id;

        $Sale_order = Sale_order::with('item_code')
            ->where('item_id', $itemId)
            ->where('status', 'Approved')
            ->get()
            ->toarray();

        if (!empty($Sale_order)) {

            for ($i = 0; $i < count($Sale_order); $i++) {
                $Sale_order[$i]['No'] = $i + 1;

                $saleOrderLine = Sale_order_line::where('sale_order_id', $Sale_order[$i]['id'])->sum('qty');
                $Sale_order[$i]['qty_sale_order'] = intval($saleOrderLine);

                $qtySaleOrderJob = Qty_sale_order_job::where('sale_order_id', $Sale_order[$i]['id'])->sum('qty');
                $Sale_order[$i]['qty_sale_order_job'] = intval($qtySaleOrderJob);

                $Sale_order[$i]['result'] = intval($saleOrderLine) - intval($qtySaleOrderJob);
            }
        }

        return $this->returnSuccess('Successful', $Sale_order);
    }

    public function SaleOrderPage(Request $request)
    {

        $columns = $request->columns;
        $length = $request->length;
        $order = $request->order;
        $search = $request->search;
        $start = $request->start;
        $page = $start / $length + 1;

        $status = $request->status;

        // if (!isset($status)) {
        //     return $this->returnErrorData('[status] Data Not Found', 404);
        // }

        $col = array(
            'id',
            'customer_id',
            'delivery_by_id',
            'sale_id',
            'order_id',
            'payment_date',
            'description',
            'name',
            'telephone',
            'email',
            'address',
            'shipping_price',
            'cod_price_surcharge',
            'main_discount',
            'vat',
            'total',
            'channal',
            'channal_remark',
            'payment_type',
            'status',
            'image_slip',
            'bank_id',
            'date_time',
            'payment_qty',
            'account_number',
            'create_by',
            'update_by',
            'created_at',
            'updated_at',
            'deleted_at',
        );

        $D = Sale_order::select($col)->where('channal','!=','SP')
            ->with('sale')
            ->with('user_create')
            ->with('item_code');

        if ($status) {
            $D->where('status', $status);
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

        $Order = $request->order;
        $loginBy = $request->login_by;

        //dd($Order);
        if (!isset($request->date_time)) {
            return $this->returnErrorData('กรุณาใส่วันที่', 404);
        } else if (!isset($request->name)) {
            return $this->returnErrorData('กรุณาใส่ชื่อลูกค้า', 404);
        } else if (!isset($request->telephone)) {
            return $this->returnErrorData('กรุณาใส่เบอร์โทร', 404);
        } else if (!isset($request->email)) {
            return $this->returnErrorData('กรุณาใส่Email', 404);
        } else if (!isset($request->address)) {
            return $this->returnErrorData('กรุณาใส่ที่อยู่', 404);
        } else if (!isset($request->delivery_by_id)) {
            return $this->returnErrorData('กรุณาเลือกช่องทางจัดส่ง', 404);
        } else if (!isset($request->payment_type)) {
            return $this->returnErrorData('กรุณาเลือกช่องทางวิธีชำระเงิน', 404);
        } else if (!isset($request->channal)) {
            return $this->returnErrorData('กรุณาเลือกช่องทางในการขาย', 404);
        } else if (!isset($loginBy)) {
            return $this->returnErrorData('[login_by] Data Not Found', 404);
        } else if (empty($Order)) {
            return $this->returnErrorData('[order] Data Not Found', 404);
        }


        $checkName = Customer::where(function ($query) use ($request) {

            $query->orwhere('email', $request->email)
                ->orWhere('name', $request->name);
        })
            ->first();



        DB::beginTransaction();

        try {



            if ($checkName) {

                //dd(new Customer());
                //dd($checkName);

                //add order
                $Sale_order = new Sale_order();
                $Customer = $checkName;


                $Sale_order->order_id = $this->getLastNumber(5);

                //run number
                $this->setRunDoc(5, $Sale_order->order_id);

                $Sale_order->date_time = $request->date_time;
                $Sale_order->customer_id = $request->customer_id;
                $Sale_order->delivery_by_id = $request->delivery_by_id;
                $Sale_order->channal = $request->channal;
                $Sale_order->name = $request->name;
                $Sale_order->sale_id = $request->sale_id;
                $Sale_order->telephone = $request->telephone;
                $Sale_order->email = $request->email;
                $Sale_order->address = $request->address;
                $Sale_order->shipping_price = $request->shipping_price;
                $Sale_order->cod_price_surcharge = $request->cod_price_surcharge;
                $Sale_order->image_slip = $request->image_slip;
                $Sale_order->bank_id = $request->bank_id;
                $Sale_order->payment_date = $request->payment_date;
                $Sale_order->payment_qty = $request->payment_qty;
                $Sale_order->account_number = $request->account_number;
                $Sale_order->main_discount = $request->main_discount;
                $Sale_order->vat = $request->vat;
                $Sale_order->total = $request->total;
                $Sale_order->status = $request->status;
                $Sale_order->customer_id = $Customer->id;
                // dd($Sale_order->status);
                $Sale_order->payment_type = $request->payment_type;
                $Sale_order->create_by = $loginBy->user_id;
                $Sale_order->save();
                $Sale_order->Customer;


                //add Withdraw

                //$Item_trans=[];




                for ($i = 0; $i < count($Order); $i++) {

                    //stock Count
                    $stockCount = $this->getStockCount($Order[$i]['item_id'], []);
                    if (abs($Order[$i]['qty']) > $stockCount) {
                        return $this->returnErrorData('Not enough item', 404);
                    }

                    $Order[$i]['item_id'] = $Order[$i]['item_id'];

                    // dd($Order[$i]['item_id']);

                    $Order[$i]['sale_order_id'] = $Sale_order->id;

                    //$Order[$i]['seq'] = $i + 1;
                    $Order[$i]['unit_price'] = floatval($Order[$i]['unit_price']);


                    //dd($Order[$i]['unit_price']);
                    $Order[$i]['create_by'] = $loginBy->user_id;
                    $Order[$i]['created_at'] = Carbon::now()->toDateTimeString();
                    $Order[$i]['updated_at'] = Carbon::now()->toDateTimeString();



                    $Item_trans = new Item_trans();

                    //qty withdraw
                    $qty = -$Order[$i]['qty'];


                    $Item = Item::where('id', $Order[$i]['item_id'])->first();


                    $stockCount = $this->getStockCount($Order[$i]['item_id'], []);

                    //  $stockCount = $this->getStockCount($Order[$i]['item_id'], [$Item->location_id]);

                    if (abs($qty) > $stockCount) {
                        return $this->returnErrorData('Not enough item', 404);
                    }


                    $Item_trans->sale_order_id = $Sale_order->id;
                    $Item_trans->item_id = $Item->id;
                    $Item_trans->qty = $qty;

                    $Item_trans->location_1_id = $Item->location_id;

                    $Item_trans->customer_id = $Customer->id;

                    $Item_trans->stock = $stockCount;
                    $Item_trans->balance = $stockCount - abs($qty);
                    $Item_trans->status = 1;
                    $Item_trans->operation = 'booking';
                    $Item_trans->date = $request->date_time;
                    $Item_trans->type = 'Withdraw';
                    $Item_trans->create_by = $loginBy->user_id;


                    $Item_trans->save();




                    //$Item_Line = Item_line::where('id', $Order[$i]['item_id'])->first();
                    // dd($Item_Line);
                    //stock Count




                    //add customer_line
                    if (!empty($Order[$i]['address'])) {
                        for ($j = 0; $j < count($Order[$i]['address']); $j++) {


                            //add customer_line
                            $Customer_line = new CustomerLine();

                            $Customer_line->customer_id = $Sale_order->id;

                            $Customer_line->address = $Order[$i]['address'][$j]['address'];
                            // dd($Customer_line->address);
                            // $Customer_line->customer_id=$checkName->id;


                            $Customer_line->save();
                        }
                    }
                }



                //add order line
                DB::table('sale_order_line')->insert($Order);


                //log
                $userId = $loginBy->user_id;
                $type = 'ขายสินค้า';
                $description = 'User' . $userId. ' has ' . $type;
                $this->LogSaleOrder($userId, $description, $type);

                DB::commit();

                DB::commit();

                return $this->returnSuccess('Successful operation', []);

                // return $this->returnErrorData('มีข้อมูลลูกค้าอยู่ในระบบแล้วสามารถค้นหาข้อมูลได้', 404);
            } else {

                $Customer = new Customer();

                $Customer->name = $request->name;
                $Customer->phone = $request->telephone;
                //$Customer->address = $request->address;
                $Customer->email = $request->email;
                $Customer->create_by = $loginBy->user_id;
                //     //$Permission->name = $request->department;
                $Customer->save();

                $CustomerLine = new CustomerLine();
                $CustomerLine->address = $request->address;
                $CustomerLine->customer_id = $Customer->id;
                $CustomerLine->save();

                //add order
                $Sale_order = new Sale_order();

                $Sale_order->order_id = $this->getLastNumber(5);

                //run number
                $this->setRunDoc(5, $Sale_order->order_id);

                $Sale_order->date_time = $request->date_time;
                $Sale_order->customer_id = $request->customer_id;
                $Sale_order->sale_id = $request->sale_id;
                $Sale_order->delivery_by_id = $request->delivery_by_id;
                $Sale_order->channal = $request->channal;
                $Sale_order->name = $request->name;
                $Sale_order->telephone = $request->telephone;
                $Sale_order->email = $request->email;
                $Sale_order->address = $request->address;
                $Sale_order->shipping_price = $request->shipping_price;
                $Sale_order->cod_price_surcharge = $request->cod_price_surcharge;
                $Sale_order->image_slip = $request->image_slip;
                $Sale_order->bank_id = $request->bank_id;
                $Sale_order->payment_date = $request->payment_date;
                $Sale_order->payment_qty = $request->payment_qty;
                $Sale_order->account_number = $request->account_number;
                $Sale_order->main_discount = $request->main_discount;
                $Sale_order->vat = $request->vat;
                $Sale_order->total = $request->total;
                $Sale_order->status = $request->status;
                $Sale_order->customer_id = $Customer->id;

                // dd($Sale_order->status);
                $Sale_order->payment_type = $request->payment_type;

                //$Sale_order->user_id = $request->user_id;
                $Sale_order->save();
                $Sale_order->Customer;


                //add Withdraw

                //$Item_trans=[];

                //dd($Sale_order);
                for ($i = 0; $i < count($Order); $i++) {

                    //stock Count
                    $stockCount = $this->getStockCount($Order[$i]['item_id'], []);
                    if (abs($Order[$i]['qty']) > $stockCount) {
                        return $this->returnErrorData('Not enough item', 404);
                    }

                    $Order[$i]['item_id'] = $Order[$i]['item_id'];

                    // dd($Order[$i]['item_id']);

                    $Order[$i]['sale_order_id'] = $Sale_order->id;

                    //$Order[$i]['seq'] = $i + 1;
                    $Order[$i]['unit_price'] = floatval($Order[$i]['unit_price']);


                    //dd($Order[$i]['unit_price']);
                    $Order[$i]['create_by'] = $loginBy->user_id;
                    $Order[$i]['created_at'] = Carbon::now()->toDateTimeString();
                    $Order[$i]['updated_at'] = Carbon::now()->toDateTimeString();





                    $Item_trans = new Item_trans();

                    //qty withdraw
                    $qty = -$Order[$i]['qty'];


                    $Item = Item::where('id', $Order[$i]['item_id'])->first();
                    // dd($Item);
                    //stock Count
                    $stockCount = $this->getStockCount($Order[$i]['item_id'], [$Item->location_id]);

                    if (abs($qty) > $stockCount) {
                        return $this->returnErrorData('สินค้าใน stock ไม่พอเบิกออก', 404);
                    }


                    $Item_trans->sale_order_id = $Sale_order->id;
                    $Item_trans->item_id = $Item->id;
                    $Item_trans->qty = $qty;

                    $Item_trans->location_1_id = $Item->location_id;


                    $Item_trans->customer_id = $request->customer_id;
                    $Item_trans->stock = $stockCount;
                    $Item_trans->balance = $stockCount - abs($qty);
                    $Item_trans->status = 1;
                    $Item_trans->operation = 'booking';
                    $Item_trans->date = $request->date_time;
                    $Item_trans->type = 'Withdraw';
                    $Item_trans->create_by = $loginBy->user_id;
                    $Item_trans->save();
                }

                //add order line
                DB::table('sale_order_line')->insert($Order);



                DB::commit();

                return $this->returnSuccess('Successful operation', []);
            }
        } catch (\Throwable $e) {

            DB::rollback();

            return $this->returnErrorData('Something went wrong Please try again' . $e, 404);
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
        $Sale_order = Sale_order::with(['sale_order_lines' => function ($query) {
            $query->with('item.location.warehouse');
            //$query->with('saleorder_id');
        }])
            ->with('saleorder_id.report_stock')
            ->with('sale')
            ->with('customer')
            ->with('user_create')
            ->where('id', $id)
            ->first();

        return $this->returnSuccess('Successful', $Sale_order);
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

        $Order = $request->order;
        $loginBy = $request->login_by;

        if (empty($Order)) {
            return $this->returnErrorData('[order] Data Not Found', 404);
        } else if (!isset($id)) {
            return $this->returnErrorData('[id] Data Not Found', 404);
        } else if (!isset($loginBy)) {
            return $this->returnErrorData('[login_by] Data Not Found', 404);
        }

        DB::beginTransaction();

        try {

            $Sale_order = Sale_order::find($id);
            $Sale_order->date_time = $request->date_time;
            $Sale_order->customer_id = $request->customer_id;
            $Sale_order->delivery_by_id = $request->delivery_by_id;
            $Sale_order->channal = $request->channal;
            $Sale_order->sale_id = $request->sale_id;
            $Sale_order->name = $request->name;
            $Sale_order->telephone = $request->telephone;
            $Sale_order->email = $request->email;
            $Sale_order->address = $request->address;
            $Sale_order->shipping_price = $request->shipping_price;
            $Sale_order->cod_price_surcharge = $request->cod_price_surcharge;
            $Sale_order->image_slip = $request->image_slip;
            $Sale_order->bank_id = $request->bank_id;
            $Sale_order->payment_date = $request->payment_date;
            $Sale_order->payment_qty = $request->payment_qty;
            $Sale_order->account_number = $request->account_number;
            $Sale_order->main_discount = $request->main_discount;
            $Sale_order->vat = $request->vat;
            $Sale_order->total = $request->total;
            $Sale_order->status = $request->status;

            // dd($Sale_order->status);
            $Sale_order->payment_type = $request->payment_type;

            // //sent noti change delivery date
            // if ($request->shipping_date != $Sale_order->original_shipping_date) {

            //     //get user planning

            //     $title = 'Change Order Delivery Date';
            //     $text = 'Order ' . $Sale_order->order_id . ' has Change Delivery Date form ' . $Sale_order->original_shipping_date . ' to ' . $Sale_order->shipping_date;
            //     $type = 'Change Order Delivery Date';

            //     //send line
            //     if ($User->line_token) {
            //         $this->sendLine($User->line_token, $text);
            //     }

            //     //send email
            //     if ($User->email) {
            //         $this->sendMail($User->email, $text, $title, $type);
            //     }
            // }
            $Sale_order->payment_type = $request->payment_type;;

            $Sale_order->customer_id = $request->customer_id;


            $Sale_order->update_by = $loginBy->user_id;
            $Sale_order->updated_at = Carbon::now()->toDateTimeString();

            $Sale_order->save();
            $Sale_order->customer;
            $Sale_order->user_id;

            //add order
            for ($i = 0; $i < count($Order); $i++) {

                //stock Count
                $stockCount = $this->getStockCount($Order[$i]['item_id'], []);

                if (abs($Order[$i]['qty']) > $stockCount) {
                    return $this->returnErrorData('Not enough item', 404);
                }
                //

                $Order[$i]['sale_order_id'] = $id;
                //$Order[$i]['seq'] = $i + 1;

                $Order[$i]['unit_price'] = floatval($Order[$i]['unit_price']);

                $Order[$i]['create_by'] = $loginBy->user_id;
                $Order[$i]['created_at'] = Carbon::now()->toDateTimeString();
                $Order[$i]['updated_at'] = Carbon::now()->toDateTimeString();
            }

            $Sale_order_line = Sale_order_line::where('sale_order_id', $id)->get();

            if ($Sale_order_line->isEmpty()) {

                //add
                DB::table('sale_order_line')->insert($Order);
            } else {

                //del
                for ($i = 0; $i < count($Sale_order_line); $i++) {

                    $Sale_order_line[$i]->deleted_at = Carbon::now()->toDateTimeString();
                    $Sale_order_line[$i]->save();
                }

                //add
                DB::table('sale_order_line')->insert($Order);
            }

            DB::commit();

            return $this->returnUpdate('Successful operation');
        } catch (\Throwable $e) {

            DB::rollback();

            return $this->returnErrorData('Something went wrong Please try again ' . $e, 404);
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

            $Sale_order = Sale_order::find($id);
            $Sale_order->delete();

            DB::commit();

            return $this->returnUpdate('Successful operation');
        } catch (\Throwable $e) {

            DB::rollback();

            return $this->returnErrorData('Something went wrong Please try again ' . $e, 404);
        }
    }

    public function approveSaleOrder(Request $request, $id)
    {
        $loginBy = $request->login_by;

        if (!isset($id)) {
            return $this->returnErrorData('[id] Data Not Found', 404);
        } else if (!isset($request->status)) {
            return $this->returnErrorData('[status] Data Not Found', 404);
        } else if (!isset($loginBy)) {
            return $this->returnErrorData('[login_by] Data Not Found', 404);
        }

        DB::beginTransaction();

        try {

            $Sale_order = Sale_order::find($id);

            $Sale_order->status = $request->status;

            if ($request->status == 'Approved') {
                $Sale_order->status_by = $loginBy->user_id;
                $Sale_order->status_at = Carbon::now()->toDateTimeString();
            }

            if ($request->status == 'Cancel') {
                $Sale_order->status_by = $loginBy->user_id;
                $Sale_order->status_at = Carbon::now()->toDateTimeString();
                $Sale_order->reason = $request->reason;
            }

            if ($request->status == 'Close') {
                $Sale_order->close_by = $loginBy->user_id;
                $Sale_order->close_at = Carbon::now()->toDateTimeString();
            }

            $Sale_order->updated_at = Carbon::now()->toDateTimeString();
            $Sale_order->save();

            DB::commit();

            return $this->returnUpdate('Successful operation');
        } catch (\Throwable $e) {

            DB::rollback();

            return $this->returnErrorData('Something went wrong Please try again ' . $e, 404);
        }
    }


    public function SaleOrderStatus(Request $request, $id)
    {
        $loginBy = $request->login_by;


        // dd($loginBy);

        if (!isset($id)) {
            return $this->returnErrorData('[id] Data Not Found', 404);
        } else if (!isset($request->status)) {
            return $this->returnErrorData('[status] Data Not Found', 404);
        } else if (!isset($loginBy)) {
            return $this->returnErrorData('[login_by] Data Not Found', 404);
        }

        DB::beginTransaction();

        try {

            $Sale_order = Sale_order::find($id);

            $Sale_order->status = $request->status;

            if ($request->status == 'paid') {

                $Sale_order->update_by = $loginBy->user_id;
            }

            if ($request->status == 'confirm') {

                $Item_trans =  Item_trans::where('sale_order_id', $id)->get();

                //add report stock
                $report_stock = new Report_stock();
                $report_stock->report_id = $this->getLastNumber(2);

                $report_stock->date = $request->date;
                $report_stock->sale_order_id = $id;
                $report_stock->create_by = $loginBy->user_id;

                $report_stock->status = 'Open';
                $report_stock->type = 'Withdraw';

                $report_stock->doc_id = 2;
                $report_stock->save();

                $report_stock->doc;
                //dd($report_stock->doc);
                //run doc
                $this->setRunDoc(2, $report_stock->report_id);
                for ($i = 0; $i < count($Item_trans); $i++) {

                    $Item_trans[$i]['report_stock_id'] = $report_stock->id;
                    //$Item_trans[$i]['customer_id'] = $Sale_order->id;
                    $Item_trans[$i]['status'] = 1;
                    $Item_trans[$i]['operation'] = 'finish';
                    $Item_trans[$i]['type'] = 'Withdraw';
                    $Item_trans[$i]['create_by'] = $loginBy->user_id;
                    $Item_trans[$i]->save();
                }
            }

            if ($request->status == 'packing') {

                $Sale_order->update_by = $loginBy->user_id;
            }

            if ($request->status == 'delivery') {

                $Sale_order->update_by = $loginBy->user_id;
            }
            if ($request->status == 'finish') {

                $Sale_order->update_by = $loginBy->user_id;
            }

            if ($request->status == 'failed') {

                $Sale_order->update_by = $loginBy->user_id;
            }


            $Sale_order->updated_at = Carbon::now()->toDateTimeString();
            $Sale_order->save();

            DB::commit();

            return $this->returnUpdate('Successful operation');
        } catch (\Throwable $e) {

            DB::rollback();

            return $this->returnErrorData('Something went wrong Please try again ' . $e, 404);
        }
    }

    // public function ImportSaleOrder(Request $request)
    // {
    //     $customerId = $request->customer_id;
    //     $approve = $request->approve;

    //     $loginBy = $request->login_by;

    //     if (!isset($customerId)) {
    //         return $this->returnErrorData('[customer_id] Data Not Found', 404);
    //     } else if (!isset($approve)) {
    //         return $this->returnErrorData('[approve] Data Not Found', 404);
    //     } else if (!isset($loginBy)) {
    //         return $this->returnErrorData('[login_by] Data Not Found', 404);
    //     }

    //     $file = request()->file('file');
    //     $fileName = $file->getClientOriginalName();

    //     $Data = Excel::toArray(new SaleOrderImport(), $file);
    //     $data = $Data[0];

    //     if (count($data) > 0) {

    //         DB::beginTransaction();

    //         try {

    //             /////////////////////////////////// add sale order ///////////////////////////////////

    //             for ($i = 0; $i < count($data); $i++) {

    //                 //head
    //                 $poNo = trim($data[$i]['po_no']);
    //                 $orderDate = trim($data[$i]['order_date']);
    //                 // $delDate = trim($data[$i]['del_date']);

    //                 $row = $i + 2;

    //                 // if ($delDate == '') {
    //                 //     return $this->returnErrorData('Row excel data ' . $row . ' please enter del date', 404);
    //                 // } else
    //                 if ($poNo == '') {
    //                     return $this->returnErrorData('Row excel data ' . $row . ' please enter po no', 404);
    //                 } else if ($orderDate == '') {
    //                     return $this->returnErrorData('Row excel data ' . $row . ' please enter order date', 404);
    //                 }

    //                 // //delDate
    //                 // $dateDelDate = Carbon::instance(\PhpOffice\PhpSpreadsheet\Shared\Date::excelToDateTimeObject($delDate));
    //                 // $DelDate = date("Y-m-d", strtotime($dateDelDate));

    //                 //OrderDate
    //                 $dateOrderDate = Carbon::instance(\PhpOffice\PhpSpreadsheet\Shared\Date::excelToDateTimeObject($orderDate));
    //                 $OrderDate = date("Y-m-d", strtotime($dateOrderDate));

    //                 //customer
    //                 $Customer = Customer::where('id', $customerId)->first();
    //                 $customerId = $Customer->id;

    //                 //check row sample
    //                 if ($poNo == 'SIMPLE-000') {
    //                     //
    //                 } else {

    //                     //po sale
    //                     $Sale_order = Sale_order::where('ref_no', $poNo)->first();

    //                     if (!$Sale_order) {

    //                         //add sale order
    //                         $Sale_order = new Sale_order();
    //                         $Sale_order->order_id = $this->getLastNumber(5);

    //                         //run number
    //                         $this->setRunDoc(5, $Sale_order->order_id);

    //                         $Sale_order->date = $OrderDate;
    //                         $Sale_order->ref_no = $poNo;
    //                         $Sale_order->customer_id = $customerId;

    //                         if ($approve == 1 || $approve == '1') {
    //                             $Sale_order->status = 'Approved';
    //                         } else {
    //                             $Sale_order->status = 'Open';
    //                         }

    //                         $Sale_order->user_id = null;

    //                         $Sale_order->save();

    //                     }

    //                 }
    //             }

    //             //////////////////////////////////////////////////////////////////////////////////////

    //             /////////////////////////////////// add sale order line ///////////////////////////////////

    //             for ($i = 0; $i < count($data); $i++) {

    //                 //head
    //                 $poNo = trim($data[$i]['po_no']);

    //                 //body
    //                 $itemId = trim($data[$i]['drawing_no'] . $data[$i]['cm']);
    //                 $itemName = trim($data[$i]['description']);
    //                 $qty = trim($data[$i]['po_qty']);
    //                 $unit = trim($data[$i]['unit']);
    //                 $unitPrice = trim($data[$i]['price']);

    //                 $row = $i + 2;

    //                 if ($poNo == '') {
    //                     return $this->returnErrorData('Row excel data ' . $row . ' please enter po no', 404);
    //                 } else if ($itemId == '') {
    //                     return $this->returnErrorData('Row excel data ' . $row . ' please enter item id', 404);
    //                 } else if ($itemName == '') {
    //                     return $this->returnErrorData('Row excel data ' . $row . ' please enter description', 404);
    //                 } else if ($qty == '') {
    //                     return $this->returnErrorData('Row excel data ' . $row . ' please enter po qty', 404);
    //                 } else if ($unit == '') {
    //                     return $this->returnErrorData('Row excel data ' . $row . ' please enter unit', 404);
    //                 } else if ($unitPrice == '') {
    //                     return $this->returnErrorData('Row excel data ' . $row . ' please enter unit price', 404);
    //                 }

    //                 //check row sample
    //                 if ($poNo == 'SIMPLE-000') {
    //                     //
    //                 } else {

    //                     //check item
    //                     $checkItem = Item::where('item_id', $itemId)->first();
    //                     if (!$checkItem) {
    //                         return $this->returnErrorData('Item Id ' . $itemId . ' was not found in the system', 404);
    //                     }
    //                     $ItemId = $checkItem->id;

    //                     //check Unit convertion
    //                     if ($unit) {
    //                         $Unit_convertion = Unit_convertion::where('name', $unit)->first();
    //                         if (!$Unit_convertion) {
    //                             return $this->returnErrorData('Unit ' . $unit . ' was not found in the system', 404);
    //                         }

    //                         $unitConvertionId = $Unit_convertion->id;

    //                     } else {
    //                         $unitConvertionId = null;
    //                     }

    //                     //po sale
    //                     $Sale_order = Sale_order::where('ref_no', $poNo)->first();

    //                     //seq
    //                     $seq = Sale_order_line::where('sale_order_id', $Sale_order->id)
    //                         ->orderby('seq', 'DESC')
    //                         ->first();

    //                     if ($seq) {
    //                         $SEQ = intval($seq->seq) + 1;
    //                     } else {
    //                         $SEQ = 1;
    //                     }
    //                     //

    //                     $Sale_order_line = new Sale_order_line();
    //                     $Sale_order_line->sale_order_id = $Sale_order->id; //id
    //                     $Sale_order_line->seq = $SEQ;
    //                     $Sale_order_line->item_id = $ItemId;
    //                     $Sale_order_line->item_name = $itemName;
    //                     $Sale_order_line->qty = $qty;
    //                     $Sale_order_line->unit_convertion_id = $unitConvertionId;
    //                     $Sale_order_line->unit_price = floatval($unitPrice);
    //                     $Sale_order_line->total_price = floatval(floatval($unitPrice) * $qty);
    //                     $Sale_order_line->discount = 0.00;
    //                     $Sale_order_line->amount = floatval(floatval($unitPrice) * $qty);
    //                     $Sale_order_line->create_by = $loginBy->user_id;
    //                     $Sale_order_line->created_at = date('Y-m-d H:i:s');
    //                     $Sale_order_line->updated_at = date('Y-m-d H:i:s');
    //                     $Sale_order_line->save();

    //                     //update sale order
    //                     $tax = 7.00;

    //                     $Amount = floatval(floatval($unitPrice) * $qty);

    //                     $updatesaleOrder = Sale_order::find($Sale_order_line->sale_order_id);
    //                     $sum = floatval(str_replace(',', '', $updatesaleOrder->amount));

    //                     $updatesaleOrder->amount = $sum + $Amount;
    //                     $updatesaleOrder->tax = floatval($tax);
    //                     $updatesaleOrder->tax_amount = floatval((floatval($sum) * $tax) / 100);
    //                     $updatesaleOrder->net_amount = floatval(floatval($sum) + floatval(($Amount * $tax) / 100));
    //                     $updatesaleOrder->save();
    //                 }

    //             }

    //             //////////////////////////////////////////////////////////////////////////////////////

    //             //log
    //             $userId = $loginBy->user_id;
    //             $type = 'Import Sale Oder';
    //             $description = 'User ' . $userId . ' has ' . $type;
    //             $this->Log($userId, $description, $type);

    //             DB::commit();

    //             return $this->returnSuccess('Successful operation', []);

    //         } catch (\Throwable $e) {

    //             DB::rollback();

    //             return $this->returnErrorData('Something went wrong Please try again ' . $e, 404);
    //         }

    //     } else {
    //         return $this->returnErrorData('Data Not Found', 404);
    //     }

    // }

    public function ImportSaleOrder(Request $request)
    {
        $customerId = $request->customer_id;
        $approve = $request->approve;

        $date = $request->date;

        $loginBy = $request->login_by;

        if (!isset($customerId)) {
            return $this->returnErrorData('[customer_id] Data Not Found', 404);
        } else if (!isset($date)) {
            return $this->returnErrorData('[date] Data Not Found', 404);
        } else if (!isset($approve)) {
            return $this->returnErrorData('[approve] Data Not Found', 404);
        } else if (!isset($loginBy)) {
            return $this->returnErrorData('[login_by] Data Not Found', 404);
        }

        $file = request()->file('file');
        $fileName = $file->getClientOriginalName();

        $Data = Excel::toArray(new SaleOrderImport(), $file);
        $data = $Data[0];

        if (count($data) > 0) {

            DB::beginTransaction();

            try {

                //group item
                $item = [];
                for ($i = 0; $i < count($data); $i++) {
                    $item[$i]['item_id'] = trim($data[$i]['drawing_no'] . $data[$i]['cm']);

                    $strDate = trim($data[$i]['del_date']);
                    $dateDelDate = Carbon::instance(\PhpOffice\PhpSpreadsheet\Shared\Date::excelToDateTimeObject($strDate));
                    $DelDate = date("Y-m-d", strtotime($dateDelDate));

                    $item[$i]['del_date'] = $DelDate;
                }

                $Item = array();

                foreach ($item as $current) {
                    if (!in_array($current, $Item)) {
                        $Item[] = $current;
                    }
                }
                //

                $trnasNo = date('YmdHis') . rand(0, 999);

                /////////////////////////////////// add sale order ///////////////////////////////////
                for ($i = 0; $i < count($Item); $i++) {

                    //check item
                    $checkItem = Item::where('item_id', $Item[$i]['item_id'])->first();
                    if (!$checkItem) {
                        return $this->returnErrorData('Item Id ' . $Item[$i]['item_id'] . ' was not found in the system', 404);
                    }

                    $ItemId = $checkItem->id;

                    //add sale order
                    $Sale_order = new Sale_order();
                    $Sale_order->order_id = $this->getLastNumber(5);

                    //run number
                    $this->setRunDoc(5, $Sale_order->order_id);

                    $Sale_order->date = $date;
                    $Sale_order->item_id = $ItemId;
                    $Sale_order->customer_id = $customerId;

                    $Sale_order->transection_no = $trnasNo;
                    $Sale_order->del_date = $Item[$i]['del_date'];

                    if ($approve == 1 || $approve == '1') {
                        $Sale_order->status = 'Approved';
                    } else {
                        $Sale_order->status = 'Open';
                    }

                    $Sale_order->user_id = null;

                    $Sale_order->save();
                }

                //////////////////////////////////////////////////////////////////////////////////////

                /////////////////////////////////// add sale order line ///////////////////////////////////

                for ($i = 0; $i < count($data); $i++) {

                    //body
                    $itemId = trim($data[$i]['drawing_no'] . $data[$i]['cm']);
                    $itemName = trim($data[$i]['description']);
                    $poNo = trim($data[$i]['po_no']);
                    $qty = trim($data[$i]['po_qty']);
                    $delDate = trim($data[$i]['del_date']);
                    $unit = trim($data[$i]['unit']);
                    $unitPrice = trim($data[$i]['price']);

                    $row = $i + 2;

                    if ($itemId == '') {
                        return $this->returnErrorData('Row excel data ' . $row . ' please enter item id', 404);
                    } else if ($itemName == '') {
                        return $this->returnErrorData('Row excel data ' . $row . ' please enter description', 404);
                    } else if ($poNo == '') {
                        return $this->returnErrorData('Row excel data ' . $row . ' please enter po no', 404);
                    } else if ($qty == '') {
                        return $this->returnErrorData('Row excel data ' . $row . ' please enter po qty', 404);
                    } else if ($delDate == '') {
                        return $this->returnErrorData('Row excel data ' . $row . ' please enter del date', 404);
                    } else if ($unit == '') {
                        return $this->returnErrorData('Row excel data ' . $row . ' please enter unit', 404);
                    } else if ($unitPrice == '') {
                        return $this->returnErrorData('Row excel data ' . $row . ' please enter unit price', 404);
                    }

                    //check row sample
                    if ($poNo == 'SIMPLE-000') {
                        //
                    } else {

                        //check item
                        $checkItem = Item::where('item_id', $itemId)->first();
                        if (!$checkItem) {
                            return $this->returnErrorData('Item Id ' . $itemId . ' was not found in the system', 404);
                        }
                        $ItemId = $checkItem->id;

                        //check Unit convertion
                        if ($unit) {
                            $Unit_convertion = Unit_convertion::where('name', $unit)->first();
                            if (!$Unit_convertion) {
                                return $this->returnErrorData('Unit ' . $unit . ' was not found in the system', 404);
                            }

                            $unitConvertionId = $Unit_convertion->id;
                        } else {
                            $unitConvertionId = null;
                        }

                        //delDate
                        $dateDelDate = Carbon::instance(\PhpOffice\PhpSpreadsheet\Shared\Date::excelToDateTimeObject($delDate));
                        $DelDate = date("Y-m-d", strtotime($dateDelDate));

                        //
                        $Sale_order = Sale_order::where('item_id', $ItemId)
                            ->where('transection_no', $trnasNo)
                            ->where('del_date', $DelDate)
                            ->first();

                        //seq
                        $seq = Sale_order_line::where('sale_order_id', $Sale_order->id)
                            ->orderby('seq', 'DESC')
                            ->first();

                        if ($seq) {
                            $SEQ = intval($seq->seq) + 1;
                        } else {
                            $SEQ = 1;
                        }
                        //

                        $Sale_order_line = new Sale_order_line();
                        $Sale_order_line->sale_order_id = $Sale_order->id; //id
                        $Sale_order_line->seq = $SEQ;
                        $Sale_order_line->item_id = $ItemId;
                        $Sale_order_line->item_name = $itemName;
                        $Sale_order_line->po_no = $poNo;
                        $Sale_order_line->del_date = $DelDate;
                        $Sale_order_line->qty = $qty;
                        $Sale_order_line->unit_convertion_id = $unitConvertionId;
                        $Sale_order_line->unit_price = floatval($unitPrice);

                        $Sale_order_line->create_by = $loginBy->user_id;
                        $Sale_order_line->created_at = date('Y-m-d H:i:s');
                        $Sale_order_line->updated_at = date('Y-m-d H:i:s');
                        $Sale_order_line->save();
                    }
                }

                //////////////////////////////////////////////////////////////////////////////////////

                //log
                $userId = $loginBy->user_id;
                $type = 'Import Sale Oder';
                $description = 'User ' . $userId . ' has ' . $type;
                $this->Log($userId, $description, $type);

                DB::commit();

                return $this->returnSuccess('Successful operation', []);
            } catch (\Throwable $e) {

                DB::rollback();

                return $this->returnErrorData('Something went wrong Please try again ' . $e, 404);
            }
        } else {
            return $this->returnErrorData('Data Not Found', 404);
        }
    }
}
