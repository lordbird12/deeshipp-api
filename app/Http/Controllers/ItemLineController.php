<?php

namespace App\Http\Controllers;

use App\Models\Item;
use App\Models\Item_line;
use App\Models\Item_type;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ItemLineController extends Controller
{

    public function ItemLineupdate (Request $request)
    {
       
        $loginBy = $request->login_by;

        if (!isset($request->id)) {
            return $this->returnErrorData('ไม่พบข้อมูล id', 404);
        } else if (!isset($loginBy)) {
            return $this->returnErrorData('ไม่พบข้อมูลเจ้าหน้าที่ กรุณาเข้าสู่ระบบใหม่อีกครั้ง', 404);
        }

        DB::beginTransaction();

        try {

            $id = $request->id;
            $Item_line = Item_line::find($id);

            

            $Item_line->item_id = $request->item_id;

            $Item_line->main_item_id = $request->main_item_id;
            $Item_line->qty = $request->qty;
            $Item_line->price = $request->price;
            $Item_line->total = $request->total;
            
            //$Bank->update_by = $loginBy->user_id;
            $Item_line->updated_at = Carbon::now()->toDateTimeString();
            $Item_line->save();

            //log
            $userId = $loginBy->user_id;
            $type = 'แก้ไขสินค้าจัดเช็ต';
            $description = 'เจ้าหน้าที่ ' . $userId . ' ได้ทำการ ' . $type;
            $this->Log($userId, $description, $type);
            //

            DB::commit();

            return $this->returnUpdate('ดำเนินการสำเร็จ');
        } catch (\Throwable $e) {

            DB::rollback();

            return $this->returnErrorData('เกิดข้อผิดพลาด กรุณาลองใหม่อีกครั้ง '.$e, 404);
        }
    }

    public function show($id)
    {

        if (!isset($id)) {
            return $this->returnErrorData('[id] Data Not Found', 404);
        }

        $Item = Item_line::with('item')

           // ->with('location')

            ->find($id);

        if (!empty($Item)) {

            //qty item
            $Item->qty = $this->getStockCount($Item->id, []);
        }

        return $this->returnSuccess('Successful', $Item);
    }

   

    //
    public function getItem_line()
    {
       
        $Item_line = Item_line::get()->toarray();

        if (!empty($Item_line)) {

            for ($i = 0; $i < count($Item_line); $i++) {
                $Item_line[$i]['No'] = $i + 1;

            }
        }

        return $this->returnSuccess('Successful', $Item_line);
    }

    public function item_line(Request $request)
    {

        $item_line = $request->item_line;
        $loginBy = $request->login_by;

       
        
        if (empty($item_line)) {
            return $this->returnErrorData('[item_line] Data Not Found', 404);
        
        } else if (!isset($loginBy)) {
            return $this->returnErrorData('[login_by] Data Not Found', 404);
        }

        DB::beginTransaction();

        try {
            $itemLine=[];
            //add item_line
            for ($i = 0; $i<  count($item_line); $i++) {

         
                $itemLine[$i]['item_id'] = $item_line[$i]['item_id'];
                $itemLine[$i]['type'] = $item_line[$i]['type'];
                $itemLine[$i]['price'] =$item_line[$i]['price']; //inv no
                $itemLine[$i]['qty'] = $item_line[$i]['qty']; 
               // $itemLine[$i]['create_by'] = $loginBy->user_id;
                $itemLine[$i]['created_at'] = Carbon::now()->toDateTimeString();
                $itemLine[$i]['updated_at'] = Carbon::now()->toDateTimeString();

            }

            DB::table('item_lines')->insert($itemLine);
           

            DB::commit();

            return $this->returnSuccess('Successful operation', []);
        } catch (\Throwable $e) {

            DB::rollback();

            return $this->returnErrorData('Something went wrong Please try again ' . $e->getMessage(), 404);
        }
    }
}
