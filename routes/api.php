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

    
    Route::controller(Api::class)->group(function(){

        Route::get('/getCompanies', 'getCompanies');
        Route::get('/getBranch', 'getBranch');
        Route::get('/getUsers', 'getUsers');
        Route::get('/getSupplier', 'getSupplier');
        Route::get('/getBrand', 'getBrand');
        Route::post('/ValidateTp', 'ValidateTp');
        Route::get('/getCategory', 'getCategory');
        Route::post('/getAllCompanies', 'getAllCompanies');
        Route::get('/getCategoryOptions', 'getCategoryOptions');
        Route::get('/getTypeOptions', 'getTypeOptions');
        Route::get('/getSupplierOptions', 'getSupplierOptions');
        Route::get('/getSales', 'getSales');
        Route::get('/getRecipe', 'getRecipe');
        
    
        //post methods
    
        // new create or save Api
        Route::post('/company', 'company');
        Route::post('/updateCompany', 'updateCompany');
        Route::post('/branch', 'branch');
        Route::post('/user', 'user');
        Route::post('/supplier', 'supplier');
        Route::post('/category', 'category');
        Route::post('/subcategory', 'subcategory');
        Route::post('/brand', 'brand');
        Route::post('/purchase', 'purchase');
        Route::post('/sales', 'sales');
        Route::post('/recipes', 'recipes');
        Route::post('/change_password', 'change_password');
        Route::post('/roles', 'roles');
        // create api ends
    
        // delete api
        Route::post('/deleteCompanies', 'deleteCompanies');
        Route::post('/deleteBranches', 'deleteBranches');
        Route::post('/deleteSupplier', 'deleteSupplier');
        Route::post('/deleteCategory', 'deleteCategory');
        Route::post('/deleteSubCategory', 'deleteSubCategory');
        Route::post('/deleteBrand', 'deleteBrand');
        Route::post('/deleteRecipe', 'deleteRecipe');
        Route::post('/deleteRecipeId', 'deleteRecipeId');
        // delete api ends
    
        // other Api's
        Route::post('/getCompanyBranch', 'getCompanyBranch');
        Route::post('/getBrandOptions', 'getBrandOptions');
        Route::post('/updateUser', 'updateUser');
        Route::post('/updateCategory', 'updateCategory');
        Route::post('/deleteUser', 'deleteUser');
        Route::post('/getLogs', 'getLogs');
        Route::post('/updateBranch', 'updateBranch');
        Route::post('/getRecipeOptions', 'getRecipeOptions');
        Route::post('/recipeSales', 'recipeSales');
        Route::post('/manage_stock', 'manage_stock');
        Route::post('/getStockApi', 'getStockApi');
        Route::post('/BarVarianceReport', 'BarVarianceReport');
        Route::post('/recipeFetchApi', 'recipeFetchApi');
        Route::post('/getMenuOptions', 'getMenuOptions');
        Route::post('/recipeDetails', 'recipeDetails');
        Route::post('/linkBrands', 'linkBrands');
        Route::post('/getAllBrandOption', 'getAllBrandOption');
        Route::post('/getAllMenuOption', 'getAllMenuOption');
        Route::post('/getAllBrandSales', 'getAllBrandSales');
        Route::post('/fetchPurchaseData', 'fetchPurchaseData');
        Route::post('/updatePurchase', 'updatePurchase');
        Route::post('/convertPurchase', 'convertPurchase');
        Route::post('/deleteTp', 'deleteTp');
        Route::post('/deleteTpList', 'deleteTpList');
        Route::post('/deleteSale', 'deleteSale');
        Route::post('/updateSales', 'updateSales');
        Route::post('/AddTransaction', 'AddTransaction');
        Route::post('/fetchOpeningData', 'fetchOpeningData');
    
        // bulk upload Api
        Route::post('/bulkImportCategory', 'bulkImportCategory');
        Route::post('/bulkImportBrand', 'bulkImportBrand');
        Route::post('/bulkStockImport', 'bulkStockImport');
        Route::post('/bulkPurchaseImport', 'bulkPurchaseImport');
        Route::post('/bulkImportRecipes', 'bulkImportRecipes');
        Route::post('/bulkSalesImport', 'bulkSalesImport');
    
        // get single entry Api
        Route::post('/fetchUser', 'fetchUser');
        Route::post('/fetchBrandData', 'fetchBrandData');
        Route::post('/fetchSupplierData', 'fetchSupplierData');
        Route::post('/fetchTPData', 'fetchTPData');
        Route::post('/fetchSalesData', 'fetchSalesData');
        Route::post('/updateRecipes', 'updateRecipes');
        Route::post('/updateNonCocktail', 'updateNonCocktail');
    
        // Dashboard Api
        Route::post('/getSalesList', 'getSalesList');
        Route::post('/getTopSalesList', 'getTopSalesList');
        Route::post('/dashboard', 'dashboard');
    
        // reports api
        Route::post('/BarVarianceReport', 'BarVarianceReport');
    });
    

});
