<?php

namespace App\Http\Controllers;

use App\Models\Sale_page_content;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class SalePageContentController extends Controller

{

    public function show($id)
    {

        if (!isset($id)) {
            return $this->returnErrorData('[id] Data Not Found', 404);
        }

        $Sale_page_content = Sale_page_content:: 

           // ->with('location')

            find($id);
  

        return $this->returnSuccess('Successful', $Sale_page_content);
    }

    public function destroy(Request $request, $id)
    {
        $loginBy = $request->login_by;

        if (!isset($loginBy)) {
            return $this->returnErrorData('[login_by] Data Not Found', 404);
        }

        DB::beginTransaction();

        try {

            $Sale_page_content = Sale_page_content::find($id);

            //log
            $userId = $loginBy->user_id;
            $type = 'Delete Sale_page_content';
            $description = 'User ' . $userId . ' has ' . $type . ' ' . $Sale_page_content->name;
            $this->Log($userId, $description, $type);
            //

            $Sale_page_content->delete();

            DB::commit();

            return $this->returnUpdate('Successful operation');
        } catch (\Throwable $e) {

            DB::rollback();

            return $this->returnErrorData('Something went wrong Please try again ' . $e, 404);
        }
    }

    public function store(Request $request)
    {

        $loginBy = $request->login_by;

        if (!isset($request->name)) {
            return $this->returnErrorData('กรุณาใส่ชื่อสาขาด้วย', 404);
        } else if (!isset($loginBy)) {
            return $this->returnErrorData('[login_by] Data Not Found', 404);
        }

        $name = $request->name;

        $checkName = Sale_page_content::where('name', $name)->first();

        if ($checkName) {
            return $this->returnErrorData('There is already this name in the system', 404);
        } else {

            DB::beginTransaction();

            try {

                $Sale_page_content = new Sale_page_content();
                $Sale_page_content->name = $name;
                $Sale_page_content->template_html = $request->template_html;

                $Sale_page_content->create_by = $loginBy->user_id;

                $Sale_page_content->save();

                //log
                $userId = $loginBy->user_id;
                $type = 'Add Sale_page_content';
                $description = 'User ' . $userId . ' has ' . $type . ' ' . $name;
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
}
