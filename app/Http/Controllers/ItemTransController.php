<?php

namespace App\Http\Controllers;

use App\Models\Item_trans;
use App\Models\Lot_trans;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ItemTransController extends Controller
{

    public function ItemTransPage(Request $request)
    {

        $columns = $request->columns;
        $length = $request->length;
        $order = $request->order;
        $search = $request->search;
        $start = $request->start;
        $page = $start / $length + 1;

        $reportStockId = $request->report_stock_id;

        if (!isset($reportStockId)) {
            return $this->returnErrorData('[report_stock_id] Data Not Found', 404);
        }

        $col = array('id', 'item_id', 'report_stock_id', 'customer_id', 'vendor_id', 'date', 'stock', 'qty'
            , 'balance', 'adj_qa','delevery_order_id'
            , 'po_number', 'remark', 'type', 'status', 'create_by', 'created_at', 'updated_at');
//dd( $col);
        $d = Item_trans::select($col)
            ->with(['item' => function ($query) {
                //$query->with('unit_store');
                //$query->with('unit_buy');
                //$query->with('unit_sell');

                //$query->with('material_group');
                //$query->with('material_type');
                //$query->with('material_grade');
                //$query->with('material_color');
                // $query->with('material_manufactu');
                // $query->with('spare_type');
              //  dd($query->with('unit_store'));
            }])
            ->with('customer')
            ->with('vendor')
            ->with('report_stock')

            //->with('job')
            //->with('delevery_order')
            //->with('qc')
            //->with('qc_incoming_receive_mat')
            ->where('report_stock_id', $reportStockId)
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

                //operater
                if ($d[$i]->qty < 0) {

                    $d[$i]->qty = abs($d[$i]->qty);
                    $d[$i]->operater = 'minus';

                } else {

                    $d[$i]->operater = 'plus';

                }

            }

        }

        return $this->returnSuccess('Successful', $d);

    }

    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */


    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */

    /**
     * Display the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */

    /**
     * Show the form for editing the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */

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

        if (!isset($id)) {
            return $this->returnErrorData('[id] Data Not Found', 404);
        } else if (!isset($loginBy)) {
            return $this->returnErrorData('[login_by] Data Not Found', 404);
        }

        DB::beginTransaction();

        try {

            $Item_trans = Item_trans::find($id);

            //check +- qty
            if ($Item_trans->type == 'Deposit' || $Item_trans->type == 'QC' || $Item_trans->type == 'Mat_QC' || $Item_trans->type == 'Mat_Cancel') {
                $QTY = $request->qty;
            } else if ($Item_trans->type == 'Withdraw') {
                $QTY = -$request->qty;
            } else if ($Item_trans->type == 'Adjust') {
                //adj
                if ($Item_trans->description == 'Add') {
                    $QTY = $request->qty;
                } else {
                    $QTY = -$request->qty;
                }

            }

            $Item_trans->qty = $QTY;
            $Item_trans->balance = $request->balance;
            // $Item_trans->lot_maker = $request->lot_maker; //lot_maker
            $Item_trans->unit_convertion_id = $request->unit_convertion_id;

            $Item_trans->remark = $request->remark;
            $Item_trans->update_by = $loginBy->user_id;
            $Item_trans->updated_at = Carbon::now()->toDateTimeString();

            // $Item_trans->customer_id = $request->customer_id;
            $Item_trans->save();



            //lot
            $Lot = $request->lot;

            if (!empty($Lot)) {

                //check qty item trans
                $sumQty = 0;
                for ($j = 0; $j < count($Lot); $j++) {

                    if (abs(intval($Lot[$j]['qty'])) > (abs($Item_trans->qty) - $sumQty)) {
                        return $this->returnErrorData('Qty is over limit', 404);
                    }

                    $sumQty += abs(intval($Lot[$j]['qty']));
                }
                //
                //

            }

            DB::commit();

            return $this->returnUpdate('Successful operation');

        } catch (\Throwable $e) {

            DB::rollback();

            return $this->returnErrorData('Something went wrong Please try again' . $e, 404);
        }

    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function destroy($id, Request $request)
    {

        $loginBy = $request->login_by;

        if (!isset($loginBy)) {
            return $this->returnErrorData('[login_by] Data Not Found', 404);
        }

        DB::beginTransaction();

        try {

            $Item_trans = Item_trans::find($id);
            $Item_trans->delete();

            DB::commit();

            return $this->returnUpdate('Successful operation');

        } catch (\Throwable $e) {

            DB::rollback();

            return $this->returnErrorData('Something went wrong Please try again ' . $e, 404);
        }
    }

    public function editItemTrans(Request $request)
    {

        $itemTrans = $request->item_trans;

        $loginBy = $request->login_by;

        if (empty($itemTrans)) {
            return $this->returnErrorData('[item_trans] Data Not Found', 404);
        } else if (!isset($loginBy)) {
            return $this->returnErrorData('[login_by] Data Not Found', 404);
        }

        DB::beginTransaction();

        try {

            for ($i = 0; $i < count($itemTrans); $i++) {

                $Item_trans = Item_trans::find($itemTrans[$i]['id']);

                if ($itemTrans[$i]->operater == 'plus') {
                    $Item_trans->qty = $itemTrans[$i]['qty']; //+
                } else {
                    $Item_trans->qty = -$itemTrans[$i]['qty']; //-
                }

                $Item_trans->balance = $itemTrans[$i]['balance'];
                $Item_trans->unit_convertion_id = $itemTrans[$i]['unit_convertion_id'];
                $Item_trans->remark = $itemTrans[$i]['remark'];
                $Item_trans->update_by = $loginBy->user_id;
                $Item_trans->updated_at = Carbon::now()->toDateTimeString();

                $Item_trans->customer_id = $itemTrans[$i]['customer_id'];
                $Item_trans->save();

            }

            DB::commit();

            return $this->returnUpdate('Successful operation');

        } catch (\Throwable $e) {

            DB::rollback();

            return $this->returnErrorData('Something went wrong Please try again' . $e, 404);
        }

    }




    public function ItemStockPage(Request $request)
    {

        $columns = $request->columns;
        $length = $request->length;
        $order = $request->order;
        $search = $request->search;
        $start = $request->start;
        $page = $start / $length + 1;

     //   $reportStockId = $request->report_stock_id;


        $col = array('id', 'item_id', 'report_stock_id', 'customer_id', 'vendor_id', 'date', 'stock', 'qty'
            , 'balance', 'adj_qa','delevery_order_id'
            , 'po_number', 'remark', 'type', 'status', 'create_by', 'created_at', 'updated_at');
//dd( $col);
        $d = Item_trans::select($col)
            ->with(['item' => function ($query) {


            }])
            ->with('item.item_type')
            ->with('customer')
            ->with('vendor')
            ->with('report_stock')
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

                //operater
                if ($d[$i]->qty < 0) {

                    $d[$i]->qty = abs($d[$i]->qty);
                    $d[$i]->operater = 'minus';

                } else {

                    $d[$i]->operater = 'plus';

                }

            }

        }

        return $this->returnSuccess('Successful', $d);

    }

}
