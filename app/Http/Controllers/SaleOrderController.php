<?php

namespace App\Http\Controllers;

use App\Imports\SaleOrderImport;
use App\Models\Customer;
use App\Models\CustomerLine;
use App\Models\Delivered_by;
use App\Models\Item;
use App\Models\Item_line;
use App\Models\Item_trans;
use App\Models\ProductLive;
use App\Models\Qty_sale_order_job;
use App\Models\Report_stock;
use App\Models\Sale_order;
use App\Models\Sale_order_line;
use App\Models\Unit_convertion;
use App\Models\User;
use App\Models\User_page;
use App\Services\FacebookApi;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Maatwebsite\Excel\Facades\Excel;

class SaleOrderController extends Controller
{

    protected $_facebookApi;

    public function __construct(FacebookApi $facebookApi)
    {
        $this->_facebookApi = $facebookApi;
    }

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

        //check sale
        $loginBy = $request->login_by;

        if ($loginBy->permission->id == 1) {
            $saleId = null;
        } else {
            $saleId = $loginBy->id;
        }
        //

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
            'track_no',
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

        $D = Sale_order::select($col)->where('channal', '!=', 'SP')
            ->with('sale')
            ->with('user_create')
            ->with('item_code');

        if ($saleId) {
            $D->where('sale_id', $saleId);
        }

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


                if ($request->payment_type  == 'COD') {


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
                } else if ($request->payment_type  == 'transfer') {

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
                    $Sale_order->status = "paid";
                    $Sale_order->customer_id = $Customer->id;
                    // dd($Sale_order->status);
                    $Sale_order->payment_type = $request->payment_type;
                    $Sale_order->create_by = $loginBy->user_id;
                    $Sale_order->save();
                    $Sale_order->Customer;
                }

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
                $description = 'User' . $userId . ' has ' . $type;
                $this->LogSaleOrder($userId, $description, $type);

                //  DB::commit();

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
            ->orWhere('order_id', '=', $id)
            ->first();

        return $this->returnSuccess('Successful', $Sale_order);
    }

    public function getOrderLiveById($id)
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

    public function SaleOrderTrack(Request $request)
    {
        $loginBy = $request->login_by;

        if (!isset($request->order_id)) {
            return $this->returnErrorData('[order_id] Data Not Found', 404);
        } else if (!isset($request->track_no)) {
            return $this->returnErrorData('[track_no] Data Not Found', 404);
        } else if (!isset($loginBy)) {
            return $this->returnErrorData('[login_by] Data Not Found', 404);
        }

        DB::beginTransaction();

        try {

            $Sale_order = Sale_order::where('order_id', $request->order_id)->first();

            if (!$Sale_order) {
                return $this->returnErrorData('ไม่มีเลขที่รายการนี้ในระบบ', 404);
            }

            $Sale_order->track_no = $request->track_no;

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

    //     $date = $request->date;

    //     $loginBy = $request->login_by;

    //     if (!isset($customerId)) {
    //         return $this->returnErrorData('[customer_id] Data Not Found', 404);
    //     } else if (!isset($date)) {
    //         return $this->returnErrorData('[date] Data Not Found', 404);
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

    //             //group item
    //             $item = [];
    //             for ($i = 0; $i < count($data); $i++) {
    //                 $item[$i]['item_id'] = trim($data[$i]['drawing_no'] . $data[$i]['cm']);

    //                 $strDate = trim($data[$i]['del_date']);
    //                 $dateDelDate = Carbon::instance(\PhpOffice\PhpSpreadsheet\Shared\Date::excelToDateTimeObject($strDate));
    //                 $DelDate = date("Y-m-d", strtotime($dateDelDate));

    //                 $item[$i]['del_date'] = $DelDate;
    //             }

    //             $Item = array();

    //             foreach ($item as $current) {
    //                 if (!in_array($current, $Item)) {
    //                     $Item[] = $current;
    //                 }
    //             }
    //             //

    //             $trnasNo = date('YmdHis') . rand(0, 999);

    //             /////////////////////////////////// add sale order ///////////////////////////////////
    //             for ($i = 0; $i < count($Item); $i++) {

    //                 //check item
    //                 $checkItem = Item::where('item_id', $Item[$i]['item_id'])->first();
    //                 if (!$checkItem) {
    //                     return $this->returnErrorData('Item Id ' . $Item[$i]['item_id'] . ' was not found in the system', 404);
    //                 }

    //                 $ItemId = $checkItem->id;

    //                 //add sale order
    //                 $Sale_order = new Sale_order();
    //                 $Sale_order->order_id = $this->getLastNumber(5);

    //                 //run number
    //                 $this->setRunDoc(5, $Sale_order->order_id);

    //                 $Sale_order->date = $date;
    //                 $Sale_order->item_id = $ItemId;
    //                 $Sale_order->customer_id = $customerId;

    //                 $Sale_order->transection_no = $trnasNo;
    //                 $Sale_order->del_date = $Item[$i]['del_date'];

    //                 if ($approve == 1 || $approve == '1') {
    //                     $Sale_order->status = 'Approved';
    //                 } else {
    //                     $Sale_order->status = 'Open';
    //                 }

    //                 $Sale_order->user_id = null;

    //                 $Sale_order->save();
    //             }

    //             //////////////////////////////////////////////////////////////////////////////////////

    //             /////////////////////////////////// add sale order line ///////////////////////////////////

    //             for ($i = 0; $i < count($data); $i++) {

    //                 //body
    //                 $itemId = trim($data[$i]['drawing_no'] . $data[$i]['cm']);
    //                 $itemName = trim($data[$i]['description']);
    //                 $poNo = trim($data[$i]['po_no']);
    //                 $qty = trim($data[$i]['po_qty']);
    //                 $delDate = trim($data[$i]['del_date']);
    //                 $unit = trim($data[$i]['unit']);
    //                 $unitPrice = trim($data[$i]['price']);

    //                 $row = $i + 2;

    //                 if ($itemId == '') {
    //                     return $this->returnErrorData('Row excel data ' . $row . ' please enter item id', 404);
    //                 } else if ($itemName == '') {
    //                     return $this->returnErrorData('Row excel data ' . $row . ' please enter description', 404);
    //                 } else if ($poNo == '') {
    //                     return $this->returnErrorData('Row excel data ' . $row . ' please enter po no', 404);
    //                 } else if ($qty == '') {
    //                     return $this->returnErrorData('Row excel data ' . $row . ' please enter po qty', 404);
    //                 } else if ($delDate == '') {
    //                     return $this->returnErrorData('Row excel data ' . $row . ' please enter del date', 404);
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

    //                     //delDate
    //                     $dateDelDate = Carbon::instance(\PhpOffice\PhpSpreadsheet\Shared\Date::excelToDateTimeObject($delDate));
    //                     $DelDate = date("Y-m-d", strtotime($dateDelDate));

    //                     //
    //                     $Sale_order = Sale_order::where('item_id', $ItemId)
    //                         ->where('transection_no', $trnasNo)
    //                         ->where('del_date', $DelDate)
    //                         ->first();

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
    //                     $Sale_order_line->po_no = $poNo;
    //                     $Sale_order_line->del_date = $DelDate;
    //                     $Sale_order_line->qty = $qty;
    //                     $Sale_order_line->unit_convertion_id = $unitConvertionId;
    //                     $Sale_order_line->unit_price = floatval($unitPrice);

    //                     $Sale_order_line->create_by = $loginBy->user_id;
    //                     $Sale_order_line->created_at = date('Y-m-d H:i:s');
    //                     $Sale_order_line->updated_at = date('Y-m-d H:i:s');
    //                     $Sale_order_line->save();
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

    public function addOrderFromLine($bot_msg)
    {

        $sale_id = "";
        $name = "";
        $phone = "";
        $address = "";
        $product = "";
        $qty = "";
        $price = "";

        $msg = explode(':', $bot_msg);

        $ms = explode(',', $msg[1]);
        $sale_id = $ms[0];
        $ms = explode(',', $msg[2]);
        $name = $ms[0];
        $ms = explode(',', $msg[3]);
        $phone = $ms[0];
        $ms = explode(',', $msg[4]);
        $address = $ms[0];
        $ms = explode(',', $msg[5]);
        $item = $ms[0];
        $ms = explode(',', $msg[5]);
        $qty = $ms[0];
        $ms = explode(',', $msg[6]);
        $price = $ms[0];
        // $ms = explode(',', $msg[7]);


        $checkName = Customer::where('phone', $phone)->first();
        $sale = User::where('user_id', $sale_id)->first();
        $product = Item::where('item_id', $item)->first();


        DB::beginTransaction();

        try {

            if ($checkName) {

                //add order
                $Sale_order = new Sale_order();
                $Customer = $checkName;

                $Sale_order->order_id = $this->getLastNumber(5);

                //run number
                $this->setRunDoc(5, $Sale_order->order_id);
                $Sale_order->date_time = date('Y-m-d');
                $Sale_order->customer_id = $Customer->customer_id;
                $Sale_order->delivery_by_id = 1;
                $Sale_order->channal = "facebook";
                $Sale_order->name = $Customer->name;
                $Sale_order->sale_id = $sale->id;
                $Sale_order->telephone = $phone;
                $Sale_order->email = $Customer->email;
                $Sale_order->address = $address;
                $Sale_order->shipping_price = 0;
                $Sale_order->cod_price_surcharge = 0;

                $Sale_order->vat = 0;
                $Sale_order->total = floatval($qty) * floatval($price);
                $Sale_order->status = "order";
                $Sale_order->customer_id = $Customer->id;
                // dd($Sale_order->status);
                $Sale_order->payment_type = "COD";
                $Sale_order->create_by = $sale->user_id;
                $Sale_order->save();
                $Sale_order->Customer;



                //stock Count
                $stockCount = $this->getStockCount($product->id, []);

                if (abs($qty) > $stockCount) {
                    return false;
                }

                $Order[0]['item_id'] = $product->item_id;


                $Order[0]['sale_order_id'] = $Sale_order->id;
                $unit_price = $price / $qty;
                $Order[0]['unit_price'] = floatval($unit_price);


                $Order[0]['create_by'] = $sale->user_id;
                $Order[0]['created_at'] = Carbon::now()->toDateTimeString();
                $Order[0]['updated_at'] = Carbon::now()->toDateTimeString();



                $Item_trans = new Item_trans();

                //qty withdraw
                $qty = -$Order[0]['qty'];


                $Item = Item::where('id', $Order[0]['item_id'])->first();


                $stockCount = $this->getStockCount($Order[0]['item_id'], []);


                if (abs($qty) > $stockCount) {
                    return false;
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
                $Item_trans->date = date('Y-m-d');
                $Item_trans->type = 'Withdraw';
                $Item_trans->create_by = $sale->user_id;


                $Item_trans->save();

                //add customer_line
                $Customer_line = new CustomerLine();

                $Customer_line->customer_id = $Customer->id;

                $Customer_line->address = $address;

                $Customer_line->save();

                //add order line
                DB::table('sale_order_line')->insert($Order);


                //log
                $userId = $sale->user_id;
                $type = 'ขายสินค้า';
                $description = 'User' . $userId . ' has ' . $type;
                $this->LogSaleOrder($userId, $description, $type);

                DB::commit();

                return true;
            } else {

                $Customer = new Customer();

                $Customer->name = $name;
                $Customer->phone = $phone;
                $Customer->email = "";
                $Customer->create_by = $sale->user_id;
                $Customer->save();

                $CustomerLine = new CustomerLine();
                $CustomerLine->address = $address;
                $CustomerLine->customer_id = $Customer->id;
                $CustomerLine->save();

                // //add order
                $Sale_order = new Sale_order();

                $Sale_order->order_id = $this->getLastNumber(5);

                // //run number
                $this->setRunDoc(5, $Sale_order->order_id);

                $Sale_order->date_time = date('Y-m-d');
                $Sale_order->customer_id = $Customer->id;
                $Sale_order->delivery_by_id = 1;
                $Sale_order->channal = "facebook";
                $Sale_order->name = $name;
                $Sale_order->sale_id = $sale->id;

                $Sale_order->telephone = $phone;
                $Sale_order->email = "";
                $Sale_order->address = $address;
                $Sale_order->shipping_price = 0;
                $Sale_order->cod_price_surcharge = 0;
                $Sale_order->vat = 0;
                $Sale_order->total = floatval($qty) * floatval($price);
                $Sale_order->status = "order";
                $Sale_order->payment_type = "COD";
                $Sale_order->create_by = $sale->user_id;
                $Sale_order->save();
                // $Sale_order->Customer;


                //stock Count
                $stockCount = $this->getStockCount($product->id, []);
                // if (abs($qty) > $stockCount) {
                //     return false;
                // }

                // $Order[0]['item_id'] = $product->item_id;


                // $Order[0]['sale_order_id'] = $Sale_order->id;
                // $unit_price = $price / $qty;
                // $Order[0]['unit_price'] = floatval($unit_price);


                // $Order[0]['create_by'] = $sale->user_id;
                // $Order[0]['created_at'] = Carbon::now()->toDateTimeString();
                // $Order[0]['updated_at'] = Carbon::now()->toDateTimeString();



                // $Item_trans = new Item_trans();

                // //qty withdraw
                // $qty = -$Order[0]['qty'];


                // $Item = Item::where('id', $Order[0]['item_id'])->first();

                // $Item_trans->sale_order_id = $Sale_order->id;
                // $Item_trans->item_id = $Item->id;
                // $Item_trans->qty = $qty;

                // $Item_trans->location_1_id = $Item->location_id;

                // $Item_trans->customer_id = $Customer->id;

                // $Item_trans->stock = $stockCount;
                // $Item_trans->balance = $stockCount - abs($qty);
                // $Item_trans->status = 1;
                // $Item_trans->operation = 'booking';
                // $Item_trans->date = date('Y-m-d');
                // $Item_trans->type = 'Withdraw';
                // $Item_trans->create_by = $sale->user_id;


                // $Item_trans->save();

                // //add order line
                // DB::table('sale_order_line')->insert($Order);



                DB::commit();

                return true;
            }
        } catch (\Throwable $e) {

            DB::rollback();

            return false;
        }
    }

    public function lineBot()
    {
        $accessToken = "/RsppJXyCaZQI9gLn30mNGGH3CybT0o5JwJXAVUhmBUVYF64S7rn2aEYF9uS/ROP8EBAOPSRomnnCrv6Rj9fuFccS8Yeu8GF1Diemd0uxb46vvS0eGUkWGu+9TWPY1jIoJypr3OPyg82ptIOdW0AegdB04t89/1O/w1cDnyilFU="; //copy ข้อความ Channel access token ตอนที่ตั้งค่า

        $content = file_get_contents('php://input');
        $arrayJson = json_decode($content, true);
        $arrayHeader = array();
        $arrayHeader[] = "Content-Type: application/json";
        $arrayHeader[] = "Authorization: Bearer {$accessToken}";
        //รับข้อความจากผู้ใช้
        $message = $arrayJson['events'][0]['message']['text'];

        //รับ id ว่ามาจากไหน
        if (isset($arrayJson['events'][0]['source']['userId'])) {
            $id = $arrayJson['events'][0]['source']['userId'];
        } else if (isset($arrayJson['events'][0]['source']['groupId'])) {
            $id = $arrayJson['events'][0]['source']['groupId'];
        } else if (isset($arrayJson['events'][0]['source']['room'])) {
            $id = $arrayJson['events'][0]['source']['room'];
        }
        // $message = explode(':', $message);

        // for ($i = 0; count($message); $i++) {
        //     $ms = explode(',', $message[$i]);

        //     $arrayPostData['to'] = $id;
        //     $arrayPostData['messages'][0]['type'] = "text";
        //     $arrayPostData['messages'][0]['text'] = $ms[0];
        //     // $arrayPostData['messages'][1]['type'] = "sticker";
        //     // $arrayPostData['messages'][1]['packageId'] = "2";
        //     // $arrayPostData['messages'][1]['stickerId'] = "34";
        //     $this->pushMsg($arrayHeader, $arrayPostData);
        // }

        $resp = $this->addOrderFromLine($message);
        // $arrayPostData['to'] = $id;
        // $arrayPostData['messages'][0]['type'] = "text";
        // $arrayPostData['messages'][0]['text'] = $resp;
        // $arrayPostData['messages'][1]['type'] = "sticker";
        // $arrayPostData['messages'][1]['packageId'] = "6136";
        // $arrayPostData['messages'][1]['stickerId'] = "10551380";
        // $this->pushMsg($arrayHeader, $arrayPostData);
        // return false;
        if ($resp == false) {
            $arrayPostData['to'] = $id;
            $arrayPostData['messages'][0]['type'] = "text";
            $arrayPostData['messages'][0]['text'] = "สร้างไม่สำเร็จ สินค้าอาจไม่พอ หรือ รหัสสินค้าไม่ถูกต้อง";
            $arrayPostData['messages'][1]['type'] = "sticker";
            $arrayPostData['messages'][1]['packageId'] = "6136";
            $arrayPostData['messages'][1]['stickerId'] = "10551380";
            $this->pushMsg($arrayHeader, $arrayPostData);
        } else {
            $arrayPostData['to'] = $id;
            $arrayPostData['messages'][0]['type'] = "text";
            $arrayPostData['messages'][0]['text'] = "สร้างรายการสำเร็จ";
            $arrayPostData['messages'][1]['type'] = "sticker";
            $arrayPostData['messages'][1]['packageId'] = "8522";
            $arrayPostData['messages'][1]['stickerId'] = "16581267";
            $this->pushMsg($arrayHeader, $arrayPostData);
        }
        #ตัวอย่าง Message Type "Text + Sticker"
        // if ($message == "ลูกค้า") {
        // $arrayPostData['to'] = $id;
        // $arrayPostData['messages'][0]['type'] = "text";
        // $arrayPostData['messages'][0]['text'] = $message;
        // $arrayPostData['messages'][1]['type'] = "sticker";
        // $arrayPostData['messages'][1]['packageId'] = "2";
        // $arrayPostData['messages'][1]['stickerId'] = "34";
        // $this->pushMsg($arrayHeader, $arrayPostData);
        // }
    }

    public function pushMsg($arrayHeader, $arrayPostData)
    {
        $strUrl = "https://api.line.me/v2/bot/message/push";
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $strUrl);
        curl_setopt($ch, CURLOPT_HEADER, false);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $arrayHeader);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($arrayPostData));
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        $result = curl_exec($ch);
        curl_close($ch);
    }

    public function orderFromLive(Request $request)
    {

        // $this->_facebookApi->SendMessageToUser("116311434766128");

        $name = $request->name;
        $email = $request->email;
        $channal = $request->channal;

        $orders = $request->orders;

        $page_id = $request->page_id;
        $fb_user_id = $request->fb_user_id;
        $fb_comment_id = $request->fb_comment_id;

        // $orders = str_replace(" ", "", $orders);
        // $orders = str_replace("CF", "", $orders);
        // $orders = str_replace("cf", "", $orders);

        // $item = explode("X", $orders);

        // $item_id = $item[0];
        $item_id = $orders;
        // $qty = $item[1];
        $qty = 1;


        if (!isset($request->email)) {
            return $this->returnErrorData('กรุณาใส่ Email', 404);
        } else if (!isset($request->channal)) {
            return $this->returnErrorData('กรุณาเลือกช่องทางในการขาย', 404);
        } else if (!isset($item_id)) {
            return $this->returnErrorData('กรุณาเลือกสินค้าในการขาย', 404);
        } else if (!isset($qty)) {
            return $this->returnErrorData('กรุณาเลือกจำนวนในการขาย', 404);
        }

        $checkName = Customer::where(function ($query) use ($request) {

            $query->orwhere('email', $request->email)
                ->orWhere('name', $request->name);
        })
            ->first();

        $ItemReal = ProductLive::where('code', $item_id)->first();

        if (!$ItemReal) {
            return $this->returnSuccess('ไม่มีรหัสสินค้า ' . $item_id . ' ในระบบ', null);
        }

        $Item = Item::find($ItemReal->item_id);

        //Sale Shop
        $page = User_page::where('user_id', $request->sale_id)
            ->where('page_id', $request->page_id)
            ->first();

        DB::beginTransaction();

        try {

            if ($checkName) {

                //add order
                $Sale_order = new Sale_order();
                $Customer = $checkName;

                $Sale_order->order_id = $this->getLastNumber(5);

                //run number
                $this->setRunDoc(5, $Sale_order->order_id);
                $Sale_order->date_time = date('Y-m-d');
                $Sale_order->customer_id = $Customer->customer_id;
                $Sale_order->delivery_by_id = 1;
                $Sale_order->channal = $channal;
                $Sale_order->name = $Customer->name;
                $Sale_order->telephone = $Customer->phone;
                $Sale_order->email = $Customer->email;
                $Sale_order->address = "";
                $Sale_order->shipping_price = 0;
                $Sale_order->cod_price_surcharge = 0;

                $Sale_order->vat = 0;
                $Sale_order->total = floatval($qty) * floatval($Item->unit_price);
                $Sale_order->status = "order";
                $Sale_order->customer_id = $Customer->id;
                $Sale_order->payment_type = "";

                $Sale_order->page_id = $page_id;
                $Sale_order->fb_user_id = $fb_user_id;
                $Sale_order->fb_comment_id = $fb_comment_id;

                $Sale_order->sale_id = $request->sale_id;

                $Sale_order->create_by = "Live";
                $Sale_order->save();

                //stock Count
                $stockCount = $this->getStockCount($Item->id, []);

                if (abs($qty) > $stockCount) {
                    return $this->returnErrorData('สินค้าไม่พอ', 404);
                }

                $sale_order_line = new sale_order_line();
                $sale_order_line->item_id = $Item->id;


                $sale_order_line->sale_order_id = $Sale_order->id;
                $total_price = $Item->unit_price / $qty;
                $sale_order_line->unit_price = floatval($Item->unit_price);
                $sale_order_line->discount = 0;
                $sale_order_line->qty = $qty;
                $sale_order_line->total = $total_price;
                $sale_order_line->create_by = "admin";

                $sale_order_line->save();


                $Item_trans = new Item_trans();

                //qty withdraw
                $Item_trans->sale_order_id = $Sale_order->id;
                $Item_trans->item_id = $Item->id;
                $Item_trans->qty = -$qty;

                $Item_trans->location_1_id = $Item->location_id;

                $Item_trans->customer_id = $Customer->id;

                $Item_trans->stock = $stockCount;
                $Item_trans->balance = $stockCount - abs($qty);
                $Item_trans->status = 1;
                $Item_trans->operation = 'booking';
                $Item_trans->date = date('Y-m-d');
                $Item_trans->type = 'Withdraw';
                $Item_trans->create_by = "Live";

                $Item_trans->save();


                $Sale_order->item_trans = $Item_trans;


                //log
                $userId = "1";
                $type = 'ขายสินค้า';
                $description = 'User' . $userId . ' has ' . $type;
                $this->LogSaleOrder($userId, $description, $type);

                DB::commit();

                if ($page) {
                    //ส่งลิงค์ sale page ไปยัง user
                    $resp = $this->_facebookApi->SendMessageFromLiveToUser(
                        $Sale_order->page_id,
                        $page->token,
                        $Sale_order->fb_comment_id,
                        "กรุณาตรวจสอบข้อมูลคำสั่งซื้อ\n\nhttps://deeshipp.vercel.app/sale-page?order_id=" . $Sale_order->id
                    );

                    $so = Sale_order::find($Sale_order->id);
                    $so->fb_user_id = $resp->recipient_id;
                    $so->save();
                    DB::commit();
                }

                return $this->returnSuccess('Successful operation', $Sale_order);
            } else {

                $Customer = new Customer();

                $Customer->name = $request->name;
                $Customer->phone = $request->telephone;
                $Customer->email = $request->email;
                $Customer->create_by = "Live";
                $Customer->save();


                //add order
                $Sale_order = new Sale_order();

                $Sale_order->order_id = $this->getLastNumber(5);

                //run number
                $this->setRunDoc(5, $Sale_order->order_id);
                $Sale_order->date_time = date('Y-m-d');
                $Sale_order->customer_id = $Customer->customer_id;
                $Sale_order->delivery_by_id = 1;
                $Sale_order->channal = $channal;
                $Sale_order->name = $Customer->name;
                $Sale_order->telephone = $Customer->phone;
                $Sale_order->email = $Customer->email;
                $Sale_order->address = "";
                $Sale_order->shipping_price = 0;
                $Sale_order->cod_price_surcharge = 0;

                $Sale_order->vat = 0;
                $Sale_order->total = floatval($qty) * floatval($Item->unit_price);
                $Sale_order->status = "order";
                $Sale_order->customer_id = $Customer->id;
                $Sale_order->payment_type = "";
                $Sale_order->create_by = "Live";

                $Sale_order->page_id = $page_id;
                $Sale_order->fb_user_id = $fb_user_id;
                $Sale_order->fb_comment_id = $fb_comment_id;

                $Sale_order->sale_id = $request->sale_id;

                $Sale_order->save();

                //stock Count
                $stockCount = $this->getStockCount($Item->id, []);

                if (abs($qty) > $stockCount) {
                    return $this->returnErrorData('สินค้าไม่พอ', 404);
                }

                $sale_order_line = new sale_order_line();
                $sale_order_line->item_id = $Item->id;


                $sale_order_line->sale_order_id = $Sale_order->id;
                $total_price = $Item->unit_price / $qty;
                $sale_order_line->unit_price = floatval($Item->unit_price);
                $sale_order_line->discount = 0;
                $sale_order_line->qty = $qty;
                $sale_order_line->total = $total_price;
                $sale_order_line->create_by = "admin";

                $sale_order_line->save();


                $Item_trans = new Item_trans();

                //qty withdraw
                $Item_trans->sale_order_id = $Sale_order->id;
                $Item_trans->item_id = $Item->id;
                $Item_trans->qty = -$qty;

                $Item_trans->location_1_id = $Item->location_id;

                $Item_trans->customer_id = $Customer->id;

                $Item_trans->stock = $stockCount;
                $Item_trans->balance = $stockCount - abs($qty);
                $Item_trans->status = 1;
                $Item_trans->operation = 'booking';
                $Item_trans->date = date('Y-m-d');
                $Item_trans->type = 'Withdraw';
                $Item_trans->create_by = "Live";

                $Item_trans->save();
                $Sale_order->item_trans = $Item_trans;


                //log
                $userId = "1";
                $type = 'ขายสินค้า';
                $description = 'User' . $userId . ' has ' . $type;
                $this->LogSaleOrder($userId, $description, $type);

                DB::commit();

                if ($page) {
                    //ส่งลิงค์ sale page ไปยัง user
                    $resp = $this->_facebookApi->SendMessageFromLiveToUser(
                        $Sale_order->page_id,
                        $page->token,
                        $Sale_order->fb_comment_id,
                        "https://deeshipp.vercel.app/sale-page?order_id=" . $Sale_order->id
                    );

                    $so = Sale_order::find($Sale_order->id);
                    $so->fb_user_id = $resp->recipient_id;
                    $so->save();
                    DB::commit();
                }

                return $this->returnSuccess('Successful operation', $Sale_order);
            }
        } catch (\Throwable $e) {

            DB::rollback();

            return false;
        }
    }

    public function PaymentOrderCM(Request $request, $id)
    {

        $name = $request->name;
        $telephone = $request->telephone;
        $address = $request->address;
        $payment_type = $request->payment_type;

        if (!isset($id)) {
            return $this->returnErrorData('[id] Data Not Found', 404);
        }
        if (!isset($name)) {
            return $this->returnErrorData('กรุณาระบุชื่อในการจัดส่ง', 404);
        } else if (!isset($telephone)) {
            return $this->returnErrorData('กรุณาระบุเบอร์โทรในการจัดส่ง', 404);
        } else if (!isset($address)) {
            return $this->returnErrorData('กรุณาระบุที่อยู่ในการจัดส่ง', 404);
        } else if (!isset($payment_type)) {
            return $this->returnErrorData('กรุณาเลือกช่องทางในการจัดส่ง', 404);
        }

        DB::beginTransaction();

        try {

            $Sale_order = Sale_order::find($id);
            $Sale_order->name = $name;
            $Sale_order->telephone = $telephone;
            $Sale_order->address =  $address;
            $Sale_order->payment_type = $payment_type;

            $Sale_order->updated_at = Carbon::now()->toDateTimeString();

            $Sale_order->save();


            DB::commit();

            return $this->returnUpdate('Successful operation');
        } catch (\Throwable $e) {

            DB::rollback();

            return $this->returnErrorData('Something went wrong Please try again ' . $e, 404);
        }
    }

    public function confirmMultiOrder(Request $request)
    {

        $saleOrderId = $request->sale_order_id;
        $status = $request->status;

        $loginBy = $request->login_by;

        if (empty($saleOrderId)) {
            return $this->returnErrorData('กรุณาระบุ รายการออเดอร์', 404);
        } else if (!isset($status)) {
            return $this->returnErrorData('กรุณาระบุสถานะ', 404);
        } else if (!isset($loginBy)) {
            return $this->returnErrorData('กรุณาเข้าสู่ระบบ', 404);
        }

        DB::beginTransaction();

        try {

            for ($i = 0; $i < count($saleOrderId); $i++) {

                //update sale order
                $Sale_order = Sale_order::find($saleOrderId[$i]);
                $Sale_order->status = $status;

                $Sale_order->update_by = $loginBy->user_id;
                $Sale_order->updated_at = Carbon::now()->toDateTimeString();

                $Sale_order->save();
            }

            DB::commit();

            return $this->returnSuccess('Successful operation', []);
        } catch (\Throwable $e) {

            DB::rollback();

            return $this->returnErrorData('Something went wrong Please try again ' . $e, 404);
        }
    }

    public function selectDelMultiOrder(Request $request)
    {
        $saleOrderId = $request->sale_order_id;
        $deliveredById = $request->delivered_by_id;
        $paymentQty = $request->payment_qty;

        $loginBy = $request->login_by;

        if (empty($saleOrderId)) {
            return $this->returnErrorData('กรุณาระบุ รายการออเดอร์', 404);
        } else if (!isset($deliveredById)) {
            return $this->returnErrorData('กรุณาเลือกขนส่ง', 404);
        } else if (!isset($paymentQty)) {
            return $this->returnErrorData('กรุณาระบุราคา', 404);
        } else if (!isset($loginBy)) {
            return $this->returnErrorData('กรุณาเข้าสู่ระบบ', 404);
        }

        DB::beginTransaction();

        try {

            $Delivered_by = Delivered_by::find($deliveredById);

            if (!$Delivered_by) {
                return $this->returnErrorData('ไม่พบข้อมูลขนส่ง', 404);
            }

            for ($i = 0; $i < count($saleOrderId); $i++) {

                //update sale order
                $Sale_order = Sale_order::find($saleOrderId[$i]);

                $Sale_order->delivery_by_id =  $deliveredById;
                $Sale_order->payment_qty = $paymentQty;
                $Sale_order->status = 'packing';

                $Sale_order->track_no = strtoupper(uniqid('TRACK', true));

                $Sale_order->update_by = $loginBy->user_id;
                $Sale_order->updated_at = Carbon::now()->toDateTimeString();

                $Sale_order->save();
            }

            DB::commit();

            return $this->returnSuccess('Successful operation', []);
        } catch (\Throwable $e) {

            DB::rollback();

            return $this->returnErrorData('Something went wrong Please try again ' . $e, 404);
        }
    }

    public function updateOrderLive(Request $request, $id)
    {
        // $Order = $request->order;
        // $loginBy = $request->login_by;

        // if (empty($Order)) {
        //     return $this->returnErrorData('[order] Data Not Found', 404);
        // } else
        if (!isset($id)) {
            return $this->returnErrorData('[id] Data Not Found', 404);
        }
        // else if (!isset($loginBy)) {
        //     return $this->returnErrorData('[login_by] Data Not Found', 404);
        // }

        DB::beginTransaction();

        try {

            $Sale_order = Sale_order::with('sale.user_pages')
                ->with('sale_order_lines.item')
                ->get()
                ->find($id);

            // return $Sale_order;
            // $Sale_order->date_time = $request->date_time;
            // $Sale_order->customer_id = $request->customer_id;
            // $Sale_order->delivery_by_id = $request->delivery_by_id;
            // $Sale_order->channal = $request->channal;
            // $Sale_order->sale_id = $request->sale_id;
            $Sale_order->name = $request->name;
            $Sale_order->telephone = $request->telephone;
            // $Sale_order->email = $request->email;
            $Sale_order->address = $request->address;
            // $Sale_order->shipping_price = $request->shipping_price;
            // $Sale_order->cod_price_surcharge = $request->cod_price_surcharge;
            // $Sale_order->image_slip = $request->image_slip;
            // $Sale_order->bank_id = $request->bank_id;
            // $Sale_order->payment_date = $request->payment_date;
            // $Sale_order->payment_qty = $request->payment_qty;
            // $Sale_order->account_number = $request->account_number;
            // $Sale_order->main_discount = $request->main_discount;
            // $Sale_order->vat = $request->vat;
            // $Sale_order->total += $Sale_order->shipping_price;
            // $Sale_order->status = $request->status;

            // dd($Sale_order->status);
            // $Sale_order->payment_type = $request->payment_type;

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
            // $Sale_order->payment_type = $request->payment_type;;

            // $Sale_order->customer_id = $request->customer_id;


            // $Sale_order->update_by = $loginBy->user_id;
            // $Sale_order->updated_at = Carbon::now()->toDateTimeString();

            $Sale_order->save();
            // $Sale_order->customer;
            // $Sale_order->user_id;

            //add order
            // for ($i = 0; $i < count($Order); $i++) {

            //     //stock Count
            //     $stockCount = $this->getStockCount($Order[$i]['item_id'], []);

            //     if (abs($Order[$i]['qty']) > $stockCount) {
            //         return $this->returnErrorData('Not enough item', 404);
            //     }
            //     //

            //     $Order[$i]['sale_order_id'] = $id;
            //     //$Order[$i]['seq'] = $i + 1;

            //     $Order[$i]['unit_price'] = floatval($Order[$i]['unit_price']);

            //     $Order[$i]['create_by'] = $loginBy->user_id;
            //     $Order[$i]['created_at'] = Carbon::now()->toDateTimeString();
            //     $Order[$i]['updated_at'] = Carbon::now()->toDateTimeString();
            // }

            // $Sale_order_line = Sale_order_line::where('sale_order_id', $id)->get();

            // if ($Sale_order_line->isEmpty()) {

            //     //add
            //     DB::table('sale_order_line')->insert($Order);
            // } else {

            //     //del
            //     for ($i = 0; $i < count($Sale_order_line); $i++) {

            //         $Sale_order_line[$i]->deleted_at = Carbon::now()->toDateTimeString();
            //         $Sale_order_line[$i]->save();
            //     }

            //     //add
            //     DB::table('sale_order_line')->insert($Order);
            // }

            DB::commit();

            //ค้นหา page ที่
            $page = User_page::where('page_id', $Sale_order->page_id)->first();

            if ($page) {
                //ส่งข้อความไปยืนยันกับลูกค้า
                $this->_facebookApi->SendPrivateMessageToUser(
                    $page->page_id,
                    $page->token,
                    $Sale_order->fb_user_id,
                    'ยืนยันคำสั่งซื้อหมายเลข ' . $Sale_order->order_id,
                );

                $item_lines = $Sale_order->sale_order_lines;

                //รายการสิ้นค้า
                $product = "";
                foreach ($item_lines as $item) {
                    $text = "- {$item->item->name} {$item->qty} ชิ้น ราคา {$item->total} บาท\n";
                    $product .= $text;
                }

                $this->_facebookApi->SendPrivateMessageToUser(
                    $page->page_id,
                    $page->token,
                    $Sale_order->fb_user_id,
                    "คุณได้ทำการสั่งซื้อสินค้า ดังนี้\n{$product}ค่าจัดส่ง {$Sale_order->shipping_price} บาท\nยอดสุทธิ {$Sale_order->total} บาท\n",
                );

                $this->_facebookApi->SendPrivateMessageToUser(
                    $page->page_id,
                    $page->token,
                    $Sale_order->fb_user_id,
                    "ที่อยู่จัดส่ง\nชื่อ {$Sale_order->name}\nโทร {$Sale_order->telephone}\nที่อยู่ {$Sale_order->address}",
                );
            }


            return $this->returnSuccess('Successful operation', $Sale_order);
        } catch (\Throwable $e) {

            DB::rollback();

            return $this->returnErrorData('Something went wrong Please try again ' . $e, 404);
        }
    }
}
