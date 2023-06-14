<?php

namespace App\Http\Controllers;

use App\Models\Checkout;
use App\Models\Sale_page;
use App\Models\Sale_page_line;
use App\Models\Sale_page_promotion;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;


class SalePageController extends Controller
{
    //
    public function destroy(Request $request, $id)
    {
        $loginBy = $request->login_by;

        if (!isset($loginBy)) {
            return $this->returnErrorData('[login_by] Data Not Found', 404);
        }

        DB::beginTransaction();

        try {

            $Sale_page = Sale_page::find($id);

            //log
            $userId = $loginBy->user_id;
            $type = 'Delete Sale_page';
            $description = 'User ' . $userId . ' has ' . $type . ' ' . $Sale_page->name;
            $this->Log($userId, $description, $type);
            //

            $Sale_page->delete();

            DB::commit();

            return $this->returnUpdate('Successful operation');
        } catch (\Throwable $e) {

            DB::rollback();

            return $this->returnErrorData('Something went wrong Please try again ' . $e, 404);
        }
    }

    public function UpdateSalePage(Request $request, $id)
    {

        $loginBy = $request->login_by;
        $salepage_promotion = $request->salepage_promotion;
        $sale_page_content = $request->sale_page_content;


        $name = $request->name;
        $Sale_page = Sale_page::find($id);

        if (!isset($Sale_page)) {
            return $this->returnErrorData('ไม่พบIDผู้ใช้งาน', 404);
        }
        $checkName = Sale_page::where('id', '!=', $id)
            ->where('name', $name)
            ->first();

        if ($checkName) {
            return $this->returnErrorData('There is already this name in the system', 404);
        } else {

            DB::beginTransaction();

            try {


                $Sale_page->name = $name;
                $Sale_page->select_product_id = $request->select_product_id;
                $Sale_page->delivery_id = $request->delivery_id;
                $Sale_page->bank_id = $request->bank_id;
                //$Sale_page->name = $request->name;

                $Sale_page->sale_pages_url = $request->sale_pages_url;
                $Sale_page->thank_you_url = $request->thank_you_url;
                $Sale_page->link_line = $request->link_line;
                $Sale_page->link_facebook = $request->link_facebook;
                $Sale_page->update_by = $loginBy->user_id;
                $Sale_page->updated_at = Carbon::now()->toDateTimeString();

                $Sale_page->save();


                for ($i = 0; $i < count($salepage_promotion); $i++) {

                    // "qty": 10,
                    // "price": 10,
                    // "total": 100

                    switch ($salepage_promotion[$i]['action']) {
                        case 'insert':
                            $newPage_sale = new Sale_page_promotion();
                            $newPage_sale->sale_pages_id = $Sale_page->id;
                            $newPage_sale->name = $salepage_promotion[$i]['name'];
                            $newPage_sale->qty = $salepage_promotion[$i]['qty'];
                            $newPage_sale->price = $salepage_promotion[$i]['price'];

                            $newPage_sale->save();
                            break;

                        case 'update':

                            $Salepage_promotion = Sale_page_promotion::find($salepage_promotion[$i]['sale_page_promotion_id']);
                            $Salepage_promotion->name = $salepage_promotion[$i]['name'];
                            $Salepage_promotion->qty = $salepage_promotion[$i]['qty'];
                            $Salepage_promotion->price = $salepage_promotion[$i]['price'];


                            $Salepage_promotion->save();
                            break;


                        case 'delete':
                            $Salepage_promotion = Sale_page_promotion::find($salepage_promotion[$i]['sale_page_promotion_id']);
                            $Salepage_promotion->delete();
                            break;

                        default:
                            # code...
                            break;
                    }
                }


                for ($j = 0; $j < count($sale_page_content); $j++) {



                    switch ($sale_page_content[$j]['action']) {
                        case 'insert':
                            $newPage_sale_line = new Sale_page_line();
                            $newPage_sale_line->sale_pages_id = $Sale_page->id;
                            $newPage_sale_line->text = $sale_page_content[$j]['text'];
                            $newPage_sale_line->html = $sale_page_content[$j]['html'];

                            $newPage_sale_line->save();
                            break;

                        case 'update':

                            $Salepage_line = Sale_page_line::find($sale_page_content[$j]['sale_page_line_id']);
                            $Salepage_line->text = $sale_page_content[$j]['text'];
                            $Salepage_line->html = $sale_page_content[$j]['html'];



                            $Salepage_promotion->save();
                            break;


                        case 'delete':
                            $Salepage_line = Sale_page_line::find($sale_page_content[$j]['sale_page_line_id']);
                            $Salepage_line->delete();
                            break;

                        default:
                            # code...
                            break;
                    }
                }


                //log
                $userId = $loginBy->user_id;
                $type = 'Edit Sale_page';
                $description = 'User ' . $userId . ' has ' . $type . ' ' . $Sale_page->name;
                $this->Log($userId, $description, $type);
                //

                DB::commit();

                return $this->returnUpdateReturnData('Successful operation',200);
            } catch (\Throwable $e) {

                DB::rollback();

                return $this->returnErrorData('Something went wrong Please try again ' . $e, 404);
            }
        }
    }


    public function SalePage(Request $request)
    {

        $columns = $request->columns;
        $length = $request->length;
        $order = $request->order;
        $search = $request->search;
        $start = $request->start;
        $page = $start / $length + 1;

        $col = array('id', 'name','select_product_id','sale_pages_url', 'created_at', 'updated_at');

        $d = Sale_page::select($col)->with('select_product')
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

            }

        }

        return $this->returnSuccess('Successful', $d);
    }

    public function showDetail($id)
    {

        if (!isset($id)) {
            return $this->returnErrorData('[id] Data Not Found', 404);
        }

        $sale_page = Sale_page::with('select_product')->with('sale_page_promotion')->with('sale_page_line')->with('html')

            ->find($id);


        return $this->returnSuccess('Successful', $sale_page);
    }
    public function getSalePages()
    {

        $Sale_page = Sale_page::get()->toarray();

        if (!empty($Sale_page)) {

            for ($i = 0; $i < count($Sale_page); $i++) {
                $Sale_page[$i]['No'] = $i + 1;

            }
        }

        return $this->returnSuccess('Successful', $Sale_page);
    }
    public function store(Request $request)
    {



        $sale_page_content = $request->sale_page_content;

        //dd($sale_page_link_social);
        $salepage_promotion = $request->salepage_promotion;


        $loginBy = $request->login_by;
        //$Item = $request->Item;

        if (!isset($request->name)) {
            return $this->returnErrorData('กรุณาใส่ชื่อสินค้า', 404);
        }else if (!isset($request->select_product_id)) {
            return $this->returnErrorData('กรุณาเลือกราย;การสินค้า', 404);

        } else if (!isset($loginBy)) {
            return $this->returnErrorData('[login_by] Data Not Found', 404);
        }

         else {

            DB::beginTransaction();

            try {

                $Sale_page = new Sale_page();
                //dd( $Item);


                    $Sale_page->select_product_id = $request->select_product_id;
                    $Sale_page->name = $request->name;

                    $Sale_page->delivery_id = $request->delivery_id;
                    $Sale_page->bank_id = $request->bank_id;


                    $Sale_page->sale_pages_url = $request->sale_pages_url;
                    $Sale_page->thank_you_url = $request->thank_you_url;
                    $Sale_page->link_line = $request->link_line;


                    $Sale_page->link_facebook = $request->link_facebook;

                    $Sale_page->create_by = $loginBy->user_id;
                    $Sale_page->save();

                    $Checkout = new Checkout();
                    $Checkout->sale_pages_id= $Sale_page->id;
                    $Checkout->item_id= $Sale_page->select_product_id;
                    $Checkout->save();
                    for ($i = 0; $i < count($salepage_promotion); $i++) {

                        $salepage_promotion[$i]['sale_pages_id'] = $Sale_page->id;


                        $salepage_promotion[$i]['created_at'] = Carbon::now()->toDateTimeString();
                        $salepage_promotion[$i]['updated_at'] = Carbon::now()->toDateTimeString();




                    }
                    for ($j = 0; $j < count($sale_page_content); $j++) {


                       // $Sale_page_line = new Sale_page_line();
                        $sale_page_content[$j]['sale_pages_id'] = $Sale_page->id;
                      // $sale_page_line->text = $sale_page_line[$j]['text'];
                        // $Sale_page_line->image = $sale_page_line[$j]['image'];
                        // $Sale_page_line->link_vido = $sale_page_line[$j]['link_vido'];
                        // $Sale_page_line->link_line = $sale_page_line[$j]['link_line'];
                        // $Sale_page_line->link_facebook = $sale_page_line[$j]['link_facebook'];
                        // $Sale_page_line->shopee_link = $sale_page_line[$j]['shopee_link'];
                        // $Sale_page_line->lasada_link = $sale_page_line[$j]['lasada_link'];
                        // $Sale_page_line->phone = $sale_page_line[$j]['phone'];
                        // $Sale_page_line->button_title = $sale_page_line[$j]['button_title'];
                       // $Sale_page_line->save();

                       $sale_page_content[$j]['created_at'] = Carbon::now()->toDateTimeString();
                       $sale_page_content[$j]['updated_at'] = Carbon::now()->toDateTimeString();

                    }


                      //add sale_page_lines
                      DB::table('sale_page_lines')->insert($sale_page_content);
                    //add sale_page_promotions
                      DB::table('sale_page_promotions')->insert($salepage_promotion);





                    DB::commit();

                    return $this->returnSuccess('Successful operation', $Sale_page);

            } catch (\Throwable $e) {

                DB::rollback();

                return $this->returnErrorData('Something went wrong Please try again ' . $e, 404);
            }
        }
    }
}
