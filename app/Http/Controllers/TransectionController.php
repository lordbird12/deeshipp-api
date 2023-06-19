<?php

namespace App\Http\Controllers;

use App\Models\Transection;
use Illuminate\Http\Request;

class TransectionController extends Controller
{


    public function Page(Request $request)
    {

        $columns = $request->columns;
        $length = $request->length;
        $order = $request->order;
        $search = $request->search;
        $start = $request->start;
        $page = $start / $length + 1;

        $user_id = $request->user_id;
        $order_id = $request->order_id;
        $status = $request->status;

        // if (!isset($status)) {
        //     return $this->returnErrorData('[status] Data Not Found', 404);
        // }

        $col = array(
            'id',
            'user_id',
            'order_id',
            'date',
            'time',
            'refNo',
            'merchantId',
            'cc',
            'qrcode',
            'price',
            'fee',
            'total',
            'pre_wallet',
            'new_wallet',
            'type',
            'status',
            'remark',
            'created_at',
            'updated_at',
            'deleted_at',
        );

        $D = Transection::select($col)
            ->with('user')
            ->with('order');

        if ($user_id) {
            $D->where('user_id', $user_id);
        }

        if ($order_id) {
            $D->where('order_id', $order_id);
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
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        //
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
        //
    }

    /**
     * Display the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function show($id)
    {
        //
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
        //
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function destroy($id)
    {
        //
    }
}
