<?php

namespace App\Http\Controllers;

use App\Imports\ItemImport;
use App\Models\Bom;
use App\Models\Item;
use App\Models\Item_attribute;
use App\Models\Item_attribute_second;
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


    public function getItem(Request $request)
    {

        //check user
        $loginBy = $request->login_by;

        if ($loginBy->permission->id == 1) {
            $userId = null;
        } else {
            $userId = $loginBy->id;
        }
        //

        $item_type_id = $request->item_type_id;

        if (!isset($request->item_type_id)) {
            return $this->returnErrorData('[item_type_id] Data Not Found', 404);
        }

        $item = Item::with('user')
            ->with('item_type')
            ->with('vendor')
            ->with('user_create')
            ->where('item_type_id', $item_type_id);

        if ($userId) {
            $item->where('user_id', $userId);
        }
        $d = $item->where('status', 1)
            ->get();

        if ($d->isNotEmpty()) {

            for ($i = 0; $i < count($d); $i++) {
                $d[$i]->No  = $i + 1;

                //qty
                $d[$i]->qty = $this->getStockCount($d[$i]->id, null, null);

                //qty item_attributes
                for ($j = 0; $j < count($d[$i]->item_attributes); $j++) {
                    $d[$i]->item_attributes[$j]->qty  = $this->getStockCount($d[$i]->id, $d[$i]->item_attributes[$j]->id, null);

                    //qty item_attribute_seconds
                    for ($k = 0; $k < count($d[$i]->item_attributes[$j]->item_attribute_seconds); $k++) {
                        $d[$i]->item_attributes[$j]->item_attribute_seconds[$k]->qty  = $this->getStockCount($d[$i]->id, $d[$i]->item_attributes[$j]->id, $d[$i]->item_attributes[$j]->item_attribute_seconds[$k]->id);
                    }
                }
                //

                //booking
                $d[$i]->qty_booking = $this->getStockCountBooking($d[$i]->id, null, null);

                //qty item_attributes
                for ($j = 0; $j < count($d[$i]->item_attributes); $j++) {
                    $d[$i]->item_attributes[$j]->qty_booking  = $this->getStockCountBooking($d[$i]->id, $d[$i]->item_attributes[$j]->id, null);

                    //qty item_attribute_seconds
                    for ($k = 0; $k < count($d[$i]->item_attributes[$j]->item_attribute_seconds); $k++) {
                        $d[$i]->item_attributes[$j]->item_attribute_seconds[$k]->qty_booking  = $this->getStockCountBooking($d[$i]->id, $d[$i]->item_attributes[$j]->id, $d[$i]->item_attributes[$j]->item_attribute_seconds[$k]->id);
                    }
                }
                //

                //booking
                $d[$i]->qty_balance = $this->getStockCountBalance($d[$i]->id, null, null);

                //qty item_attributes
                for ($j = 0; $j < count($d[$i]->item_attributes); $j++) {
                    $d[$i]->item_attributes[$j]->qty_balance  = $this->getStockCountBalance($d[$i]->id, $d[$i]->item_attributes[$j]->id, null);

                    //qty item_attribute_seconds
                    for ($k = 0; $k < count($d[$i]->item_attributes[$j]->item_attribute_seconds); $k++) {
                        $d[$i]->item_attributes[$j]->item_attribute_seconds[$k]->qty_balance  = $this->getStockCountBalance($d[$i]->id, $d[$i]->item_attributes[$j]->id, $d[$i]->item_attributes[$j]->item_attribute_seconds[$k]->id);
                    }
                }

            }
        }

        return $this->returnSuccess('Successful', $d);
    }


    public function ItemPage(Request $request)
    {

        $columns = $request->columns;
        $length = $request->length;
        $order = $request->order;
        $search = $request->search;
        $start = $request->start;
        $page = $start / $length + 1;

        //check user
        $loginBy = $request->login_by;

        if ($loginBy->permission->id == 1) {
            $userId = null;
        } else {
            $userId = $loginBy->id;
        }
        //

        $item_type_id = $request->item_type_id;
        $set_type = $request->set_type;


        $col = array(
            'id',
            'user_id',
            'item_type_id',
            'item_id',
            'name',
            'barcode',
            'brand',
            'image',
            'unit_cost',
            'unit_price',
            'description',
            'set_type',
            'vendor_id',
            'weight',
            'width',
            'hight',
            'status',
            'create_by',
            'created_at',
            'update_by',
            'updated_at'
        );

        $orderby = array(
            'id',
            'user_id',
            'item_type_id',
            'item_id',
            'name',
            'barcode',
            'brand',
            'image',
            'unit_cost',
            'unit_price',
            'description',
            'set_type',
            'vendor_id',
            'weight',
            'width',
            'hight',
            'status',
            'create_by',
            'created_at',
            'update_by',
            'updated_at'
        );

        $d = Item::select($col)
            ->with('user')
            ->with('item_type')
            ->with('vendor')
            ->with('item_images')
            ->with('item_attributes.item_attribute_seconds')
            ->with('item_lines')
            ->with('user_create')
            ->where('set_type', $set_type);

        if ($userId) {
            $d->where('user_id', $userId);
        }


        if ($item_type_id) {
            $d->where('item_type_id', $item_type_id);
        }

        if ($orderby[$order[0]['column']]) {
            $d->orderby($orderby[$order[0]['column']], $order[0]['dir']);
        }
        if ($search['value'] != '' && $search['value'] != null) {

            $d->Where(function ($query) use ($search, $col) {

                //search datatable
                $query->where(function ($query) use ($search, $col) {
                    foreach ($col as &$c) {
                        $query->orWhere($c, 'like', '%' . $search['value'] . '%');
                    }
                });

                //search with

            });
        }

        $d = $d->paginate($length, ['*'], 'page', $page);

        if ($d->isNotEmpty()) {

            //run no
            $No = (($page - 1) * $length);

            for ($i = 0; $i < count($d); $i++) {

                $No = $No + 1;
                $d[$i]->No = $No;

                //qty
                $d[$i]->qty = $this->getStockCount($d[$i]->id, null, null);

                //qty item_attributes
                for ($j = 0; $j < count($d[$i]->item_attributes); $j++) {
                    $d[$i]->item_attributes[$j]->qty  = $this->getStockCount($d[$i]->id, $d[$i]->item_attributes[$j]->id, null);

                    //qty item_attribute_seconds
                    for ($k = 0; $k < count($d[$i]->item_attributes[$j]->item_attribute_seconds); $k++) {
                        $d[$i]->item_attributes[$j]->item_attribute_seconds[$k]->qty  = $this->getStockCount($d[$i]->id, $d[$i]->item_attributes[$j]->id, $d[$i]->item_attributes[$j]->item_attribute_seconds[$k]->id);
                    }
                }
                //

                //booking
                $d[$i]->qty_booking = $this->getStockCountBooking($d[$i]->id, null, null);

                //qty item_attributes
                for ($j = 0; $j < count($d[$i]->item_attributes); $j++) {
                    $d[$i]->item_attributes[$j]->qty_booking  = $this->getStockCountBooking($d[$i]->id, $d[$i]->item_attributes[$j]->id, null);

                    //qty item_attribute_seconds
                    for ($k = 0; $k < count($d[$i]->item_attributes[$j]->item_attribute_seconds); $k++) {
                        $d[$i]->item_attributes[$j]->item_attribute_seconds[$k]->qty_booking  = $this->getStockCountBooking($d[$i]->id, $d[$i]->item_attributes[$j]->id, $d[$i]->item_attributes[$j]->item_attribute_seconds[$k]->id);
                    }
                }
                //

                //booking
                $d[$i]->qty_balance = $this->getStockCountBalance($d[$i]->id, null, null);

                //qty item_attributes
                for ($j = 0; $j < count($d[$i]->item_attributes); $j++) {
                    $d[$i]->item_attributes[$j]->qty_balance  = $this->getStockCountBalance($d[$i]->id, $d[$i]->item_attributes[$j]->id, null);

                    //qty item_attribute_seconds
                    for ($k = 0; $k < count($d[$i]->item_attributes[$j]->item_attribute_seconds); $k++) {
                        $d[$i]->item_attributes[$j]->item_attribute_seconds[$k]->qty_balance  = $this->getStockCountBalance($d[$i]->id, $d[$i]->item_attributes[$j]->id, $d[$i]->item_attributes[$j]->item_attribute_seconds[$k]->id);
                    }
                }
                //


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

    public function Putstore(Request $request)
    {

        $item_line = $request->item_line;
        $item_image = $request->item_image;
        $item_attribute = $request->item_attribute;
        $loginBy = $request->login_by;


        if (!isset($request->name)) {
            return $this->returnErrorData('กรุณาเพิ่มชื่อสินค้า', 404);
        } else if (!isset($request->item_type_id)) {
            return $this->returnErrorData('กรุณาเลือกหมวดหมู่', 404);
        } else if (!isset($request->brand)) {
            return $this->returnErrorData('กรุณาใส่แบรนด์สินค้า', 404);
        } else if (!isset($request->image)) {
            return $this->returnErrorData('กรุณาเพิ่มรูปสินค้า', 404);
        } else if (!isset($request->unit_cost)) {
            return $this->returnErrorData('กรุณาใส่ต้นทุนสินค้า', 404);
        } else if (!isset($request->unit_price)) {
            return $this->returnErrorData('กรุณาใส่ราคาสินค้า', 404);
        } else if (!isset($request->set_type)) {
            return $this->returnErrorData('กรุณาประเภทสินค้า', 404);
        } else if (!isset($loginBy)) {
            return $this->returnErrorData('[login_by] Data Not Found', 404);
        } else if (count($item_image) > 10) {
            return $this->returnErrorData('คุณสามารถอัปโหลดภาพได้สูงสุด 10 ภาพ', 404);
        }

        $itemId = $request->item_id;
        //dd( $itemId);
        $checkitemId = Item::where('item_id', $itemId)->first();

        if ($checkitemId) {
            return $this->returnErrorData('There is already this item id in the system', 404);
        } else {

            DB::beginTransaction();

            try {

                //get item type
                $Item_type  = Item_type::find($request->item_type_id);
                if (!$Item_type) {
                    return $this->returnErrorData('ไม่พบข้อมูลประเภทสินค้า', 404);
                }


                //get last item no
                $lastNoItem = Item::where('user_id', $loginBy->id) //user_id
                    ->where('item_type_id', $Item_type->id) //item type
                    ->orderby('id', 'DESC')
                    ->first();

                if ($lastNoItem) {

                    $lastNumber = substr($lastNoItem->item_id, -7);

                    $newNumber = intval($lastNumber) + 1;
                    $Number = sprintf('%0' . strval(7) . 'd', $newNumber);

                    $runNumber =  $Item_type->code . '-' . $Number;
                } else {
                    $runNumber =  $Item_type->code . '-0000001';
                }
                //

                $Item = new Item();
                $Item->item_id = $runNumber;
                $Item->name = $request->name;
                $Item->barcode =  $this->genBarcodeNumber();
                $Item->brand = $request->brand;
                $Item->image = $request->image;
                $Item->unit_cost = $request->unit_cost;
                $Item->unit_price = $request->unit_price;
                $Item->description = $request->description;
                $Item->set_type = $request->set_type;
                $Item->weight = $request->weight;
                $Item->width = $request->width;
                $Item->hight = $request->hight;

                $Item->status = 1;

                $Item->create_by = $loginBy->user_id;

                $Item->item_type_id = $request->item_type_id;
                $Item->vendor_id = $request->vendor_id;
                $Item->user_id = $loginBy->id;
                $Item->save();

                if ($request->set_type  == 'set_products') {

                    for ($i = 0; $i < count($item_line); $i++) {

                        $item_line[$i]['main_item_id'] = $Item->id;
                        $item_line[$i]['created_at'] = Carbon::now()->toDateTimeString();
                        $item_line[$i]['updated_at'] = Carbon::now()->toDateTimeString();
                        $item_line[$i]['type']  = 'normal';
                    }

                    //add Item line
                    DB::table('item_lines')->insert($item_line);
                }

                //item image
                if (!empty($item_image)) {

                    for ($i = 0; $i < count($item_image); $i++) {

                        $item_image[$i]['item_id'] = $Item->id;
                        $item_image[$i]['created_at'] = Carbon::now()->toDateTimeString();
                        $item_image[$i]['updated_at'] = Carbon::now()->toDateTimeString();
                    }

                    //add Item line
                    DB::table('item_images')->insert($item_image);
                }

                //item attribute
                if (!empty($item_attribute)) {

                    //1 attribute
                    $Item->attribute = 1;
                    $Item->save();

                    for ($i = 0; $i < count($item_attribute); $i++) {

                        $Item_attribute =  new Item_attribute();
                        $Item_attribute->item_id = $Item->id;
                        $Item_attribute->image =  $item_attribute[$i]['image'];
                        $Item_attribute->name =  $item_attribute[$i]['name'];
                        $Item_attribute->unit_cost =  $item_attribute[$i]['unit_cost'];
                        $Item_attribute->unit_price =  $item_attribute[$i]['unit_price'];
                        $Item_attribute->barcode =  $this->genBarcodeNumber();
                        $Item_attribute->save();


                        if (!empty($item_attribute[$i]['item_attribute_second'])) {

                            //2 attribute
                            $Item->attribute = 2;
                            $Item->save();


                            for ($j = 0; $j < count($item_attribute[$i]['item_attribute_second']); $j++) {

                                $Item_attribute_second =  new Item_attribute_second();
                                $Item_attribute_second->item_id = $Item->id;
                                $Item_attribute_second->item_attribute_id = $Item_attribute->id;
                                $Item_attribute_second->image =  $item_attribute[$i]['item_attribute_second'][$j]['image'];
                                $Item_attribute_second->name =  $item_attribute[$i]['item_attribute_second'][$j]['name'];
                                $Item_attribute_second->unit_cost =  $item_attribute[$i]['item_attribute_second'][$j]['unit_cost'];
                                $Item_attribute_second->unit_price =  $item_attribute[$i]['item_attribute_second'][$j]['unit_price'];
                                $Item_attribute_second->barcode =  $this->genBarcodeNumber();
                                $Item_attribute_second->save();


                                //2 attribute
                                $item_id = $Item->id;
                                $item_attribute_id = $Item_attribute->id;
                                $Item_attribute_second_id = $Item_attribute_second->id;
                                $qty = $item_attribute[$i]['item_attribute_second'][$j]['qty'];

                                //add item trans
                                $Item_trans = new Item_trans();

                                $stockCount = $this->getStockCount($item_id, $item_attribute_id, $Item_attribute_second_id);

                                $Item_trans->description = null;
                                $Item_trans->item_id = $item_id;
                                $Item_trans->item_attribute_id = $item_attribute_id;
                                $Item_trans->Item_attribute_second_id = $Item_attribute_second_id;

                                $Item_trans->qty = $qty;
                                $Item_trans->stock = $stockCount;
                                $Item_trans->balance = $stockCount + abs($qty);
                                $Item_trans->status = 1;

                                $Item_trans->operation = 'finish';
                                $Item_trans->date = date('Y-m-d H:i:s');
                                $Item_trans->type = 'deposit';
                                $Item_trans->create_by = $loginBy->user_id;

                                $Item_trans->save();
                            }
                        } else {

                            //1 attribute
                            $item_id = $Item->id;
                            $item_attribute_id = $Item_attribute->id;
                            $Item_attribute_second_id = null;
                            $qty = $item_attribute[$i]['qty'];

                            //add item trans
                            $Item_trans = new Item_trans();

                            $stockCount = $this->getStockCount($item_id, $item_attribute_id, $Item_attribute_second_id);

                            $Item_trans->description = null;
                            $Item_trans->item_id = $item_id;
                            $Item_trans->item_attribute_id = $item_attribute_id;
                            $Item_trans->Item_attribute_second_id = $Item_attribute_second_id;

                            $Item_trans->qty = $qty;
                            $Item_trans->stock = $stockCount;
                            $Item_trans->balance = $stockCount + abs($qty);
                            $Item_trans->status = 1;

                            $Item_trans->operation = 'finish';
                            $Item_trans->date = date('Y-m-d H:i:s');
                            $Item_trans->type = 'deposit';
                            $Item_trans->create_by = $loginBy->user_id;

                            $Item_trans->save();
                        }
                    }
                } else {

                    //no attribute
                    $Item->attribute = 0;
                    $Item->save();

                    //no attribute
                    $item_id = $Item->id;
                    $item_attribute_id = null;
                    $Item_attribute_second_id = null;
                    $qty = $request->qty;


                    //add item trans
                    $Item_trans = new Item_trans();

                    $stockCount = $this->getStockCount($item_id, $item_attribute_id, $Item_attribute_second_id);

                    $Item_trans->description = null;
                    $Item_trans->item_id = $item_id;
                    $Item_trans->item_attribute_id = $item_attribute_id;
                    $Item_trans->Item_attribute_second_id = $Item_attribute_second_id;

                    $Item_trans->qty = $qty;
                    $Item_trans->stock = $stockCount;
                    $Item_trans->balance = $stockCount + abs($qty);
                    $Item_trans->status = 1;

                    $Item_trans->operation = 'finish';
                    $Item_trans->date = date('Y-m-d H:i:s');
                    $Item_trans->type = 'deposit';
                    $Item_trans->create_by = $loginBy->user_id;

                    $Item_trans->save();
                }

                DB::commit();

                return $this->returnSuccess('Successful operation', $Item);
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

        $Item = Item::with('user')
            ->with('item_type')
            ->with('vendor')
            ->with('item_images')
            ->with('item_attributes.item_attribute_seconds')
            ->with('item_lines.item.item_type')
            ->with('user_create')
            ->find($id);

        if (!empty($Item)) {

            //qty
            $Item->qty = $this->getStockCount($Item->id, null, null);

            //qty item_attributes
            for ($i = 0; $i < count($Item->item_attributes); $i++) {
                $Item->item_attributes[$i]->qty  = $this->getStockCount($Item->id, $Item->item_attributes[$i]->id, null);

                //qty item_attribute_seconds
                for ($j = 0; $j < count($Item->item_attributes[$i]->item_attribute_seconds); $j++) {
                    $Item->item_attributes[$i]->item_attribute_seconds[$j]->qty  = $this->getStockCount($Item->id, $Item->item_attributes[$i]->id, $Item->item_attributes[$i]->item_attribute_seconds[$j]->id);
                }
            }
            //

            //booking
            $Item->qty_booking = $this->getStockCountBooking($Item->id, null, null);

            //booking item_attributes
            for ($i = 0; $i < count($Item->item_attributes); $i++) {
                $Item->item_attributes[$i]->qty_booking  = $this->getStockCountBooking($Item->id, $Item->item_attributes[$i]->id, null);

                //booking item_attribute_seconds
                for ($j = 0; $j < count($Item->item_attributes[$i]->item_attribute_seconds); $j++) {
                    $Item->item_attributes[$i]->item_attribute_seconds[$j]->qty_booking  = $this->getStockCountBooking($Item->id, $Item->item_attributes[$i]->id, $Item->item_attributes[$i]->item_attribute_seconds[$j]->id);
                }
            }
            //

            //balance
            $Item->qty_balance = $this->getStockCountBalance($Item->id, null, null);

            //balance item_attributes
            for ($i = 0; $i < count($Item->item_attributes); $i++) {
                $Item->item_attributes[$i]->qty_balance  = $this->getStockCountBalance($Item->id, $Item->item_attributes[$i]->id, null);

                //balance item_attribute_seconds
                for ($j = 0; $j < count($Item->item_attributes[$i]->item_attribute_seconds); $j++) {
                    $Item->item_attributes[$i]->item_attribute_seconds[$j]->qty_balance  = $this->getStockCountBalance($Item->id, $Item->item_attributes[$i]->id, $Item->item_attributes[$i]->item_attribute_seconds[$j]->id);
                }
            }
            //
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


    public function update(Request $request, $id)
    {

        $item_line = $request->item_line;
        $item_image = $request->item_image;
        $item_attribute = $request->item_attribute;

        $loginBy = $request->login_by;

        if (!isset($id)) {
            return $this->returnErrorData('[id] Data Not Found', 404);
        } else  if (!isset($loginBy)) {
            return $this->returnErrorData('[login_by] Data Not Found', 404);
        } else if (count($item_image) > 10) {
            return $this->returnErrorData('คุณสามารถอัปโหลดภาพได้สูงสุด 10 ภาพ', 404);
        }


        DB::beginTransaction();

        try {

            $Item = Item::with('item_images')
                ->with('item_attributes')
                ->with('item_attribute_seconds')
                ->with('item_lines')
                ->find($id);

            $Item->name = $request->name;
            $Item->brand = $request->brand;

            if (!isset($request->image)) {
                $Item->image = $request->image;
            }

            $Item->unit_cost = $request->unit_cost;
            $Item->unit_price = $request->unit_price;
            $Item->description = $request->description;
            $Item->set_type = $request->set_type;
            $Item->weight = $request->weight;
            $Item->width = $request->width;
            $Item->hight = $request->hight;

            $Item->vendor_id = $request->vendor_id;
            $Item->item_type_id = $request->item_type_id;
            $Item->updated_at = Carbon::now()->toDateTimeString();

            $Item->save();

            if ($Item->set_type  == 'set_products') {

                if (!empty($item_lines)) {

                    //del
                    for ($i = 0; $i < count($Item->item_lines); $i++) {
                        $Item->item_lines[$i]->delete();
                    }

                    //add
                    for ($i = 0; $i < count($item_line); $i++) {

                        $item_line[$i]['main_item_id'] = $Item->id;
                        $item_line[$i]['created_at'] = Carbon::now()->toDateTimeString();
                        $item_line[$i]['updated_at'] = Carbon::now()->toDateTimeString();
                        $item_line[$i]['type']  = 'normal';
                    }

                    //add Item line
                    DB::table('item_lines')->insert($item_line);
                }
            }

            //item image
            if (!empty($item_image)) {

                //del
                for ($i = 0; $i < count($Item->item_image); $i++) {
                    $Item->item_image[$i]->delete();
                }

                //add
                for ($i = 0; $i < count($item_image); $i++) {

                    $item_image[$i]['item_id'] = $Item->id;
                    $item_image[$i]['created_at'] = Carbon::now()->toDateTimeString();
                    $item_image[$i]['updated_at'] = Carbon::now()->toDateTimeString();
                }
                //add Item line
                DB::table('item_images')->insert($item_image);
            }
            //

            //item attribute
            if (!empty($item_attribute)) {

                //del item_attributes
                for ($i = 0; $i < count($Item->item_attributes); $i++) {
                    $Item->item_attributes[$i]->delete();
                }
                //

                //del item_attribute_seconds
                for ($i = 0; $i < count($Item->item_attribute_seconds); $i++) {
                    $Item->item_attribute_seconds[$i]->delete();
                }
                //

                //add

                //1 attribute
                $Item->attribute = 1;
                $Item->save();

                for ($i = 0; $i < count($item_attribute); $i++) {

                    $Item_attribute =  new Item_attribute();
                    $Item_attribute->item_id = $Item->id;
                    $Item_attribute->image =  $item_attribute[$i]['image'];
                    $Item_attribute->name =  $item_attribute[$i]['name'];
                    $Item_attribute->unit_cost =  $item_attribute[$i]['unit_cost'];
                    $Item_attribute->unit_price =  $item_attribute[$i]['unit_price'];
                    $Item_attribute->barcode =  $this->genBarcodeNumber();
                    $Item_attribute->save();


                    if (!empty($item_attribute[$i]['item_attribute_second'])) {

                        //2 attribute
                        $Item->attribute = 2;
                        $Item->save();

                        for ($j = 0; $j < count($item_attribute[$i]['item_attribute_second']); $j++) {

                            $Item_attribute_second =  new Item_attribute_second();
                            $Item_attribute_second->item_id = $Item->id;
                            $Item_attribute_second->item_attribute_id = $Item_attribute->id;
                            $Item_attribute_second->image =  $item_attribute[$i]['image'];
                            $Item_attribute_second->name =  $item_attribute[$i]['name'];
                            $Item_attribute_second->unit_cost =  $item_attribute[$i]['unit_cost'];
                            $Item_attribute_second->unit_price =  $item_attribute[$i]['unit_price'];
                            $Item_attribute_second->barcode =  $this->genBarcodeNumber();
                            $Item_attribute_second->save();
                        }
                    }
                }
            } else {

                //0 attribute
                $Item->attribute = 0;
                $Item->save();
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
