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
                $Order->unit_cost = $request->unit_cost;
                $Order->unit_price = $request->unit_price;
                $Order->description = $request->description;
                $Order->item_type_id = $request->item_type_id;
                $Order->set_type = $request->set_type;
                $Order->weight = $request->weight;

                $Order->updated_at = Carbon::now()->toDateTimeString();

                $Order->save();



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

                    switch ($item_line[$i]['action']) {
                        case 'insert':
                            
                            $newItemLine = new Item_line();
                            $newItemLine->item_id = $item_line[$i]['item_id'];
                            $newItemLine->main_item_id = $id;
                            $newItemLine->qty = $item_line[$i]['qty'];
                            $newItemLine->price = $item_line[$i]['price'];
                            $newItemLine->total = $item_line[$i]['total'];
                            $newItemLine->type = 'normal';
                            $newItemLine->save();
                            break;

                        case 'update':

                            $Item_line = Item_line::find($item_line[$i]['item_line_id']);

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
            }

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
            'weight',
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
                $Item->weight = $request->weight;

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
                    $Item->vendor_id = $request->vendor_id;

                    $Item->set_type = $request->set_type;
                    
                    $Item->brand = $request->brand;
                    $Item->unit_cost = $request->unit_cost;
                    $Item->unit_price = $request->unit_price;

                    $Item->description = $request->description;
                    $Item->weight = $request->weight;
                    $Item->status = 1;

                    $Item->create_by = $loginBy->user_id;

                    $Item->item_type_id = $request->item_type_id;

                    $Item->save();

                    //log
                    $userId = $loginBy->user_id;
                    $type = 'Add Item';
                    $description = 'User ' . $userId . ' has ' . $type . ' ' . $itemId;
                    $this->Log($userId, $description, $type);
                    //


                    DB::commit();
                    return $this->returnSuccess('Successful operation', $Item);

                    
                    
                } else if ($request->set_type  == 'set_products') {

                    $itemId = $request->item_id;

                    $Item->item_id = $this->getLastNumber(5);
                    $Item->name = $request->name;
                    $Item->barcode = $this->getLastNumber(5);
                    $Item->image = $request->image;
                    $this->setRunDoc(5, $Item->barcode, $Item->item_id);
                    $Item->total_price = $request->total_price;
                    $Item->brand = $request->brand;
                    $Item->description = $request->description;

                  
                    $Item->set_type = $request->set_type;


                    $Item->status = 1;

                    $Item->create_by = $loginBy->user_id;

                    $Item->item_type_id = $request->item_type_id;

                    $Item->save();;



                 

                    for ($i = 0; $i < count($item_line); $i++) {


                        
                        $item_line[$i]['main_item_id'] = $Item->id;

                        $item_line[$i]['created_at'] = Carbon::now()->toDateTimeString();
                        $item_line[$i]['updated_at'] = Carbon::now()->toDateTimeString();

                        $Item_trans = new Item_trans();
                        //qty withdraw
                        $qty = -$item_line[$i]['qty'];
    
                        $Item_Line = Item::where('id', $item_line[$i]['item_id'])->first();
    
                         $stockCount = $this->getStockCount($item_line[$i]['item_id'], []);
    
    
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

                $Item->set_type = $request->set_type;
                $Item->total_price = $request->total_price;


                $Item->brand = $request->brand;
                $Item->unit_cost = $request->unit_cost;
                $Item->unit_price = $request->unit_price;

                $Item->description = $request->description;
                $Item->weight = $request->weight;

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

                return $this->returnUpdate('Successful operation',$Item);
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

}
