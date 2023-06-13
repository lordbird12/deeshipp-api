<?php

namespace App\Http\Controllers;

use App\Models\Config_stock;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ConfigStockController extends Controller
{
    public function index()
    {
        $Config_stock = Config_stock::first();

        return $this->returnSuccess('Successful', $Config_stock);
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

        if (!isset($loginBy)) {
            return $this->returnErrorData('[login_by] Data Not Found', 404);
        }

        DB::beginTransaction();

        try {

            $checkConfig = Config_stock::first();

            if (!$checkConfig) {

                //add config
                $Config_stock = new Config_stock();

                $Config_stock->stock_dead = $request->stock_dead;
                $Config_stock->stock_slow = $request->stock_slow;

                $Config_stock->create_by = $loginBy->user_id;

                $Config_stock->save();

            } else {

                //update config
                $checkConfig->stock_dead = $request->stock_dead;
                $checkConfig->stock_slow = $request->stock_slow;

                $checkConfig->update_by = $loginBy->user_id;

                $checkConfig->save();

            }

            //log
            $userId = $loginBy->user_id;
            $type = 'Setting Config Stock';
            $description = 'User ' . $userId . ' has ' . $type;
            $this->Log($userId, $description, $type);
            //

            DB::commit();

            return $this->returnSuccess('Successful operation', []);

        } catch (\Throwable $e) {

            DB::rollback();

            return $this->returnErrorData('Something went wrong Please try again' . $e, 404);
        }
    }

}
