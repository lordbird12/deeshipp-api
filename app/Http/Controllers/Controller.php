<?php

namespace App\Http\Controllers;

use App\Mail\SendMail;
use App\Models\Doc;
use App\Models\Item_line;
use App\Models\Item_trans;
use App\Models\Log;
use App\Models\Log_saleOrder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Foundation\Bus\DispatchesJobs;
use Illuminate\Foundation\Validation\ValidatesRequests;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller as BaseController;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Mail;
use Intervention\Image\Facades\Image;

class Controller extends BaseController
{
    use AuthorizesRequests, DispatchesJobs, ValidatesRequests;





    public function returnSuccess($massage, $data)
    {

        return response()->json([
            'code' => strval(200),
            'status' => true,
            'message' => $massage,
            'data' => $data,
        ], 200);
    }

    public function returnUpdate($massage)
    {
        return response()->json([
            'code' => strval(201),
            'status' => true,
            'message' => $massage,
            'data' => [],
        ], 201);
    }




    public function returnUpdateReturnData($massage, $data)
    {
        return response()->json([
            'code' => strval(201),
            'status' => true,
            'message' => $massage,
            'data' => $data,
        ], 201);
    }

    public function returnErrorData($massage, $code)
    {
        return response()->json([
            'code' => strval($code),
            'status' => false,
            'message' => $massage,
            'data' => [],
        ], 404);
    }

    public function returnError($massage)
    {
        return response()->json([
            'code' => strval(401),
            'status' => false,
            'message' => $massage,
            'data' => [],
        ], 401);
    }

    public function Log($userId, $description, $type)
    {
        $Log = new Log();
        $Log->user_id = $userId;
        $Log->description = $description;
        $Log->type = $type;
        $Log->save();
    }



    public function LogSaleOrder($userId, $description, $type)
    {
        $Log = new Log_saleOrder();
        $Log->user_id = $userId;
        $Log->description = $description;
        $Log->type = $type;
        $Log->save();
    }
    public function sendMail($email, $data, $title, $type)
    {

        $mail = new SendMail($email, $data, $title, $type);
        Mail::to($email)->send($mail);
    }

    public function sendLine($line_token, $text)
    {

        $sToken = $line_token;
        $sMessage = $text;

        $chOne = curl_init();
        curl_setopt($chOne, CURLOPT_URL, "https://notify-api.line.me/api/notify");
        curl_setopt($chOne, CURLOPT_SSL_VERIFYHOST, 0);
        curl_setopt($chOne, CURLOPT_SSL_VERIFYPEER, 0);
        curl_setopt($chOne, CURLOPT_POST, 1);
        curl_setopt($chOne, CURLOPT_POSTFIELDS, "message=" . $sMessage);
        $headers = array('Content-type: application/x-www-form-urlencoded', 'Authorization: Bearer ' . $sToken . '');
        curl_setopt($chOne, CURLOPT_HTTPHEADER, $headers);
        curl_setopt($chOne, CURLOPT_RETURNTRANSFER, 1);
        $result = curl_exec($chOne);

        curl_close($chOne);
    }


    public function uploadImage1(Request $request)
    {
        $image = $request->image;
        $path = $request->path;

        $input['imagename'] = md5(rand(0, 999999) . $image->getClientOriginalName()) . '.' . $image->extension();
        $destinationPath = public_path('/thumbnail');
        if (!File::exists($destinationPath)) {
            File::makeDirectory($destinationPath, 0777, true);
        }

        $img = Image::make($image->path());
        $img->save($destinationPath . '/' . $input['imagename']);
        $destinationPath = public_path($path);
        $image->move($destinationPath, $input['imagename']);


        return $this->returnSuccess('Successful operation', $path . $input['imagename']);
    }



    public function uploadImage($image, $path)
    {

        $input['imagename'] = md5(rand(0, 999999) . $image->getClientOriginalName()) . '.' . $image->extension();
        $destinationPath = public_path('/thumbnail');
        if (!File::exists($destinationPath)) {
            File::makeDirectory($destinationPath, 0777, true);
        }

        $img = Image::make($image->path());
        $img->save($destinationPath . '/' . $input['imagename']);
        $destinationPath = public_path($path);
        $image->move($destinationPath, $input['imagename']);

        return $path . $input['imagename'];
    }

    public function uploadFile($file, $path)
    {
        $input['filename'] = time() . '.' . $file->extension();
        $destinationPath = public_path('/file_thumbnail');
        if (!File::exists($destinationPath)) {
            File::makeDirectory($destinationPath, 0777, true);
        }

        $destinationPath = public_path($path);
        $file->move($destinationPath, $input['filename']);

        return $path . $input['filename'];
    }

    public function uploadFile2(Request $request)
    {

        $file = $request->file;
        $path = $request->path;

        $input['filename'] = time() . '.' . $file->extension();

        $destinationPath = public_path('/file_thumbnail');
        if (!File::exists($destinationPath)) {
            File::makeDirectory($destinationPath, 0777, true);
        }

        $destinationPath = public_path($path);
        $file->move($destinationPath, $input['filename']);

        return $path . $input['filename'];
    }

    public function getDropDownYear()
    {
        $Year = intval(((date('Y')) + 1) + 543);

        $data = [];

        for ($i = 0; $i < 10; $i++) {

            $Year = $Year - 1;
            $data[$i]['year'] = $Year;
        }

        return $this->returnSuccess('เรียกดูข้อมูลสำเร็จ', $data);
    }

    public function getDropDownProvince()
    {

        $province = array("กระบี่", "กรุงเทพมหานคร", "กาญจนบุรี", "กาฬสินธุ์", "กำแพงเพชร", "ขอนแก่น", "จันทบุรี", "ฉะเชิงเทรา", "ชลบุรี", "ชัยนาท", "ชัยภูมิ", "ชุมพร", "เชียงราย", "เชียงใหม่", "ตรัง", "ตราด", "ตาก", "นครนายก", "นครปฐม", "นครพนม", "นครราชสีมา", "นครศรีธรรมราช", "นครสวรรค์", "นนทบุรี", "นราธิวาส", "น่าน", "บุรีรัมย์", "บึงกาฬ", "ปทุมธานี", "ประจวบคีรีขันธ์", "ปราจีนบุรี", "ปัตตานี", "พะเยา", "พังงา", "พัทลุง", "พิจิตร", "พิษณุโลก", "เพชรบุรี", "เพชรบูรณ์", "แพร่", "ภูเก็ต", "มหาสารคาม", "มุกดาหาร", "แม่ฮ่องสอน", "ยโสธร", "ยะลา", "ร้อยเอ็ด", "ระนอง", "ระยอง", "ราชบุรี", "ลพบุรี", "ลำปาง", "ลำพูน", "เลย", "ศรีสะเกษ", "สกลนคร", "สงขลา", "สตูล", "สมุทรปราการ", "สมุทรสงคราม", "สมุทรสาคร", "สระแก้ว", "สระบุรี", "สิงห์บุรี", "สุโขทัย", "สุพรรณบุรี", "สุราษฎร์ธานี", "สุรินทร์", "หนองคาย", "หนองบัวลำภู", "อยุธยา", "อ่างทอง", "อำนาจเจริญ", "อุดรธานี", "อุตรดิตถ์", "อุทัยธานี", "อุบลราชธานี");

        $data = [];

        for ($i = 0; $i < count($province); $i++) {

            $data[$i]['province'] = $province[$i];
        }

        return $this->returnSuccess('เรียกดูข้อมูลสำเร็จ', $data);
    }

    public function getDownloadFomatImport($params)
    {

        $file = $params;
        $destinationPath = public_path() . "/fomat_import/";

        return response()->download($destinationPath . $file);
    }

    public function checkDigitMemberId($memberId)
    {

        $sum = 0;
        for ($i = 0; $i < 12; $i++) {

            $sum += (int) ($memberId[$i]) * (13 - $i);
        }

        if ((11 - ($sum % 11)) % 10 == (int) ($memberId[12])) {
            return 'true';
        } else {
            return 'false';
        }
    }

    public function setRunDoc($docId, $lastId)
    {

        $doc = Doc::find($docId);
        $doc->gen = $lastId;

        $doc->save();
    }


    public function getStockCount($item_id, $item_attribute_id, $Item_attribute_second_id)
    {

        $QtyItem = Item_trans::where('item_id', $item_id);

        if ($item_attribute_id) {
            $QtyItem->where('item_attribute_id', $item_attribute_id);
        }

        if ($Item_attribute_second_id) {
            $QtyItem->where('Item_attribute_second_id', $Item_attribute_second_id);
        }


        $qtyItem = $QtyItem->where('status', 1)
            ->where('operation', 'finish')
            ->sum('qty');

        return intval($qtyItem);
    }

    public function getStockCountBalance($item_id, $item_attribute_id, $Item_attribute_second_id)
    {
        $QtyItem = Item_trans::where('item_id', $item_id);

        if ($item_attribute_id) {
            $QtyItem->where('item_attribute_id', $item_attribute_id);
        }

        if ($Item_attribute_second_id) {
            $QtyItem->where('Item_attribute_second_id', $Item_attribute_second_id);
        }


        $qtyItem = $QtyItem->where('status', 1)
            ->sum('qty');

        return intval($qtyItem);
    }

    public function getStockCountBooking($item_id, $item_attribute_id, $Item_attribute_second_id)
    {
        $QtyItem = Item_trans::where('item_id', $item_id);

        if ($item_attribute_id) {
            $QtyItem->where('item_attribute_id', $item_attribute_id);
        }

        if ($Item_attribute_second_id) {
            $QtyItem->where('Item_attribute_second_id', $Item_attribute_second_id);
        }


        $qtyItem = $QtyItem->where('status', 1)
            ->where('operation', 'booking')
            ->sum('qty');

        return intval($qtyItem);
    }


    public function getItemCount($item_id, $main_item_id)
    {

        $QtyItem = Item_line::where('item_id', $item_id);


        if (!empty($main_item_id)) {
            $QtyItem->whereIn('main_item_id', $main_item_id);
        }
        $qtyItem = $QtyItem
            ->sum('qty');

        // dd($qtyItem);
        return intval($qtyItem);
    }

    public function genCodeReportStock(Model $model, $prefix, $number, $type, $user_id)
    {

        $countPrefix = strlen($prefix);
        $countRunNumber = strlen($number);

        //get last code
        $m = $model::where('user_id', $user_id)
            ->where('type', $type)
            ->orderby('report_id', 'desc')
            ->first();
        if ($m) {
            $lastCode = $m->report_id;
        } else {
            $lastCode = $prefix . $number;
        }


        $codelast = substr($lastCode, -$countRunNumber);

        $newNumber = intval($codelast) + 1;
        $Number = sprintf('%0' . strval($countRunNumber) . 'd', $newNumber);

        $runNumber = $prefix . date('y') . date('m') . '-' . $Number;

        return $runNumber;
    }

    public function genCodeOrder(Model $model, $prefix, $number, $user_id)
    {

        $countPrefix = strlen($prefix);
        $countRunNumber = strlen($number);

        //get last code
        $m = $model::where('user_id', $user_id)
            ->orderby('order_id', 'desc')
            ->first();
        if ($m) {
            $lastCode = $m->order_id;
        } else {
            $lastCode = $prefix . $number;
        }


        $codelast = substr($lastCode, -$countRunNumber);

        $newNumber = intval($codelast) + 1;
        $Number = sprintf('%0' . strval($countRunNumber) . 'd', $newNumber);

        $runNumber = $prefix . date('y') . date('m') . '-' . $Number;

        return $runNumber;
    }


    public function getLastNumber($docId)
    {
        $doc = Doc::find($docId);
        //dd($doc);
        if ($doc->gen) {

            //prefix
            if ($doc->prefix) {
                $Prefix = $doc->prefix;
            } else {
                $Prefix = '';
            }

            //date
            if ($doc->date) {

                if ($doc->date == 'YY') {
                    $Date = date('y');
                } else if ($doc->date == 'YYMM') {
                    $Date = date('ym');
                } else if ($doc->date == 'YYMMDD') {
                    $Date = date('ymd');
                }
            } else {
                $Date = '';
            }

            //run number
            if ($doc->run_number) {

                $countPrefix = strlen($doc->prefix);
                $countRunNumber = strlen($doc->run_number);

                $lastDate = substr($doc->gen, $countPrefix, -$countRunNumber);

                //check date
                if ($Date > $lastDate) {

                    if ($doc->prefix == 'SO') {
                        $lastNumber = 000;
                    } else {
                        $lastNumber = 00;
                    }

                    $newNumber = intval($lastNumber) + 1;
                    $Number = sprintf('%0' . strval($countRunNumber) . 'd', $newNumber);
                } else {

                    $lastNumber = substr($doc->gen, -$countRunNumber);
                    $newNumber = intval($lastNumber) + 1;
                    $Number = sprintf('%0' . strval($countRunNumber) . 'd', $newNumber);
                }
            } else {
                $Number = null;
            }
        } else {

            //case new gen

            //prefix
            if ($doc->prefix) {
                $Prefix = $doc->prefix;
            } else {
                $Prefix = '';
            }

            //date
            if ($doc->date) {

                if ($doc->date == 'YY') {
                    $Date = date('Y');
                } else if ($doc->date == 'YYMM') {
                    $Date = date('Ym');
                } else if ($doc->date == 'YYMMDD') {
                    $Date = date('Ymd');
                }
            } else {
                $Date = '';
            }

            // dd($date);

            if ($doc->run_number) {
                $runNumber = intval($doc->run_number) + 1;
                $countZero = Strlen($doc->run_number);
                $Number = sprintf('%0' . strval($countZero) . 'd', $runNumber);
            } else {
                $Number = null;
            }
        }

        //format
        $prefix = $Prefix;
        $date = $Date;
        $run_number = $Number;

        //gen
        $gen = $prefix . $date . '-' . $run_number;

        return $gen;
    }
    public function genBarcodeNumber()
    {

        // Specify the desired barcode length
        $barcodeLength = 12;

        // Generate a random barcode number
        $barcodeNumber = '';
        $characters = '0123456789ABCDEFGHIJKLMNOPQRSTUVWXYZ';

        for ($i = 0; $i < $barcodeLength; $i++) {
            $barcodeNumber .= $characters[rand(0, strlen($characters) - 1)];
        }

        return $barcodeNumber;
    }

    public function dateBetween($dateStart, $dateStop)
    {
        $datediff = strtotime($dateStop) - strtotime($this->dateform($dateStart));
        return abs($datediff / (60 * 60 * 24));
    }

    public function dateform($date)
    {
        $d = explode('/', $date);
        return $d[2] . '-' . $d[1] . '-' . $d[0];
    }
    // public function ConfigStock()
    // {
    //     $Config_stock = Config_stock::first();
    //     return $Config_stock;
    // }


    // public function getStockControlByItemType($itemTypeId)
    // {
    //     $Stock_control = Stock_control::select('ua.user_id as approver_id', 'ua.name as approver_name', 'ua.signature as approver_signature'
    //         , 'um.user_id as manager_id', 'um.name as manager_name', 'um.signature as manager_signature')
    //         ->leftjoin('item_type as it', 'it.id', 'stock_control.item_type_id')
    //         ->leftjoin('users as ua', 'ua.id', 'stock_control.approver_id')
    //         ->leftjoin('users as um', 'um.id', 'stock_control.manager_id')
    //         ->where('it.id', $itemTypeId)
    //         ->first();

    //     if ($Stock_control) {

    //         $Stock_control->approver_signature = url($Stock_control->approver_signature);
    //         $Stock_control->manager_signature = url($Stock_control->manager_signature);
    //     }

    //     return $Stock_control;
    // }
    // public function dateBetween($dateStart, $dateStop)
    // {
    //     $datediff = strtotime($dateStop) - strtotime($this->dateform($dateStart));
    //     return abs($datediff / (60 * 60 * 24));
    // }

    // public function log_noti($Title, $Description, $Url, $Pic, $Type)
    // {
    //     $log_noti = new Log_noti();
    //     $log_noti->title = $Title;
    //     $log_noti->description = $Description;
    //     $log_noti->url = $Url;
    //     $log_noti->pic = $Pic;
    //     $log_noti->log_noti_type = $Type;

    //     $log_noti->save();
    // }

}
