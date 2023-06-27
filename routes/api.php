<?php

use App\Http\Controllers\Api;
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

Route::get('/', function () {
    return response()->json([
        'message' => 'please login first',
        'type' => 'failed'
    ], 401);
})->name('login');

Route::post('/login', [Api::class, 'login']);
Route::post('/test', [Api::class, 'test']);
Route::post('/register', [Api::class, 'register']);

Route::group(['middleware' => ('auth:sanctum')], function () {
    //get methods
    Route::post('/logout', function (Request $request) {
        if ($request->user()->currentAccessToken()->delete())
            return response()->json([
                'message' => 'Logout successful',
                'type' => 'success'
            ]);
        else
            return response()->json([
                'message' => 'Opps! failed',
                'type' => 'failed'
            ]);
    });
    Route::get('/user', function (Request $request) {
        return $request->user();
    });
    Route::get('/getCompanies', [Api::class, 'getCompanies']);
    Route::get('/getBranch', [Api::class, 'getBranch']);
    Route::get('/getUsers', [Api::class, 'getUsers']);
    Route::get('/getBrand', [Api::class, 'getBrand']);
    Route::post('/ValidateTp', [Api::class, 'ValidateTp']);
    Route::get('/getCategory', [Api::class, 'getCategory']);
    Route::post('/getAllCompanies', [Api::class, 'getAllCompanies']);
    Route::get('/getCategoryOptions', [Api::class, 'getCategoryOptions']);
    Route::get('/getTypeOptions', [Api::class, 'getTypeOptions']);
    Route::get('/getSales', [Api::class, 'getSales']);
    Route::get('/getRecipe', [Api::class, 'getRecipe']);
    
    
    //post methods
    
    // new create or save Api
    Route::post('/getSupplierOptions', [Api::class, 'getSupplierOptions']);
    Route::post('/getSupplier', [Api::class, 'getSupplier']);
    Route::post('/company', [Api::class, 'company']);
    Route::post('/getlinkedList', [Api::class, 'getlinkedList']);
    Route::post('/LinkCompany', [Api::class, 'LinkCompany']);
    Route::post('/getCompanyDetail', [Api::class, 'getCompanyDetail']);
    Route::post('/updateCompany', [Api::class, 'updateCompany']);
    Route::post('/branch', [Api::class, 'branch']);
    Route::post('/user', [Api::class, 'user']);
    Route::post('/supplier', [Api::class, 'supplier']);
    Route::post('/category', [Api::class, 'category']);
    Route::post('/subcategory', [Api::class, 'subcategory']);
    Route::post('/brand', [Api::class, 'brand']);
    Route::post('/purchase', [Api::class, 'purchase']);
    Route::post('/sales', [Api::class, 'sales']);
    Route::post('/recipes', [Api::class, 'recipes']);
    Route::post('/change_password', [Api::class, 'change_password']);
    Route::post('/roles', [Api::class, 'roles']);
    // create api ends

    // delete api
    Route::post('/deleteCompanies', [Api::class, 'deleteCompanies']);
    Route::post('/deleteBranches', [Api::class, 'deleteBranches']);
    Route::post('/deleteSupplier', [Api::class, 'deleteSupplier']);
    Route::post('/deleteCategory', [Api::class, 'deleteCategory']);
    Route::post('/deleteSubCategory', [Api::class, 'deleteSubCategory']);
    Route::post('/deleteBrand', [Api::class, 'deleteBrand']);
    Route::post('/deleteOPApi', [Api::class, 'deleteOPApi']);
    Route::post('/deleteRecipe', [Api::class, 'deleteRecipe']);
    Route::post('/deleteRecipeId', [Api::class, 'deleteRecipeId']);
    Route::post('/deleteLinkApi', [Api::class, 'deleteLinkApi']);
    // delete api ends

    // other Api's
    Route::post('/getCompanyBranch', [Api::class, 'getCompanyBranch']);
    Route::post('/getBrandOptions', [Api::class, 'getBrandOptions']);
    Route::post('/updateUser', [Api::class, 'updateUser']);
    Route::post('/updateCategory', [Api::class, 'updateCategory']);
    Route::post('/deleteUser', [Api::class, 'deleteUser']);
    Route::post('/getLogs', [Api::class, 'getLogs']);
    Route::post('/updateBranch', [Api::class, 'updateBranch']);
    Route::post('/getRecipeOptions', [Api::class, 'getRecipeOptions']);
    Route::post('/recipeSales', [Api::class, 'recipeSales']);
    Route::post('/manage_stock', [Api::class, 'manage_stock']);
    Route::post('/getStockApi', [Api::class, 'getStockApi']);
    Route::post('/BarVarianceReport', [Api::class, 'BarVarianceReport']);
    Route::post('/BarVarianceReportMl', [Api::class, 'BarVarianceReportMl']);
    Route::post('/recipeFetchApi', [Api::class, 'recipeFetchApi']);
    Route::post('/getMenuOptions', [Api::class, 'getMenuOptions']);
    Route::post('/recipeDetails', [Api::class, 'recipeDetails']);
    Route::post('/linkBrands', [Api::class, 'linkBrands']);
    Route::post('/getAllBrandOption', [Api::class, 'getAllBrandOption']);
    Route::post('/getAllMenuOption', [Api::class, 'getAllMenuOption']);
    Route::post('/getAllBrandSales', [Api::class, 'getAllBrandSales']);
    Route::post('/fetchPurchaseData', [Api::class, 'fetchPurchaseData']);
    Route::post('/updatePurchase', [Api::class, 'updatePurchase']);
    Route::post('/convertPurchase', [Api::class, 'convertPurchase']);
    Route::post('/deleteTp', [Api::class, 'deleteTp']);
    Route::post('/deleteTpList', [Api::class, 'deleteTpList']);
    Route::post('/deleteSale', [Api::class, 'deleteSale']);
    Route::post('/updateSales', [Api::class, 'updateSales']);
    Route::post('/AddTransaction', [Api::class, 'AddTransaction']);
    Route::post('/fetchOpeningData', [Api::class, 'fetchOpeningData']);
    Route::post('/getPhysicalData', [Api::class, 'getPhysicalData']);

    // bulk upload Api
    Route::post('/bulkImportCategory', [Api::class, 'bulkImportCategory']);
    Route::post('/bulkImportBrand', [Api::class, 'bulkImportBrand']);
    Route::post('/bulkStockImport', [Api::class, 'bulkStockImport']);
    Route::post('/PhysicalBulkApi', [Api::class, 'PhysicalBulkApi']);
    Route::post('/bulkPurchaseImport', [Api::class, 'bulkPurchaseImport']);
    Route::post('/bulkImportRecipes', [Api::class, 'bulkImportRecipes']);
    Route::post('/bulkSalesImport', [Api::class, 'bulkSalesImport']);

    // get single entry Api
    Route::post('/fetchUser', [Api::class, 'fetchUser']);
    Route::post('/fetchBrandData', [Api::class, 'fetchBrandData']);
    Route::post('/fetchSupplierData', [Api::class, 'fetchSupplierData']);
    Route::post('/fetchTPData', [Api::class, 'fetchTPData']);
    Route::post('/fetchUserId', [Api::class, 'fetchUserId']);
    Route::post('/fetchSalesData', [Api::class, 'fetchSalesData']);
    Route::post('/updateRecipes', [Api::class, 'updateRecipes']);
    Route::post('/updateNonCocktail', [Api::class, 'updateNonCocktail']);

    // Dashboard Api
    Route::post('/getSalesList', [Api::class, 'getSalesList']);
    Route::post('/getPriceList', [Api::class, 'getPriceList']);
    Route::post('/getTransaction', [Api::class, 'getTransaction']);
    Route::post('/getTopSalesList', [Api::class, 'getTopSalesList']);
    Route::post('/dashboard', [Api::class, 'dashboard']);

    // reports api
    Route::post('/BarVarianceReport', [Api::class, 'BarVarianceReport']);
    Route::post('/BarVarianceSummaryReport', [Api::class, 'BarVarianceSummaryReport']);
});
