<?php

namespace App\Http\Controllers;

use App\Models\Doc;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class DocController extends Controller
{
    public function DocPage(Request $request)
    {

        $columns = $request->columns;
        $length = $request->length;
        $order = $request->order;
        $search = $request->search;
        $start = $request->start;
        $page = $start / $length + 1;

        $col = array('id', 'name', 'format', 'gen', 'controll_number', 'status', 'create_by', 'update_by', 'created_at', 'updated_at');

        $d = Doc::select($col)
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
       

        $loginBy = $request->login_by;

       // dd($loginBy);
        if (!isset($request->name)) {
            return $this->returnErrorData('[name] Data Not Found', 404);
        } else if (!isset($loginBy)) {
            return $this->returnErrorData('[login_by] Data Not Found', 404);
        }

        $prefix = $request->prefix;
        $date = $request->date;
        $run_number = $request->run_number;

        //fomat
        $format = $prefix . $date . $run_number;

        $name = $request->name;

        $checkName = Doc::where(function ($query) use ($request, $format) {
            $query->orwhere('name', $request->name)
                ->orWhere('format', $format);
        })
            ->first();

        if ($checkName) {
            return $this->returnErrorData('There is already this name in the system', 404);

        } else {

            DB::beginTransaction();

            try {

                $Doc = new Doc();
                $Doc->name = $name;
                $Doc->format = $format;
               
                $Doc->gen = $format;
                $Doc->controll_number = $request->controll_number;
                $Doc->status = 1;

                $Doc->create_by = $loginBy->user_id;

                $Doc->prefix = $prefix;
                $Doc->date = $date;
                $Doc->run_number = $run_number;

                $Doc->save();

                //user appove
                $userAppove = $request->user_appove;

                
                if (!empty($userAppove)) {
                    $Doc->users()->sync($userAppove); //add m to m
                }

                //log
                $userId = $loginBy->user_id;
                $type = 'Add Doc';
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
     * Display the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function show($id)
    {
        $Doc = Doc::with('users')->find($id);
        return $this->returnSuccess('Successful', $Doc);
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

        $loginBy = $request->login_by;

        if (!isset($loginBy)) {
            return $this->returnErrorData('[login_by] Data Not Found', 404);
        }

        $name = $request->name;

        
        DB::beginTransaction();

        try {

            $Doc = Doc::find($id);

            $Doc->name = $name;
            // $Doc->format = $format;
            // $Doc->gen = $request->gen;
            // $Doc->controll_number = $request->controll_number;
            $Doc->status = $request->status;

            $Doc->update_by = $loginBy->user_id;
            $Doc->updated_at = Carbon::now()->toDateTimeString();

            // $Doc->prefix = $prefix;
            // $Doc->date = $date;
            // $Doc->run_number = $run_number;

            $Doc->save();

            //user appove
            $userAppove = $request->user_appove;

            if (!empty($userAppove)) {
                $Doc->users()->sync($userAppove); //add m to m
            }

            //log
            $userId = $loginBy->user_id;
            $type = 'Edit Doc';
            $description = 'User ' . $userId . ' has ' . $type . ' ' . $Doc->name;
            $this->Log($userId, $description, $type);
            //

            DB::commit();

            return $this->returnUpdate('Successful operation');

        } catch (\Throwable $e) {

            DB::rollback();

            return $this->returnErrorData('Something went wrong Please try again ' . $e, 404);
        }
        // }
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

            $Doc = Doc::find($id);

            //log
            $userId = $loginBy->user_id;
            $type = 'Delete Doc';
            $description = 'User ' . $userId . ' has ' . $type . ' ' . $Doc->name;
            $this->Log($userId, $description, $type);
            //

            $Doc->delete();

            DB::commit();

            return $this->returnUpdate('Successful operation');

        } catch (\Throwable $e) {

            DB::rollback();

            return $this->returnErrorData('Something went wrong Please try again ' . $e, 404);
        }
    }

}
