<?php

namespace App\Http\Controllers;

use App\Models\Product;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ProductController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        //
    }
    public function getProduct()
    {
        $Product = Product::get()->toarray();

        if (!empty($Product)) {

            for ($i = 0; $i < count($Product); $i++) {
                $Product[$i]['No'] = $i + 1;

            }
        }

        return $this->returnSuccess('Successful', $Product);
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
        //dd($loginBy);
                if (!isset($request->code)) {
                    return $this->returnErrorData('[code] Data Not Found', 404);
                } else if (!isset($request->name)) {
                    return $this->returnErrorData('[name] Data Not Found', 404);
                } else if (!isset($request->image)) {
                    return $this->returnErrorData('[image] Data Not Found', 404);
                } else if (!isset($request->barcode)) {
                    return $this->returnErrorData('[barcode] Data Not Found', 404);
                } else if (!isset($request->brand)) {
                    return $this->returnErrorData('[brand] Data Not Found', 404);
                } 
             else if (!isset($request->weight)) {
                return $this->returnErrorData('[weight] Data Not Found', 404);
            } 
            else if (!isset($request->length)) {
                return $this->returnErrorData('[length] Data Not Found', 404);
            } 

            else if (!isset($request->width)) {
                return $this->returnErrorData('[width] Data Not Found', 404);
            } 
            else if (!isset($request->heigth)) {
                return $this->returnErrorData('[heigth] Data Not Found', 404);
            } 
            else if (!isset($request->price)) {
                return $this->returnErrorData('[price] Data Not Found', 404);
            } 
            else if (!isset($request->description)) {
                return $this->returnErrorData('[description] Data Not Found', 404);
            } 
                else if (!isset($loginBy)) {
                    return $this->returnErrorData('[login_by] Data Not Found', 404);
                }
                
        
                $name = $request->name;
                
        
                $checkName = Product::where('name', $name,)->first();
        
                if ($checkName) {
                    return $this->returnErrorData('There is already this name in the system', 404);
        
                }
                $checkCode = Product::where('code', $request->code)->first();
                if ($checkCode) {
                    return $this->returnErrorData('มีรหัสสินค้า ' . $request->code . ' ในระบบแล้ว', 404);
                }
                 else {
        
                    DB::beginTransaction();
        
                    try {
        
                        $Product = new Product();
                        $Product->name = $name;
                        $Product->code = $request->code;
                        $Product->image = $this->uploadImage($request->image, '/images/products/');
                        $Product->barcode = $request->barcode;
                        $Product->brand = $request->brand;
                        $Product->price = $request->price;
                        $Product->weight = $request->weight;
                        $Product->length = $request->length;
                        $Product->width = $request->width;
                        $Product->heigth = $request->heigth;
                        $Product->description = $request->description;
        
                        $Product->create_by = $loginBy->user_id;
        
                        $Product->save();
        
                        //log
                        $userId = $loginBy->user_id;
                        $type = 'Add Product';
                       $description = 'User ' . $userId . ' has ' . $type . ' ' . $name;
                       $this->Log($userId, $description, $type);
                        
        
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
     * @param  \App\Models\Product  $product
     * @return \Illuminate\Http\Response
     */
    public function show($id)
    {
        $Customer = Product::find($id);
        return $this->returnSuccess('Successful', $Customer);
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  \App\Models\Product  $product
     * @return \Illuminate\Http\Response
     */
    public function edit(Product $product)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \App\Models\Product  $product
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, $id)
    {
        $loginBy = $request->login_by;
        
        
        
        if (!isset($loginBy)) {
            return $this->returnErrorData('[login_by] Data Not Found', 404);
        }

       
        $name = $request->name;
        $checkName = Product::where('id', '!=', $id)
            ->where('name', $name)
            ->first();

        if ($checkName) {
            return $this->returnErrorData('There is already this name in the system', 404);

        } else {

            DB::beginTransaction();

            try {

                $Product = Product::find($id);

                $Product->name = $name;
                $Product->code = $request->code;
                $Product->barcode = $request->barcode;
                $Product->brand = $request->brand;
                $Product->price = $request->price;
                $Product->weight = $request->weight;
                $Product->length = $request->length;
                $Product->width = $request->width;
                $Product->heigth = $request->heigth;
                $Product->description = $request->description;
                $Product->update_by = $loginBy->user_id;
                $Product->updated_at = Carbon::now()->toDateTimeString();

                $Product->save();

                //log
                $userId = $loginBy->user_id;
                $type = 'Edit Product';
                $description = 'User ' . $userId . ' has ' . $type . ' ' . $Product->name;
                $this->Log($userId, $description, $type);
                //

                DB::commit();

                return $this->returnUpdate('Successful operation');

            } catch (\Throwable $e) {

                DB::rollback();

                return $this->returnErrorData('Something went wrong Please try again ' . $e, 404);
            }
        }
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  \App\Models\Product  $product
     * @return \Illuminate\Http\Response
     */
    public function destroy(Request $request,$id)
    {
        $loginBy = $request->login_by;

        if (!isset($loginBy)) {
            return $this->returnErrorData('[login_by] Data Not Found', 404);
        }

        DB::beginTransaction();

        try {

            $Product = Product::find($id);

            //log
            $userId = $loginBy->user_id;
            $type = 'Delete Product';
            $description = 'User ' . $userId . ' has ' . $type . ' ' . $Product->name;
            $this->Log($userId, $description, $type);
            //

            $Product->delete();

            DB::commit();

            return $this->returnUpdate('Successful operation');

        } catch (\Throwable $e) {

            DB::rollback();

            return $this->returnErrorData('Something went wrong Please try again ' . $e, 404);
        }
    }
}
