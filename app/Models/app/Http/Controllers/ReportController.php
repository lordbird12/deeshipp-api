<?php

namespace App\Http\Controllers;

use App\Models\Delevery_order_line;
use App\Models\Forcash;
use App\Models\Forcash_line;
use App\Models\Item;
use App\Models\Item_trans;
use App\Models\Job;
use App\Models\Location;
use App\Models\Lot_trans;
use App\Models\Machine;
use App\Models\Maintenance_plan_line_component;
use App\Models\Qc;
use App\Models\Routing_line;
use App\Models\Sale_order_line;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ReportController extends Controller
{
    public function ReportStockItem(Request $request)
    {
        
        $itemTypeId = $request->item_type_id;
        $locationId = $request->location_id;
        $itemId = $request->item_id;


        //get item location
        $Item = Item_trans::select('item_trans.item_id', 'item_trans.location_1_id')
            ->leftjoin('item as i', 'i.id', '=', 'item_trans.item_id')
           


            ->where('i.item_type_id', 'like', '%' . $itemTypeId . '%')
            ->with(['item' => function ($query) {
                $query->with('item_type');
              
                 $query->with('location');
                // $query->with('material_group');
                // $query->with('material_type');
                // $query->with('material_grade');
                // $query->with('material_color');
                // $query->with('material_manufactu');
                // $query->with('spare_type');
            }]);


        if ($locationId) {
            $Item->where('item_trans.location_1_id', $locationId);
        }

        if ($itemId) {
            $Item->where('item_trans.item_id', $itemId);
        }

        $item = $Item->where('i.status', 1)
            ->groupby('item_trans.item_id', 'item_trans.location_1_id')
            ->get();

        for ($i = 0; $i < count($item); $i++) {

            $item[$i]->No = $i + 1;

            //qty item
            $item[$i]->qty = $this->getStockCount($item[$i]->item_id, [$item[$i]->location_1_id]);

            // dd( $item[$i]->qty);

            // //qty item
            // $itemTransQTY = Item_trans::where('item_id', $item[$j]->item_id)
            //     ->where('status', 1)
            //     ->where('location_1_id', $locationId)
            //     ->sum('qty');

            // $item[$i]->qty = $itemTransQTY;

            //location
            $location = Location::with('warehouse');

            if ($item[$i]->location_1_id) {
                $location->where('id', $item[$i]->location_1_id);
            }

            $Location = $location->first();

            if ($Location) {
                $item[$i]->location = $Location;
            } else {
                $item[$i]->location = null;
            }

        }

        return $this->returnSuccess('Successful', $item);
    }

    public function ReportTansItem(Request $request)
    {

        $itemId = $request->item_id;
        $dateStart = $request->date_start;
        $dateStop = $request->date_stop;

        $item_trans = Item_trans::with(['item' => function ($query) {
            
            $query->with('item_type');

        }])
        
            ->with('customer')
            ->with(['location_1' => function ($query) {
                $query->with('warehouse');
                
            }])
            ->with(['location_2' => function ($query) {
                $query->with('warehouse');
            }])
            ->with('report_stock')
            ->where('item_id', $itemId);

        if ($dateStart || $dateStop) {

            $item_trans->where('date', '>=', $dateStart);
            $item_trans->where('date', '<=', $dateStop);
        }

        $Item_trans = $item_trans->where('item_id', $itemId)
            ->where('status', 1)
            ->get();

        for ($i = 0; $i < count($Item_trans); $i++) {

            $Item_trans[$i]->No = $i + 1;

        }

        return $this->returnSuccess('Successful', $Item_trans);
    }

    public function ReportIemTypeStock(Request $request)
    {
       
        $dateStart = $request->date_start;
        $dateStop = $request->date_stop;
        $itemTypeId = $request->item_type_id;

        //location
        $locationId = $request->location_id;
        if (empty($locationId)) {
            $locationAll = Location::get();
            for ($i = 0; $i < count($locationAll); $i++) {
                $locationId[] = $locationAll[$i]->id;
            }
        }
        //

        $item = Item::with('item_type')

        
            
            ->with('location')
          
            // ->with('spare_type')
            ->whereIn('item_type_id', $itemTypeId)
           // ->Where('id', '1')
            ->get();

        if ($item->isNotEmpty()) {

            for ($i = 0; $i < count($item); $i++) {

                //No
                $item[$i]->No = $i + 1;

                //input
                $input = Item_trans::where('item_id', $item[$i]->id)
                    ->whereIn('location_1_id', $locationId);

                if ($dateStart || $dateStop) {

                    $input->where('date', '>=', $dateStart);
                    $input->where('date', '<=', $dateStop);
                };

                $Input = $input->where('type', 'Deposit')
                    ->where('status', 1)
                    ->sum('qty');
                //

                //output
                $output = Item_trans::where('item_id', $item[$i]->id)
                    ->whereIn('location_1_id', $locationId);
                if ($dateStart || $dateStop) {

                    $output->where('date', '>=', $dateStart);
                    $output->where('date', '<=', $dateStop);
                };

                $Output = $output->where('type', 'Withdraw')
                    ->where('status', 1)
                    ->sum('qty');

                $Output = abs($Output);
                //

                //Adj input
                $inputAdj = Item_trans::where('item_id', $item[$i]->id)
                    ->whereIn('location_1_id', $locationId);

                if ($dateStart || $dateStop) {

                    $inputAdj->where('date', '>=', $dateStart);
                    $inputAdj->where('date', '<=', $dateStop);
                };

                $InputAdj = $inputAdj->where('type', 'Adjust')
                    ->where('qty', '>', 0)
                    ->where('status', 1)
                    ->sum('qty');
                //

                //Adj output
                $outputAdj = Item_trans::where('item_id', $item[$i]->id)
                    ->whereIn('location_1_id', $locationId);
                if ($dateStart || $dateStop) {

                    $outputAdj->where('date', '>=', $dateStart);
                    $outputAdj->where('date', '<=', $dateStop);
                };

                $OutputAdj = $outputAdj->where('type', 'Adjust')
                    ->where('qty', '<', 0)
                    ->where('status', 1)
                    ->sum('qty');

                $OutputAdj = abs($OutputAdj);
                //

                //balance
                $balance = Item_trans::where('item_id', $item[$i]->id)
                    ->whereIn('location_1_id', $locationId)
                    ->where('date', '<=', $dateStop)
                    ->where('status', 1)
                    ->sum('qty');
                //

                //sum befor date start
                $sumBeforDateStart = Item_trans::where('item_id', $item[$i]->id)
                    ->whereIn('location_1_id', $locationId)
                    ->where('date', '<=', $dateStart)
                    ->where('status', 1)
                    ->sum('qty');
                //

                $item[$i]->adj_input = $InputAdj;
                $item[$i]->adj_output = $OutputAdj;

                $item[$i]->qty_input = $Input;
                $item[$i]->qty_output = $Output;

                $item[$i]->qty_input = $Input;
                $item[$i]->qty_output = $Output;

                //balance
                $item[$i]->balance = $balance;

                //sum befor date start
                $item[$i]->sum_befor_date_start = number_format($sumBeforDateStart, 0);




            }

            //stock control
           // $StockControl = $this->getStockControlByItemType($itemTypeId);

            return response()->json([
                'code' => strval(200),
                'status' => true,
                'message' => 'Successful',
              //  'stock_control' => $StockControl,
                'data' => $item,
            ], 200);

        }

    }

    public function ReportStockFG(Request $request)
    {
       
        $dateStart = $request->date_start;
        $dateStop = $request->date_stop;

        //location
        $locationId = $request->location_id;
        if (empty($locationId)) {
            $locationAll = Location::get();
            for ($i = 0; $i < count($locationAll); $i++) {
                $locationId[] = $locationAll[$i]->id;
            }
        }


        //
        $item = Item::with('item_type')
            //->with('unit_store')
            //->with('unit_buy')
           //->with('unit_sell')
            ->with('location')
          
            ->where('item_type_id', 1)
        // ->Where('id', '222')
            ->get();

        if ($item->isNotEmpty()) {

            for ($i = 0; $i < count($item); $i++) {

                //No
                $item[$i]->No = $i + 1;

                //input
                $input = Item_trans::where('item_id', $item[$i]->id)
                    ->whereIn('location_1_id', $locationId);

                if ($dateStart || $dateStop) {

                    $input->where('date', '>=', $dateStart);
                    $input->where('date', '<=', $dateStop);
                };

                $Input = $input->where('type', 'Deposit')
                    ->where('status', 1)
                    ->sum('qty');
                //

                //output
                $output = Item_trans::where('item_id', $item[$i]->id)
                    ->whereIn('location_1_id', $locationId);
                if ($dateStart || $dateStop) {

                    $output->where('date', '>=', $dateStart);
                    $output->where('date', '<=', $dateStop);
                };

                $Output = $output->where('type', 'Withdraw')
                    ->where('status', 1)
                    ->sum('qty');

                $Output = abs($Output);
                //

                //Adj input
                $inputAdj = Item_trans::where('item_id', $item[$i]->id)
                    ->whereIn('location_1_id', $locationId);

                if ($dateStart || $dateStop) {

                    $inputAdj->where('date', '>=', $dateStart);
                    $inputAdj->where('date', '<=', $dateStop);
                };

                $InputAdj = $inputAdj->where('type', 'Adjust')
                    ->where('status', 1)
                    ->where('qty', '>', 0)
                    ->sum('qty');
                //

                //Adj output
                $outputAdj = Item_trans::where('item_id', $item[$i]->id)
                    ->whereIn('location_1_id', $locationId);
                if ($dateStart || $dateStop) {

                    $outputAdj->where('date', '>=', $dateStart);
                    $outputAdj->where('date', '<=', $dateStop);
                };

                $OutputAdj = $outputAdj->where('type', 'Adjust')
                    ->where('status', 1)
                    ->where('qty', '<', 0)
                    ->sum('qty');

                $OutputAdj = abs($OutputAdj);
                //

                //balance
                $balance = Item_trans::where('item_id', $item[$i]->id)
                    ->whereIn('location_1_id', $locationId)
                    ->where('date', '<=', $dateStop)
                    ->where('status', 1)
                    ->sum('qty');
                //

                //sum befor date start
                $sumBeforDateStart = Item_trans::where('item_id', $item[$i]->id)
                    ->whereIn('location_1_id', $locationId)
                    ->where('date', '<=', $dateStart)
                    ->where('status', 1)
                    ->sum('qty');
                //

                $item[$i]->adj_input = $InputAdj;
                $item[$i]->adj_output = $OutputAdj;

                $item[$i]->qty_input = $Input;
                $item[$i]->qty_output = $Output;

                $item[$i]->qty_input = $Input;
                $item[$i]->qty_output = $Output;

                //balance
                $item[$i]->balance = $balance;

                //sum befor date start
                $item[$i]->sum_befor_date_start = number_format($sumBeforDateStart, 0);

            }

            //stock control
           // $StockControl = $this->getStockControlByItemType(1);

            return response()->json([
                'code' => strval(200),
                'status' => true,
                'message' => 'Successful',
                //'stock_control' => $StockControl,
                'data' => $item,
            ], 200);

        }

    }

    // public function ReportItemLot(Request $request)
    // {
    //     $itemLotId = $request->item_lot_id;
    //     $itemId = $request->item_id;
    //     $locationId = $request->location_1_id;

    //     $lot_trans = Lot_trans::with(['item' => function ($query) {
    //         $query->with('unit_store');
    //     }])
    //         ->with('location_1')
    //         ->with('item_trans');

    //     if ($itemId) {
    //         $lot_trans->where('item_id', $itemId);
    //     }

    //     if ($itemLotId) {
    //         $lot_trans->where('lot_id', $itemLotId);
    //     }

    //     if ($locationId) {
    //         $lot_trans->where('location_1_id', $locationId);
    //     }

    //     $Lot_trans = $lot_trans->where('status', 1)
    //         ->orderby('lot_id')
    //         ->get();

    //     if ($Lot_trans->isNotEmpty()) {

    //         for ($i = 0; $i < count($Lot_trans); $i++) {
    //             $Lot_trans[$i]->No = $i + 1;
    //         }
    //     }

    //     return $this->returnSuccess('Successful', $Lot_trans);

    // }

    // public function ReportForcash(Request $request)
    // {

    //     $year = $request->year;
    //     $customerId = $request->customer_id;

    //     $customer = Forcash::select('forcash.id as forcash_id', 'c.name', 'forcash.year')
    //         ->leftjoin('customer as c', 'c.id', 'forcash.customer_id')
    //         ->where('forcash.year', $year);

    //     if ($customerId) {
    //         $customer->where('c.id', $customerId);
    //     }

    //     $Customer = $customer->groupby('forcash.id', 'c.name', 'forcash.year')
    //         ->get();

    //     if ($Customer->isNotEmpty()) {

    //         for ($i = 0; $i < count($Customer); $i++) {

    //             $Customer[$i]->No = $i + 1;

    //             $Forcash_line = Forcash_line::with(['forcash' => function ($query) {
    //                 $query->with('customer');
    //             }])
    //                 ->where('forcash_id', $Customer[$i]->forcash_id)
    //                 ->get();

    //             $Customer[$i]->forcash = $Forcash_line;

    //             //sum

    //             $sum_p1 = 0;
    //             $sum_p2 = 0;
    //             $sum_p3 = 0;
    //             $sum_p4 = 0;
    //             $sum_p5 = 0;
    //             $sum_p6 = 0;
    //             $sum_p7 = 0;
    //             $sum_p8 = 0;
    //             $sum_p9 = 0;
    //             $sum_p10 = 0;
    //             $sum_p11 = 0;
    //             $sum_p12 = 0;

    //             $sum_r1 = 0;
    //             $sum_r2 = 0;
    //             $sum_r3 = 0;
    //             $sum_r4 = 0;
    //             $sum_r5 = 0;
    //             $sum_r6 = 0;
    //             $sum_r7 = 0;
    //             $sum_r8 = 0;
    //             $sum_r9 = 0;
    //             $sum_r10 = 0;
    //             $sum_r11 = 0;
    //             $sum_r12 = 0;

    //             for ($j = 0; $j < count($Forcash_line); $j++) {

    //                 //p
    //                 $p_jan = intval(str_replace(',', '', $Forcash_line[$j]->p_jan));
    //                 $sum_p1 += $p_jan;

    //                 $p_feb = intval(str_replace(',', '', $Forcash_line[$j]->p_feb));
    //                 $sum_p2 += $p_feb;

    //                 $p_mar = intval(str_replace(',', '', $Forcash_line[$j]->p_mar));
    //                 $sum_p3 += $p_mar;

    //                 $p_apr = intval(str_replace(',', '', $Forcash_line[$j]->p_apr));
    //                 $sum_p4 += $p_apr;

    //                 $p_may = intval(str_replace(',', '', $Forcash_line[$j]->p_may));
    //                 $sum_p5 += $p_may;

    //                 $p_jun = intval(str_replace(',', '', $Forcash_line[$j]->p_jun));
    //                 $sum_p6 += $p_jun;

    //                 $p_jul = intval(str_replace(',', '', $Forcash_line[$j]->p_jul));
    //                 $sum_p7 += $p_jul;

    //                 $aug = intval(str_replace(',', '', $Forcash_line[$j]->aug));
    //                 $sum_p8 += $aug;

    //                 $p_sep = intval(str_replace(',', '', $Forcash_line[$j]->p_sep));
    //                 $sum_p9 += $p_sep;

    //                 $p_oct = intval(str_replace(',', '', $Forcash_line[$j]->p_oct));
    //                 $sum_p10 += $p_oct;

    //                 $p_nov = intval(str_replace(',', '', $Forcash_line[$j]->p_nov));
    //                 $sum_p11 += $p_nov;

    //                 $p_dec = intval(str_replace(',', '', $Forcash_line[$j]->p_dec));
    //                 $sum_p12 += $p_dec;

    //                 //

    //                 //r
    //                 $r_jan = intval(str_replace(',', '', $Forcash_line[$j]->r_jan));
    //                 $sum_r1 += $r_jan;

    //                 $r_feb = intval(str_replace(',', '', $Forcash_line[$j]->r_feb));
    //                 $sum_r2 += $r_feb;

    //                 $r_mar = intval(str_replace(',', '', $Forcash_line[$j]->r_mar));
    //                 $sum_r3 += $r_mar;

    //                 $r_apr = intval(str_replace(',', '', $Forcash_line[$j]->r_apr));
    //                 $sum_r4 += $r_apr;

    //                 $r_may = intval(str_replace(',', '', $Forcash_line[$j]->r_may));
    //                 $sum_r5 += $r_may;

    //                 $r_jun = intval(str_replace(',', '', $Forcash_line[$j]->r_jun));
    //                 $sum_r6 += $r_jun;

    //                 $r_jul = intval(str_replace(',', '', $Forcash_line[$j]->r_jul));
    //                 $sum_r7 += $r_jul;

    //                 $aug = intval(str_replace(',', '', $Forcash_line[$j]->aug));
    //                 $sum_r8 += $aug;

    //                 $r_sep = intval(str_replace(',', '', $Forcash_line[$j]->r_sep));
    //                 $sum_r9 += $r_sep;

    //                 $r_oct = intval(str_replace(',', '', $Forcash_line[$j]->r_oct));
    //                 $sum_r10 += $r_oct;

    //                 $r_nov = intval(str_replace(',', '', $Forcash_line[$j]->r_nov));
    //                 $sum_r11 += $r_nov;

    //                 $r_dec = intval(str_replace(',', '', $Forcash_line[$j]->r_dec));
    //                 $sum_r12 += $r_dec;

    //                 //
    //             }

    //             $Customer[$i]->sum_p1 = number_format($sum_p1, 0);
    //             $Customer[$i]->sum_p2 = number_format($sum_p2, 0);
    //             $Customer[$i]->sum_p3 = number_format($sum_p3, 0);
    //             $Customer[$i]->sum_p4 = number_format($sum_p4, 0);
    //             $Customer[$i]->sum_p5 = number_format($sum_p5, 0);
    //             $Customer[$i]->sum_p6 = number_format($sum_p6, 0);
    //             $Customer[$i]->sum_p7 = number_format($sum_p7, 0);
    //             $Customer[$i]->sum_p8 = number_format($sum_p8, 0);
    //             $Customer[$i]->sum_p9 = number_format($sum_p9, 0);
    //             $Customer[$i]->sum_p10 = number_format($sum_p10, 0);
    //             $Customer[$i]->sum_p11 = number_format($sum_p11, 0);
    //             $Customer[$i]->sum_p12 = number_format($sum_p12, 0);

    //             $Customer[$i]->sum_r1 = number_format($sum_r1, 0);
    //             $Customer[$i]->sum_r2 = number_format($sum_r2, 0);
    //             $Customer[$i]->sum_r3 = number_format($sum_r3, 0);
    //             $Customer[$i]->sum_r4 = number_format($sum_r4, 0);
    //             $Customer[$i]->sum_r5 = number_format($sum_r5, 0);
    //             $Customer[$i]->sum_r6 = number_format($sum_r6, 0);
    //             $Customer[$i]->sum_r7 = number_format($sum_r7, 0);
    //             $Customer[$i]->sum_r8 = number_format($sum_r8, 0);
    //             $Customer[$i]->sum_r9 = number_format($sum_r9, 0);
    //             $Customer[$i]->sum_r10 = number_format($sum_r10, 0);
    //             $Customer[$i]->sum_r11 = number_format($sum_r11, 0);
    //             $Customer[$i]->sum_r12 = number_format($sum_r12, 0);

    //         }
    //     }

    //     return $this->returnSuccess('Successful', $Customer);

    // }

    public function ReportSaleOrder(Request $request)
    {

        $dateStart = $request->date_start;
        $dateStop = $request->date_stop;
        $customerId = $request->customer_id;
        $itemId = $request->item_id;
        $itemTypeId = $request->item_type_id;
        $status = $request->status;

        

        $sale_order_line = Sale_order_line::select(
            'i.id as item_id',
            'i.item_id as item_code',
            'sale_order_line.item_name',
            'sale_order_line.unit_price',
            'it.id as item_type_id',
            'it.name as item_type_name',
            DB::raw("SUM(sale_order_line.qty) as total_line_qty"),
            
            'so.id as sale_order_id',
            'so.order_id',
            'so.date_time',
            'so.status',

            //  'so.remark',
            //  'so.reason',
            //  'so.status_by',
            //  'so.status_at',
            //  'so.close_by',
            //  'so.close_at',
            //  'so.create_by',
            //  'so.created_at',
            //  'u.id as user_id',
            //  'u.user_id as sale_id',
            //  'u.name as sale_name',
            'c.id as customer_id',
            'c.name',
            'c.contact',
            'c.email',
            'c.phone',

        
            
        )
            ->leftjoin('sale_order as so', 'so.id', 'sale_order_line.sale_order_id')
            ->leftjoin('users as u', 'u.id', 'so.sale_id')
            ->leftjoin('customer as c', 'c.id', 'so.customer_id')
            ->leftjoin('item as i', 'i.id', 'sale_order_line.item_id')
            ->leftjoin('item_type as it', 'it.id', 'i.item_type_id');
           // ->leftjoin('customer_lines as cl', 'cl.id', 'c.id');

        if ($dateStart || $dateStop) {
            $sale_order_line->where('so.date_time', '>=', $dateStart);
            $sale_order_line->where('so.date_time', '<=', $dateStop);
        };

        if ($itemId) {
            $sale_order_line->where('sale_order_line.item_id', $itemId);
        }
        if ($itemTypeId) {
            $sale_order_line->where('i.item_type_id', $itemTypeId);
        }
        if ($customerId) {
            $sale_order_line->where('so.customer_id', $customerId);
        }

        $Sale_order = $sale_order_line->where('so.status',$status)
            ->groupby(
                'i.id',
                'i.item_id',
                'sale_order_line.item_name',
                'sale_order_line.unit_price',
                'it.id',
                'it.name',

                //'uc.id',
                //'uc.name',

                'so.id',
                'so.order_id',
                'so.date_time',

                //  'so.remark',
                'so.status',
                //  'so.reason',
                //  'so.status_by',
                //  'so.status_at',
                //  'so.close_by',
                //  'so.close_at',
                //  'so.create_by',
                //  'so.created_at',
                //  'u.id',
                //  'u.user_id',
                //  'u.name',
                'c.id',
                'c.name',
                'c.contact',
                'c.email',
                'c.phone',
                //'cl.address',
            )
            ->get();

        if ($Sale_order->isNotEmpty()) {

            for ($i = 0; $i < count($Sale_order); $i++) {

                $Sale_order[$i]->No = $i + 1;

                $Sale_order[$i]->total_line_qty = number_format($Sale_order[$i]->total_line_qty, 2);
                $Sale_order[$i]->unit_price = number_format($Sale_order[$i]->unit_price, 2);

            }
        }

        return $this->returnSuccess('Successful', $Sale_order);

    }

    // public function ReportDeleveryOrder(Request $request)
    // {
    //     // $dateStart = $request->date_start;
    //     // $dateStop = $request->date_stop;
    //     // $customerId = $request->customer_id;

    //     // $saleOrderId = $request->sale_order_id;
    //     // $itemId = $request->item_id;

    //     // $Delevery_order_line = Delevery_order_line::
    //     //     select('do.id', 'do.do_id', 'do.date', 'u.id as user_id', 'u.user_id as delevery_id', 'u.name as delevery_name', 'do.remark', 'do.status', 'do.status_by', 'do.status_at', 'do.reason', 'do.create_by', 'do.created_at', 'delevery_order_line.id as delevery_order_line_id', 'i.id as item_id', 'i.item_id as item_code', 'delevery_order_line.item_name', 'it.id as item_type_id', 'it.name as item_type_name'
    //     //     , 'delevery_order_line.qty', 'uc.id as unit_convertion_id', 'uc.name as unit_convertion_name', 'delevery_order_line.box as line_box', 'so.id as sale_order_id', 'so.order_id'
    //     //     , 'c.id as customer_id', 'c.name', 'c.contact', 'c.email', 'c.phone', 'c.adress')
    //     //     ->leftjoin('delevery_order as do', 'do.id', 'delevery_order_line.delevery_order_id')
    //     //     ->leftjoin('users as u', 'u.id', 'do.user_id')
    //     //     ->leftjoin('sale_order as so', 'so.id', 'do.sale_order_id')
    //     //     ->leftjoin('customer as c', 'c.id', 'do.customer_id')
    //     //     ->leftjoin('item as i', 'i.id', 'delevery_order_line.item_id')
    //     //     ->leftjoin('item_type as it', 'it.id', 'i.item_type_id')
    //     //     ->leftjoin('unit_convertion as uc', 'uc.id', 'delevery_order_line.unit_convertion_id');

    //     // if ($dateStart || $dateStop) {
    //     //     $Delevery_order_line->where('do.date', '>=', $dateStart);
    //     //     $Delevery_order_line->where('do.date', '<=', $dateStop);
    //     // };
    //     // $Delevery_order = $Delevery_order_line->where('do.customer_id', 'like', '%' . $customerId . '%')
    //     //     ->where('delevery_order_line.item_id', 'like', '%' . $itemId . '%')
    //     //     ->where('do.sale_order_id', 'like', '%' . $saleOrderId . '%')
    //     //     ->get();

    //     // if ($Delevery_order->isNotEmpty()) {

    //     //     for ($i = 0; $i < count($Delevery_order); $i++) {

    //     //         $Delevery_order[$i]->No = $i + 1;
    //     //     }
    //     // }

    //     // return $this->returnSuccess('Successful', $Delevery_order);

    //     // $dateStart = $request->date_start;
    //     // $dateStop = $request->date_stop;
    //     // $customerId = $request->customer_id;

    //     // $itemId = $request->item_id;
    //     // $item = Item::find($itemId);

    //     // $customer = Delevery_order_line::select('c.name', 'do.id as delevery_order_id')
    //     //     ->leftjoin('delevery_order as do', 'do.id', 'delevery_order_line.delevery_order_id')
    //     //     ->leftjoin('customer as c', 'c.id', 'do.customer_id')
    //     //     ->where('delevery_order_line.date', '>=', $dateStart)
    //     //     ->where('delevery_order_line.date', '<=', $dateStop);

    //     // if ($customerId) {
    //     //     $customer->where('c.id', $customerId);
    //     // }

    //     // $Customer = $customer->groupby('c.name', 'do.id')
    //     //     ->get();

    //     // if ($Customer->isNotEmpty()) {

    //     //     for ($i = 0; $i < count($Customer); $i++) {

    //     //         $Customer[$i]->No = $i + 1;

    //     //         $total = 0;

    //     //         //delevery
    //     //         $delevery_order_line = Delevery_order_line::with(['Delevery_order' => function ($query) {
    //     //             // $query->with('customer');
    //     //         }])
    //     //             ->where('delevery_order_id', $Customer[$i]->delevery_order_id);

    //     //         if ($item->item_id) {
    //     //             $delevery_order_line->where('item_id', $item->item_id);
    //     //         }

    //     //         $Delevery_order_line = $delevery_order_line->where('delevery_order_line.date', '>=', $dateStart)
    //     //             ->where('delevery_order_line.date', '<=', $dateStop)
    //     //             ->get();

    //     //         if ($Delevery_order_line->isNotEmpty()) {

    //     //             for ($j = 0; $j < count($Delevery_order_line); $j++) {
    //     //                 $total += $Delevery_order_line[$j]->qty;
    //     //             }
    //     //             //

    //     //         }

    //     //         $Customer[$i]->Delevery_order = $Delevery_order_line;

    //     //         $Customer[$i]->total = $total;

    //     //     }

    //     // }

    //     // return $this->returnSuccess('Successful', $Customer);

    //     $dateStart = $request->date_start;
    //     $dateStop = $request->date_stop;
    //     $customerId = $request->customer_id;

    //     $itemId = $request->item_id;

    //    $Delevery_order_line = Delevery_order_line::select(
    //         'do.do_id',
    //         'c.name as customer_name',

    //         'delevery_order_line.date',
    //         'delevery_order_line.time',

    //         'i.item_id',
    //         'i.name as item_name',

    //         'so.order_id',
    //         'delevery_order_line.po_no',
    //         'delevery_order_line.do_no',

    //         'delevery_order_line.do_qty',
    //         'uc.name as unit_convertion_name',

    //         'delevery_order_line.wh_no',
    //         'delevery_order_line.qty_box',
    //     )
    //         ->leftjoin('delevery_order as do', 'do.id', 'delevery_order_line.delevery_order_id')
    //         ->leftjoin('customer as c', 'c.id', 'do.customer_id')
    //         ->leftjoin('sale_order as so', 'so.id', 'delevery_order_line.sale_order_id')
    //         ->leftjoin('item as i', 'i.id', 'delevery_order_line.item_id')
    //         ->leftjoin('unit_convertion as uc', 'uc.id', 'delevery_order_line.unit_convertion_id')
    //         ->where('delevery_order_line.date', '>=', $dateStart)
    //         ->where('delevery_order_line.date', '<=', $dateStop);

    //     if ($customerId) {
    //         $Delevery_order_line->where('c.id', $customerId);
    //     }

    //     if ($itemId) {
    //         $Delevery_order_line->where('i.id', $itemId);
    //     }

    //     $delevery_order_line = $Delevery_order_line->where('do.status', 'Approved')
    //         ->get();

    //     if ($delevery_order_line->isNotEmpty()) {

    //         for ($i = 0; $i < count($delevery_order_line); $i++) {

    //             $delevery_order_line[$i]->No = $i + 1;

    //         }

    //     }

    //     return $this->returnSuccess('Successful', $delevery_order_line);

    // }

    public function ReportStockSlow(Request $request)
    {

        $item_type_id = $request->item_type_id;
        $dateNow = date('Y-m-d');

        $ItemTransMove = Item_trans::select('item_trans.item_id', 'item_trans.date')
            ->leftjoin('report_stock as rs', 'rs.id', 'item_trans.report_stock_id')
            ->where(function ($query) {
                $query->orwhere('rs.type', 'Deposit');
                $query->orwhere('rs.type', 'Withdraw');

            })
            ->where('item_trans.status', 1)
            ->groupby('item_trans.item_id', 'item_trans.date')
            ->get();

        $ID = [];
      //  $Id = [];

        if ($ItemTransMove->isNotEmpty()) {

            for ($i = 0; $i < count($ItemTransMove); $i++) {

                //slow 6 month
               // $countDate = $this->dateBetween($ItemTransMove[$i]->date, $dateNow);

                //get config stock
               // $Config_stock = $this->ConfigStock();

                // if ($countDate < intval($Config_stock->stock_dead)) {
                //     $Id[] = $ItemTransMove[$i]->item_id;
                // }

            }

            //unig id
           // $ID = array_unique($Id);
        }

        //get item stock slow
        $item = Item::with('item_type')
            
            ->with('location')
          
            ->whereNotIn('id', $ID);

        if ($item_type_id) {
            $item->where('item_type_id', $item_type_id);
        }

        $Item = $item->get();
        if ($Item->isNotEmpty()) {

            for ($i = 0; $i < count($Item); $i++) {

                $Item[$i]->No = $i + 1;

                //qty item
                $Item[$i]->qty = $this->getStockCount($Item[$i]->id, []);

                //trans move last date
                $Item_trans = Item_trans::select('item_trans.date')
                    ->leftjoin('report_stock as rs', 'rs.id', 'item_trans.report_stock_id')
                    ->where(function ($query) {
                        $query->orwhere('rs.type', 'Deposit');
                        $query->orwhere('rs.type', 'Withdraw');

                    })
                    ->where('item_trans.item_id', $Item[$i]->id)
                    ->where('item_trans.status', 1)
                    ->orderby('item_trans.date', 'DESC')
                    ->first();

                if ($Item_trans) {
                    $Item[$i]->last_move_date = $Item_trans->date;

                    //slow 6 month
                    $Item[$i]->count_date = number_format($this->dateBetween($Item[$i]->last_move_date, $dateNow), 0);

                } else {

                    $Item[$i]->last_move_date = null;

                    //slow 6 month
                    $Item[$i]->count_date = null;

                }
                //
            }
        }

        //qty > 0
        $data = [];
        for ($i = 0; $i < count($Item); $i++) {

            if ($Item[$i]->qty > 0) {
                $data[] = $Item[$i];
            }

        }

        return $this->returnSuccess('Successful', $data);

    }

    public function ReportStockDead(Request $request)
    {

        $item_type_id = $request->item_type_id;
        $dateNow = date('Y-m-d');

        $ItemTransMove = Item_trans::select('item_trans.item_id', 'item_trans.date')
            ->leftjoin('report_stock as rs', 'rs.id', 'item_trans.report_stock_id')
            ->where(function ($query) {
                $query->orwhere('rs.type', 'Deposit');
                $query->orwhere('rs.type', 'Withdraw');

            })
            ->where('item_trans.status', 1)
            ->groupby('item_trans.item_id', 'item_trans.date')
            ->get();
        $ID = [];
        $Id = [];

        if ($ItemTransMove->isNotEmpty()) {

            for ($i = 0; $i < count($ItemTransMove); $i++) {

                //dead 12 month
                $countDate = $this->dateBetween($ItemTransMove[$i]->date, $dateNow);

            //   if ($countDate < intval($Config_stock->stock_dead)) {
            //         $Id[] = $ItemTransMove[$i]->item_id;
            //      }

            }

        //     //unig id
        //     $ID = array_unique($Id);
         }

        //get item stock slow
        $item = Item::with('item_type')
          
            ->with('location')
           
            ->whereNotIn('id', $ID);

        if ($item_type_id) {
            $item->where('item_type_id', $item_type_id);
        }

        $Item = $item->get();

        if ($Item->isNotEmpty()) {

            for ($i = 0; $i < count($Item); $i++) {

                $Item[$i]->No = $i + 1;

                //qty item
                $Item[$i]->qty = $this->getStockCount($Item[$i]->id, []);

                //trans move last date
                $Item_trans = Item_trans::select('item_trans.date')
                    ->leftjoin('report_stock as rs', 'rs.id', 'item_trans.report_stock_id')
                    ->where(function ($query) {
                        $query->orwhere('rs.type', 'Deposit');
                        $query->orwhere('rs.type', 'Withdraw');

                    })
                    ->where('item_trans.item_id', $Item[$i]->id)
                    ->where('item_trans.status', 1)
                    ->orderby('item_trans.date', 'DESC')
                    ->first();

                if ($Item_trans) {
                    $Item[$i]->last_move_date = $Item_trans->date;

                    //dead 12 month
                    $Item[$i]->count_date = number_format($this->dateBetween($Item[$i]->last_move_date, $dateNow), 0);

                } else {

                    $Item[$i]->last_move_date = null;

                    // //dead 12 month
                    $Item[$i]->count_date = null;

                }
                //
            }

        }

        //qty > 0
        $data = [];
        for ($i = 0; $i < count($Item); $i++) {

            if ($Item[$i]->qty > 0) {
                $data[] = $Item[$i];
            }

        }

        return $this->returnSuccess('Successful', $data);
    }

    // public function ReportJob(Request $request)
    // {

    //     $dateStart = $request->date_start;
    //     $dateStop = $request->date_stop;
    //     $sale_order_id = $request->sale_order_id;

    //     $job = Job::select(
    //         'job.id',
    //         'job.job_id',
    //         'job.bom_id',
    //         'job.sale_order_id',
    //         'job.routing_id',
    //         'job.qty',
    //         'job.unit_convertion_id',
    //         'job.start_date',
    //         'job.stop_date',
    //         'job.finish_qty',
    //         'job.status',
    //         'job.status_by',
    //         'job.status_at',
    //         'job.reason'

    //     )
    //         ->leftjoin('sale_order as s', 's.id', 'job.sale_order_id')
    //         ->with('bom')
    //         ->with('sale_order')
    //         ->with('routing')
    //         ->with('unit_convertion');

    //     if ($sale_order_id) {
    //         $job->where('s.id', $sale_order_id);
    //     }

    //     if ($dateStart) {
    //         $job->where('job.start_date', '>=', $dateStart . ' 00:00:00');
    //     }

    //     if ($dateStop) {
    //         $job->where('job.start_date', '<=', $dateStop . ' 23:59:59');
    //     }

    //     $Job = $job->get();

    //     if ($Job->isNotEmpty()) {

    //         for ($i = 0; $i < count($Job); $i++) {

    //             $Job[$i]->No = $i + 1;
    //         }
    //     }

    //     return $this->returnSuccess('Successful', $Job);

    // }

    // public function ReportMachine(Request $request)
    // {
    //     $dateStart = $request->date_start;
    //     $dateStop = $request->date_stop;
    //     $machine_id = $request->machine_id;
    //     $type = $request->type;

    //     $machine = Machine::with('components');

    //     if ($machine_id) {
    //         $machine->where('id', $machine_id);
    //     }

    //     if ($type) {
    //         $machine->where('type', $type);
    //     }

    //     if ($dateStart) {
    //         $machine->where('created_at', '>=', $dateStart . ' 00:00:00');
    //     }

    //     if ($dateStop) {
    //         $machine->where('created_at', '<=', $dateStop . ' 23:59:59');
    //     }

    //     $Machine = $machine->get();

    //     if ($Machine->isNotEmpty()) {

    //         for ($i = 0; $i < count($Machine); $i++) {

    //             $Machine[$i]->No = $i + 1;
    //         }
    //     }

    //     return $this->returnSuccess('Successful', $Machine);
    // }

    // public function ReportMantenance(Request $request)
    // {
    //     $dateStart = $request->date_start;
    //     $dateStop = $request->date_stop;
    //     $mantenance_plan_id = $request->mantenance_plan_id;
    //     $plan_type = $request->plan_type;

    //     $mantenance = MatennencePlan::with('matennence_lines');

    //     if ($mantenance_plan_id) {
    //         $mantenance->where('id', $mantenance_plan_id);
    //     }

    //     if ($plan_type) {
    //         $mantenance->where('plan_type', $plan_type);
    //     }

    //     if ($dateStart) {
    //         $mantenance->where('created_at', '>=', $dateStart . ' 00:00:00');
    //     }

    //     if ($dateStop) {
    //         $mantenance->where('created_at', '<=', $dateStop . ' 23:59:59');
    //     }

    //     $Mantenance = $mantenance->get();

    //     if ($Mantenance->isNotEmpty()) {

    //         for ($i = 0; $i < count($Mantenance); $i++) {

    //             $Mantenance[$i]->No = $i + 1;
    //         }
    //     }

    //     return $this->returnSuccess('Successful', $Mantenance);
    // }

    // public function ReportResult(Request $request)
    // {
    //     $dateStart = $request->date_start;
    //     $dateStop = $request->date_stop;
    //     $result_mantenance_plan_id = $request->result_mantenance_plan_id;
    //     $plan_type = $request->plan_type;

    //     $resultmantenanceplan = ResultMantenancePlan::with('result_matennence_lines');

    //     if ($result_mantenance_plan_id) {
    //         $resultmantenanceplan->where('id', $result_mantenance_plan_id);
    //     }

    //     if ($plan_type) {
    //         $resultmantenanceplan->where('plan_type', $plan_type);
    //     }

    //     if ($dateStart) {
    //         $resultmantenanceplan->where('created_at', '>=', $dateStart . ' 00:00:00');
    //     }

    //     if ($dateStop) {
    //         $resultmantenanceplan->where('created_at', '<=', $dateStop . ' 23:59:59');
    //     }

    //     $Resultmantenanceplan = $resultmantenanceplan->get();

    //     if ($Resultmantenanceplan->isNotEmpty()) {

    //         for ($i = 0; $i < count($Resultmantenanceplan); $i++) {

    //             $Resultmantenanceplan[$i]->No = $i + 1;
    //         }
    //     }

    //     return $this->returnSuccess('Successful', $Resultmantenanceplan);
    // }

    public function ReportNG(Request $request)
    {

        $itemId = $request->item_id;

        $dateStart = $request->date_start;
        $dateStop = $request->date_stop;

        $ng = Item_trans::select(
            'j.start_date as production_date',
            'jt.period as shift',
            'jt.team',
            'c.name',
            'i.item_id as item_code',
            'i.name as item_description',
            'jt.machine',
            'j.job_id as production_order_no',
            'jt.lot_id as production_lot',
            'item_trans.qty',
            'item_trans.qty',
            'df.code as defect_code',
            'df.name as defect_name',

        )
            ->leftjoin('item as i', 'i.id', 'item_trans.item_id')
            ->leftjoin('job_trans as jt', 'jt.id', 'item_trans.job_trans_id')
            ->leftjoin('job as j', 'j.id', 'jt.job_id')
            ->leftjoin('sale_order as so', 'so.id', 'j.sale_order_id')
            ->leftjoin('customer as c', 'c.id', 'so.customer_id')
            ->leftjoin('qc_defect as df', 'df.id', 'item_trans.qc_defect_id')
            ->where('item_trans.location_1_id', $this->getLocationFACNG());

        if ($itemId) {
            $ng->where('item_trans.item_id', $itemId);
        }

        if ($dateStart) {
            $ng->where('item_trans.date', '>=', $dateStart);
        }

        if ($dateStop) {
            $ng->where('item_trans.date', '<=', $dateStop);
        }

        $Ng = $ng->get();

        $sum_qty = 0;

        if ($Ng->isNotEmpty()) {

            for ($i = 0; $i < count($Ng); $i++) {

                $Ng[$i]->No = $i + 1;
                $Ng[$i]->production_date = date('d/m/Y', strtotime(explode(' ', $Ng[$i]->production_date)[0]));

                $sum_qty += $Ng[$i]->qty;
            }
        }

        return response()->json([
            'code' => strval(200),
            'status' => true,
            'message' => 'Successful',
            'total_qty' => $sum_qty,
            'data' => $Ng,
        ], 200);
    }

  

    public function sort_array_multidim(array $array, $order_by)
    {
        //TODO -c flexibility -o tufanbarisyildirim : this error can be deleted if you want to sort as sql like "NULL LAST/FIRST" behavior.
        if (!is_array($array[0])) {
            throw new Exception('$array must be a multidimensional array!', E_USER_ERROR);
        }

        $columns = explode(',', $order_by);
        foreach ($columns as $col_dir) {
            if (preg_match('/(.*)([\s]+)(ASC|DESC)/is', $col_dir, $matches)) {
                if (!array_key_exists(trim($matches[1]), $array[0])) {
                    trigger_error('Unknown Column <b>' . trim($matches[1]) . '</b>', E_USER_NOTICE);
                } else {
                    if (isset($sorts[trim($matches[1])])) {
                        trigger_error('Redundand specified column name : <b>' . trim($matches[1] . '</b>'));
                    }

                    $sorts[trim($matches[1])] = 'SORT_' . strtoupper(trim($matches[3]));
                }
            } else {
                // throw new Exception("Incorrect syntax near : '{$col_dir}'",E_USER_ERROR);
            }
        }

        //TODO -c optimization -o tufanbarisyildirim : use array_* functions.
        $colarr = array();
        foreach ($sorts as $col => $order) {
            $colarr[$col] = array();
            foreach ($array as $k => $row) {
                $colarr[$col]['_' . $k] = strtolower($row[$col]);
            }
        }

        $multi_params = array();
        foreach ($sorts as $col => $order) {
            $multi_params[] = '$colarr[\'' . $col . '\']';
            $multi_params[] = $order;
        }

        $rum_params = implode(',', $multi_params);
        eval("array_multisort({$rum_params});");

        $sorted_array = array();
        foreach ($colarr as $col => $arr) {
            foreach ($arr as $k => $v) {
                $k = substr($k, 1);
                if (!isset($sorted_array[$k])) {
                    $sorted_array[$k] = $array[$k];
                }

                $sorted_array[$k][$col] = $array[$k][$col];
            }
        }

        return array_values($sorted_array);

    }

}
