<?php

namespace App\Http\Controllers;

use App\Models\Bank;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class BankController extends Controller
{


    public function destroy(Request $request, $id)
    {
        $loginBy = $request->login_by;

        if (!isset($loginBy)) {
            return $this->returnErrorData('[login_by] Data Not Found', 404);
        }

        DB::beginTransaction();

        try {

            $Bank = Bank::find($id);

            //log
            $userId = $loginBy->user_id;
            $type = 'Delete Bank';
            $description = 'User ' . $userId . ' has ' . $type . ' ' . $Bank->name;
            $this->Log($userId, $description, $type);
            //

            $Bank->delete();

            DB::commit();

            return $this->returnUpdate('Successful operation');
        } catch (\Throwable $e) {

            DB::rollback();

            return $this->returnErrorData('Something went wrong Please try again ' . $e, 404);
        }
    }


    public function Bankupdate (Request $request)
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
            $Bank = Bank::find($id);

            $Bank->name = $request->name;
            $Bank->status = $request->status;

            if ($request->image && $request->image != null && $request->image != 'null') {
                $Bank->image = $this->uploadImage($request->image, '/images/Delivered_by/');
            }
            //$Bank->update_by = $loginBy->user_id;
            $Bank->updated_at = Carbon::now()->toDateTimeString();
            $Bank->save();

            //log
            $userId = $loginBy->user_id;
            $type = 'แก้ไขธนาคาร';
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


    public function BankPage(Request $request)
    {

        $columns = $request->columns;
        $length = $request->length;
        $order = $request->order;
        $search = $request->search;
        $start = $request->start;
        $page = $start / $length + 1;

        $col = array('id', 'name','image','status','create_by', 'created_at', 'updated_at');

        $d = Bank::select($col)->with('user_create')
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


    public function show($id)
    {

        if (!isset($id)) {
            return $this->returnErrorData('[id] Data Not Found', 404);
        }

        $Bank = Bank::

           // ->with('location')

            find($id);


        return $this->returnSuccess('Successful', $Bank);
    }



    public function getBank()
    {

        $Bank = Bank::get()->toarray();

        if (!empty($Bank)) {

            for ($i = 0; $i < count($Bank); $i++) {
                $Bank[$i]['No'] = $i + 1;

            }
        }

        return $this->returnSuccess('Successful', $Bank);
    }

    public function store(Request $request)
    {


        $loginBy = $request->login_by;


        if (!isset($request->name)) {
            return $this->returnErrorData('กรุณาใส่ชื่อ', 404);
        }
        else if (!isset($request->image)) {
            return $this->returnErrorData('กรุณาใส่รูป', 404);
        }
         else if (!isset($loginBy)) {
            return $this->returnErrorData('[login_by] Data Not Found', 404);
        }

        $name = $request->name;

        $checkName = Bank::where('name', $name)->first();

        if ($checkName) {
            return $this->returnErrorData('มีชื่อธนาคารอยู่แล้ว', 404);

        } else {

            DB::beginTransaction();

            try {

                $Bank = new Bank();
                $Bank->name = $request->name;
                $Bank->image = $this->uploadImage($request->image, '/images/Delivered_by/');

                $Bank->status = 1;

                $Bank->create_by = $loginBy->user_id;
                $Bank->save();

                //log
                $userId = $loginBy->user_id;
                $type = 'Add Bank';
                $description = 'User ' . $userId . ' has ' . $type . ' ' . $Bank;
                $this->Log($userId, $description, $type);
                //

                DB::commit();

                return $this->returnSuccess('Successful operation', []);

            } catch (\Throwable $e) {

                DB::rollback();

                return $this->returnErrorData('Something went wrong Please try again ' . $e, 404);
            }
        }
    }
}
