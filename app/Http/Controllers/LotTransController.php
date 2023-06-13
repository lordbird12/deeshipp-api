<?php

namespace App\Http\Controllers;

use App\Models\Lot_trans;
use Illuminate\Http\Request;

class LotTransController extends Controller
{
    public function LotTransPage(Request $request)
    {
        
        $columns = $request->columns;
        $length = $request->length;
        $order = $request->order;
        $search = $request->search;
        $start = $request->start;
        $page = $start / $length + 1;

        $itemId = $request->item_id;
        $lotId = $request->item_lot_id;
        $locationId = $request->location_1_id;

        $col = array('id', 'item_id', 'lot_id', 'lot_maker', 'qty', 'location_1_id', 'item_trans_id', 'create_by', 'update_by', 'created_at', 'updated_at');

        $D = Lot_trans::select($col)
            ->with(['item' => function ($query) {
                $query->with('unit_store');
            }])
            ->with('location_1')
            ->with('item_trans');

        if ($itemId) {
            $D->where('item_id', $itemId);
        }

        if ($lotId) {
            $D->where('lot_id', $lotId);
        }

        if ($locationId) {
            $D->where('location_1_id', $locationId);
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

}
