<?php

namespace App\Http\Controllers;

use App\Models\Sale_order_line;
use Illuminate\Http\Request;

class SaleOrderLineController extends Controller
{

    public function getSaleOrderLine(Request $request)
    {
        $saleOrderId = $request->sale_order_id;

        $Sale_order_line = Sale_order_line::with('sale_order')
            ->where('sale_order_id', $saleOrderId)
            ->get()
            ->toarray();

        if (!empty($Sale_order_line)) {

            for ($i = 0; $i < count($Sale_order_line); $i++) {
                $Sale_order_line[$i]['No'] = $i + 1;

            }
        }

        return $this->returnSuccess('Successful', $Sale_order_line);
    }
}
