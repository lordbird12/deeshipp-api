<?php

namespace App\Http\Controllers;

use App\Models\Delivered_by;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class Delivered_byController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
   
    public function show($id)
    {

        if (!isset($id)) {
            return $this->returnErrorData('[id] Data Not Found', 404);
        }

        $Channel = Delivered_by:: 

           // ->with('location')

            find($id);

        return $this->returnSuccess('Successful', $Channel);
    

    }

     
    public function deliveryPage(Request $request)
    {

        $columns = $request->columns;
        $length = $request->length;
        $order = $request->order;
        $search = $request->search;
        $start = $request->start;
        $page = $start / $length + 1;

        $col = array('id', 'image', 'name', 'status', 'create_by', 'update_by', 'created_at', 'updated_at');

        $d = Delivered_by::select($col)->with('user_create')
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

   
   
     public function updateDeliver (Request $request)
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
            $Delivered_by = Delivered_by::find($id);

            

            $Delivered_by->name = $request->name;



            if ($request->image && $request->image != null && $request->image != 'null') {
                $Delivered_by->image = $this->uploadImage($request->image, '/images/Delivered_by/');
            }
            
            $Delivered_by->update_by = $loginBy->user_id;
            $Delivered_by->updated_at = Carbon::now()->toDateTimeString();
            $Delivered_by->save();

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

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {
        //
    }

    public function getDeliveredBy()
    {
       
        $Delivered_by = Delivered_by::where('status', 1)->get()->toarray();

        if (!empty($Delivered_by)) {

            for ($i = 0; $i < count($Delivered_by); $i++) {
                $Delivered_by[$i]['No'] = $i + 1;

            }
        }

        return $this->returnSuccess('Successful', $Delivered_by);
    }
    public function store(Request $request)
    {

        
        $loginBy = $request->login_by;


        if (!isset($request->name)) {
            return $this->returnErrorData('กรุณาใส่ชื่อบริษัทจัดส่ง', 404);
        }
        else if (!isset($request->image)) {
            return $this->returnErrorData('กรุณาเพิ่มรูป', 404);
        }
         else if (!isset($loginBy)) {
            return $this->returnErrorData('[login_by] Data Not Found', 404);
        }

        $name = $request->name;

        $checkName = Delivered_by::where('name', $name)->first();

        if ($checkName) {
            return $this->returnErrorData('There is already this name in the system', 404);

        } else {

            DB::beginTransaction();

            try {

                $Delivered_by = new Delivered_by();
                $Delivered_by->name = $name;
                $Delivered_by->image = $this->uploadImage($request->image, '/images/Delivered_by/');
                $Delivered_by->status = 1;

                $Delivered_by->create_by = $loginBy->user_id;
                $Delivered_by->save();

                //log
                $userId = $loginBy->user_id;
                $type = 'Add Delivered_by';
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

  

    /**
     * Show the form for editing the specified resource.
     *
     * @param  \App\Models\Delivered_by  $delivered_by
     * @return \Illuminate\Http\Response
     */
    public function edit(Delivered_by $delivered_by)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \App\Models\Delivered_by  $delivered_by
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, Delivered_by $delivered_by)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  \App\Models\Delivered_by  $delivered_by
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

            $Branch = Delivered_by::find($id);

            //log
            $userId = $loginBy->user_id;
            $type = 'Delete Delivered_by';
            $description = 'User ' . $userId . ' has ' . $type . ' ' . $Branch->name;
            $this->Log($userId, $description, $type);
            //

            $Branch->delete();

            DB::commit();

            return $this->returnUpdate('Successful operation');
        } catch (\Throwable $e) {

            DB::rollback();

            return $this->returnErrorData('Something went wrong Please try again ' . $e, 404);
        }
    }
}
