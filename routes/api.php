<?php

use App\Http\Controllers\MerchantController;
use Illuminate\Http\Request;
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
Route::get('/merchant/order-stats', [MerchantController::class, 'orderStats'])->name('merchant.orderStats');

Route::prefix('merchant')->group(function () {
    Route::post('/register', [MerchantController::class, 'registerMerchant']);
    Route::put('/update/{userId}', [MerchantController::class, 'updateMerchant']);
    Route::get('/find/{email}', [MerchantController::class, 'findMerchantByEmail']);
    Route::get('/order-statds', [MerchantController::class, 'orderStats'])->name('merchant.order-stats');
    Route::post('/affiliate/payout/{affiliateId}', [MerchantController::class, 'payoutAffiliateOrders']);
});
