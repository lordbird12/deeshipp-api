<?php

namespace App\Http\Controllers;

use App\Models\Menu;
use App\Models\Permission;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class MenuController extends Controller
{

    public function index()
    {
        $Menu = Menu::with('permission')->get();

        if ($Menu->isNotEmpty()) {

            for ($i = 0; $i < count($Menu); $i++) {
                $Menu[$i]->No = $i + 1;
            }
        }

        return $this->returnSuccess('Successful', $Menu);

    }

    public function store(Request $request)
    {
        $loginBy = $request->login_by;

        if (!isset($request->permission_id)) {
            return $this->returnErrorData('[permission_id] Data Not Found', 404);
        } else if (!isset($request->name)) {
            return $this->returnErrorData('[name] Data Not Found', 404);
        } else if (!isset($loginBy)) {
            return $this->returnErrorData('[login_by] Data Not Found', 404);
        }

        $name = $request->name;

        $Name = [];

        for ($i = 0; $i < count($name); $i++) {
            $Name[$i]['name'] = $name[$i];
        }

        DB::beginTransaction();

        try {

            $Permission = Permission::find($request->permission_id);

            if ($Permission->menus->isEmpty()) {
                //add one to many
                $Permission->menus()->createMany($Name);

            } else {

                //delete
                $Permission->menus()->where('permission_id', $request->permission_id)->delete();

                //add one to many
                $Permission->menus()->createMany($Name);
            }

            //log
            $useId = $loginBy->user_id;
            $log_type = 'Setting Menu Permission';
            $log_description = 'User ' . $useId . ' has ' . $log_type . ' ' . $Permission->name;
            $this->Log($useId, $log_description, $log_type);
            //

            DB::commit();

            return $this->returnSuccess('Successful operation', []);

        } catch (\Throwable $e) {

            DB::rollback();

            return $this->returnErrorData('Something went wrong Please try again', 404);
        }

    }

}
