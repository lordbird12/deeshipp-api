<?php

namespace App\Http\Controllers;

use App\Models\Channel;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ChannelController extends Controller
{

    public function destroy(Request $request, $id)
    {
        $loginBy = $request->login_by;

        if (!isset($loginBy)) {
            return $this->returnErrorData('[login_by] Data Not Found', 404);
        }

        DB::beginTransaction();

        try {

            $Channel = Channel::find($id);

            //log
            $userId = $loginBy->user_id;
            $type = 'Delete Channel';
            $description = 'User ' . $userId . ' has ' . $type . ' ' . $Channel->name;
            $this->Log($userId, $description, $type);
            //

            $Channel->delete();

            DB::commit();

            return $this->returnUpdate('Successful operation');
        } catch (\Throwable $e) {

            DB::rollback();

            return $this->returnErrorData('Something went wrong Please try again ' . $e, 404);
        }
    }


    public function Channelupdate (Request $request)
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
            $Channel = Channel::find($id);

            

            $Channel->name = $request->name;
            $Channel->status = $request->status;

            if ($request->image && $request->image != null && $request->image != 'null') {
                $Channel->image = $this->uploadImage($request->image, '/images/Delivered_by/');
            }
            
            //$Bank->update_by = $loginBy->user_id;
            $Channel->updated_at = Carbon::now()->toDateTimeString();
            $Channel->save();

            //log
            $userId = $loginBy->user_id;
            $type = 'แก้ไขช่องทางจัดส่ง';
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


    public function ChannelPage(Request $request)
    {

        $columns = $request->columns;
        $length = $request->length;
        $order = $request->order;
        $search = $request->search;
        $start = $request->start;
        $page = $start / $length + 1;

        $col = array('id', 'name', 'image','status','create_by', 'update_by', 'created_at', 'updated_at');

        $d = Channel::select($col)
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

        $Channel = Channel:: 

           // ->with('location')

            find($id);

        return $this->returnSuccess('Successful', $Channel);
    

    }

    public function getChannel()
    {
       
        $Channel = Channel::get()->toarray();

        if (!empty($Channel)) {

            for ($i = 0; $i < count($Channel); $i++) {
                $Channel[$i]['No'] = $i + 1;

            }
        }

        return $this->returnSuccess('Successful', $Channel);
    }
    public function store(Request $request)
    {

        
        $loginBy = $request->login_by;


        if (!isset($request->name)) {
            return $this->returnErrorData('[name] Data Not Found', 404);
        }
        else if (!isset($request->image)) {
            return $this->returnErrorData('[image] ไม่มีข้อมูล', 404);
        }
         else if (!isset($loginBy)) {
            return $this->returnErrorData('[login_by] Data Not Found', 404);
        }

        $name = $request->name;

        $checkName = Channel::where('name', $name)->first();

        if ($checkName) {
            return $this->returnErrorData('There is already this name in the system', 404);

        } else {

            DB::beginTransaction();

            try {

                $Channel = new Channel();
                $Channel->name = $name;
                $Channel->image = $this->uploadImage($request->image, '/images/Delivered_by/');
                $Channel->status = 1;

                $Channel->create_by = $loginBy->user_id;
                $Channel->save();

                //log
                $userId = $loginBy->user_id;
                $type = 'Add Channel';
                $description = 'User ' . $userId . ' has ' . $type . ' ' . $name;
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
