<?php

namespace App\Http\Controllers;

use App\Models\Sale_order;
use App\Models\Sale_page_order;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class SalePageOrderController extends Controller
{

    public function destroy(Request $request, $id)
    {
        $loginBy = $request->login_by;

        if (!isset($loginBy)) {
            return $this->returnErrorData('[login_by] Data Not Found', 404);
        }

        DB::beginTransaction();

        try {

            $Sale_page_order = Sale_page_order::find($id);

            //log
            $userId = $loginBy->user_id;
            $type = 'Delete Sale_page_order';
            $description = 'User ' . $userId . ' has ' . $type . ' ' . $Sale_page_order->name;
            $this->Log($userId, $description, $type);
            //

            $Sale_page_order->delete();

            DB::commit();

            return $this->returnUpdate('Successful operation');
        } catch (\Throwable $e) {

            DB::rollback();

            return $this->returnErrorData('Something went wrong Please try again ' . $e, 404);
        }
    }


    public function SalePageOrder(Request $request)
    {

        $type = $request->type;
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

        $D = Sale_order::select($col)
            ->where('channal', $type)
            ->with('customer')
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
}
