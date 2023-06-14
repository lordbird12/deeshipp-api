<?php

use App\Http\Controllers\BankController;
use App\Http\Controllers\BranchController;
use App\Http\Controllers\ChannelController;
use App\Http\Controllers\CheckoutController;
use App\Http\Controllers\Controller;
use App\Http\Controllers\CustomerController;
use App\Http\Controllers\DeductPaidController;
use App\Http\Controllers\DeductTypeController;
use App\Http\Controllers\Delivered_byController;
use App\Http\Controllers\DepartmentController;
use App\Http\Controllers\DocController;
use App\Http\Controllers\EmployeeSalaryController;
use App\Http\Controllers\IncomePaidController;
use App\Http\Controllers\IncomeTypeController;
use App\Http\Controllers\ItemController;
use App\Http\Controllers\ItemLineController;
use App\Http\Controllers\ItemLotController;
use App\Http\Controllers\ItemReturnController;
use App\Http\Controllers\ItemTransController;
use App\Http\Controllers\ItemTypeController;
use App\Http\Controllers\LeaveTableController;
use App\Http\Controllers\LeaveTypeController;
use App\Http\Controllers\LocationController;
use App\Http\Controllers\LogController;
use App\Http\Controllers\LoginController;
use App\Http\Controllers\LotTransController;
use App\Http\Controllers\MenuController;
use App\Http\Controllers\PermissionController;
use App\Http\Controllers\PositionController;
use App\Http\Controllers\ProductController;
use App\Http\Controllers\ReportController;
use App\Http\Controllers\ReportStockController;
use App\Http\Controllers\SaleOrderController;
use App\Http\Controllers\SalePageContentController;
use App\Http\Controllers\SalePageController;
use App\Http\Controllers\SalePageOrderController;
use App\Http\Controllers\UnitController;

use App\Http\Controllers\UserController;
use App\Http\Controllers\VendorController;
use App\Http\Controllers\WarehouseController;
use App\Http\Controllers\WorkAdminController;
use App\Http\Controllers\WorkAdsController;
use App\Http\Controllers\WorkingTimeController;
use App\Http\Controllers\WorkTelesaleController;
use App\Http\Controllers\WorkTimeController;
use App\Http\Controllers\ProductLiveController;
use App\Http\Controllers\UserBankController;
use App\Http\Controllers\UserPageController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| is assigned the "api" middleware group. Enjoy building your API!
|
 */

// Route::middleware('auth:api')->get('/user', function (Request $request) {
//     return $request->user();
// });

//////////////////////////////////////////web no route group/////////////////////////////////////////////////////
//Login Admin
Route::post('/login', [LoginController::class, 'login']);

Route::post('/check_login', [LoginController::class, 'checkLogin']);

//user
Route::post('/create_admin', [UserController::class, 'createUserAdmin']);
Route::get('/get_userID', [UserController::class, 'showUser']);
Route::post('/activate_user_page', [UserController::class, 'ActivateUserPage']);
//lot trans
Route::post('/lot_trans_page', [LotTransController::class, 'LotTransPage']);

//format import
Route::get('/download_format_import/{params}', [Controller::class, 'getDownloadFomatImport']);


Route::post('/forgot_password_user', [UserController::class, 'ForgotPasswordUser']);

Route::post('upload_images', [Controller::class, 'uploadImage1']);



//Route::resource('checkout_product',CheckoutController::class);
Route::post('/checkout_product', [CheckoutController::class, 'Pushstore']);

Route::put('/update_check/{id}', [CheckoutController::class, 'UpdateCheckout']);

Route::get('/getcheckout_id/{id}', [CheckoutController::class, 'ShowDetail']);
Route::get('/sale_page_show/{id}', [SalePageController::class, 'showDetail']);

/////////////////////////////////////////////////////////////////////////////////////////////////////////////////

Route::group(['middleware' => 'checkjwt'], function () {

    //Sale_page_content

    Route::resource('sale_pages_contents', SalePageContentController::class);



    Route::get('/getcheckout', [CheckoutController::class, 'getcheckout']);

    //salePage_Order
    Route::resource('sale_pages_order', SalePageOrderController::class);
    Route::post('/sale_pages_order', [SalePageOrderController::class, 'SalePageOrder']);
    //salePage
    Route::resource('sale_pages', SalePageController::class);
    Route::get('/get_sale_pages', [SalePageController::class, 'getSalePages']);
    Route::post('/sale_page_product', [SalePageController::class, 'SalePage']);
    Route::put('/update_sale_page/{id}', [SalePageController::class, 'UpdateSalePage']);

    //salary
    Route::resource('salary', EmployeeSalaryController::class);
    Route::get('/get_salary', [EmployeeSalaryController::class, 'getsalary']);
    Route::post('/salary_page', [EmployeeSalaryController::class, 'salaryPage']);


    //Bank
    Route::resource('bank', BankController::class);
    Route::get('/get_bank', [BankController::class, 'getBank']);
    Route::post('/bankPage_page', [BankController::class, 'BankPage']);
    Route::post('/reset_bank', [BankController::class, 'Bankupdate']);


    //item_line
    Route::post('/report_item_line', [ItemLineController::class, 'item_line']);
    Route::get('/get_line', [ItemLineController::class, 'getItem_line']);
    Route::resource('item_line', ItemLineController::class);
    Route::post('/updateItemLine', [ItemLineController::class, 'ItemLineupdate']);


    //channels
    Route::resource('channels', ChannelController::class);
    Route::get('/get_channels', [ChannelController::class, 'getChannel']);
    Route::post('/channels_page', [ChannelController::class, 'ChannelPage']);
    Route::post('/up_Channel', [ChannelController::class, 'Channelupdate']);

    //delivered by
    Route::resource('delivered_by', Delivered_byController::class);
    Route::get('/get_delivered_by', [Delivered_byController::class, 'getDeliveredBy']);
    Route::post('/update', [Delivered_byController::class, 'updateDeliver']);
    Route::post('/deliverry_page', [Delivered_byController::class, 'deliveryPage']);

    //Sale order
    Route::get('/get_sale_order', [SaleOrderController::class, 'getSaleOrder']);
    Route::get('/get_sale_order_approve', [SaleOrderController::class, 'getSaleOrderApprove']);
    Route::resource('sale_order', SaleOrderController::class);
    Route::post('/sale_order_page', [SaleOrderController::class, 'SaleOrderPage']);
    Route::put('/approve_sale_order/{id}', [SaleOrderController::class, 'approveSaleOrder']);
    Route::put('/SaleOrder_status/{id}', [SaleOrderController::class, 'SaleOrderStatus']);
    Route::post('/SaleOrderTrack', [SaleOrderController::class, 'SaleOrderTrack']);
    Route::post('/import_sale_order', [SaleOrderController::class, 'ImportSaleOrder']);
    Route::post('/get_sale_order_open_job', [SaleOrderController::class, 'getSaleOrderOpenJob']);
    Route::post('/get_sale_order_line_by_item', [SaleOrderController::class, 'getSaleOrderLineByItem']);

    Route::post('/get_sale_order_approve_page', [SaleOrderController::class, 'getSaleOrderApprovePage']);



    //postition
    Route::resource('position', PositionController::class);
    Route::post('/position_page', [PositionController::class, 'PositionPage']);
    Route::post('/import_position', [PositionController::class, 'ImportPosition']);
    Route::get('/get_position', [PositionController::class, 'getPosition']);


    //department
    Route::get('/get_department', [DepartmentController::class, 'getDepartment']);
    Route::resource('department', DepartmentController::class);
    Route::post('/department_page', [DepartmentController::class, 'DepartmentPage']);
    Route::post('/import_department', [DepartmentController::class, 'ImportDepartment']);

    //branch
    Route::get('get_branch', [BranchController::class, 'getBranch']);
    Route::resource('branch', BranchController::class);
    Route::post('/branch_page', [BranchController::class, 'BranchPage']);
    Route::post('/import_branch', [BranchController::class, 'ImportBranch']);

    //Product
    Route::resource('Product', ProductController::class);
    Route::get('/get_product', [ProductController::class, 'getProduct']);
    //select
    Route::post('/register', [UserController::class, 'registerUser']);
    //permission
    Route::get('/get_permission', [PermissionController::class, 'getPermission']);
    //

    //vendor
    Route::get('/get_vendor', [VendorController::class, 'getVendor']);
    Route::resource('vendor', VendorController::class);
    Route::post('/vendor_page', [VendorController::class, 'VendorPage']);
    Route::post('/import_vendor', [VendorController::class, 'ImportVendor']);
    //Unit
    Route::get('/get_unit', [UnitController::class, 'getUnit']);
    Route::resource('unit', UnitController::class);
    Route::post('/unit_page', [UnitController::class, 'UnitPage']);
    Route::post('/import_unit', [UnitController::class, 'ImportUnit']);
    //customer
    Route::get('/get_customer', [CustomerController::class, 'getCustomer']);
    Route::resource('customer', CustomerController::class);
    Route::post('/customer_page', [CustomerController::class, 'CustomerPage']);
    Route::post('/customer_telesale_page', [CustomerController::class, 'CustomerTelesalePage']);
    Route::post('/import_customer', [CustomerController::class, 'ImportCustomer']);
    Route::post('/update_call', [CustomerController::class, 'updateCall']);
    //Item Trans
    Route::resource('item_trans', ItemTransController::class);
    Route::post('/item_trans_page', [ItemTransController::class, 'ItemTransPage']);
    Route::post('/item_stock_page', [ItemTransController::class, 'ItemStockPage']);
    Route::post('/edit_item_trans', [ItemTransController::class, 'editItemTrans']);

    //report
    Route::post('/report_stock_item', [ReportController::class, 'ReportStockItem']);
    Route::post('/report_tans_item', [ReportController::class, 'ReportTansItem']);
    Route::post('/report_item_type_stock', [ReportController::class, 'ReportIemTypeStock']);
    Route::post('/report_stockFG', [ReportController::class, 'ReportStockFG']);
    Route::post('/report_item_lot', [ReportController::class, 'ReportItemLot']);
    Route::post('/report_forcash', [ReportController::class, 'ReportForcash']);

    Route::post('/report_sale_order', [ReportController::class, 'ReportSaleOrder']);
    Route::post('/report_delevery_order', [ReportController::class, 'ReportDeleveryOrder']);

    Route::post('/report_stock_slow', [ReportController::class, 'ReportStockSlow']);
    Route::post('/report_stock_dead', [ReportController::class, 'ReportStockDead']);

    Route::post('/report_job', [ReportController::class, 'ReportJob']);

    Route::post('/report_machine', [ReportController::class, 'ReportMachine']);
    Route::post('/report_mantenance', [ReportController::class, 'ReportMantenance']);
    Route::post('/report_result', [ReportController::class, 'ReportResult']);

    Route::post('/report_ng', [ReportController::class, 'ReportNG']);

    Route::post('/report_maintenance_plan_component', [ReportController::class, 'ReportMaintenanceplanComponent']);

    Route::post('/report_item_machine', [ReportController::class, 'ReportItemMachine']);

    Route::post('/report_stock_on_due', [ReportController::class, 'ReportStockOnDue']);

    Route::post('/report_item_ng', [ReportController::class, 'ReportItemNG']);
    //

    Route::get('/get_warehouse', [WarehouseController::class, 'getWarehouse']);
    Route::resource('warehouse', WarehouseController::class);
    Route::post('/warehouse_page', [WarehouseController::class, 'WarehousePage']);

    //location
    Route::get('/get_location', [LocationController::class, 'getLocation']);
    Route::resource('location', LocationController::class);
    Route::post('/location_page', [LocationController::class, 'LocationPage']);
    Route::post('/get_location_item', [LocationController::class, 'getLocationItem']);
    Route::post('/get_location_stock_item', [LocationController::class, 'getLocationStockItem']);
    Route::post('/get_location_by_warehouse', [LocationController::class, 'getLocationByWarehouse']);
    Route::post('/import_location', [LocationController::class, 'ImportLocation']);
    Route::post('/get_location_by_item', [LocationController::class, 'getLocationByItem']);
    //
    //Item_type
    Route::get('/get_item_type', [ItemTypeController::class, 'getItemType']);
    Route::resource('item_type', ItemTypeController::class);
    Route::post('/item_type_page', [ItemTypeController::class, 'ItemTypePage']);
    Route::post('/import_item_type', [ItemTypeController::class, 'ImportItemType']);

    //Item
    Route::post('/get_item', [ItemController::class, 'getItem']);
    Route::get('/get_item_all', [ItemController::class, 'getItemAll']);
    Route::put('/update2_item/{id}', [ItemController::class, 'update2']);
    Route::resource('item', ItemController::class);
    Route::post('/item_page', [ItemController::class, 'ItemPage']);
    Route::post('/item_add', [ItemController::class, 'Putstore']);
    Route::post('/update_item', [ItemController::class, 'update']);
    Route::post('/import_item', [ItemController::class, 'ImportItem']);

    Route::post('/get_stock_item_by_bom_id', [ItemController::class, 'getStockItemByBomId']);
    Route::post('/get_stock_item_by_Location', [ItemController::class, 'getStockItemByLocation']);
    // Permission
    Route::resource('permission', PermissionController::class);
    Route::post('/permission_page', [PermissionController::class, 'PermissionPage']);
    Route::get('/get_permisson_user', [PermissionController::class, 'getPermissonUser']);
    Route::post('/get_permisson_menu', [PermissionController::class, 'getPermissonMenu']);

    //menu
    Route::resource('menu', MenuController::class);

    //user
    Route::get('/get_user', [UserController::class, 'getUser']);
    Route::resource('user', UserController::class);
    Route::get('/user_profile', [UserController::class, 'getProfileUser']);
    Route::post('/update_user', [UserController::class, 'updateUser']);
    Route::post('/user_page', [UserController::class, 'UserPage']);
    Route::put('/reset_password_user/{id}', [UserController::class, 'ResetPasswordUser']);
    Route::post('/update_profile_user', [UserController::class, 'updateProfileUser']);
    Route::delete('delete_user/{id}', [UserController::class, 'deleteUser']);
    Route::put('/activate_user/{id}', [UserController::class, 'ActivateUser']);
    Route::put('/update_password_user/{id}', [UserController::class, 'updatePasswordUser']);
    Route::post('/import_user', [UserController::class, 'ImportUser']);
    Route::get('/get_last_user_id', [UserController::class, 'getLastUserID']);
    Route::post('/get_user_payroll_page', [UserController::class, 'getUserPayroll']);


    //item_lot
    Route::get('/get_item_lot', [ItemLotController::class, 'getItemLot']);
    Route::resource('item_lot', ItemLotController::class);
    Route::post('/item_lot_page', [ItemLotController::class, 'ItemLotPage']);
    Route::post('/get_item_lot_by_item', [ItemLotController::class, 'getItemLotByItem']);

    //Report stock
    Route::post('/get_report_stock_by_type', [ReportStockController::class, 'getReportStockByType']);

    Route::resource('report_stock', ReportStockController::class);
    Route::post('/report_stock_page', [ReportStockController::class, 'ReportStockPage']);

    Route::post('/report_deposit_item', [ReportStockController::class, 'DepositItem']);
    Route::get('/report_stock1/{id}', [ReportStockController::class, 'showReport']);

    Route::post('/report_withdraw_item', [ReportStockController::class, 'WithdrawItem']);
    Route::post('/report_movement_item', [ReportStockController::class, 'MoveMentItem']);
    Route::post('/report_adjust_item', [ReportStockController::class, 'AdjustItem']);

    Route::put('/appove_report_stock/{id}', [ReportStockController::class, 'AppoveReportStock']);

    //WorkTime
    Route::resource('working_time', WorkTimeController::class);
    Route::post('/working_time_page', [WorkTimeController::class, 'WorkingTimePage']);
    Route::post('/working_time_type', [WorkTimeController::class, 'WorkingTimeType']);
    Route::post('/get_working_time', [WorkTimeController::class, 'getWorkingTime']);
    Route::post('/delete_working_time', [WorkTimeController::class, 'deleteWorkTime']);

    //Leave Type
    Route::resource('leave_type', LeaveTypeController::class);
    Route::post('/leave_type_page', [LeaveTypeController::class, 'getPage']);
    Route::get('/get_leave_type', [LeaveTypeController::class, 'getList']);

    //Doc
    Route::resource('doc', DocController::class);
    Route::post('/doc_page', [DocController::class, 'DocPage']);

    //Work Telesale
    Route::resource('work_telesale', WorkTelesaleController::class);
    Route::post('/work_telesale_page', [WorkTelesaleController::class, 'getPage']);
    Route::get('/get_work_telesale', [WorkTelesaleController::class, 'getList']);

    //Work Admin
    Route::resource('work_admin', WorkAdminController::class);
    Route::post('/work_admin_page', [WorkAdminController::class, 'getPage']);
    Route::get('/get_work_admin', [WorkAdminController::class, 'getList']);

    //Work Ads
    Route::resource('work_ads', WorkAdsController::class);
    Route::post('/work_ads_page', [WorkAdsController::class, 'getPage']);
    Route::get('/get_work_ads', [WorkAdsController::class, 'getList']);

    //item return
    Route::resource('item_return', ItemReturnController::class);
    Route::post('/item_return_page', [ItemReturnController::class, 'getPage']);
    Route::get('/get_item_return', [ItemReturnController::class, 'getList']);

    //Income Type
    Route::resource('income_type', IncomeTypeController::class);
    Route::post('/income_type_page', [IncomeTypeController::class, 'getPage']);
    Route::get('/get_income_type', [IncomeTypeController::class, 'getList']);

    //Deduct Type
    Route::resource('deduct_type', DeductTypeController::class);
    Route::post('/deduct_type_page', [DeductTypeController::class, 'getPage']);
    Route::get('/get_deduct_type', [DeductTypeController::class, 'getList']);

    //Leave Table
    Route::resource('leave', LeaveTableController::class);
    Route::post('/leave_page', [LeaveTableController::class, 'getPage']);
    Route::get('/get_leave', [LeaveTableController::class, 'getList']);

    //Income
    Route::resource('income_paid', IncomePaidController::class);
    Route::post('/income_paid_page', [IncomePaidController::class, 'getPage']);
    Route::get('/get_income_paid/{id}', [IncomePaidController::class, 'getList']);

    //Deduct
    Route::resource('deduct_paid', DeductPaidController::class);
    Route::post('/deduct_paid_page', [DeductPaidController::class, 'getPage']);
    Route::get('/get_deduct_paid/{id}', [DeductPaidController::class, 'getList']);

    //Working Time
    Route::resource('user_work_time', WorkingTimeController::class);
    Route::post('/user_work_time_page', [WorkingTimeController::class, 'getPage']);
    Route::get('/get_user_work_time', [WorkingTimeController::class, 'getList']);
    Route::get('/get_user_work_time_list/{id}', [WorkingTimeController::class, 'getUserList']);
    Route::post('/user_work_time_import', [WorkingTimeController::class, 'Import']);

    //log
    Route::post('/log_page', [LogController::class, 'LogPage']);
    Route::get('/get_log_type', [LogController::class, 'getLogType']);

     //user bank
     Route::resource('user_bank', UserBankController::class);
     Route::post('/get_user_bank', [UserBankController::class, 'getUserBank']);
     Route::post('/user_bank_page', [UserBankController::class, 'UserBankPage']);

       //user page
       Route::resource('user_page', UserPageController::class);
       Route::post('/get_user_page', [UserPageController::class, 'getUserPage']);
       Route::post('/user_page_page', [UserPageController::class, 'UserPagePage']);
});

Route::post('/line_bot', [SaleOrderController::class, 'lineBot']);

Route::post('/order_from_live', [SaleOrderController::class, 'orderFromLive']);

 //product live
 Route::resource('product_live', ProductLiveController::class);
 Route::post('/product_live_page', [ProductLiveController::class, 'getPage']);
 Route::get('/get_product_live', [ProductLiveController::class, 'getList']);

//export
Route::get('/export_user', [UserController::class, 'ExportUser']);

Route::get('/export_log', [LogController::class, 'ExportLog']);


Route::get('/get_order_live_by_id/{id}', [SaleOrderController::class, 'getOrderLiveById']);

