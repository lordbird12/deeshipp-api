<?php

namespace App\Http\Controllers;

use App\Models\Checkout;
use App\Models\Item;
use App\Models\Item_trans;
use App\Models\Sale_order;
use App\Models\Sale_order_line;
use App\Models\Sale_page;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Mpdf\Tag\Dd;

class CheckoutController extends Controller
{



    public function ShowDetail($id)
    {

        if (!isset($id)) {
            return $this->returnErrorData('[id] Data Not Found', 404);
        }

        $Checkout = Checkout::with('sale_page_promotion')->with('item')->find($id);


        return $this->returnSuccess('Successful', $Checkout);
    }

    public function getCheckout()
    {

        $Checkout = Checkout::with('sale_page_id')->get()->toarray();

        if (!empty($Checkout)) {

            for ($i = 0; $i < count($Checkout); $i++) {
                $Checkout[$i]['No'] = $i + 1;
            }
        }

        return $this->returnSuccess('Successful', $Checkout);
    }


    public function Pushstore(Request $request)
    {


        $sale_orders = $request->sale_orders;
        //dd( $sale_orders);

        $salepage_promotion = $request->salepage_promotion;
        //$Item = $request->Item;

        if (!isset($request->text)) {
            return $this->returnErrorData('กรุณาเลือกรายการสินค้า', 404);
        } else {

            DB::beginTransaction();

            try {



                $Checkout = new Checkout();


                $Checkout->text = $request->text;
                $Checkout->discount = $request->discount;

                $Checkout->qty = $request->qty;
                $Checkout->price = $request->price;
                $Checkout->save();

                $sale_page = new Sale_page();
                $sale_page->checkout_id = $Checkout->id;
                $sale_page->save();


                for ($i = 0; $i < count($sale_orders); $i++) {


                    $sale_orders[$i]['order_id'] = $this->getLastNumber(3);
                    //run number
                    $this->setRunDoc(5, $sale_orders[$i]['order_id']);

                    $sale_orders[$i]['created_at'] = Carbon::now()->toDateTimeString();
                    $sale_orders[$i]['updated_at'] = Carbon::now()->toDateTimeString();
                }
                // // for ($j = 0; $j < count($sale_page_line); $j++) {


                // //     $Sale_page_line = new Sale_page_line();
                // //     $Sale_page_line->sale_pages_id = $Sale_page->id;
                // //     $Sale_page_line->text = $sale_page_line[$j]['text'];
                // //     $Sale_page_line->image = $sale_page_line[$j]['image'];
                // //     $Sale_page_line->link_vido = $sale_page_line[$j]['link_vido'];
                // //     $Sale_page_line->link_line = $sale_page_line[$j]['link_line'];
                // //     $Sale_page_line->link_facebook = $sale_page_line[$j]['link_facebook'];
                // //     $Sale_page_line->shopee_link = $sale_page_line[$j]['shopee_link'];
                // //     $Sale_page_line->lasada_link = $sale_page_line[$j]['lasada_link'];
                // //     $Sale_page_line->phone = $sale_page_line[$j]['phone'];
                // //     $Sale_page_line->button_title = $sale_page_line[$j]['button_title'];

                // //     $Sale_page_line->save();

                // // }
                // //add Item line
                DB::table('sale_order')->insert($sale_orders);


                DB::commit();

                return $this->returnSuccess('Successful operation', $Checkout);
            } catch (\Throwable $e) {

                DB::rollback();

                return $this->returnErrorData('Something went wrong Please try again ' . $e, 404);
            }
        }
    }

    public function UpdateCheckout(Request $request, $id)
    {
        $sale_orders = $request->sale_orders;
        //$sale_order_line = $request->sale_order_line;

        if (!isset($id)) {
            return $this->returnErrorData('[id] Data Not Found', 404);
        } else if (!isset($request->text)) {
            return $this->returnErrorData('[text] Data Not Found', 404);
        }

        DB::beginTransaction();

        try {


            $Checkout = Checkout::find($id);

            $Checkout->text = $request->text;
            $Checkout->discount = $request->discount;
            $Checkout->qty = $request->qty;
            $Checkout->price = $request->price;

            $Checkout->updated_at = Carbon::now()->toDateTimeString();
            $Checkout->save();

            // $sale_order = new Sale_order();
            // $sale_order->channal = "SP";

            // $sale_orders->order_id = $this->getLastNumber(3);
            // //run number
            // $this->setRunDoc(5, $sale_orders->order_id);
            // $sale_order->save();



            // $Item_trans = new Item_trans();
            // //qty withdraw
            // $qty = -$sale_order_line->qty;

            // $Item = Item::where('id', $sale_order_line->item_id)->first();


            // $stockCount = $this->getStockCount($sale_order_line->item_id, []);


            // //  $stockCount = $this->getStockCount($Order[$i]['item_id'], [$Item->location_id]);

            // if (abs($qty) > $stockCount) {
            //     return $this->returnErrorData('Not enough item', 404);
            // }

            //     $Item_trans->sale_order_id = $sale_order_line->id;
            //     $Item_trans->item_id = $Item->id;
            //     $Item_trans->qty = $qty;

            //     $Item_trans->location_1_id = $Item->location_id;
            //    // $Item_trans->customer_id = $Customer->id;
            //     $Item_trans->stock = $stockCount;
            //     $Item_trans->balance = $stockCount - abs($qty);
            //     $Item_trans->status = 1;
            //     $Item_trans->operation = 'booking';
            //     $Item_trans->date = $request->date_time;
            //     $Item_trans->type = 'Withdraw';
            //     $Item_trans->save();




            for ($i = 0; $i < count($sale_orders); $i++) {


                $sale_orders[$i]['order_id'] = $this->getLastNumber(3);
                //run number
                $this->setRunDoc(5, $sale_orders[$i]['order_id']);

                //$sale_orders[$i]['item_id'] = $Checkout->item_id;
                $sale_orders[$i]['description'] = $Checkout->text;
                //$sale_orders[$i]['channal'] = "SP";
                // $sale_orders[$i]['qty'] = $Checkout->qty;
                // $sale_orders[$i]['price'] = $Checkout->price;
                // $sale_orders[$i]['discount'] = $Checkout->discount;


                $sale_orders[$i]['created_at'] = Carbon::now()->toDateTimeString();
                $sale_orders[$i]['updated_at'] = Carbon::now()->toDateTimeString();




                $sale_order_line = new sale_order_line();
                $sale_order_line->item_id = $Checkout->item_id;
                $sale_order_line->qty = $Checkout->qty;
                // $sale_order_line->sale_order_id = $sale_order->id;
                $sale_order_line->unit_price = $Checkout->price;
                $sale_order_line->discount = $Checkout->discount;
                $sale_order_line->save();

                //stock Count
                $stockCount = $this->getStockCount($sale_order_line->item_id, null, null);
                if (abs($sale_order_line->qty) > $stockCount) {
                    return $this->returnErrorData('สินค้าใน stock ไม่พอ', 404);
                }

                //$sale_order =  Sale_order::orderby('id','DESC')->first();
                //dd( $sale_order);

            }

            // for ($j = 0; $j < count($sale_order_line); $j++) {



            //     $sale_order_line[$j]['item_id'] = $Checkout->item_id;

            //     $sale_order_line[$j]['qty'] = $Checkout->qty;
            //     $sale_order_line[$j]['unit_price'] = $Checkout->price;
            //     $sale_order_line[$j]['discount'] = $Checkout->discount;

            //     //$sale_order_line[$i]['sale_order_id'] = $sale_order->id;
            //    //$Order[$i]['seq'] = $i + 1;

            //    $sale_order_line[$j]['unit_price'] = floatval($sale_order_line[$j]['unit_price']);


            //    $sale_order_line[$j]['created_at'] = Carbon::now()->toDateTimeString();
            //    $sale_order_line[$j]['updated_at'] = Carbon::now()->toDateTimeString();




            // }
            // //add Item line
            DB::table('sale_order')->insert($sale_orders);
            // DB::table('sale_order_line')->insert($sale_order_line);

            DB::commit();

            return $this->returnSuccess('Successful operation', $Checkout);
        } catch (\Throwable $e) {

            DB::rollback();

            return $this->returnErrorData('Something went wrong Please try again ' . $e, 404);
        }
    }
}
