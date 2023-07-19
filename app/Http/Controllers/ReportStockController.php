<?php

namespace App\Http\Controllers;

use App\Models\Doc;
use App\Models\Item;
use App\Models\Item_line;
use App\Models\Item_lot;
use App\Models\Item_trans;
use App\Models\Lot_trans;
use App\Models\Report_stock;
use App\Models\Sale_order;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ReportStockController extends Controller
{


    /**
     * Display the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function show($id)
    {
        $Report_stock = Report_stock::with('user')
            ->with('vendor')
            ->with('user_create')
            ->with(['item_trans' => function ($query) {
                $query->with('item');
                $query->with('item_attribute');
                $query->with('item_attribute_second');
            }])
            ->find($id);

        return $this->returnSuccess('Successful', $Report_stock);
    }




    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function destroy(Request $request, $id)
    {
        $loginBy = $request->login_by;

        if (!isset($loginBy)) {
            return $this->returnErrorData('[login_by] Data Not Found', 404);
        }

        DB::beginTransaction();

        try {

            $Report_stock = Report_stock::find($id);
            $Report_stock->delete();

            DB::commit();

            return $this->returnUpdate('Successful operation');
        } catch (\Throwable $e) {

            DB::rollback();

            return $this->returnErrorData('Something went wrong Please try again ' . $e, 404);
        }
    }

    public function ReportStockPage(Request $request)
    {

        $columns = $request->columns;
        $length = $request->length;
        $order = $request->order;
        $search = $request->search;
        $start = $request->start;
        $page = $start / $length + 1;

        $type = $request->type;

        if (!isset($type)) {
            return $this->returnErrorData('[type] Data Not Found', 404);
        }

        $col = array('id', 'user_id', 'vendor_id', 'report_id', 'date', 'po_number', 'type', 'create_by', 'status', 'status_by', 'status_at', 'reason', 'created_at', 'updated_at');

        $d = Report_stock::select($col)
            ->with('user')
            ->with('vendor')
            ->with('user_create')
            ->where('type', $type);


        $d->orderby($col[$order[0]['column']], $order[0]['dir']);
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

    public function DepositItem(Request $request)
    {

        $Deposit = $request->deposit;
        $loginBy = $request->login_by;

        if (empty($Deposit)) {
            return $this->returnErrorData('กรุณาเพิ่มสินค้า', 404);
        } else if (!isset($request->date)) {
            return $this->returnErrorData('กรุณาระบุวันที่', 404);
        } else if (!isset($loginBy)) {
            return $this->returnErrorData('[login_by] Data Not Found', 404);
        }

        DB::beginTransaction();

        try {

            //add report stock
            $report_stock = new Report_stock();
            $report_stock->user_id = $loginBy->id;
            $report_stock->vendor_id = $request->vendor_id;

            $report_stock->report_id = $this->genCodeReportStock($report_stock, 'D', '000','Deposit', $loginBy->id);

            $report_stock->date = $request->date;
            $report_stock->po_number = $request->po_number;

            $report_stock->create_by = $loginBy->user_id;
            $report_stock->status = 'Approved';
            $report_stock->type = 'Deposit';
            $report_stock->save();

            //add Deposit
            for ($i = 0; $i < count($Deposit); $i++) {

                //stock Count
                $stockCount = $this->getStockCount($Deposit[$i]['item_id'], $Deposit[$i]['item_attribute_id'], $Deposit[$i]['item_attribute_second_id']);

                $Deposit[$i]['stock'] = $stockCount;
                $Deposit[$i]['balance'] = $stockCount + $Deposit[$i]['qty'];
                $Deposit[$i]['report_stock_id'] = $report_stock->id; //report id
                $Deposit[$i]['po_number'] = $request->po_number; //inv no
                $Deposit[$i]['vendor_id'] = $request->vendor_id; //vendor_id
                $Deposit[$i]['date'] = $request->date;
                $Deposit[$i]['type'] = 'Deposit';
                $Deposit[$i]['operation'] = 'finish';
                $Deposit[$i]['status'] = 1;
                $Deposit[$i]['create_by'] = $loginBy->user_id;
                $Deposit[$i]['created_at'] = Carbon::now()->toDateTimeString();
                $Deposit[$i]['updated_at'] = Carbon::now()->toDateTimeString();
            }

            DB::table('item_trans')->insert($Deposit);

            DB::commit();

            return $this->returnSuccess('Successful operation', ['report_stock_id' => $report_stock->id]);
        } catch (\Throwable $e) {

            DB::rollback();

            return $this->returnErrorData('Something went wrong Please try again ' . $e->getMessage(), 404);
        }
    }

    public function WithdrawItem(Request $request)
    {
        $loginBy = $request->login_by;
        $Withdraw = $request->withdraw;

        if (empty($Withdraw)) {
            return $this->returnErrorData('[withdraw] Data Not Found', 404);
        } else if (!isset($request->date)) {
            return $this->returnErrorData('[date] Data Not Found', 404);
        } else if (!isset($loginBy)) {
            return $this->returnErrorData('[login_by] Data Not Found', 404);
        }

        DB::beginTransaction();

        try {

            //add report stock
            $report_stock = new Report_stock();

            $report_stock->user_id = $loginBy->id;
            $report_stock->vendor_id = $request->vendor_id;

            $report_stock->report_id = $this->genCodeReportStock($report_stock, 'W', '000','Withdraw', $loginBy->id);

            $report_stock->date = $request->date;

            $report_stock->create_by = $loginBy->user_id;
            $report_stock->status = 'Approved';
            $report_stock->type = 'Withdraw';
            $report_stock->save();

            //add Withdraw
            for ($i = 0; $i < count($Withdraw); $i++) {

                //qty withdraw
                $qty = -$Withdraw[$i]['qty'];;
                //stock Count
                $stockCount = $this->getStockCount($Withdraw[$i]['item_id'], $Withdraw[$i]['item_attribute_id'], $Withdraw[$i]['item_attribute_second_id']);

                if (abs($qty) > $stockCount) {
                    return $this->returnErrorData('Not enough item', 404);
                }

                $Item_trans = new Item_trans();
                $Item_trans->item_id = $Withdraw[$i]['item_id'];
                $Item_trans->qty = $qty;
                $Item_trans->stock = $stockCount;
                $Item_trans->balance = $stockCount - abs($qty);
                $Item_trans->report_stock_id = $report_stock->id; //report id fk
                $Item_trans->date = $request->date;
                $Item_trans->type = 'Withdraw';
                $Item_trans->operation = 'finish';
                $Item_trans->status = 1;
                $Item_trans->create_by = $loginBy->user_id;
                $Item_trans->save();
            }


            DB::commit();

            return $this->returnSuccess('Successful operation', ['report_stock_id' => $report_stock->id]);
        } catch (\Throwable $e) {

            DB::rollback();

            return $this->returnErrorData('Something went wrong Please try again ' . $e, 404);
        }
    }

    // public function AppoveReportStock(Request $request, $id)
    // {
    //     $loginBy = $request->login_by;

    //     if (!isset($id)) {
    //         return $this->returnErrorData('[id] Data Not Found', 404);
    //     } else if (!isset($request->status)) {
    //         return $this->returnErrorData('[status] Data Not Found', 404);
    //     } else if (!isset($loginBy)) {
    //         return $this->returnErrorData('[login_by] Data Not Found', 404);
    //     }

    //     DB::beginTransaction();

    //     try {


    //         $Report_stock = Report_stock::find($id);

    //         $Report_stock->status = $request->status;

    //         $Report_stock->status_by = $loginBy->user_id;
    //         $Report_stock->status_at = Carbon::now()->toDateTimeString();

    //         if ($request->status == 'Reject') {
    //             $Report_stock->reason = $request->reason;
    //         }

    //         $Report_stock->updated_at = Carbon::now()->toDateTimeString();
    //         $Report_stock->save();

    //         if ($request->status == 'Approved') {

    //             $ItemTrans = Item_trans::where('report_stock_id', $Report_stock->id)->get();
    //             for ($i = 0; $i < count($ItemTrans); $i++) {

    //                 $ItemTrans[$i]->status = 1;
    //                 $ItemTrans[$i]->operation = 'finish';
    //                 $ItemTrans[$i]->save();
    //             }
    //         }

    //         //log
    //         $userId = $loginBy->user_id;
    //         $type = 'Appove Report Stock (' . $Report_stock->type . ')';
    //         $description = 'User ' . $userId . ' has ' . $type . ' number ' . $Report_stock->report_id;
    //         $this->Log($userId, $description, $type);
    //         //

    //         DB::commit();

    //         return $this->returnSuccess('Successful operation', ['report_stock_id' => $Report_stock->id]);
    //     } catch (\Throwable $e) {

    //         DB::rollback();

    //         return $this->returnErrorData('Something went wrong Please try again ' . $e, 404);
    //     }
    // }

    // public function ReportStockFgPDF($ReportId)
    // {

    //     //config
    //     $config = Config::first();

    //     $ReportStockFG = Report_stock_fg::find($ReportId);

    //     if ($ReportStockFG) {

    //         $ReportStockFG->date = date('d-M-Y', strtotime($ReportStockFG->date)); //date

    //         $ItemTrans = Item_trans::with('item')->where('report_stock_fg_id', $ReportStockFG->id)->get();

    //         $item = Item::get();

    //         if ($item->isNotEmpty()) {

    //             $totalItemStock = 0;
    //             $totalQtyTrans = 0;
    //             $totalItemExc = 0;
    //             $totalItemadjQA = 0;

    //             $totalItemBalance = 0;

    //             for ($i = 0; $i < count($item); $i++) {

    //                 $item[$i]->No = $i + 1;

    //                 $item[$i]->stock = $item[$i]->qty;
    //                 $item[$i]->qty_trans = 0;
    //                 $item[$i]->exc = 0;
    //                 $item[$i]->adj_qa = 0;
    //                 $item[$i]->balance = $item[$i]->qty;
    //                 $item[$i]->remark = '';
    //                 $item[$i]->type = '';

    //                 for ($j = 0; $j < count($ItemTrans); $j++) {

    //                     $ItemTrans[$j]->No = $i + 1;

    //                     // dd($ItemTrans[$j]);
    //                     if ($item[$i]->id == $ItemTrans[$j]->item_id) {

    //                         $item[$i]->stock = $ItemTrans[$j]->stock;
    //                         $item[$i]->qty_trans = $ItemTrans[$j]->qty;
    //                         $item[$i]->exc = $ItemTrans[$j]->exc;
    //                         $item[$i]->adj_qa = $ItemTrans[$j]->adj_qa;
    //                         $item[$i]->balance = $ItemTrans[$j]->balance;
    //                         $item[$i]->remark = $ItemTrans[$j]->remark;
    //                         $item[$i]->type = $ItemTrans[$j]->type;

    //                     }

    //                 }

    //                 //sum total
    //                 $totalItemStock = $totalItemStock + $item[$i]->stock;

    //                 $totalQtyTrans = $totalQtyTrans + $item[$i]->qty_trans;

    //                 $totalItemExc = $totalItemExc + $item[$i]->exc;
    //                 $totalItemadjQA = $totalItemadjQA + $item[$i]->adj_qa;

    //                 $totalItemBalance = $totalItemBalance + $item[$i]->balance;

    //             }

    //         }

    //         $ReportStockFG->item = $item;

    //         //appove by
    //         $userCreate = User::where('user_id', $ReportStockFG->create_by)->first();
    //         $userAppove = User::where('user_id', $ReportStockFG->status_by)->first();

    //         //date appove
    //         $dateCreate = date('d / m / y', strtotime($ReportStockFG->date));
    //         $dateAppove = date('d / m / y', strtotime(explode(' ', $ReportStockFG->status_at)[0]));

    //         //pdf
    //         $mpdf = new \Mpdf\Mpdf([
    //             'mode' => 'utf-8',
    //             'format' => 'A4',
    //             'default_font_size' => 16,
    //             'default_font' => 'sarabun',
    //             'margin_left' => 5,
    //             'margin_right' => 5,
    //             'margin_top' => 25,
    //             'margin_bottom' => 5,
    //             'margin_header' => 5,
    //             'margin_footer' => 5,
    //         ]);

    //         // $stylesheet = file_get_contents('lib/bootstrap-4.3.1-dist/css/bootstrap.css'); // external css
    //         // $mpdf->WriteHTML($stylesheet, 1);

    //         $mpdf->SetTitle('Report Stock Finish Goods');
    //         $mpdf->AddPage('P', '', '', '', '', 10, 10, 10, 10, 5, 5);

    //         $mpdf->WriteHTML('

    //         <htmlpageheader name="MyHeader1">
    //         <div>
    //         <table style="width:100%; line-height: 1.3;">
    //         <tr>
    //             <td style="text-align: right; font-size: 16px;">Page {PAGENO} of {nb}</td>
    //         </tr>
    //          </table>
    //         </div>
    //     </htmlpageheader>

    //         <htmlpagefooter name="MyFooter1">
    //         <table width="100%">
    //             <tr>
    //             <td style="text-align: left; font-size: 16px;">F1-ST-04 แก้ไขครั้งที่ 2</td>
    //             <td  style="text-align: center; font-size: 16px;">ระยะเวลาการจัดเก็บ 2 ปี</td>
    //             <td style="text-align: right; font-size: 16px;">อนุมัติใช้ 1 กันยายน 2558</td>
    //             </tr>
    //         </table>
    //     </htmlpagefooter>

    //     <sethtmlpageheader name="MyHeader1" value="on" show-this-page="1" />
    //     <sethtmlpagefooter name="MyFooter1" value="on" />
    //     ');

    //         $mpdf->WriteHTML('

    //     <table style="width:100%;  font-size: 18px; line-height: 1.3; border-collapse: collapse;">
    //     <tr>
    //     <td style="text-align: center;"><h3>' . $config->name_th . '</h3></td>
    //     </tr>
    //     <tr>
    //     <td style="text-align: center;"><h3><b>STOCK FINISHED GOODS</b></h3></td>
    //     </tr>
    //     </table>
    //     <br/>

    //     <table style="width:100%;  font-size: 16px; line-height: 1.3; border-collapse: collapse;">
    //     <tr>
    //     <td style="text-align: left; font-size: 16px;">TYPE ............................</td>
    //     </tr>
    //     <tr>

    //     </tr>
    //     </table>

    //     <table style="width:100%;  font-size: 14px; line-height: 1.3; border: 0.5px solid black; border-collapse: collapse;">
    //     <tr style="border: 0.5px solid black;">
    //     <th style="text-align: center; border: 0.5px solid black; width:5%;">ITEM</th>
    //     <th style="text-align: center; border: 0.5px solid black; width:10%;" rowspan="2">PART DRAWING NO</th>
    //     <th style="text-align: center; border: 0.5px solid black;" rowspan="2">PART NAME</th>
    //     <th style="text-align: center; border: 0.5px solid black;">STOCK</th>
    //     <th style="text-align: center; border: 0.5px solid black;">IN-PUT</th>
    //     <th style="text-align: center; border: 0.5px solid black;">OUT-PUT</th>
    //     <th style="text-align: center; border: 0.5px solid black;">EXC</th>
    //     <th style="text-align: center; border: 0.5px solid black;">ADJ/QA</th>
    //     <th style="text-align: center; border: 0.5px solid black;">BALANCE</th>
    //     <th style="text-align: center; border: 0.5px solid black; width:9%;" rowspan="2"></th>
    //     </tr>
    //     <tr style="border: 0.5px solid black;">
    //     <th style="text-align: center; border: 0.5px solid black;">DATE</th>
    //     <th style="text-align: center; border: 0.5px solid black;">' . $ReportStockFG->date . '</th>
    //     <th style="text-align: center; border: 0.5px solid black;">' . $ReportStockFG->date . '</th>
    //     <th style="text-align: center; border: 0.5px solid black;">' . $ReportStockFG->date . '</th>
    //     <th style="text-align: center; border: 0.5px solid black;">' . $ReportStockFG->date . '</th>
    //     <th style="text-align: center; border: 0.5px solid black;">' . $ReportStockFG->date . '</th>
    //     <th style="text-align: center; border: 0.5px solid black;">' . $ReportStockFG->date . '</th>
    //     </tr>
    //     ');

    //         for ($i = 0; $i < count($ReportStockFG->item); $i++) {

    //             if ($ReportStockFG->item[$i]->stock == 0) {
    //                 $ReportStockFG->item[$i]->stock = '-';
    //             } else {
    //                 $ReportStockFG->item[$i]->stock = number_format($ReportStockFG->item[$i]->stock);
    //             }

    //             if ($ReportStockFG->item[$i]->qty_trans == 0) {
    //                 $ReportStockFG->item[$i]->qty_trans = '';
    //             }

    //             if ($ReportStockFG->item[$i]->exc == 0) {
    //                 $ReportStockFG->item[$i]->exc = '';
    //             } else {
    //                 $ReportStockFG->item[$i]->exc = number_format($ReportStockFG->item[$i]->exc);
    //             }

    //             if ($ReportStockFG->item[$i]->adj_qa == 0) {
    //                 $ReportStockFG->item[$i]->adj_qa = '';
    //             } else {
    //                 $ReportStockFG->item[$i]->adj_qa = number_format($ReportStockFG->item[$i]->adj_qa);
    //             }

    //             if ($ReportStockFG->item[$i]->balance == 0) {
    //                 $ReportStockFG->item[$i]->balance = '-';
    //             } else {
    //                 $ReportStockFG->item[$i]->balance = number_format($ReportStockFG->item[$i]->balance);
    //             }

    //             if ($ReportStockFG->item[$i]->type == 'Deposit') {
    //                 $input = number_format($ReportStockFG->item[$i]->qty_trans);
    //             } else {
    //                 $input = '';
    //             }

    //             if ($ReportStockFG->item[$i]->type == 'Withdraw') {
    //                 $output = number_format($ReportStockFG->item[$i]->qty_trans);
    //             } else {
    //                 $output = '';
    //             }

    //             $mpdf->WriteHTML('
    //     <tr style="border: 0.5px solid black;">
    //     <td style="text-align: center; border: 0.5px solid black; width:5%;">' . $ReportStockFG->item[$i]->No . '</td>
    //     <td style="text-align: center; border: 0.5px solid black; width:10%;">' . $ReportStockFG->item[$i]->item_id . '</td>
    //     <td style="text-align: center; border: 0.5px solid black;">' . $ReportStockFG->item[$i]->name . '</td>
    //     <td style="text-align: right; border: 0.5px solid black;">' . $ReportStockFG->item[$i]->stock . '</td>
    //     <td style="text-align: right; border: 0.5px solid black;">' . $input . '</td>
    //     <td style="text-align: right; border: 0.5px solid black;">' . $output . '</td>
    //     <td style="text-align: right; border: 0.5px solid black;">' . $ReportStockFG->item[$i]->exc . '</td>
    //     <td style="text-align: right; border: 0.5px solid black;">' . $ReportStockFG->item[$i]->adj_qa . '</td>
    //     <td style="text-align: right; border: 0.5px solid black;">' . $ReportStockFG->item[$i]->balance . '</td>
    //     <td style="text-align: left; border: 0.5px solid black;">' . $ReportStockFG->item[$i]->remark . '</td>
    //     </tr>
    //     ');

    //         }

    //         $mpdf->WriteHTML('</table>');

    //         if ($totalItemStock == 0) {
    //             $totalItemStock = '-';
    //         }

    //         if ($totalQtyTrans == 0) {
    //             $totalQtyTrans = '-';
    //         }

    //         if ($totalItemadjQA == 0) {
    //             $totalItemadjQA = '-';
    //         }

    //         if ($totalItemExc == 0) {
    //             $totalItemExc = '-';
    //         }

    //         if ($totalItemBalance == 0) {
    //             $totalItemBalance = '-';
    //         }

    //         if ($ReportStockFG->type == 'Deposit') {
    //             $totalInput = $totalQtyTrans;
    //         } else {
    //             $totalInput = '-';
    //         }

    //         if ($ReportStockFG->type == 'Withdraw') {
    //             $totalOutput = $totalQtyTrans;
    //         } else {
    //             $totalOutput = '-';
    //         }

    //         $mpdf->WriteHTML('
    //         <table style="width:100%;  font-size: 14px; line-height: 1.3; border: 0.5px solid black; border-collapse: collapse;">
    //         <tr style="border: 0.5px solid black;">
    //         <td style="text-align: center; border: 0.5px solid black; width:43.18%;">Total</td>
    //         <td style="text-align: right; border: 0.5px solid black; width:8%;">' . $totalItemStock . '</td>
    //         <td style="text-align: right; border: 0.5px solid black; width:7.97%;">' . $totalInput . '</td>
    //         <td style="text-align: right; border: 0.5px solid black; width:7.97%">' . $totalOutput . '</td>
    //         <td style="text-align: right; border: 0.5px solid black; width:7.97%">' . $totalItemExc . '</td>
    //         <td style="text-align: right; border: 0.5px solid black; width:7.97%">' . $totalItemadjQA . '</td>
    //         <td style="text-align: right; border: 0.5px solid black; width:7.97%">' . $totalItemBalance . '</td>
    //         <td style="text-align: right; border: 0.5px solid black;"></td>

    //         </tr>
    //         </table>
    //         <br/>

    //         <table width="100%">
    //         <tr>
    //         <td style="text-align: left; font-size: 16px; line-height: 0.1;">
    //         <span>
    //         &nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;
    //         <img style="width:125px; height:60px;" src=' . url($userCreate->signature) . '/>
    //         <br/>
    //         REPORT BY:...............................................................................
    //         </span>
    //         </td>

    //         <td style="text-align: left; font-size: 16px; line-height: 0.1;">
    //         <span>
    //         &nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;
    //         <img style="width:125px; height:60px;" src=' . url($userCreate->signature) . '/>
    //         <br/>
    //         APPOVED BY:...............................................................................
    //         </span>
    //         </td>

    //         </tr>
    //         <tr>
    //         <td style="text-align: left; font-size: 16px;">WAREHOUSE CONTROL</td>
    //         <td style="text-align: left; font-size: 16px;">MANAGER</td>
    //         </tr>
    //         <tr>

    //         <td style="text-align: left; font-size: 16px; line-height: 0.1;">
    //         <span>
    //         &nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;
    //         ' . $dateCreate . '
    //         <br/>
    //         DATE ............................
    //         </span>
    //         </td>

    //         <td style="text-align: left; font-size: 16px; line-height: 0.1;">
    //         <span>
    //         &nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;
    //         ' . $dateAppove . '
    //         <br/>
    //         DATE ............................
    //         </span>
    //         </td>

    //         </tr>
    //     </table>

    //         ');

    //         $mpdf->Output();

    //     } else {
    //         return $this->returnErrorData('Report Not Found ', 404);
    //     }

    // }

    // public function ReportRequirementFgPDF($ReportId)
    // {

    //     //config
    //     $config = Config::first();

    //     $ReportStockFG = Report_stock_fg::find($ReportId);

    //     if ($ReportStockFG) {

    //         $ReportStockFG->date = date('d-M-Y', strtotime($ReportStockFG->date));

    //         $ItemTrans = Item_trans::with('item')->where('report_stock_fg_id', $ReportStockFG->id)->get();

    //         $item = Item::with('size')->with('item_type')->get();

    //         if ($item->isNotEmpty()) {

    //             for ($i = 0; $i < count($item); $i++) {

    //                 $item[$i]->No = $i + 1;

    //                 $item[$i]->qty_trans = 0;
    //                 $item[$i]->exc = 0;
    //                 $item[$i]->remark = '';
    //                 $item[$i]->type = '';

    //                 $item[$i]->box_per_qty = 0;
    //                 $item[$i]->scrap = '-'; //เศษ

    //                 // $item[$i]->adj_qa = 0;
    //                 // $item[$i]->balance = $item[$i]->qty;

    //                 for ($j = 0; $j < count($ItemTrans); $j++) {

    //                     $ItemTrans[$j]->No = $i + 1;

    //                     // dd($ItemTrans[$j]);
    //                     if ($item[$i]->id == $ItemTrans[$j]->item_id) {

    //                         $item[$i]->qty_trans = $ItemTrans[$j]->qty;
    //                         $item[$i]->exc = $ItemTrans[$j]->exc;

    //                         $item[$i]->remark = $ItemTrans[$j]->remark;
    //                         $item[$i]->type = $ItemTrans[$j]->type;

    //                         // $item[$i]->adj_qa = $ItemTrans[$j]->adj_qa;
    //                         // $item[$i]->balance = $ItemTrans[$j]->balance;

    //                     }

    //                 }

    //                 // dd($item[$i]->qty_trans / $item[$i]->qty_per_box);

    //                 if (($item[$i]->qty_trans / $item[$i]->qty_per_box) < 1) {

    //                     $item[$i]->box_per_qty = 0;
    //                 } else {

    //                     $Box = $item[$i]->qty_trans / $item[$i]->qty_per_box;

    //                     if (is_int($Box)) {

    //                         $item[$i]->scrap = '-';

    //                     } else {
    //                         $item[$i]->scrap = $item[$i]->qty_trans - $item[$i]->qty_per_box;
    //                     }

    //                     $item[$i]->box_per_qty = intval($Box);

    //                     // dd($item[$i]->box_per_qty);
    //                     // dd($item[$i]->qty_trans / $item[$i]->qty_per_box);
    //                 }

    //             }

    //         }

    //         $ReportStockFG->item = $item;

    //         //appove by
    //         $userCreate = User::where('user_id', $ReportStockFG->create_by)->first();
    //         $userAppove = User::where('user_id', $ReportStockFG->status_by)->first();

    //         //date appove
    //         $dateCreate = date('d / m / y', strtotime($ReportStockFG->date));
    //         $dateAppove = date('d / m / y', strtotime(explode(' ', $ReportStockFG->status_at)[0]));

    //         //pdf
    //         $mpdf = new \Mpdf\Mpdf([
    //             'mode' => 'utf-8',
    //             'format' => 'A4',
    //             'default_font_size' => 16,
    //             'default_font' => 'sarabun',
    //             'margin_left' => 5,
    //             'margin_right' => 5,
    //             'margin_top' => 25,
    //             'margin_bottom' => 5,
    //             'margin_header' => 5,
    //             'margin_footer' => 5,
    //         ]);

    //         // $stylesheet = file_get_contents('lib/bootstrap-4.3.1-dist/css/bootstrap.css'); // external css
    //         // $mpdf->WriteHTML($stylesheet, 1);

    //         $mpdf->SetTitle('Report Stock Finish Goods');
    //         $mpdf->AddPage('P', '', '', '', '', 10, 10, 10, 10, 5, 5);

    //         $mpdf->WriteHTML('

    //         <htmlpageheader name="MyHeader1">
    //         <div>
    //         <table style="width:100%; line-height: 1.3;">
    //         <tr>
    //             <td style="text-align: right; font-size: 16px;">Page {PAGENO} of {nb}</td>
    //         </tr>
    //          </table>
    //         </div>
    //     </htmlpageheader>

    //         <htmlpagefooter name="MyFooter1">
    //         <table width="100%">
    //             <tr>
    //             <td style="text-align: left; font-size: 16px;">F1-MK-07 แก้ไขครั้งที่ 4</td>
    //             <td  style="text-align: center; font-size: 16px;">ระยะเวลาการจัดเก็บ 1 ปี</td>
    //             <td style="text-align: right; font-size: 16px;">อนุมัติใช้ 1 พฤศจิกายน 2555</td>
    //             </tr>
    //         </table>
    //     </htmlpagefooter>

    //     <sethtmlpageheader name="MyHeader1" value="on" show-this-page="1" />
    //     <sethtmlpagefooter name="MyFooter1" value="on" />
    //     ');

    //         $mpdf->WriteHTML('

    // <table style="width:100%;  font-size: 18px; line-height: 1.3; border-collapse: collapse;">
    // <tr>
    // <td style="text-align: center;"><h3>' . $config->name_en . '</h3></td>
    // </tr>
    // <tr>
    // <td style="text-align: center;"><h3><b>REQUIREMENT FINISHED GOODS</b></h3></td>
    // </tr>
    // </table>
    // <br/>

    // <table style="width:100%;  font-size: 16px; line-height: 1.3; border-collapse: collapse;">
    // <tr>
    // <td style="text-align: left; font-size: 16px;">TYPE ............................</td>
    // </tr>
    // <tr>

    // </tr>
    // </table>

    // <table style="width:100%;  font-size: 12px; line-height: 1.3; border: 0.5px solid black; border-collapse: collapse;">
    // <tr style="border: 0.5px solid black;">
    // <th style="text-align: center; border: 0.5px solid black;  width:5%;" rowspan="2">ITEM</th>
    // <<th style="text-align: center; border: 0.5px solid black;  width:10%;" rowspan="2">PART CODE DAIKIN</th>
    // <th style="text-align: center; border: 0.5px solid black; width:20%;" rowspan="2">PART NAME</th>
    // <th style="text-align: center; border: 0.5px solid black; width:5%;" rowspan="2">QTY</th>
    // <th style="text-align: center; border: 0.5px solid black; width:5%;" rowspan="2">ADD</th>
    // <th style="text-align: center; border: 0.5px solid black; width:4%;" rowspan="2">R/J</th>
    // <th style="text-align: center; border: 0.5px solid black; width:4%;" rowspan="2">EXC</th>
    // <th style="text-align: center; border: 0.5px solid black; width:4%;" rowspan="2">TOTAL</th>
    // <th style="text-align: center; border: 0.5px solid black; width:6%;" rowspan="2">QTY/BOX</th>
    // <th style="text-align: center; border: 0.5px solid black; width:5%;">BOX</th>
    // <th style="text-align: center; border: 0.5px solid black; width:5%;" rowspan="2">SIZE</th>
    // <th style="text-align: center; border: 0.5px solid black; width:5%" rowspan="2">PAD</th>
    // <th style="text-align: center; border: 0.5px solid black; width:6%" rowspan="2">ITEM/NO</th>
    // <th style="text-align: center; border: 0.5px solid black; width:6%;" rowspan="2">เศษ</th>
    // <th style="text-align: center; border: 0.5px solid black; width:5%;" rowspan="2">PAD P5</th>
    // <th style="text-align: center; border: 0.5px solid black; width:5%;" rowspan="2">PAD P6</th>
    // <th style="text-align: center; border: 0.5px solid black; width:5%;" rowspan="2">REMARK</th>
    // </tr>
    // <tr style="border: 0.5px solid black;">

    // <th style="text-align: center; border: 0.5px solid black;">QTY</th>
    // </tr>
    // ');

    //         for ($i = 0; $i < count($ReportStockFG->item); $i++) {

    //             // if ($ReportStockFG->item[$i]->stock == 0) {
    //             //     $ReportStockFG->item[$i]->stock = '-';
    //             // }

    //             // if ($ReportStockFG->item[$i]->qty == 0) {
    //             //     $ReportStockFG->item[$i]->qty = '';
    //             // }

    //             // if ($ReportStockFG->item[$i]->exc == 0) {
    //             //     $ReportStockFG->item[$i]->exc = '';
    //             // }

    //             // if ($ReportStockFG->item[$i]->adj_qa == 0) {
    //             //     $ReportStockFG->item[$i]->adj_qa = '';
    //             // }

    //             // if ($ReportStockFG->item[$i]->balance == 0) {
    //             //     $ReportStockFG->item[$i]->balance = '-';
    //             // }

    //             if ($ReportStockFG->item[$i]->type == 'Withdraw') {

    //                 $output = number_format($ReportStockFG->item[$i]->qty_trans);
    //             } else {
    //                 $output = '';
    //             }

    //             $mpdf->WriteHTML('
    // <tr style="border: 0.5px solid black;">
    // <td style="text-align: center; border: 0.5px solid black; width:5%;">' . $ReportStockFG->item[$i]->No . '</td>
    // <td style="text-align: center; border: 0.5px solid black;">' . $ReportStockFG->item[$i]->item_id . '</td>
    // <td style="text-align: center; border: 0.5px solid black;">' . $ReportStockFG->item[$i]->name . '</td>
    // <td style="text-align: center; border: 0.5px solid black;">' . $output . '</td>

    // <td style="text-align: center; border: 0.5px solid black;"></td>
    // <td style="text-align: center; border: 0.5px solid black;"></td>
    // <td style="text-align: center; border: 0.5px solid black;"></td>
    // <td style="text-align: center; border: 0.5px solid black;"></td>
    // <td style="text-align: center; border: 0.5px solid black;">' . $ReportStockFG->item[$i]->qty_per_box . '</td>

    // <td style="text-align: center; border: 0.5px solid black;">' . $ReportStockFG->item[$i]->box_per_qty . '</td>
    // <td style="text-align: center; border: 0.5px solid black;">' . $ReportStockFG->item[$i]->size->name . '</td>

    // <td style="text-align: center; border: 0.5px solid black;"></td>
    // <td style="text-align: center; border: 0.5px solid black;"></td>
    // <td style="text-align: center; border: 0.5px solid black;">' . $ReportStockFG->item[$i]->scrap . '</td>

    // <td style="text-align: center; border: 0.5px solid black;"></td>
    // <td style="text-align: center; border: 0.5px solid black;"></td>
    // <td style="text-align: left; border: 0.5px solid black;">' . $ReportStockFG->item[$i]->remark . '</td>

    // </tr>

    //  ');

    //         }

    //         $mpdf->WriteHTML('</table>');

    //         $mpdf->WriteHTML('
    //         <p style="text-align: left; font-size: 16px;">&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;REMARKS:</p>
    //         <table width="100%">
    //         <tr>
    //         <td style="text-align: center; font-size: 16px; line-height: 0.1; width:25%;">
    //         <span>
    //         &nbsp;&nbsp;&nbsp;&nbsp;&nbsp;
    //         <img style="width:125px; height:60px;" src=' . url($userCreate->signature) . '/>
    //         <br/>
    //        .................................................................
    //         </span>
    //         </td>

    //         <td style="text-align: center; font-size: 16px; line-height: 0.1; width:30%;"></td>

    //         <td style="text-align: center; font-size: 16px; line-height: 0.1;">
    //         <span>
    //         &nbsp;&nbsp;&nbsp;&nbsp;&nbsp;
    //         <img style="width:125px; height:60px;" src=' . url($userCreate->signature) . '/>
    //         <br/>
    //         ................................................................................................................
    //         </span>
    //         </td>
    //         </tr>
    //         <tr>
    //         <td style="text-align: center; font-size: 16px;">REQUEST BY MAKETING</td>
    //         <td style="text-align: center; font-size: 16px; line-height: 0.1; width:30%;"></td>

    //         <td style="text-align: center; font-size: 16px;">APPOVED BY MANAGER</td>
    //         </tr>

    //         <tr>
    //         <td style="text-align: center; font-size: 16px; line-height: 0.1;">
    //         <span>
    //         &nbsp;&nbsp;&nbsp;&nbsp;&nbsp;
    //         <img style="width:125px; height:60px;" src=' . url($userCreate->signature) . '/>
    //         <br/>
    //        .................................................................
    //         </span>
    //         </td>
    //         </tr>
    //         <tr>
    //         <td style="text-align: center; font-size: 16px;">PREPARE BY WAREHOUSE</td>
    //         </tr>
    //     </table>
    //         ');

    //         $mpdf->Output();

    //     } else {
    //         return $this->returnErrorData('Report Not Found ', 404);
    //     }

    // }

    // public function QCItem($dateFinish, $request, $depositFG, $depositOther, $Job)
    // {
    //     $loginBy = $request->login_by;

    //     if (!isset($dateFinish)) {
    //         return $this->returnErrorData('[date] Data Not Found', 404);
    //     } else if (!isset($loginBy)) {
    //         return $this->returnErrorData('[login_by] Data Not Found', 404);
    //     }

    //     DB::beginTransaction();

    //     try {

    //         //check item fg
    //         if (!empty($depositFG)) {

    //             //add report stock
    //             $report_stock = new Report_stock();
    //             $report_stock->report_id = $this->getLastNumber(1);
    //             $report_stock->date = $dateFinish;
    //             $report_stock->create_by = $loginBy->user_id;
    //             $report_stock->status = 'Open';
    //             $report_stock->type = 'QC';

    //             $report_stock->doc_id = 1;
    //             $report_stock->save();
    //             $report_stock->doc;

    //             //run doc
    //             $this->setRunDoc(1, $report_stock->report_id);
    //             //

    //             //add Deposit
    //             $newDeposit = [];
    //             for ($i = 0; $i < count($depositFG); $i++) {

    //                 $newDeposit[$i]['item_id'] = $depositFG[$i]['item_id'];
    //                 $newDeposit[$i]['qty'] = $depositFG[$i]['qty'];
    //                 $newDeposit[$i]['unit_convertion_id'] = $depositFG[$i]['unit_convertion_id'];
    //                 $newDeposit[$i]['location_1_id'] = $depositFG[$i]['location_1_id'];

    //                 //stock Count
    //                 $stockCount = $this->getStockCount($depositFG[$i]['item_id'], [$depositFG[$i]['location_1_id']]);

    //                 $newDeposit[$i]['stock'] = $stockCount;
    //                 $newDeposit[$i]['balance'] = $stockCount + $depositFG[$i]['qty'];

    //                 $newDeposit[$i]['report_stock_id'] = $report_stock->id; //report id

    //                 $newDeposit[$i]['job_id'] = $Job->id; //job id

    //                 $newDeposit[$i]['date'] = $dateFinish;
    //                 $newDeposit[$i]['type'] = 'Deposit';
    //                 $newDeposit[$i]['create_by'] = $loginBy->user_id;
    //                 $newDeposit[$i]['created_at'] = Carbon::now()->toDateTimeString();
    //                 $newDeposit[$i]['updated_at'] = Carbon::now()->toDateTimeString();

    //             }

    //             DB::table('item_trans')->insert($newDeposit);

    //         }

    //         //check item orther
    //         if (!empty($depositOther)) {

    //             //add report stock
    //             $report_stock = new Report_stock();
    //             $report_stock->report_id = $this->getLastNumber(1);
    //             $report_stock->date = $dateFinish;
    //             $report_stock->create_by = $loginBy->user_id;
    //             $report_stock->status = 'Open';
    //             $report_stock->type = 'Deposit';

    //             $report_stock->doc_id = 1;
    //             $report_stock->save();
    //             $report_stock->doc;

    //             //run doc
    //             $this->setRunDoc(1, $report_stock->report_id);
    //             //

    //             //add Deposit
    //             $newDepositOther = [];
    //             for ($i = 0; $i < count($depositOther); $i++) {

    //                 $newDepositOther[$i]['item_id'] = $depositOther[$i]['item_id'];
    //                 $newDepositOther[$i]['qty'] = $depositOther[$i]['qty'];
    //                 $newDepositOther[$i]['unit_convertion_id'] = $depositOther[$i]['unit_convertion_id'];
    //                 $newDepositOther[$i]['location_1_id'] = $depositOther[$i]['location_1_id'];

    //                 //stock Count
    //                 $stockCount = $this->getStockCount($depositOther[$i]['item_id'], [$depositOther[$i]['location_1_id']]);

    //                 $newDepositOther[$i]['stock'] = $stockCount;
    //                 $newDepositOther[$i]['balance'] = $stockCount + $depositOther[$i]['qty'];

    //                 $newDepositOther[$i]['report_stock_id'] = $report_stock->id; //report id

    //                 $newDepositOther[$i]['job_id'] = $Job->id; //job id

    //                 $newDepositOther[$i]['date'] = $dateFinish;
    //                 $newDepositOther[$i]['type'] = 'Deposit';
    //                 $newDepositOther[$i]['create_by'] = $loginBy->user_id;
    //                 $newDepositOther[$i]['created_at'] = Carbon::now()->toDateTimeString();
    //                 $newDepositOther[$i]['updated_at'] = Carbon::now()->toDateTimeString();

    //             }

    //             DB::table('item_trans')->insert($newDepositOther);

    //         }

    //         // //send mail appove
    //         // $user_appove = Doc::with('users')->where('id', $report_stock->doc_id)->first();

    //         // $text = 'There is a request to approve the receipt of the item to the stock, can be viewed at ';
    //         // $title = 'Report Stock Finish Goods (Deposit)';
    //         // $type = 'Appove Deposit Item';

    //         // for ($j = 0; $j < count($user_appove->users); $j++) {

    //         //     $this->sendMail($user_appove->users[$j]->email, $text, $title, $type);

    //         // }

    //         DB::commit();
    //         return ['status' => true, 'msg' => ''];

    //     } catch (\Throwable $e) {

    //         DB::rollback();
    //         return ['status' => false, 'msg' => $e->getmessage()];

    //     }

    // }
}
