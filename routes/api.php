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
use App\Http\Controllers\OrderController;
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
use App\Http\Controllers\TransectionController;
use App\Http\Controllers\UserAddressSentController;
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


  Route::post('/confirm_multi_order', [SaleOrderController::class, 'confirmMultiOrder']);
  Route::post('/select_del_multi_order', [SaleOrderController::class, 'selectDelMultiOrder']);
  Route::post('/confirm_order_by_code', [SaleOrderController::class, 'confirmOrderByCode']);



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


  //Item_type
  Route::get('/get_item_type', [ItemTypeController::class, 'getItemType']);
  Route::resource('item_type', ItemTypeController::class);
  Route::post('/item_type_page', [ItemTypeController::class, 'ItemTypePage']);
  Route::post('/import_item_type', [ItemTypeController::class, 'ImportItemType']);

  //Item
  Route::post('/get_item', [ItemController::class, 'getItem']);
  Route::post('/get_item_all', [ItemController::class, 'getItemAll']);
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

  Route::post('/update_delivery_user', [UserController::class, 'updateDeliveryUser']);


  Route::post('/user_transection', [UserController::class, 'userTransection']);


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


  //Doc
  Route::resource('doc', DocController::class);
  Route::post('/doc_page', [DocController::class, 'DocPage']);



  //item return
  Route::resource('item_return', ItemReturnController::class);
  Route::post('/item_return_page', [ItemReturnController::class, 'getPage']);
  Route::get('/get_item_return', [ItemReturnController::class, 'getList']);



  //log
  Route::post('/log_page', [LogController::class, 'LogPage']);
  Route::get('/get_log_type', [LogController::class, 'getLogType']);

  //user bank
  Route::resource('user_bank', UserBankController::class);
  Route::post('/get_user_bank', [UserBankController::class, 'getUserBank']);
  Route::post('/user_bank_page', [UserBankController::class, 'UserBankPage']);

  //user page
  Route::resource('users_page', UserPageController::class);
  Route::post('/get_users_page', [UserPageController::class, 'getUserPage']);
  Route::post('/users_page_page', [UserPageController::class, 'UserPagePage']);

  //user address send
  Route::resource('user_address_sent', UserAddressSentController::class);
  Route::post('/get_user_address_sent', [UserAddressSentController::class, 'getUserAddressSent']);
  Route::post('/user_address_sent_page', [UserAddressSentController::class, 'UserAddressSentPage']);

  //Transection
  Route::post('/transection_page', [TransectionController::class, 'Page']);

  //order
  Route::post('/order_page', [OrderController::class, 'Page']);
});

Route::post('/line_bot', [SaleOrderController::class, 'lineBot']);

Route::post('/order_from_live', [SaleOrderController::class, 'orderFromLive']);

Route::put('/order_from_live/{id}', [SaleOrderController::class, 'updateOrderLive']);

//product live
Route::resource('product_live', ProductLiveController::class);
Route::post('/product_live_page', [ProductLiveController::class, 'getPage']);
Route::get('/get_product_live', [ProductLiveController::class, 'getList']);

//export
Route::get('/export_user', [UserController::class, 'ExportUser']);

Route::get('/export_log', [LogController::class, 'ExportLog']);


Route::get('/get_order_live_by_id/{id}', [SaleOrderController::class, 'getOrderLiveById']);

//อัปเดตการชำระเงินลูกค้า
Route::put('/payment_order_cm/{id}', [SaleOrderController::class, 'PaymentOrderCM']);

//calback payment
Route::get('/callback_user_transection', [UserController::class, 'callbackUserTransection']);
