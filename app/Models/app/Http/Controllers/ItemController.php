<?php

namespace App\Http\Controllers;

use App\Imports\ItemImport;
use App\Models\Bom;
use App\Models\Item;
use App\Models\Item_line;
use App\Models\Item_trans;
use App\Models\Item_type;
use App\Models\Location;
use App\Models\Material_color;
use App\Models\Material_grade;
use App\Models\Material_group;
use App\Models\Material_manufactu;
use App\Models\Material_type;
use App\Models\Report_stock;
use App\Models\Size;
use App\Models\Spare_type;
use App\Models\Unit;
use App\Models\Vendor;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Maatwebsite\Excel\Facades\Excel;

class ItemController extends Controller
{


    public function getItemAll()
    {
      
     // $User = User::get()->toarray();
       
        $User = Item::get()->toarray();
        if (!empty($User)) {

            for ($i = 0; $i < count($User); $i++) {
                $User[$i]['No'] = $i + 1;
            }
        }

        return $this->returnSuccess('เรียกดูข้อมูลสำเร็จ', $User);
    }

    public function update2(Request $request, $id)
    {

        $item_line = $request->item_line;
        $loginBy = $request->login_by;
        //dd($Item_line);
        if ($request->set_type  == 'set_products') {
            if (empty($item_line)) {
                return $this->returnErrorData('[order] Data Not Found', 404);
            } else if (!isset($id)) {
                return $this->returnErrorData('[id] Data Not Found', 404);
            } else if (!isset($loginBy)) {
                return $this->returnErrorData('[login_by] Data Not Found', 404);
            }
        } else {

            if (!isset($id)) {
                return $this->returnErrorData('[id] Data Not Found', 404);
            } else if (!isset($loginBy)) {
                return $this->returnErrorData('[login_by] Data Not Found', 404);
            }
        }
        DB::beginTransaction();

        try {


            if ($request->set_type  == 'normal') {

                $Order = Item::find($id);

                $Order->vendor_id = $request->vendor_id;
                $Order->location_id = $request->location_id;
                $Order->name = $request->name;
                $Order->brand = $request->brand;

                if (!empty($request->image)) {
                    $Order->image = $request->image;
                }
                // $Order->qty = $request->qty;
                $Order->unit_cost = $request->unit_cost;
                $Order->unit_price = $request->unit_price;
                // $Order->total_price = $request->total_price;
                $Order->description = $request->description;
                $Order->item_type_id = $request->item_type_id;
                $Order->set_type = $request->set_type;

                $Order->updated_at = Carbon::now()->toDateTimeString();

                $Order->save();
                //  $Sale_order->customer;
                //$Sale_order->user_id;

                //add ItemLine


            } else if ($request->set_type  == 'set_products') {

                $Order = Item::find($id);
                $Order->name = $request->name;
                $Order->brand = $request->brand;
                $Order->total_price = $request->total_price;
                $Order->description = $request->description;
                $Order->item_type_id = $request->item_type_id;
                if (!empty($request->image)) {
                    $Order->image = $request->image;
                }
                $Order->save();

                for ($i = 0; $i < count($item_line); $i++) {

                    // "qty": 10,
                    // "price": 10,
                    // "total": 100
                    //$Item_trans =  Item_trans::where('item_id', $id)->first();
//dd($Item_trans);
                    switch ($item_line[$i]['action']) {
                        case 'insert':
                            
                            $newItemLine = new Item_line();
                           // $Item_trans1 = new Item_trans();
                            $newItemLine->item_id = $item_line[$i]['item_id'];
                            $newItemLine->main_item_id = $id;
                            $newItemLine->qty = $item_line[$i]['qty'];
                           // $Item_trans->qty = $item_line[$i]['qty'];
                            $newItemLine->price = $item_line[$i]['price'];
                            $newItemLine->total = $item_line[$i]['total'];
                            $newItemLine->type = 'normal';
                            $newItemLine->save();
                            break;

                        case 'update':
                            //$Item_trans1 = new Item_trans();
                            $Item_line = Item_line::find($item_line[$i]['item_line_id']);
                            // $Item_line = Item_line::find($id);
//dd($Item_line);
                            //$Item_line->item_id = $item_line[$i]['item_id'];
                            $Item_line->qty = $item_line[$i]['qty'];
                            $Item_line->price = $item_line[$i]['price'];
                            $Item_line->total = $item_line[$i]['total'];
                            $Item_line->save();
                            break;

                            case 'delete':
                                $Item_line = Item_line::find($item_line[$i]['item_line_id']);

                                 $Item_line->delete();
                                break;

                        default:
                            # code...
                            break;
                    }

                   
                  
                }
                // $Item_line = Item_line::where('item_id', $id)->first();

                // if ($Item_line->IsEmpty()) {
                //     $newItemLine = new Item_line();
                //     $newItemLine->qty = $item_line[$i]['qty'];
                //     $newItemLine->price = $item_line[$i]['price'];
                //     $newItemLine->total = $item_line[$i]['total'];
                //     $newItemLine->save();
                // } else {
                //     $Item_line->qty = $item_line[$i]['qty'];
                //     $Item_line->price = $item_line[$i]['price'];
                //     $Item_line->total = $item_line[$i]['total'];
                //     $Item_line->save();
                // }



                //$Item_line[$i]['sale_order_id'] = $id;
                //$Order[$i]['seq'] = $i + 1;

                // $item_line[$i]['unit_price'] = floatval($item_line[$i]['unit_price']);

                // $item_line[$i]['create_by'] = $loginBy->user_id;
                // $item_line[$i]['created_at'] = Carbon::now()->toDateTimeString();
                // $item_line[$i]['updated_at'] = Carbon::now()->toDateTimeString();
            }


            // if ($Item_line->isEmpty()) {

            //     //add
            //     DB::table('item_lines')->insert($Item_line);
            // } else {

            //     //del
            //     for ($i = 0; $i < count($Item_line); $i++) {

            //         //  $item_line[$i]->deleted_at = Carbon::now()->toDateTimeString();
            //         $Item_line[$i]->save();
            //     }

            //add
            //     DB::table('item_lines')->insert($item_line);
            // }

            DB::commit();

            return $this->returnUpdate('Successful operation');
        } catch (\Throwable $e) {

            DB::rollback();

            return $this->returnErrorData('Something went wrong Please try again ' . $e, 404);
        }
    }



    public function getItem(Request $request)
    {

        $item_type_id = $request->item_type_id;

        if (!isset($request->item_type_id)) {
            return $this->returnErrorData('[item_type_id] Data Not Found', 404);
        }

        $Item = Item::with('item_type')

            ->with('location')
            ->where('item_type_id', $item_type_id)
            ->where('status', 1)
            ->get()
            ->toarray();

        if (!empty($Item)) {

            for ($i = 0; $i < count($Item); $i++) {
                $Item[$i]['No'] = $i + 1;

                //qty item
                $Item[$i]['qty'] = $this->getStockCount($Item[$i]['id'], []);
            }
        }

        return $this->returnSuccess('Successful', $Item);
    }

    // public function getStockItemByBomId(Request $request)
    // {
    //     $bom = Bom::where('id', $request->bom_id)->where('status', 'Approved')->first();

    //     $getStockCount = $this->getStockCount($bom->item_id, []);
    //     return intval($getStockCount);
    // }

    public function getStockItemByLocation(Request $request)
    {

        $itemId = $request->item_id;
        $locationId = $request->location_1_id;

        $QtyItem = Item_trans::where('item_id', $itemId);

        if (!empty($location_id)) {
            $QtyItem->whereIn('location_1_id', $locationId);
        }
        $qtyItem = $QtyItem->where('status', 1)
            ->sum('qty');

        return $this->returnSuccess('Successful', intval($qtyItem));
    }

    public function ItemPage(Request $request)
    {

        $columns = $request->columns;
        $length = $request->length;
        $order = $request->order;
        $search = $request->search;
        $start = $request->start;
        $page = $start / $length + 1;

        $item_type_id = $request->item_type_id;
        $set_type = $request->set_type;

        // if (!isset($request->item_type_id)) {
        //     return $this->returnErrorData('[item_type_id] Data Not Found', 404);
        // }

        $col = array(
            'id',
            'name',
            'status',
            'create_by',
            'created_at',
            'item_type_id',
            'item_id',
            'image',
            'unit_price',
            'unit_cost',
            'location_id',
            'vendor_id',
            'barcode',
            'brand',
            'set_type',
            'description',
            'update_by',
            'updated_at'

        );

        $d = Item::select($col)

             ->with('user_create')
            ->with('item_type')
            ->with('location.warehouse')
            ->with('main_itemLine.item')
           
            //->where('item_type_id', $item_type_id)
            ->where('set_type', $set_type);


        if ($item_type_id) {
            $d->where('item_type_id', $item_type_id);
        }

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

                //qty item
                $d[$i]->balance = $this->getStockCount($d[$i]->id, []);
                $d[$i]->booking = abs($this->getStockBookingCount($d[$i]->id, []));
                $d[$i]->qty = $this->getStockCountqty($d[$i]->id, []);
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
        } else if (!isset($request->unit_price)) {
            return $this->returnErrorData('[unit_price] Data Not Found', 404);
        } else if (!isset($loginBy)) {
            return $this->returnErrorData('[login_by] Data Not Found', 404);
        }

        $itemId = $request->item_id;
        //dd( $itemId);
        $checkitemId = Item::where('item_id', $itemId)->first();

        if ($checkitemId) {
            return $this->returnErrorData('There is already this item id in the system', 404);
        } else {

            DB::beginTransaction();

            try {

                $Item = new Item();

                $itemId = $request->item_id;
                $Item->item_id = $this->getLastNumber(5);
                $itemId;
                $Item->name = $request->name;
                $Item->barcode = $this->getLastNumber(5);

                $this->setRunDoc(5, $Item->barcode, $Item->item_id);

                //des
                if ($request->image && $request->image != 'null' && $request->image != null) {
                    $Item->image = $this->uploadImage($request->image, '/images/item/');
                }





                $Item->location_id = $request->location_id;

                $Item->vendor_id = $request->vendor_id;

                $Item->qty = $request->qty;

                $Item->set_type = $request->set_type;
                $Item->total_price = $request->total_price;


                $Item->brand = $request->brand;
                $Item->unit_cost = $request->unit_cost;
                $Item->unit_price = $request->unit_price;

                $Item->description = $request->description;

                $Item->status = 1;

                $Item->create_by = $loginBy->user_id;

                $Item->item_type_id = $request->item_type_id;

                $Item->save();
                $Item->item_type;

                //log
                $userId = $loginBy->user_id;
                $type = 'Add Item';
                $description = 'User ' . $userId . ' has ' . $type . ' ' . $itemId;
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

    public function Putstore(Request $request)
    {


        $item_line = $request->item_line;
        $loginBy = $request->login_by;
        //$Item = $request->Item;

        // dd($request->all());
        //dd($item_line);

        if (!isset($request->name)) {
            return $this->returnErrorData('กรุณาเพิ่มชื่อสินค้า', 404);
        }else if (!isset($request->item_type_id)) {
            return $this->returnErrorData('กรุณาเลือกหมวดหมู่', 404);
        }
         else if (!isset($request->brand)) {
            return $this->returnErrorData('กรุณาใส่แบรนด์สินค้า', 404);
        } else if (!isset($request->image)) {
            return $this->returnErrorData('กรุณาเพิ่มรูปสินค้า', 404);
        } else if (!isset($request->unit_cost)&& $request->set_type == 'normal') {
            return $this->returnErrorData('กรุณาใส่ต้นทุนสินค้า', 404);
        } else if (!isset($request->unit_price) && $request->set_type == 'normal') {
            return $this->returnErrorData('กรุณาใส่ราคาสินค้า', 404);
        } else if (!isset($request->location_id) && $request->set_type == 'normal') {
            return $this->returnErrorData('กรุณาใส่ที่อยู่สินค้า', 404);
        } else if (!isset($loginBy)) {
            return $this->returnErrorData('[login_by] Data Not Found', 404);
        }

        $itemId = $request->item_id;
        //dd( $itemId);
        $checkitemId = Item::where('item_id', $itemId)->first();

        if ($checkitemId) {
            return $this->returnErrorData('There is already this item id in the system', 404);
        } else {

            DB::beginTransaction();

            try {

                $Item = new Item();
                //dd( $Item);

                if ($request->set_type  == 'normal') {

                    $itemId = $request->item_id;

                    $Item->item_id = $this->getLastNumber(5);
                    //$itemId;
                    $Item->name = $request->name;
                    $Item->barcode = $this->getLastNumber(5);

                    $this->setRunDoc(5, $Item->barcode, $Item->item_id);

                    //des

                    $Item->image = $request->image;
                    $Item->location_id = $request->location_id;
                    //$Item->total_price = $request->total_price;
                    $Item->vendor_id = $request->vendor_id;
                    //$Item->qty = $request->qty;
                    //$Item->total_price = $request->qty;
                    //$Item->type = $request->type;
                    $Item->set_type = $request->set_type;
                    
                    $Item->brand = $request->brand;
                    $Item->unit_cost = $request->unit_cost;
                    $Item->unit_price = $request->unit_price;

                    $Item->description = $request->description;

                    $Item->status = 1;

                    $Item->create_by = $loginBy->user_id;

                    $Item->item_type_id = $request->item_type_id;

                    $Item->save();

                    //add report stock
                   // $report_stock = new Report_stock();
                  //  $report_stock->report_id = $this->getLastNumber(1);

                 //   $report_stock->date = $request->date;

                 //   $report_stock->create_by = $loginBy->user_id;
                    
                  //  $report_stock->status = 'Open';
                  //  $report_stock->type = 'Deposit';
                    //$report_stock->type = 'Deposit';

                  //  $report_stock->doc_id = 1;
                 //   $report_stock->save();
                  //  $report_stock->doc;
                    
                    //run doc
                  //  $this->setRunDoc(1, $report_stock->report_id);
                    


                    //add item_trans

                    //check type mat 


                    //DB::table('item_trans')->insert($Deposit);




                    //log
                    $userId = $loginBy->user_id;
                    $type = 'Add Item';
                    $description = 'User ' . $userId . ' has ' . $type . ' ' . $itemId;
                    $this->Log($userId, $description, $type);
                    //


                    DB::commit();
                    return $this->returnSuccess('Successful operation', $Item);

                    
                    
                } else if ($request->set_type  == 'set_products') {
                    //$Item1= new Item();

                    $itemId = $request->item_id;

                    $Item->item_id = $this->getLastNumber(5);
                    //$itemId;
                    $Item->name = $request->name;
                    $Item->barcode = $this->getLastNumber(5);
                    $Item->image = $request->image;
                    $this->setRunDoc(5, $Item->barcode, $Item->item_id);
                   // $Item->location_id = $request->location_id;
                    $Item->total_price = $request->total_price;
                    $Item->brand = $request->brand;
                    $Item->description = $request->description;
                    //des
                  //  $Item->unit_price = $request->unit_price;
                  
                    $Item->set_type = $request->set_type;


                    $Item->status = 1;

                    $Item->create_by = $loginBy->user_id;

                    $Item->item_type_id = $request->item_type_id;

                    $Item->save();;



                 

                    for ($i = 0; $i < count($item_line); $i++) {


                        
                        $item_line[$i]['main_item_id'] = $Item->id;
                        //$stockCount = $this->getItemCount($item_line[$i]['item_id'], $item_line[$i]['main_item_id']);
                        //dd($stockCount);

                        $item_line[$i]['created_at'] = Carbon::now()->toDateTimeString();
                        $item_line[$i]['updated_at'] = Carbon::now()->toDateTimeString();


                        //  $Item = Item::where('id', $item_line[$i]['item_id'])->first();

                        // $Item->qty = $item_line[$i]['qty'];
                        //$Item->item_type_id = $request->item_type_id;

                       // $Item->save();



                        $Item_trans = new Item_trans();
                        //qty withdraw
                        $qty = -$item_line[$i]['qty'];
    
                        $Item_Line = Item::where('id', $item_line[$i]['item_id'])->first();
    
                         $stockCount = $this->getStockCount($item_line[$i]['item_id'], []);
    
                          //  $stockCount = $this->getStockCount($Order[$i]['item_id'], [$Item->location_id]);
    
                            if (abs($qty) > $stockCount) {
                                return $this->returnErrorData('สินค้าบางอย่างไม่พอที่จะจัดเซ็ต', 404);
                            }
        
        
                            $Item_trans->description = "products Promotion";
                            $Item_trans->item_id = $Item_Line->id;
                            $Item_trans->qty = $qty;
                            $Item_trans->main_item_id = $Item->id;
        
                            $Item_trans->location_1_id = $Item_Line->location_id;
        
        
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

                    //add Item line
                    DB::table('item_lines')->insert($item_line);


                    //log
                    $userId = $loginBy->user_id;
                    $type = 'Add Item_line';
                    $description = 'User ' . $userId . ' has ' . $type . ' ' . $itemId;
                    $this->Log($userId, $description, $type);
                    //

                    DB::commit();

                    return $this->returnSuccess('Successful operation', [$item_line]);
                }
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

        $Item = Item::with('item_type')

            ->with('location')
            ->with('vendor')

            ->with('main_itemLine.item')

            ->find($id);

        if (!empty($Item)) {

            //qty item
            $Item->qty = $this->getStockCount($Item->id, []);
        }

        return $this->returnSuccess('Successful', $Item);
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
    public function update(Request $request)
    {

        $loginBy = $request->login_by;
        $id = $request->id;

        if (!isset($loginBy)) {
            return $this->returnErrorData('[login_by] Data Not Found', 404);
        }

        $itemId = $request->item_id;

        $checkitemId = Item::where('id', '!=', $id)
            ->where('item_id', $itemId)
            ->first();

        if ($checkitemId) {
            return $this->returnErrorData('There is already this item id in the system', 404);
        } else {

            DB::beginTransaction();

            try {

                $Item = Item::find($id);
                $Item->item_id = $itemId;


                if ($request->image && $request->image != 'null' && $request->image != null) {
                    $Item->image = $this->uploadImage($request->image, '/images/item/');
                }

                $Item->location_id = $request->location_id;
                $Item->name = $request->name;
                $Item->vendor_id = $request->vendor_id;
                $Item->item_id = $this->getLastNumber(5);
                $Item->barcode = $this->getLastNumber(5);

                $this->setRunDoc(5, $Item->barcode, $Item->item_id);

                $Item->qty = $request->qty;

                $Item->set_type = $request->set_type;
                $Item->total_price = $request->total_price;


                $Item->brand = $request->brand;
                $Item->unit_cost = $request->unit_cost;
                $Item->unit_price = $request->unit_price;

                $Item->description = $request->description;

                $Item->item_type_id = $request->item_type_id;

                $Item->status = $request->status;

                $Item->update_by = $loginBy->user_id;
                $Item->updated_at = Carbon::now()->toDateTimeString();

                $Item->item_type_id = $request->item_type_id;

                $Item->save();
                $Item->item_type;

                //log
                $userId = $loginBy->user_id;
                $type = 'Edit Item';
                $description = 'User ' . $userId . ' has ' . $type . ' ' . $Item->name;
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

            $Item = Item::find($id);

            //log
            $userId = $loginBy->user_id;
            $type = 'Delete Item';
            $description = 'User ' . $userId . ' has ' . $type . ' ' . $Item->name;
            $this->Log($userId, $description, $type);
            //

            $Item->delete();

            DB::commit();

            return $this->returnUpdate('Successful operation');
        } catch (\Throwable $e) {

            DB::rollback();

            return $this->returnErrorData('Something went wrong Please try again ' . $e, 404);
        }
    }

    // public function ImportItem(Request $request)
    // {

    //     $loginBy = $request->login_by;

    //     if (!isset($loginBy)) {
    //         return $this->returnErrorData('User information not found. Please login again', 404);
    //     }

    //     $file = request()->file('file');
    //     $fileName = $file->getClientOriginalName();

    //     $Data = Excel::toArray(new ItemImport(), $file);
    //     $data = $Data[0];

    //     if (count($data) > 0) {

    //         $insert_data = [];

    //         for ($i = 0; $i < count($data); $i++) {

    //             $itemId = trim($data[$i]['itemcode']);
    //             $name = trim($data[$i]['name']);
    //             $size = trim($data[$i]['size']);
    //             $packing = trim($data[$i]['packing']);
    //             $itemtypeid = trim($data[$i]['itemcategoryid']);

    //             $price = trim($data[$i]['price']);
    //             $pricePerSet = trim($data[$i]['priceperset']);
    //             $min = trim($data[$i]['min']);
    //             $max = trim($data[$i]['max']);

    //             $unitSellid = trim($data[$i]['unitsellid']);
    //             $unitBuyid = trim($data[$i]['unitbuyid']);
    //             $unitStoreid = trim($data[$i]['unitstoreid']);
    //             $location = trim($data[$i]['locationcode']);
    //             $vendorid = trim($data[$i]['vendorid']);

    //             $spareTypeid = trim($data[$i]['sparetypeid']);

    //             $materialgroupid = trim($data[$i]['itemgroupid']);
    //             $materialTypeid = trim($data[$i]['itemtypeid']);
    //             $materialGradeid = trim($data[$i]['itemgradeid']);
    //             $materialColorid = trim($data[$i]['itemcolorid']);
    //             $materialManufactuid = trim($data[$i]['manufactureid']);

    //             $row = $i + 2;

    //             if ($itemId == '') {
    //                 return $this->returnErrorData('Row excel data ' . $row . ' please enter item id', 404);
    //             } else if ($name == '') {
    //                 return $this->returnErrorData('Row excel data ' . $row . ' please enter name', 404);
    //             } else if ($itemtypeid == '') {
    //                 return $this->returnErrorData('Row excel data ' . $row . ' please enter item type id', 404);
    //             } else if ($unitSellid == '') {
    //                 return $this->returnErrorData('Row excel data ' . $row . ' please enter unit sell id', 404);
    //             } else if ($unitBuyid == '') {
    //                 return $this->returnErrorData('Row excel data ' . $row . ' please enter unit buy id', 404);
    //             } else if ($unitStoreid == '') {
    //                 return $this->returnErrorData('Row excel data ' . $row . ' please enter unit store id', 404);
    //             } else if ($location == '') {
    //                 return $this->returnErrorData('Row excel data ' . $row . ' please enter location', 404);
    //             }

    //             //check row sample
    //             if ($itemId == 'SIMPLE-000') {
    //                 //
    //             } else {

    //                 // //check item id
    //                 // $Item = Item::where('item_id', $itemId)->first();
    //                 // if ($Item) {
    //                 //     return $this->returnErrorData('Item id ' . $itemId . ' was information information is already in the system', 404);
    //                 // }

    //                 //check Item type
    //                 $Item_type = Item_type::where('id', $itemtypeid)->first();
    //                 if (!$Item_type) {
    //                     return $this->returnErrorData('Item type id' . $itemtypeid . ' was not found in the system', 404);
    //                 }

    //                 $ItemTypeId = $Item_type->id;

    //                 //check Unit sell
    //                 if ($unitSellid) {
    //                     $UnitSell = Unit::where('id', $unitSellid)->first();
    //                     if (!$UnitSell) {
    //                         return $this->returnErrorData('Unit sell Id' . $unitSellid . ' was not found in the system', 404);
    //                     }

    //                     $unitSellId = $UnitSell->id;
    //                 } else {
    //                     $unitSellId = null;
    //                 }

    //                 //check Unit buy
    //                 if ($unitBuyid) {
    //                     $UnitBuy = Unit::where('id', $unitBuyid)->first();
    //                     if (!$UnitBuy) {
    //                         return $this->returnErrorData('Unit buy Id' . $unitBuyid . ' was not found in the system', 404);
    //                     }
    //                     $unitBuyId = $UnitBuy->id;
    //                 } else {
    //                     $unitBuyId = null;
    //                 }

    //                 //check Unit store
    //                 if ($unitStoreid) {
    //                     $UnitStore = Unit::where('id', $unitStoreid)->first();
    //                     if (!$UnitStore) {
    //                         return $this->returnErrorData('Unit store Id' . $unitStoreid . ' was not found in the system', 404);
    //                     }
    //                     $unitStoreId = $UnitStore->id;
    //                 } else {
    //                     $unitStoreId = null;
    //                 }

    //                 //check location
    //                 $Location = Location::where('code', $location)->first();
    //                 if (!$Location) {
    //                     return $this->returnErrorData('Location ' . $location . ' was not found in the system', 404);
    //                 }

    //                 //check Spare_type
    //                 if ($spareTypeid) {
    //                     $Spare_type = Spare_type::where('id', $spareTypeid)->first();
    //                     if (!$Spare_type) {
    //                         return $this->returnErrorData('Spare type Id' . $spareTypeid . ' was not found in the system', 404);
    //                     }
    //                     $spareTypeId = $Spare_type->id;
    //                 } else {
    //                     $spareTypeId = null;
    //                 }

    //                 //check Vendor
    //                 if ($vendorid) {
    //                     $Vendor = Vendor::where('id', $vendorid)->first();
    //                     if (!$Vendor) {
    //                         return $this->returnErrorData('Vendor Id' . $vendorid . ' was not found in the system', 404);
    //                     }
    //                     $VendorId = $Vendor->id;
    //                 } else {
    //                     $VendorId = null;
    //                 }

    //                 //check Material_type
    //                 if ($materialTypeid) {
    //                     $Material_type = Material_type::where('id', $materialTypeid)->first();
    //                     if (!$Material_type) {
    //                         return $this->returnErrorData('Item type Id' . $materialTypeid . ' was not found in the system', 404);
    //                     }
    //                     $materialTypeId = $Material_type->id;
    //                 } else {
    //                     $materialTypeId = null;
    //                 }

    //                 //check Material_grade
    //                 if ($materialGradeid) {
    //                     $Material_grade = Material_grade::where('id', $materialGradeid)->first();
    //                     if (!$Material_grade) {
    //                         return $this->returnErrorData('Item Grade Id' . $materialGradeid . ' was not found in the system', 404);
    //                     }
    //                     $materialGradeId = $Material_grade->id;
    //                 } else {
    //                     $materialGradeId = null;
    //                 }

    //                 //check Material_color
    //                 if ($materialColorid) {
    //                     $Material_color = Material_color::where('id', $materialColorid)->first();
    //                     if (!$Material_color) {
    //                         return $this->returnErrorData('Item Color Id' . $materialColorid . ' was not found in the system', 404);
    //                     }
    //                     $materialColorId = $Material_color->id;
    //                 } else {
    //                     $materialColorId = null;
    //                 }

    //                 //check Material_manufactu
    //                 if ($materialManufactuid) {
    //                     $Material_manufactu = Material_manufactu::where('id', $materialManufactuid)->first();
    //                     if (!$Material_manufactu) {
    //                         return $this->returnErrorData('Manufactu id' . $materialManufactuid . ' was not found in the system', 404);
    //                     }
    //                     $materialManufactuId = $Material_manufactu->id;
    //                 } else {
    //                     $materialManufactuId = null;
    //                 }

    //                 //check Material_group
    //                 if ($materialgroupid) {
    //                     $Material_group = Material_group::where('id', $materialgroupid)->first();
    //                     if (!$Material_group) {
    //                         return $this->returnErrorData('Manufactu id' . $materialgroupid . ' was not found in the system', 404);
    //                     }
    //                     $materialGroupId = $Material_group->id;
    //                 } else {
    //                     $materialGroupId = null;
    //                 }

    //                 //check dupicate data form file import
    //                 for ($j = 0; $j < count($insert_data); $j++) {

    //                     if ($itemId == $insert_data[$j]['item_id']) {
    //                         return $this->returnErrorData('Item id ' . $itemId . ' There is duplicate data in the import file', 404);
    //                     }
    //                 }
    //                 ///

    //                 $insert_data[] = array(
    //                     'item_id' => $itemId,
    //                     'name' => $name,
    //                     'size' => $size,
    //                     'packing' => $packing,

    //                     'item_type_id' => $ItemTypeId,
    //                     'price' => floatval($price),
    //                     'price_per_set' => floatval($pricePerSet),
    //                     'min' => intval($min),
    //                     'max' => intval($max),

    //                     'unit_sell_id' => $unitSellId,
    //                     'unit_buy_id' => $unitBuyId,
    //                     'unit_store_id' => $unitStoreId,

    //                     'location_id' => $Location->id,

    //                     'vendor_id' => $VendorId,

    //                     'spare_type_id' => $spareTypeId,

    //                     'material_group_id' => $materialGroupId,
    //                     'material_type_id' => $materialTypeId,
    //                     'material_grade_id' => $materialGradeId,
    //                     'material_color_id' => $materialColorId,
    //                     'material_manufactu_id' => $materialManufactuId,

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

    //                     DB::table('item')
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
    //                 $type = 'Import Item';
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

    // public function ImportItem1(Request $request)
    // {
    //     $loginBy = $request->login_by;

    //     if (!isset($loginBy)) {
    //         return $this->returnErrorData('[login_by] Data Not Found', 404);
    //     }

    //     $file = request()->file('select_file');
    //     $fileName = $file->getClientOriginalName();

    //     $Data = Excel::toArray(new ItemImport(), $file);
    //     $data = $Data[0];

    //     if (count($data) > 0) {

    //         for ($i = 0; $i < count($data); $i++) {

    //             //item Type
    //             $itemType = Item_type::where('initial', $data[$i]['item_type'])->first();

    //             //Unit
    //             $unitSell = Unit::where('name', $data[$i]['unit_sell'])->first();

    //             $unitBuy = Unit::where('name', $data[$i]['unit_buy'])->first();

    //             $unitStore = Unit::where('name', $data[$i]['unit_store'])->first();

    //             //pice
    //             $price = number_format(floatval($data[$i]['price']), 2, '.', '');

    //             if ($data[$i]['price_per_set']) {
    //                 $price_per_set = number_format(floatval($data[$i]['price_per_set']), 2, '.', '');
    //             } else {
    //                 $price_per_set = null;
    //             }
    //             //

    //             //location
    //             if ($data[$i]['location']) {
    //                 $Location = Location::where('code', $data[$i]['location'])->first();
    //                 if ($Location) {
    //                     $Location = $Location->id;
    //                 } else {
    //                     $Location = null;
    //                 }

    //             } else {
    //                 $Location = null;
    //             }
    //             //

    //             //material
    //             if ($data[$i]['material_type']) {
    //                 $MaterialType = Material_type::where('name', $data[$i]['material_type'])->first();
    //                 if ($MaterialType) {
    //                     $MaterialType = $MaterialType->id;
    //                 } else {
    //                     $MaterialColor = null;
    //                 }

    //             } else {
    //                 $MaterialType = null;
    //             }

    //             if ($data[$i]['material_grade']) {
    //                 $MaterialGrade = Material_grade::where('name', $data[$i]['material_grade'])->first();
    //                 if ($MaterialGrade) {
    //                     $MaterialGrade = $MaterialGrade->id;
    //                 } else {
    //                     $MaterialGrade = null;
    //                 }

    //             } else {
    //                 $MaterialGrade = null;
    //             }

    //             if ($data[$i]['material_color']) {
    //                 $MaterialColor = Material_color::where('name', $data[$i]['material_color'])->first();
    //                 if ($MaterialColor) {
    //                     $MaterialColor = $MaterialColor->id;
    //                 } else {
    //                     $MaterialColor = null;
    //                 }

    //             } else {
    //                 $MaterialColor = null;
    //             }

    //             if ($data[$i]['material_manufactu']) {
    //                 $MaterialManufactu = Material_manufactu::where('name', $data[$i]['material_manufactu'])->first();

    //                 if ($MaterialManufactu) {
    //                     $MaterialManufactu = $MaterialManufactu->id;
    //                 } else {
    //                     $MaterialManufactu = null;
    //                 }

    //             } else {
    //                 $MaterialManufactu = null;
    //             }
    //             //

    //             //spare
    //             if ($data[$i]['spare_type']) {
    //                 $SpareType = Spare_type::where('name', $data[$i]['spare_type'])->first();

    //                 if ($SpareType) {
    //                     $SpareType = $SpareType->id;
    //                 } else {
    //                     $SpareType = null;
    //                 }
    //             } else {
    //                 $SpareType = null;
    //             }
    //             //

    //             $insert_data[] = array(
    //                 'item_id' => $data[$i]['item_id'],
    //                 'name' => $data[$i]['name'],
    //                 'size' => $data[$i]['size'],
    //                 'packing' => $data[$i]['packing'],

    //                 'item_type_id' => $itemType->id,
    //                 'price' => $price,
    //                 'price_per_set' => $price_per_set,
    //                 'min' => $data[$i]['min'],
    //                 'max' => $data[$i]['max'],

    //                 'unit_sell_id' => $unitSell->id,
    //                 'unit_buy_id' => $unitBuy->id,
    //                 'unit_store_id' => $unitStore->id,

    //                 'location_id' => $Location,

    //                 'material_type_id' => $MaterialType,
    //                 'material_grade_id' => $MaterialGrade,
    //                 'material_color_id' => $MaterialColor,
    //                 'material_manufactu_id' => $MaterialManufactu,

    //                 'spare_type_id' => $SpareType,

    //                 'status' => 1,

    //                 'create_by' => $loginBy->user_id,
    //                 'created_at' => Carbon::now()->toDateTimeString(),
    //                 'updated_at' => Carbon::now()->toDateTimeString(),
    //             );

    //         }

    //         // dd($insert_data);

    //         if (!empty($insert_data)) {

    //             DB::beginTransaction();

    //             try {

    //                 DB::table('item')->insert($insert_data);

    //                 //log
    //                 $username = $loginBy->user_id;
    //                 $log_type = 'Import Item';
    //                 $log_description = 'User ' . $username . ' has ' . $log_type . ' ' . $fileName;
    //                 $this->Log($username, $log_description, $log_type);
    //                 //

    //                 DB::commit();

    //                 return $this->returnSuccess('Successful operation', []);

    //             } catch (\Throwable $e) {

    //                 DB::rollback();

    //                 return $this->returnErrorData('Something went wrong Please try again ', 404);
    //             }

    //         }

    //     } else {
    //         return $this->returnErrorData('Data Not Found', 404);
    //     }

    // }

    public function ExportItem(Request $request)
    {
        //
    }
}
