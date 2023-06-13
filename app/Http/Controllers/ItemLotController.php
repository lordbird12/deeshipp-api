<?php

namespace App\Http\Controllers;

use App\Models\Item_lot;
use App\Models\Lot_trans;
use Illuminate\Http\Request;

class ItemLotController extends Controller
{
    public function getItemLot()
    {

        $Item_lot = Item_lot::select('lot_id')
            ->groupby('lot_id')
            ->get()
            ->toarray();

        return $this->returnSuccess('Successful', $Item_lot);
    }

    public function ItemLotPage(Request $request)
    {
        $columns = $request->columns;
        $length = $request->length;
        $order = $request->order;
        $search = $request->search;
        $start = $request->start;
        $page = $start / $length + 1;

        $itemId = $request->item_id;

        $col = array('id', 'item_id', 'lot_id', 'lot_maker', 'qty', 'balance', 'create_by', 'update_by', 'created_at', 'updated_at');

        $D = Item_lot::select($col)
            ->with(['item' => function ($query) {
                $query->with('unit_store');
                $query->with('unit_buy');
                $query->with('unit_sell');
                $query->with('location');
                $query->with('material_group');
                $query->with('material_type');
                $query->with('material_grade');
                $query->with('material_color');
                $query->with('material_manufactu');
                $query->with('spare_type');
            }]);

        if ($itemId) {
            $D->where('item_id', $itemId);
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

    public function getItemLotByItem(Request $request)
    {
        
        $itemId = $request->item_id;
        $location_1_id = $request->location_1_id;

        $Item_lot = Lot_trans::with('location_1')
            ->with(['item' => function ($query) {
                $query->with('unit_store');
                $query->with('unit_buy');
                $query->with('unit_sell');
                $query->with('location');
                $query->with('material_group');
                $query->with('material_type');
                $query->with('material_grade');
                $query->with('material_color');
                $query->with('material_manufactu');
                $query->with('spare_type');
            }])
            ->select('item_id', 'lot_id', 'lot_maker', 'location_1_id')
            ->where('item_id', $itemId)
            ->where('location_1_id', $location_1_id)
            ->groupby('item_id', 'lot_id', 'lot_maker', 'location_1_id')
            ->orderby('lot_id')
            ->get();

        if ($Item_lot->isNotEmpty()) {

            for ($i = 0; $i < count($Item_lot); $i++) {

                $Item_lot[$i]->No = $i + 1;

                //qty item
                $qtyItemLot = Lot_trans::where('item_id', $Item_lot[$i]->item_id)
                    ->where('lot_id', $Item_lot[$i]->lot_id)
                    ->where('location_1_id', $Item_lot[$i]->location_1_id)
                    ->where('status', 1)
                    ->sum('qty');

                $Item_lot[$i]->qty = $qtyItemLot;
                //

                //qty in progress manual
                $Item_trans = Lot_trans::leftjoin('item_trans as it', 'it.id', 'lot_trans.item_trans_id')
                    ->where('lot_trans.item_id', $Item_lot[$i]->item_id)
                    ->where('lot_trans.location_1_id', $Item_lot[$i]->location_1_id)
                    ->where('lot_trans.lot_id', $Item_lot[$i]->lot_id)
                    ->where('it.type', 'Withdraw')
                    ->where('lot_trans.status', 0)
                    ->sum('lot_trans.qty');

                $Item_lot[$i]->in_progress = abs($Item_trans); //in_progress

                $Item_lot[$i]->can_withdraw = $qtyItemLot - abs($Item_trans); //can withdraw
            }

        }

        return $this->returnSuccess('Successful', $Item_lot);
    }

}
