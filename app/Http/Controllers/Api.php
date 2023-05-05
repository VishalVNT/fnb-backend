<?php

namespace App\Http\Controllers;

use DateTime;
use App\Models\branch;
use App\Models\Brand;
use App\Models\Company;
use App\Models\Supplier;
use App\Models\DailyOpening;
use App\Models\User;
use App\Models\Category;
use App\Models\purchase;
use App\Models\Roles;
use App\Models\Stock;
use App\Models\Transaction;
use App\Models\Sales;
use App\Models\Recipe;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Throwable;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class Api extends Controller
{
    public function register(Request $request)
    {
        try {
            $data = $request->validate(
                [
                    'name' => 'required|string',
                    'mobile' => 'required|string|unique:users',
                    'email' => 'required|string|unique:users',
                    'password' => 'required|string',
                    'c_password' => 'required|string|same:password',
                ]
            );
        } catch (Throwable $e) {
            return response()->json([
                'message' => 'Incorrect inputs',
                'type' => 'failed'
            ], 401);
        }
        $data['roles'] = json_encode($request->roles);
        $data['password'] = bcrypt($data['password']);
        $data['type'] = 1; // type admin
        $admin = new User($data);
        if ($admin->save()) {
            return response()->json([
                'message' => 'Admin registered',
                'type' => 'success'
            ], 201);
        } else {
            return response()->json([
                'message' => 'Oops! Operation failed',
                'type' => 'failed'
            ], 401);
        }
    }
    public function updateUser(Request $request)
    {
        try {
            $data = $request->validate(
                [
                    'name' => 'required|string',
                    'mobile' => 'required|string',
                    'email' => 'required|string',
                ]
            );
        } catch (Throwable $e) {
            return response()->json([
                'message' => 'Incorrect inputs',
                'type' => 'failed'
            ], 401);
        }
        $data['roles'] = json_encode($request->roles);
        if (User::where(['id' => $request->id])->update($data)) {
            return response()->json([
                'message' => 'Admin Updated',
                'type' => 'success'
            ], 201);
        } else {
            return response()->json([
                'message' => 'Oops! Operation failed',
                'type' => 'failed'
            ], 401);
        }
    }

    public function deleteUser(Request $request)
    {
        if (User::where(['id' => $request->id])->update(['status' => 0])) {
            return response()->json([
                'message' => 'Admin deleted',
                'type' => 'success'
            ], 201);
        } else {
            return response()->json([
                'message' => 'Oops! Operation failed',
                'type' => 'failed'
            ], 401);
        }
    }
    public function login(Request $request)
    {
        $request->validate([
            'mobile' => 'required|string',
            'password' => 'required|string',
        ]);
        $user = User::where('mobile', $request->mobile)->first();

        if (!$user || !Hash::check($request->password, $user->password)) {
            return response()->json([
                'message' => 'Unauthorized',
                'type' => 'failed'
            ]);
        } else {
            $token = $user->createToken('token')->plainTextToken;
            return response()->json([
                'user' => $user,
                'token' => $token
            ]);
        }
    }

    // create company
    public function company(Request $request)
    {

        $data = $request->validate([
            'name' => 'required|string',
            'license_name' => 'required|string',
            'license_no' => 'required',
            'pan_no' => 'required',
            'gst_no' => 'required',
        ]);
        $data['address'] = $request->address;
        $data['city'] = $request->city;
        $data['pincode'] = $request->pincode;
        $company = new Company($data);
        if ($company->save()) {
            $data_log = [
                'user_type' => $request->user()->type,
                'user_id' => $request->user()->id,
                'ip' => $request->ip(),
                'log' => 'company created',
                'platform' => 'web'
            ];

            $log_save = SaveLog($data_log);

            if (($log_save)) {

                return response()->json([
                    'message' => 'Company Added',
                    'type' => 'success'
                ], 201);
            } else {
                return response()->json([
                    'message' => 'Oops! Operation failed',
                    'type' => 'failed'
                ], 401);
            }
        } else {
            return response()->json([
                'message' => 'Oops! Operation failed',
                'type' => 'failed'
            ], 401);
        }
    }
    // create user
    public function user(Request $request)
    {
        $data = $request->validate([
            'company_id' => 'required|string',
            // 'branch_id' => 'required|string',
            'name' => 'required|string',
            'mobile' => 'required|string',
            'email' => 'required|string',
            'roles' => 'required|string',
            'password' => 'required|string',
        ]);



        /* $data = [
            'password' 
        ] = Hash::make($request->password);*/
        $User = new User($data);
        if ($User->save()) {

            $data_log = [
                'user_type' => $request->user()->type,
                'user_id' => $request->user()->id,
                'ip' => $request->ip(),
                'log' => 'user created',
                'platform' => 'web'
            ];

            $log_save = SaveLog($data_log);

            if (($log_save)) {

                return response()->json([
                    'message' => 'User Added',
                    'type' => 'success'
                ], 201);
            } else {
                return response()->json([
                    'message' => 'Oops! Operation failed',
                    'type' => 'failed'
                ], 401);
            }
        } else {
            return response()->json([
                'message' => 'Oops! Operation failed',
                'type' => 'failed'
            ], 401);
        }
    }
    // create supplier
    public function supplier(Request $request)
    {
        $data = $request->validate([
            'name' => 'required|string',
            'contact_person' => 'required|string',
            'mail' => 'required|string',
            'mobile' => 'required|string',
            'landline' => 'required|string',
            'license_no' => 'required|string',
            'address' => 'required|string',
            'pincode' => 'required|string',
            'city' => 'required|string',
        ]);
        $data['company_id'] = $request->company_id;
        $data['created_by'] = $request->user()->id;
        if (!empty($request->id)) {
            if (Supplier::where('id', $request->id)->update($data)) {
                $data_log = [
                    'user_type' => $request->user()->type,
                    'user_id' => $request->user()->id,
                    'ip' => $request->ip(),
                    'log' =>  $request->id . ' supplier id updated',
                    'platform' => 'web'
                ];
                $log_save = SaveLog($data_log);
                if (($log_save)) {
                    return response()->json([
                        'message' => 'Supplier Updated',
                        'type' => 'success'
                    ], 201);
                } else {
                    return response()->json([
                        'message' => 'Oops! Operation failed',
                        'type' => 'failed'
                    ], 401);
                }
            }
        } else {
            $company = new Supplier($data);
            if ($company->save()) {
                $data_log = [
                    'user_type' => $request->user()->type,
                    'user_id' => $request->user()->id,
                    'ip' => $request->ip(),
                    'log' => 'supplier created',
                    'platform' => 'web'
                ];
                $log_save = SaveLog($data_log);
                if (($log_save)) {
                    return response()->json([
                        'message' => 'Supplier Added',
                        'type' => 'success'
                    ], 201);
                } else {
                    return response()->json([
                        'message' => 'Oops! Operation failed',
                        'type' => 'failed'
                    ], 401);
                }
            }
        }
        return response()->json([
            'message' => 'Oops! Operation failed',
            'type' => 'failed'
        ], 401);
    }

    // create category
    public function category(Request $request)
    {
        $data = $request->validate([
            'name' => 'required|string',
            'short_name' => 'required|string',

        ]);
        //
        $data['created_by'] = $request->user()->id;

        $category = new Category($data);

        if ($category->save()) {

            $data_log = [
                'user_type' => $request->user()->type,
                'user_id' => $request->user()->id,
                'ip' => $request->ip(),
                'log' => 'Category created',
                'platform' => 'web'
            ];

            $log_save = SaveLog($data_log);

            if (($log_save)) {

                return response()->json([
                    'message' => 'Category Added',
                    'type' => 'success'
                ], 201);
            } else {
                return response()->json([
                    'message' => 'Oops! Operation failed',
                    'type' => 'failed'
                ], 401);
            }
        } else {

            return response()->json([
                'message' => 'Oops! Operation failed',
                'type' => 'failed'
            ], 401);
        }
    }
    // update category
    public function updateCategory(Request $request)
    {
        $data = $request->validate([
            'name' => 'required|string',
            'short_name' => 'required|string',
        ]);
        $id = $request->id;
        if (Category::where(['id' => $id])->update($data)) {
            $data_log = [
                'user_type' => $request->user()->type,
                'user_id' => $request->user()->id,
                'ip' => $request->ip(),
                'log' => 'Category updated',
                'platform' => 'web'
            ];

            $log_save = SaveLog($data_log);

            if (($log_save)) {
                return response()->json([
                    'message' => 'Category updated',
                    'type' => 'success'
                ], 201);
            } else {
                return response()->json([
                    'message' => 'Oops! Operation failed',
                    'type' => 'failed'
                ], 401);
            }
        } else {

            return response()->json([
                'message' => 'Oops! Operation failed',
                'type' => 'failed'
            ], 401);
        }
    }
    // create brand
    public function brand(Request $request)
    {
        $data = $request->validate([
            'name' => 'required|string',
            'short_name' => 'required|string',
            'btl_size' => 'required',
            'peg_size' => 'required',
            'no_peg' => 'required',
        ]);
        $isSaved = false;
        if ($request->isUpdate == 1) {
            if (Brand::where('id', $request->id)->update($data))
                $isSaved = true;
            $data_log = [
                'user_type' => $request->user()->type,
                'user_id' => $request->user()->id,
                'ip' => $request->ip(),
                'log' => $request->id . ' Brand Id Updated',
                'platform' => 'web'
            ];
            SaveLog($data_log);
        } else {
            $data['category_id'] = $request->category_id;
            $data['name'] = strtoupper($data['name']);
            $data['created_by'] = $request->user()->id;
            $brand = new Brand($data);
            if ($brand->save())
                $isSaved = true;
            $data_log = [
                'user_type' => $request->user()->type,
                'user_id' => $request->user()->id,
                'ip' => $request->ip(),
                'log' => $data['name'] . ' Brand created',
                'platform' => 'web'
            ];
            SaveLog($data_log);
        }

        if ($isSaved) {
            return response()->json([
                'message' => 'Brand Updated',
                'type' => 'success'
            ], 201);
        } else {
            return response()->json([
                'message' => 'Oops! Operation failed',
                'type' => 'failed'
            ], 401);
        }
    }
    // manage_stock

    public function manage_stock(Request $request)
    {
        $data = $request->validate([
            'company_id' => 'required',
            // 'branch_id' => 'required',
            'category_id' => 'required',
            'brand_id' => 'required',
            'btl' => 'required',
            'peg' => 'required',
            // 'physical_btl' => 'required',
            // 'physical_peg' => 'required',
            'cost_price' => 'required',
            'btl_selling_price' => 'required',
            'peg_selling_price' => 'required',

        ]);

        $count = Stock::where(['company_id' => $request->company_id,  'brand_id' => $request->brand_id])->get()->count();
        if ($count > 0) {
            $brandSize = Brand::select('btl_size', 'peg_size')->where('id', $data['brand_id'])->get();
            $MlSize = ($brandSize[0]['btl_size'] * $data['btl']) + ($brandSize[0]['peg_size'] * $data['peg']);

            // $physical_MlSize = ($brandSize[0]['btl_size'] * $data['physical_btl']) + ($brandSize[0]['peg_size'] * $data['physical_peg']);
            $store_btl = $request->store_btl;
            $store_peg = $request->store_peg;
            $bar1_btl = $request->bar1_btl;
            $bar1_peg = $request->bar1_peg;
            $bar2_btl = $request->bar2_btl;
            $bar2_peg = $request->bar2_peg;
            //update stock
            Stock::where(['company_id' => $request->company_id,  'brand_id' => $request->brand_id])->update(['qty' => $MlSize, 'cost_price' => $data['cost_price'], 'btl_selling_price' => $data['btl_selling_price'], 'peg_selling_price' => $data['peg_selling_price'], 'store_btl' => $store_btl, 'store_peg' => $store_peg, 'bar1_btl' => $bar1_btl, 'bar1_peg' => $bar1_peg, 'bar2_btl' => $bar2_btl, 'bar2_peg' => $bar2_peg]);
        } else {
            $brandSize = Brand::select('btl_size', 'peg_size')->where('id', $data['brand_id'])->get();
            $MlSize = ($brandSize[0]['btl_size'] * $data['btl']) + ($brandSize[0]['peg_size'] * $data['peg']);
            // $physical_MlSize = ($brandSize[0]['btl_size'] * $data['physical_btl']) + ($brandSize[0]['peg_size'] * $data['physical_peg']);
            $data['qty'] = $MlSize;
            //Stock entry
            // $data['physical_closing'] = $physical_MlSize;
            $manage_stock = new Stock($data);
            $manage_stock->save();
        }

        $data_log = [
            'user_type' => $request->user()->type,
            'user_id' => $request->user()->id,
            'ip' => $request->ip(),
            'log' => 'Stock created',
            'platform' => 'web'
        ];

        $log_save = SaveLog($data_log);

        if (($log_save)) {

            return response()->json([
                'message' => 'Stock Updated',
                'type' => 'success'
            ], 201);
        } else {
            return response()->json([
                'message' => 'Oops! Operation failed',
                'type' => 'failed'
            ], 401);
        }
    }

    // create roles
    public function roles(Request $request)
    {
        $data = $request->validate([
            'role_name' => 'required|string',

        ]);
        $page_id = json_encode($request->role);
        $data['page_id'] = $page_id;
        $Roles = new Roles($data);
        if ($Roles->save()) {
            $data_log = [
                'user_type' => $request->user()->type,
                'user_id' => $request->user()->id,
                'ip' => $request->ip(),
                'log' => 'supplier created',
                'platform' => 'web'
            ];

            $log_save = SaveLog($data_log);

            if (($log_save)) {

                return response()->json([
                    'message' => 'Roles Added',
                    'type' => 'success'
                ], 201);
            } else {
                return response()->json([
                    'message' => 'Oops! Operation failed',
                    'type' => 'failed'
                ], 401);
            }
        } else {
            return response()->json([
                'message' => 'Oops! Operation failed',
                'type' => 'failed'
            ], 401);
        }
    }

    // create sales
    public function sales(Request $request)
    {
        error_reporting(0);
        $data = $request->validate([
            'company_id' => 'required',
        ]);
        //
        $data['created_by'] = $request->user()->id;
        $data['sale_date'] = $request->created_at;
        $data['description'] = 'liqour sale';
        $brands = explode(',', $request->brand_id);
        $category_id = explode(',', $request->category_id);
        $sales_type = explode(',', $request->sales_type);
        $no_btl = explode(',', $request->no_btl);
        $no_peg = explode(',', $request->no_peg);
        $servingSize = explode(',', $request->servingSize);
        $counter = 0;
        $skipped = 0;
        foreach ($brands as $key => $brand) {
            // check if brand is in stock or not
            $saved = false;
            $stock = Stock::select('id', 'btl_selling_price', 'peg_selling_price')->where(['company_id' => $request->company_id,  'brand_id' => $brand])->get();
            if (count($stock) > 0) {

                $data['brand_id'] = $brand;
                $data['sales_type'] = $sales_type[$key];
                $data['category_id'] = $category_id[$key];
                $data['sale_price'] = ($no_btl[$key] * $stock[0]['btl_selling_price']) + ($no_peg[$key] * $stock[0]['peg_selling_price']);
                // get sale quantity in ml
                if (($servingSize[$key]) > 0) {
                    $MlSize = ($servingSize[$key] * $no_btl[$key]);
                } else {
                    $brandSize = Brand::select('btl_size', 'peg_size')->where('id', $brand)->get();
                    $MlSize = ($brandSize[0]['btl_size'] * $no_btl[$key]) + ($brandSize[0]['peg_size'] * $no_peg[$key]);
                }
                $data['qty'] = $MlSize;
                $data['no_btl'] = $no_btl[$key];
                $data['no_peg'] = $no_peg[$key];
                $Sales = new Sales($data);
                if ($Sales->save()) {
                    //update stocks
                    Stock::where(['company_id' => $request->company_id,  'brand_id' => $brand])->decrement('qty', $MlSize);
                    // logs
                    $data_log = [
                        'user_type' => $request->user()->type,
                        'user_id' => $request->user()->id,
                        'ip' => $request->ip(),
                        'log' => 'Sales created',
                        'platform' => 'web'
                    ];
                    SaveLog($data_log);
                    $saved = true;
                    $counter++;
                } else {
                    $skipped++;
                }
            } else {
                $skipped++;
            }
        }
        if ($saved) {
            return response()->json([
                'message' => $counter . ' Sales Added, ' . $skipped . ' Entries failed',
                'type' => 'success'
            ], 201);
        } else {
            return response()->json([
                'message' => 'Oops! Operation failed',
                'type' => 'failed'
            ], 401);
        }
        return response()->json([
            'message' => 'Oops! Operation failed',
            'type' => 'failed'
        ], 401);
    }
    public function recipeSales(Request $request)
    {
        error_reporting(0);
        $data = $request->validate([
            'company_id' => 'required',
        ]);
        $recipe_ids = explode(',', $request->recipe_id);
        $total_qty = explode(',', $request->qty);
        $sales_type = explode(',', $request->sales_type);
        $data['sale_date'] = $request->created_at;
        $saved = false;
        $counter = 0;
        $skipped = 0;
        foreach ($recipe_ids as $key => $recipe) {
            $brands = Recipe::select('category_id', 'brand_id', 'serving_size')->where(['recipe_code' => $recipe, 'status' => 1])->get();
            $sale_qty = $total_qty[$key];
            $type = $sales_type[$key];
            foreach ($brands as $brand) {
                $stocks = Stock::select('id', 'btl_selling_price', 'peg_selling_price')->where(['company_id' => $data['company_id'], 'brand_id' => $brand['brand_id']])->get();
                if (count($stocks) > 0) {
                    [$peg_size] = Brand::select('peg_size', 'btl_size')->where('id', $brand['brand_id'])->get();
                    $data['qty'] = $brand['serving_size'] * $sale_qty;
                    $qty = $data['qty'];
                    $btl = 0;
                    while ($qty > $peg_size['btl_size']) {
                        $qty = $qty - $peg_size['btl_size'];
                        $btl++;
                    }
                    $peg = $qty / $peg_size['peg_size'];
                    $data['sale_price'] = ($btl * $stocks[0]['btl_selling_price']) + ($peg * $stocks[0]['peg_selling_price']);
                    $data['no_peg'] = $peg;
                    $data['no_btl'] = $btl;
                    $data['category_id'] = $brand['category_id'];
                    $data['brand_id'] = $brand['brand_id'];
                    $data['created_by'] = $request->user()->id;
                    $data['sales_type'] = $type;
                    $data['description'] = $recipe . ' recipe sale';
                    $Sales = new Sales($data);
                    if ($Sales->save()) {
                        $counter++;
                        $saved = true;
                        Stock::where(['company_id' => $data['company_id'], 'brand_id' => $brand['brand_id']])->decrement('qty', $data['qty']);
                    }
                    // logs
                    $data_log = [
                        'user_type' => $request->user()->type,
                        'user_id' => $request->user()->id,
                        'ip' => $request->ip(),
                        'log' => 'Recipe sold ' . $recipe,
                        'platform' => 'web'
                    ];
                    SaveLog($data_log);
                } else {
                    $skipped++;
                }
            }
        }
        if ($saved) {
            // logs
            return response()->json([
                'message' => $counter . ' Sales Added, ' . $skipped . ' Entries failed',
                'type' => 'success'
            ], 201);
        } else {

            return response()->json([
                'message' => 'Oops! Operation failed',
                'type' => 'failed'
            ], 401);
        }
    }

    // create recipes
    public function recipes(Request $request)
    {
        $data = $request->validate([
            'name' => 'required|string',
            'company_id' => 'required',
            // 'branch_id' => 'required',
        ]);
        $brand = explode(',', $request->brand_id);
        $serving_size = explode(',', $request->serving_size);
        $isSaved = false;
        do {
            // generate unique recipe_code code
            $recipe_code = $data['company_id'] . rand(11111, 99999);
            $count = Recipe::where(['recipe_code' => $recipe_code])->get()->count();
        } while ($count > 0);
        foreach ($brand as $key => $id) {
            $data['brand_id'] = $id;
            $data['recipe_code'] = $recipe_code;
            $category = Brand::select('category_id')->where('id', $id)->first();
            $data['category_id'] = $category->category_id;
            $data['serving_size'] = $serving_size[$key];
            $data['created_by'] = $request->user()->id;
            $Recipe = new Recipe($data);
            if ($Recipe->save())
                $isSaved = true;
        }
        if ($isSaved) {
            $data_log = [
                'user_type' => $request->user()->type,
                'user_id' => $request->user()->id,
                'ip' => $request->ip(),
                'log' =>  $request->name . ' Recipe created',
                'platform' => 'web'
            ];
            $log_save = SaveLog($data_log);
            if (($log_save)) {
                return response()->json([
                    'message' => 'Recipe Added',
                    'type' => 'success'
                ], 201);
            } else {
                return response()->json([
                    'message' => 'Oops! Operation failed',
                    'type' => 'failed'
                ], 401);
            }
        } else {

            return response()->json([
                'message' => 'Oops! Operation failed',
                'type' => 'failed'
            ], 401);
        }
    }
    public function deleteRecipe(Request $request)
    {
        if (Recipe::where(['recipe_code' => $request->recipe_code])->update(['status' => 0])) {
            return response()->json([
                'message' => 'Recipe deleted',
                'type' => 'success'
            ], 201);
        } else {
            return response()->json([
                'message' => 'Oops! Operation failed',
                'type' => 'failed'
            ], 401);
        }
    }
    public function deleteRecipeId(Request $request)
    {
        if (Recipe::where(['id' => $request->id])->update(['status' => 0])) {
            return response()->json([
                'message' => 'Recipe deleted',
                'type' => 'success'
            ], 201);
        } else {
            return response()->json([
                'message' => 'Oops! Operation failed',
                'type' => 'failed'
            ], 401);
        }
    }



    // create child brand and link with parent
    public function linkBrands(Request $request)
    {
        $data = $request->validate([
            'company_id' => 'required',
            'brand_id' => 'required',
        ]);
        $name = explode(',', $request->name);
        $serving_size = explode(',', $request->serving_size);
        $isSaved = false;
        foreach ($name as $key => $name) {
            $data['name'] = $name;
            $data['serving_size'] = $serving_size[$key];
            do {
                // generate unique recipe_code code
                $recipe_code = $data['company_id'] . rand(11111, 99999);
                $count = Recipe::where(['recipe_code' => $recipe_code])->get()->count();
            } while ($count > 0);
            $data['recipe_code'] = $recipe_code;
            $data['is_cocktail'] = 0;
            $category = Brand::select('category_id')->where('id', $data['brand_id'])->first();
            $data['category_id'] = $category->category_id;
            $data['created_by'] = $request->user()->id;
            $Recipe = new Recipe($data);
            if ($Recipe->save())
                $isSaved = true;
        }
        if ($isSaved) {
            $data_log = [
                'user_type' => $request->user()->type,
                'user_id' => $request->user()->id,
                'ip' => $request->ip(),
                'log' =>  $request->name . ' Link Recipe created',
                'platform' => 'web'
            ];
            $log_save = SaveLog($data_log);
            if (($log_save)) {
                return response()->json([
                    'message' => 'Recipe Added',
                    'type' => 'success'
                ], 201);
            } else {
                return response()->json([
                    'message' => 'Oops! Operation failed',
                    'type' => 'failed'
                ], 401);
            }
        } else {

            return response()->json([
                'message' => 'Oops! Operation failed',
                'type' => 'failed'
            ], 401);
        }
    }


    // get company
    public function getCompanies()
    {
        $data = Company::select('name as value', 'id', DB::raw("CONCAT(name,' - ',license_no) AS label"))->where('status', 1)->get();
        if ($data) {
            return response()->json($data);
        } else {
            return response()->json([
                'message' => 'Oops! operation failed!',
                'type' => 'failed'
            ]);
        }
    }
    // get company
    public function getAllCompanies(Request $request)
    {
        if (!empty($request->keyword))
            $data = Company::where(['status' => 1, ['name', 'like', '%' . $request->keyword . '%']])->get();
        else
            $data = Company::where('status', 1)->get();
        if ($data) {
            return response()->json($data);
        } else {
            return response()->json([
                'message' => 'Oops! operation failed!',
                'type' => 'failed'
            ]);
        }
    }

    // get branch
    public function getCompanyDetail(Request $request)
    {
        $data = Company::select('*')->where(['status' => 1, 'id' => $request->company_id])->get();
        if ($data) {
            return response()->json($data);
        } else {
            return response()->json([
                'message' => 'Oops! operation failed!',
                'type' => 'failed'
            ]);
        }
    }
    // get Users
    public function getUsers()
    {
        $data = User::where('status', 1)->get();
        if ($data) {
            return response()->json($data);
        } else {
            return response()->json([
                'message' => 'Oops! operation failed!',
                'type' => 'failed'
            ]);
        }
    }
    // get fetch user
    public function fetchUser(Request $request)
    {
        $data = User::select('id', 'name', 'mobile', 'email', 'roles')->where(['status' => 1, ['name', 'like', '%' . $request->keyword . '%']])->get();
        if ($data) {
            return response()->json($data);
        } else {
            return response()->json([
                'message' => 'Oops! operation failed!',
                'type' => 'failed'
            ]);
        }
    }
    // fetchbrand search
    public function fetchBrandData(Request $request)
    {
        if ($request->search === 1)
            $data = Brand::select('categories.name as c_name', 'brands.*')->join('categories', 'brands.category_id', '=', 'categories.id')->where(['brands.status' => 1, ['brands.name', 'like', '%' . $request->keyword . '%']])->get();
        else
            $data = Brand::where(['brands.status' => 1, 'brands.id' => $request->id])->get();
        if ($data) {
            return response()->json($data);
        } else {
            return response()->json([
                'message' => 'Oops! operation failed!',
                'type' => 'failed'
            ]);
        }
    }
    // fetch supplier search
    public function fetchSupplierData(Request $request)
    {
        $data = Supplier::where(['company_id' => $request->company_id, ['name', 'like', '%' . $request->keyword . '%']])->get();
        if ($data) {
            return response()->json($data);
        } else {
            return response()->json([
                'message' => 'Oops! operation failed!',
                'type' => 'failed'
            ]);
        }
    }
    // fetch Tp search
    public function fetchTPData(Request $request)
    {
        $dateTime = new DateTime($request->date);
        $date = $dateTime->format('Y-m-d');
        if ($request->isInvoice == 0)
            $data = Purchase::select('brands.name', 'no_btl', 'purchases.invoice_no', 'purchases.created_at', 'purchases.id')->join('brands', 'brands.id', '=', 'purchases.brand_id')->where(['purchases.status' => 1, 'purchases.mrp' => null, 'purchases.company_id' => $request->company_id])->orderBy('id', 'DESC');
        else
            $data = Purchase::select('brands.name', 'no_btl', 'purchases.invoice_no', 'purchases.created_at', 'purchases.id')->join('brands', 'brands.id', '=', 'purchases.brand_id')->where(['purchases.status' => 1, ['purchases.mrp', '>=', 0], 'purchases.company_id' => $request->company_id])->orderBy('id', 'DESC');
        if (!empty($request->keyword))
            $data->where('purchases.invoice_no', 'like', '%' . $request->keyword . '%');
        if (!empty($request->date))
            $data->whereDate('purchases.invoice_date', '=', $date);
        // return $data->toSql();
        $data = $data->get();

        if ($data) {
            return response()->json($data);
        } else {
            return response()->json([
                'message' => 'Oops! operation failed!',
                'type' => 'failed'
            ]);
        }
    }
    // fetch sales search
    public function fetchSalesData(Request $request)
    {
        $dateTime = new DateTime($request->date);
        $date = $dateTime->format('Y-m-d');
        $data = Sales::select('brands.name', 'sales.no_btl', 'sales.no_peg', 'sales.created_at', 'sales.id')->join('brands', 'brands.id', '=', 'sales.brand_id')->where('sales.company_id', $request->company_id)->orderBy('id', 'DESC');
        if (!empty($request->keyword))
            $data->where('brands.name', 'like', '%' . $request->keyword . '%');
        if (!empty($request->date))
            $data->whereDate('sales.sale_date', '=', $date);
        $data = $data->get();
        if ($data) {
            return response()->json($data);
        } else {
            return response()->json([
                'message' => 'Oops! operation failed!',
                'type' => 'failed'
            ]);
        }
    }
    // fetch sales search
    public function fetchSalesDetail(Request $request)
    {
        $data = Sales::where('id', $request->id)->get();
        if ($data) {
            return response()->json($data);
        } else {
            return response()->json([
                'message' => 'Oops! operation failed!',
                'type' => 'failed'
            ]);
        }
    }

    // get supplier
    public function getSupplier()
    {
        $data = Supplier::where('status', 1)->get();
        if ($data) {
            return response()->json($data);
        } else {
            return response()->json([
                'message' => 'Oops! operation failed!',
                'type' => 'failed'
            ]);
        }
    }
    // get supplier
    public function getSupplierOptions()
    {
        $data = Supplier::select('id', 'name as label', 'name as value')->where('status', 1)->get();
        if ($data) {
            return response()->json($data);
        } else {
            return response()->json([
                'message' => 'Oops! operation failed!',
                'type' => 'failed'
            ]);
        }
    }
    // get getBrand
    public function getBrand()
    {
        $data = Brand::select('categories.name as c_name', 'brands.*')->join('categories', 'brands.category_id', '=', 'categories.id')->where('brands.status', 1)->get();
        if ($data) {
            return response()->json($data);
        } else {
            return response()->json([
                'message' => 'Oops! operation failed!',
                'type' => 'failed'
            ]);
        }
    }

    //getCategory
    public function getCategory()
    {
        $data = Category::where('status', 1)->get();
        //  echo "<pre>";print_r($data);exit();

        if ($data) {
            return response()->json($data);
        } else {
            return response()->json([
                'message' => 'Oops! operation failed!',
                'type' => 'failed'
            ]);
        }
    }
    //getCategory
    public function getCategoryOptions()
    {
        $data = Category::select('*', 'name as label')->where('status', 1)->get();
        //  echo "<pre>";print_r($data);exit();

        if ($data) {
            return response()->json($data);
        } else {
            return response()->json([
                'message' => 'Oops! operation failed!',
                'type' => 'failed'
            ]);
        }
    }
    public function deleteCompanies(Request $request)
    {
        $data = $request->validate([
            'id' => 'required'
        ]);
        $data = Company::where('id', $data['id'])->update(['status' => 0]);
        if ($data) {
            return response()->json([
                'message' => 'Company deleted',
                'type' => 'success'
            ], 201);
        } else {
            return response()->json([
                'message' => 'Oops! operation failed!',
                'type' => 'failed'
            ]);
        }
    }
    public function deleteBranches(Request $request)
    {
        $data = $request->validate([
            'id' => 'required'
        ]);
        $data = branch::where('id', $data['id'])->update(['status' => 0]);
        if ($data) {
            return response()->json([
                'message' => 'Branch deleted',
                'type' => 'success'
            ], 201);
        } else {
            return response()->json([
                'message' => 'Oops! operation failed!',
                'type' => 'failed'
            ]);
        }
    }
    public function deleteSupplier(Request $request)
    {
        error_reporting(0);
        $data = $request->validate([
            'id' => 'required'
        ]);
        $task = Supplier::where('id', $data['id'])->update(['status' => 0]);
        if ($task) {
            return response()->json([
                'message' => 'Company deleted',
                'type' => 'success'
            ], 201);
        } else {
            return response()->json([
                'message' => 'Oops! operation failed!',
                'type' => 'failed'
            ]);
        }
    }
    public function deleteCategory(Request $request)
    {
        error_reporting(0);
        $data = $request->validate([
            'id' => 'required'
        ]);
        $task = Category::where('id', $data['id'])->update(['status' => 0]);
        if ($task) {
            return response()->json([
                'message' => 'Category deleted',
                'type' => 'success'
            ], 201);
        } else {
            return response()->json([
                'message' => 'Oops! operation failed!',
                'type' => 'failed'
            ]);
        }
    }
    public function deleteBrand(Request $request)
    {
        error_reporting(0);
        $data = $request->validate([
            'id' => 'required'
        ]);
        $data = Brand::where('id', $data['id'])->update(['status' => 0]);
        if ($data) {
            return response()->json([
                'message' => 'Company deleted',
                'type' => 'success'
            ], 201);
        } else {
            return response()->json([
                'message' => 'Oops! operation failed!',
                'type' => 'failed'
            ]);
        }
    }
    public function purchase(Request $request)
    {
        $data = $request->validate([
            'category_id' => 'required',
            'company_id' => 'required',
            'invoice_no' => Rule::unique('purchases')->where(function ($query) {
                $query->where('status', 1);
            }),
        ]);
        $isSaved = false;
        $brand = explode(',', $request->brand_id);
        $nobtl = explode(',', $request->nobtl);
        $data['mrp'] = $request->mrp;
        $data['court_fees'] = $request->court_fees;
        $data['tcs'] = $request->tcs;
        $data['total_amount'] = $request->total_amount;
        $data['invoice_date'] = date('Y-m-d', strtotime($request->invoice_date));
        $data['created_by'] = $request->user()->id;
        $data['batch_no'] = $request->batch_no;
        $data['discount'] = $request->discount;
        $data['vat'] = $request->vat;
        $data['vendor_id'] = $request->vendor_id;
        foreach ($brand as $key => $item) {
            $data['brand_id'] = $item;
            $data['no_btl'] = $nobtl[$key];
            $brandSize = Brand::select('btl_size')->where('id', $data['brand_id'])->get();
            $MlSize = ($brandSize[0]['btl_size'] * $data['no_btl']);
            $data['qty'] = $MlSize;
            $save = new purchase($data);
            if ($save->save()) {
                $isSaved = true;
                // check stock
                $count = Stock::where(['company_id' => $request->company_id, 'brand_id' => $data['brand_id']])->get()->count();
                if ($count > 0) {
                    //update stock
                    Stock::where(['company_id' => $request->company_id,  'brand_id' => $data['brand_id']])->increment('qty', $MlSize);
                } else {
                    //Stock entry
                    $stock = new Stock(array(
                        'company_id' => $request->company_id,
                        //'branch_id' => $request->branch_id,
                        'category_id' => $request->category_id,
                        'brand_id' => $data['brand_id'],
                        'qty' => $MlSize,
                        'cost_price' => $request->mrp,
                    ));
                    $stock->save();
                }
            }
        }
        if ($isSaved) {
            // logs
            SaveLog([
                'user_type' => $request->user()->type,
                'user_id' => $request->user()->id,
                'ip' => $request->ip(),
                'log' => 'made purchase entry with purchase id :' . $save->id,
                'platform' => 'web'
            ]);
            return response()->json([
                'message' => 'TP Added',
                'type' => 'success'
            ], 201);
        } else {
            return response()->json([
                'message' => 'Oops! Operation failed',
                'type' => 'failed'
            ], 401);
        }
    }
    public function convertPurchase(Request $request)
    {
        $request->validate([
            'id' => 'required',
            'isInvoice' => 'required',
        ]);
        Purchase::where(['id' => $request->id])->update(['mrp' => $request->isInvoice == 1 ? null : 0]);
        $log_save = SaveLog([
            'user_type' => $request->user()->type,
            'user_id' => $request->user()->id,
            'ip' => $request->ip(),
            'log' => 'converted purchase entry with purchase id :' . $request->id,
            'platform' => 'web'
        ]);
        if ($log_save) {
            return response()->json([
                'message' => 'TP Updated',
                'type' => 'success'
            ], 201);
        } else {
            return response()->json([
                'message' => 'Oops! Operation failed',
                'type' => 'failed'
            ], 401);
        }
    }
    public function updatePurchase(Request $request)
    {
        $data = $request->validate([
            'category_id' => 'required',
            'brand_id' => 'required',
            'company_id' => 'required',
        ]);
        $data['mrp'] = $request->mrp;
        $data['court_fees'] = $request->court_fees;
        $data['tcs'] = $request->tcs;
        $data['total_amount'] = $request->total_amount;
        $data['invoice_no'] = $request->invoice_no;
        $data['invoice_date'] = date('Y-m-d', strtotime($request->invoice_date));
        $data['created_by'] = $request->user()->id;
        $data['no_btl'] = $request->no_btl; //number of btl
        $data['batch_no'] = $request->batch_no;
        $data['vendor_id'] = $request->vendor_id;

        $stockEntry = Purchase::select('no_btl')->where(['id' => $request->id])->get();
        if (count($stockEntry) > 0) {
            Purchase::where(['id' => $request->id])->update($data);
            // check stock
            $count = Stock::where(['company_id' => $request->company_id, 'brand_id' => $request->brand_id])->get()->count();
            $brandSize = Brand::select('btl_size')->where('id', $data['brand_id'])->get();
            $MlSize = ($brandSize[0]['btl_size'] * $data['no_btl']);
            $OldMlSize = ($brandSize[0]['btl_size'] * $stockEntry[0]['no_btl']);
            if ($count > 0) {
                Stock::where(['company_id' => $request->company_id,  'brand_id' => $request->brand_id])->decrement('qty', $OldMlSize);
                //update stock
                Stock::where(['company_id' => $request->company_id,  'brand_id' => $request->brand_id])->increment('qty', $MlSize);
            } else {
                //Stock entry
                $stock = new Stock(array(
                    'company_id' => $request->company_id,
                    //'branch_id' => $request->branch_id,
                    'category_id' => $request->category_id,
                    'brand_id' => $request->brand_id,
                    'qty' => $MlSize,
                    'cost_price' => $request->mrp,
                ));
                $stock->save();
            }
            // logs
            $log_save = SaveLog([
                'user_type' => $request->user()->type,
                'user_id' => $request->user()->id,
                'ip' => $request->ip(),
                'log' => 'updated purchase entry with purchase id :' . $request->id,
                'platform' => 'web'
            ]);
            if ($log_save) {
                return response()->json([
                    'message' => 'TP Updated',
                    'type' => 'success'
                ], 201);
            } else {
                return response()->json([
                    'message' => 'Oops! Operation failed',
                    'type' => 'failed'
                ], 401);
            }
        } else {
            return response()->json([
                'message' => 'Oops! Operation failed',
                'type' => 'failed'
            ], 401);
        }
    }
    public function deleteTp(Request $request)
    {
        $data = $request->validate([
            'id' => 'required'
        ]);
        $stockEntry = Purchase::select('no_btl', 'company_id', 'brand_id')->where(['id' => $request->id])->get();
        if (count($stockEntry) > 0) {
            // check stock
            $brandSize = Brand::select('btl_size')->where('id', $stockEntry[0]['brand_id'])->get();
            $OldMlSize = ($brandSize[0]['btl_size'] * $stockEntry[0]['no_btl']);
            //update stock
            Stock::where(['company_id' => $stockEntry[0]['company_id'],  'brand_id' => $stockEntry[0]['brand_id']])->decrement('qty', $OldMlSize);
            $data = Purchase::where('id', $data['id'])->update(['status' => 0]);
            if ($data) {
                return response()->json([
                    'message' => 'Purchase deleted',
                    'type' => 'success'
                ], 201);
            }
        }
        return response()->json([
            'message' => 'Oops! operation failed!',
            'type' => 'failed'
        ]);
    }
    public function deleteSale(Request $request)
    {
        $data = $request->validate([
            'id' => 'required'
        ]);
        $stockEntry = Sales::select('qty', 'company_id', 'brand_id')->where(['id' => $request->id])->get();
        if (count($stockEntry) > 0) {
            // check stock
            $OldMlSize = $stockEntry[0]['qty'];
            //update stock
            Stock::where(['company_id' => $stockEntry[0]['company_id'],  'brand_id' => $stockEntry[0]['brand_id']])->increment('qty', $OldMlSize);
            $data = Sales::where('id', $data['id'])->update(['status' => 0]);
            if ($data) {
                return response()->json([
                    'message' => 'Sales deleted',
                    'type' => 'success'
                ], 201);
            }
        }
        return response()->json([
            'message' => 'Oops! operation failed!',
            'type' => 'failed'
        ]);
    }
    public function getBrandOptions(Request $request)
    {
        $brands = Brand::select('name as value', 'name as label', 'id')->where(['category_id' => $request->category_id, 'status' => 1])->get();
        if ($brands) {
            return response()->json($brands);
        } else {
            return response()->json([
                'message' => 'Oops! Operation failed',
                'type' => 'failed'
            ], 401);
        }
    }
    public function getAllBrandOption(Request $request)
    {
        $brands = Brand::select('name as value', 'name as label', 'id', 'category_id', DB::raw('0 as recipe'))->where(['status' => 1])->get();
        if ($brands) {
            return response()->json($brands);
        } else {
            return response()->json([
                'message' => 'Oops! Operation failed',
                'type' => 'failed'
            ], 401);
        }
    }
    public function getAllBrandSales(Request $request)
    {
        $dataArray = array();
        $brands = Brand::select('name as value', 'name as label', 'brands.id', 'brands.category_id', DB::raw('0 as recipe'))->join('stocks', 'stocks.brand_id', '=', 'brands.id')->where(['brands.status' => 1, 'stocks.company_id' => $request->company_id])->get();
        if ($brands) {
            foreach ($brands as $brand) {
                array_push($dataArray, $brand);
            }
            $data = Recipe::select('name as value', 'name as label', 'id', 'category_id', 'brand_id', 'serving_size', DB::raw('1 as recipe'))->where(['is_cocktail' => 0, 'company_id' => $request->company_id])->get();
            foreach ($data as $brand) {
                array_push($dataArray, $brand);
            }
            return response()->json($dataArray);
        } else {
            return response()->json([
                'message' => 'Oops! Operation failed',
                'type' => 'failed'
            ], 401);
        }
    }
    public function getAllMenuOption(Request $request)
    {
        $dataArray = array();
        $brands = Brand::select('name as value', 'name as label', 'brands.id', 'brands.category_id', DB::raw('0 as recipe'))->join('stocks', 'stocks.brand_id', '=', 'brands.id')->where(['brands.status' => 1, 'stocks.company_id' => $request->company_id])->get();
        if ($brands) {
            foreach ($brands as $brand) {
                array_push($dataArray, $brand);
            }
            $data = Recipe::select('name as value', 'name as label', 'id', 'category_id', 'brand_id', 'serving_size', DB::raw('1 as recipe'))->where(['company_id' => $request->company_id, 'status' => 1])->get();
            foreach ($data as $brand) {
                array_push($dataArray, $brand);
            }
            return response()->json($dataArray);
        } else {
            return response()->json([
                'message' => 'Oops! Operation failed',
                'type' => 'failed'
            ], 401);
        }
    }
    //getSales
    public function getSales()
    {
        $data = Sales::where('status', 1)->get();
        //  echo "<pre>";print_r($data);exit();

        if ($data) {
            return response()->json($data);
        } else {
            return response()->json([
                'message' => 'Oops! operation failed!',
                'type' => 'failed'
            ]);
        }
    }
    // update sales
    public function updateSales(Request $request)
    {
        $data = $request->validate([
            'brand_id' => 'required',
            'no_btl' => 'required',
            'no_peg' => 'required',
            'sales_type' => 'required',
        ]);
        $brandSize = Brand::select('btl_size', 'peg_size')->where('id', $data['brand_id'])->get();
        if ($brandSize) {
            $btl = $brandSize[0]['btl_size'] * $data['no_btl'];
            $peg = $brandSize[0]['peg_size'] * $data['no_peg'];
            $qty = $btl + $peg;
        }
        $fetch = Sales::where('id', $request->id)->get()->first();
        if (Sales::where('id', $request->id)->update($data)) {
            Stock::where(['company_id' => $fetch['company_id'], 'brand_id' => $fetch['brand_id']])->increment('qty', $fetch['qty']);
            Stock::where(['company_id' => $fetch['company_id'], 'brand_id' => $fetch['brand_id']])->decrement('qty', $qty);
            return response()->json([
                'message' => 'Sales Updated',
                'type' => 'success'
            ], 201);
        } else {
            return response()->json([
                'message' => 'Oops! operation failed!',
                'type' => 'failed'
            ]);
        }
    }
    //getRecipe
    public function getRecipe()
    {
        $data = Recipe::where('status', 1)->get();
        if ($data) {
            return response()->json($data);
        } else {
            return response()->json([
                'message' => 'Oops! operation failed!',
                'type' => 'failed'
            ]);
        }
    }

    //getRecipe
    public function getRecipeOptions(Request $request)
    {
        $input = $request->validate([
            'company_id' => 'required',
            // 'branch_id' => 'required',
        ]);

        $datas = Recipe::select('recipe_code', 'name')->where(['company_id' => $input['company_id'], 'status' => 1])->get();
        $res = [];
        $checker = [];
        $i = 0;
        foreach ($datas as $key => $data) {
            if (!in_array($data['recipe_code'], $checker)) {
                $res[$i]['recipe_code'] = $data['recipe_code'];
                $checker[] = $data['recipe_code'];
                $res[$i]['value'] = $data['name'];
                $res[$i]['label'] = $data['name'];
                $i++;
            }
        }
        if ($res) {
            return response()->json($res);
        } else {
            return response()->json([
                'message' => 'No recipe found!',
                'type' => 'failed'
            ]);
        }
    }

    public function getMenuOptions(Request $request)
    {
        $input = $request->validate([
            'company_id' => 'required',
            'isLink' => 'required',
        ]);
        if ($input['isLink'] == 1)
            $datas = Recipe::select('id', 'recipe_code', 'name', 'is_cocktail')->where(['company_id' => $input['company_id'], ['brand_id', '>', '0'], 'status' => 1])->get();
        else
            $datas = Recipe::select('id', 'recipe_code', 'name', 'is_cocktail')->where(['company_id' => $input['company_id'], ['brand_id', '=', '0'], 'status' => 1])->get();
        $res = [];
        $checker = [];
        $i = 0;
        foreach ($datas as $key => $data) {
            if (!in_array($data['recipe_code'], $checker)) {
                $res[$i]['recipe_code'] = $data['recipe_code'];
                $checker[] = $data['recipe_code'];
                $res[$i]['value'] = $data['name'];
                $res[$i]['label'] = $data['name'];
                $res[$i]['is_cocktail'] = $data['is_cocktail'];
                $i++;
            }
        }
        if ($res) {
            return response()->json($res);
        } else {
            return response()->json([
                'message' => 'No recipe found!',
                'type' => 'failed'
            ]);
        }
    }
    public function recipeFetchApi(Request $request)
    {
        $input = $request->validate([
            'company_id' => 'required',
            'isLink' => 'required',
        ]);
        $key = $request->keyword;
        if ($input['isLink'] == 1)
            $datas = Recipe::select('id', 'recipe_code', 'name', 'is_cocktail')->where(['company_id' => $input['company_id'], ['brand_id', '>', '0'], ['name', 'like', '%' . $key . '%'], 'status' => 1])->get();
        else
            $datas = Recipe::select('id', 'recipe_code', 'name', 'is_cocktail')->where(['company_id' => $input['company_id'], ['brand_id', '=', '0'], ['name', 'like', '%' . $key . '%'], 'status' => 1])->get();
        $res = [];
        $checker = [];
        $i = 0;
        foreach ($datas as $key => $data) {
            if (!in_array($data['recipe_code'], $checker)) {
                $res[$i]['recipe_code'] = $data['recipe_code'];
                $checker[] = $data['recipe_code'];
                $res[$i]['value'] = $data['name'];
                $res[$i]['label'] = $data['name'];
                $res[$i]['is_cocktail'] = $data['is_cocktail'];
                $i++;
            }
        }
        if ($res) {
            return response()->json($res);
        } else {
            return response()->json([
                'message' => 'No recipe found!',
                'type' => 'failed'
            ]);
        }
    }
    public function change_password(Request $request)
    {
        $data = $request->validate([
            'password' => 'required|string',
            'c_password' => 'required|string|same:password',
        ]);
        $data['password'] = bcrypt($data['password']);
        $data_update = User::where('id', $request->user()->id)->update(['password' => $data['password']]);
        if ($data_update) {
            return response()->json([
                'message' => 'Password Change Successfully',
                'type' => 'success'
            ], 201);
        } else {
            return response()->json([
                'message' => 'Oops! operation failed!',
                'type' => 'failed'
            ]);
        }
    }
    public function getLogs(Request $request)
    {
        $db = DB::table('logs')->select('log');
        if (!empty($request->keyword))
            $db->where('log', 'like', '%' . $request->keyword . '%');
        if ($request->user !== 0)
            $db->where('user_id', $request->user_id);
        $data = $db->get();
        dd($data);
        if ($data) {
            return response()->json($data);
        } else {
            return response()->json([
                'message' => 'Oops! operation failed!',
                'type' => 'failed'
            ]);
        }
    }
    public function updateCompany(Request $request)
    {
        $data = $request->validate([
            'company_id' => 'required',
            'company' => 'required|string',
            'address' => 'required|string',
            'city' => 'required|string',
            'pincode' => 'required',
        ]);
        $data['license_name'] = $request->license_name;
        $data['license_no'] = $request->license_no;
        $data['pan_no'] = $request->pan_no;
        $data['gst_no'] = $request->gst_no;
        $update = Company::where('id', $data['company_id'])->update(['name' => $data['company'], 'license_name' => $data['license_name'], 'license_no' => $data['license_no'], 'pan_no' => $data['pan_no'], 'gst_no' => $data['gst_no'], 'address' => $data['address'], 'city' => $data['city'], 'pincode' => $data['pincode']]);
        if ($update) {
            return response()->json([
                'message' => 'Company updated',
                'type' => 'success'
            ], 201);
        } else {
            return response()->json([
                'message' => 'Oops! operation failed!',
                'type' => 'failed'
            ]);
        }
    }
    public function getStockApi(Request $request)
    {
        $req = $request->validate([
            'company_id' => 'required',
            // 'branch_id' => 'required',
            'brand_id' => 'required',
        ]);
        $data = Stock::where(['company_id' => $req['company_id'], 'brand_id' => $req['brand_id']])->get();
        $result = getBtlPeg($req['brand_id'], $data[0]['qty']);
        $data[0]['btl'] = $result['btl'];
        $data[0]['peg'] = intval($result['peg']);
        $data[0]['btl_size'] = intval($result['btl_size']);
        //physical stock
        // $p_btl = 0;
        // while ($physical_qty > $brand_size) {
        //     $physical_qty = $physical_qty - $brand_size;
        //     $p_btl++;
        // }
        // $physical_peg = $physical_qty / $brandSize[0]['peg_size'];
        // $data[0]['physical_btl'] = $p_btl;
        // $data[0]['physical_peg'] = intval($physical_peg);
        if ($data) {
            return response()->json($data);
        } else {
            return response()->json([
                'message' => 'Oops! operation failed!',
                'type' => 'failed'
            ]);
        }
        return response()->json([
            'message' => 'Oops! Not in stock!',
            'type' => 'failed'
        ]);
    }
    public function bulkStockImport(Request $request)
    {
        $dataArray = $request->data;
        $company_id = $dataArray[0]['company_id'];
        // $branch_id = $dataArray[0]['branch_id'];
        $isSaved = false;
        foreach ($dataArray as $dataArr) {
            $brandName = rtrim(preg_replace("/[^\W\d]*\d\w*/", " ", $dataArr['brand']));
            $total = explode('.', $dataArr['total']);
            $btl = intval($total[0]);
            $peg = intval($total[1]);
            $data['company_id'] = $company_id;
            //$data['branch_id'] = $branch_id;
            $brandSize = Brand::select('id', 'category_id', 'btl_size', 'peg_size')->where([['name', 'like', '%' . $brandName . '%'], 'btl_size' => $dataArr['ml']])->get();
            if (count($brandSize) > 0) {
                $count = Stock::where(['company_id' => $company_id, 'brand_id' => $brandSize[0]['id']])->get()->count();
                $MlSize = ($brandSize[0]['btl_size'] * $btl) + ($brandSize[0]['peg_size'] * $peg);
                $data['category_id'] = $brandSize[0]['category_id'];
                $data['brand_id'] = $brandSize[0]['id'];
                if ($count > 0) {
                    //update stock
                    Stock::where(['company_id' => $company_id, 'brand_id' => $brandSize[0]['id']])->update(['qty' => $MlSize]);
                } else {
                    $data['qty'] = $MlSize;
                    //Stock entry
                    // $data['physical_closing'] = $MlSize;
                    $manage_stock = new Stock($data);
                    $manage_stock->save();
                }
                $isSaved = true;
            }
        }
        if ($isSaved) {
            $data_log = [
                'user_type' => $request->user()->type,
                'user_id' => $request->user()->id,
                'ip' => $request->ip(),
                'log' => 'Stock created',
                'platform' => 'web'
            ];
            SaveLog($data_log);
            return response()->json([
                'message' => 'Stock Updated',
                'type' => 'success'
            ], 201);
        } else {
            return response()->json([
                'message' => 'Oops! Operation failed',
                'type' => 'failed'
            ], 401);
        }
    }
    public function bulkPurchaseImport(Request $request)
    {
        $dataArray = $request->data;
        $company_id = $dataArray[0]['company_id'];
        //  $branch_id = $dataArray[0]['branch_id'];
        $isSaved = false;
        $counter = 0;
        $skipped = 0;

        foreach ($dataArray as $dataArr) {
            $brandName = rtrim(preg_replace("/[^\W\d]*\d\w*/", " ", $dataArr['brand']));
            $btl = intval($dataArr['total']);
            $data['invoice_no'] = $dataArr['invoiceNo'];
            $data['invoice_date'] = date('Y-m-d', strtotime($dataArr['date']));
            $supplier = Supplier::select('id')->where([['name', 'like', '%' . $dataArr['supplier'] . '%'], 'company_id' => $company_id])->get();
            $data['vendor_id'] = $supplier[0]['id'];
            $data['company_id'] = $company_id;
            // $data['branch_id'] = $branch_id;
            $brandSize = Brand::select('id', 'category_id', 'btl_size', 'peg_size')->where([['name', 'like', '%' . $brandName . '%']])->get();
            if (count($brandSize) < 1) {
                $skipped++;
                continue;
            }
            $data['created_by'] = $request->user()->id;
            $data['category_id'] = $brandSize[0]['category_id'];
            $data['brand_id'] = $brandSize[0]['id'];
            $data['no_btl'] = $btl; //number of btl
            $MlSize = ($brandSize[0]['btl_size'] * $data['no_btl']);
            $data['qty'] = $MlSize;
            $save = new purchase($data);
            if ($save->save()) {
                $counter++;
                // check stock
                $count = Stock::where(['company_id' => $company_id,  'brand_id' => $brandSize[0]['id']])->get()->count();

                if ($count > 0) {
                    //update stock
                    Stock::where(['company_id' => $company_id, 'brand_id' => $brandSize[0]['id']])->increment('qty', $MlSize);
                } else {
                    //Stock entry
                    $stock = new Stock(array(
                        'company_id' => $company_id,
                        //'branch_id' => $branch_id,
                        'category_id' => $brandSize[0]['category_id'],
                        'brand_id' => $brandSize[0]['id'],
                        'qty' => $MlSize,
                    ));
                    $stock->save();
                }
                $isSaved = true;
            }
        }
        if ($isSaved) {
            $data_log = [
                'user_type' => $request->user()->type,
                'user_id' => $request->user()->id,
                'ip' => $request->ip(),
                'log' => 'Tp updated',
                'platform' => 'web'
            ];
            SaveLog($data_log);
            return response()->json([
                'message' => $counter . ' Tp added, ' . $skipped . ' failed',
                'type' => 'success'
            ], 201);
        } else {
            return response()->json([
                'message' => 'Oops! Operation failed',
                'type' => 'failed'
            ], 401);
        }
    }
    public function bulkSalesImport(Request $request)
    {
        error_reporting(0);
        $dataArray = $request->data;
        $counter = 0;
        $skipped = 0;
        $data['company_id'] = $dataArray[0]['company_id'];
        foreach ($dataArray as $key => $dataAr) {
            $name = $dataAr['name'];
            $success = false;
            $isCocktail = false;
            $brands = Brand::select('id as brand_id', 'category_id')->where(['name' => $name, 'status' => 1])->get();
            if (count($brands) < 1) {
                $brands = Recipe::select('recipe_code', 'brand_id', 'serving_size', 'category_id', 'is_cocktail')->where(['name' => $name, 'company_id' => $data['company_id'], 'status' => 1])->get();
                if (count($brands) < 1) {
                    $skipped++;
                    continue; // if no matching brand or recipe found then skip to next entry
                } else
                    $isCocktail = true;
            }
            foreach ($brands as $brand) {

                $brand_id = $brand['brand_id'];
                $data['category_id'] = $brand['category_id'];
                $data['brand_id'] = $brand['brand_id'];
                $peg_size = Brand::select('peg_size', 'btl_size')->where(['id' => $brand_id])->get();
                $stock = Stock::select('id', 'btl_selling_price', 'peg_selling_price')->where(['company_id' => $data['company_id'],  'brand_id' => $brand_id])->get();
                if (count($stock) > 0) {
                    $data['created_by'] = $request->user()->id;
                    $data['description'] = $brand_id . ' brand sales entry has been done from bulk import by ' . $request->user()->id;
                    if ($isCocktail) {
                        $qty = ($brand['serving_size'] * $dataAr['sale']) / $peg_size[0]['peg_size'];
                        $data['sale_price'] = ($qty * $stock[0]['peg_selling_price']);
                        $MlSize = ($brand['serving_size'] * $dataAr['sale']);
                        $data['qty'] = $MlSize;
                        $data['sales_type'] = 1;
                        // get sale quantity in ml
                        $result = getBtlPeg($brand_id, $MlSize);
                        $data['no_btl'] = $result['btl'];
                        $data['no_peg'] = $result['peg'];
                        $Sales = new Sales($data);
                        if ($Sales->save()) {
                            $success = true;
                            if ($dataAr['complimentary'] > 0) {
                                $data['sale'] = $dataAr['complimentary'];
                                // calculate qty for complimentary
                                $qty = ($brand['serving_size'] * $data['sale']) / $peg_size[0]['peg_size'];

                                $data['sale_price'] = ($qty * $stock[0]['peg_selling_price']);
                                $MlSize1 = ($brand['serving_size'] * $data['sale']);
                                $data['qty'] = $MlSize1;
                                $data['sales_type'] = 2;
                                $result = getBtlPeg($brand_id, $MlSize1);
                                $data['no_btl'] = $result['btl'];
                                $data['no_peg'] = $result['peg'];
                                $Sales = new Sales($data);
                                $Sales->save();
                            }
                            if ($dataAr['combo'] > 0) {
                                $data['sale'] = $dataAr['combo'];
                                // calculate qty for combo
                                $qty = ($brand['serving_size'] * $data['sale']) / $peg_size[0]['peg_size'];
                                $data['sale_price'] = ($qty * $stock[0]['peg_selling_price']);
                                $MlSize2 = ($brand['serving_size'] * $data['sale']);
                                $data['qty'] = $MlSize2;
                                $data['sales_type'] = 3;
                                $result = getBtlPeg($brand_id, $MlSize2);
                                $data['no_btl'] = $result['btl'];
                                $data['no_peg'] = $result['peg'];
                                $Sales = new Sales($data);
                                $Sales->save();
                            }
                        }
                    } else {
                        $data['sale_price'] = ($dataAr['sale'] * $stock[0]['btl_selling_price']);
                        $MlSize = ($peg_size[0]['btl_size'] * $dataAr['sale']);
                        $data['qty'] = $MlSize;
                        $data['sales_type'] = 1;
                        $success = true;
                        $result = getBtlPeg($brand_id, $MlSize);
                        $data['no_btl'] = $result['btl'];
                        $data['no_peg'] = $result['peg'];
                        $Sales = new Sales($data);
                        if ($Sales->save()) {
                            if ($dataAr['complimentary'] > 0) {
                                $data['sale'] = $dataAr['complimentary'];
                                // calculate qty for complimentary
                                $qty = ($brand['serving_size'] * $dataAr['sale']) / $peg_size[0]['peg_size'];

                                $data['sale_price'] = ($qty * $stock[0]['peg_selling_price']);
                                $MlSize1 = ($brand['serving_size'] * $dataAr['sale']);
                                $data['qty'] = $MlSize1;
                                $data['sales_type'] = 2;
                                $result = getBtlPeg($brand_id, $MlSize1);
                                $data['no_btl'] = $result['btl'];
                                $data['no_peg'] = $result['peg'];
                                $Sales = new Sales($data);
                                $Sales->save();
                            }
                            if ($dataAr['combo'] > 0) {
                                $data['sale'] = $dataAr['combo'];
                                // calculate qty for combo
                                $qty = ($brand['serving_size'] * $dataAr['sale']) / $peg_size[0]['peg_size'];
                                $data['sale_price'] = ($qty * $stock[0]['peg_selling_price']);
                                $MlSize2 = ($brand['serving_size'] * $dataAr['sale']);
                                $data['qty'] = $MlSize2;
                                $data['sales_type'] = 3;
                                $result = getBtlPeg($brand_id, $MlSize2);
                                $data['no_btl'] = $result['btl'];
                                $data['no_peg'] = $result['peg'];
                                $Sales = new Sales($data);
                                $Sales->save();
                            }
                        }
                    }
                    $total_qty_sold = $MlSize + $MlSize1 + $MlSize2;
                    if ($success) {
                        //update stocks
                        Stock::where(['company_id' => $data['company_id'],  'brand_id' => $brand_id])->decrement('qty', $total_qty_sold);
                        $MlSize + $MlSize1 + $MlSize2;
                        unset($MlSize, $MlSize1, $MlSize2, $total_qty_sold);
                        // logs
                        $data_log = [
                            'user_type' => $request->user()->type,
                            'user_id' => $request->user()->id,
                            'ip' => $request->ip(),
                            'log' => 'Sales created',
                            'platform' => 'web'
                        ];

                        SaveLog($data_log);
                        $counter++; // counter for sales entry
                    } else {
                        $skipped++; // counter for error in entry
                    }
                }
            }
        }
        if ($counter > 0) {
            return response()->json([
                'message' => $counter . ' Sales Added, ' . $skipped . 'Entries failed',
                'type' => 'success'
            ], 201);
        } else {
            return response()->json([
                'message' => 'Oops! Operation failed',
                'type' => 'failed'
            ], 401);
        }
    }
    public function bulkImportRecipes(Request $request)
    {
        $dataArray = ($request->data);
        foreach ($dataArray as $key => $dataAr) {
            $name = $dataAr['name'];
            $serving_size = $dataAr['serving_size'];
            $isSaved = false;
            do {
                // generate unique recipe_code code
                $recipe_code = $dataArray[0]['company_id'] . rand(11111, 99999);
                $count = Recipe::where(['recipe_code' => $recipe_code])->get()->count();
            } while ($count > 0);

            $data['name'] = $name;
            $data['serving_size'] = $serving_size;
            $data['company_id'] = $dataArray[0]['company_id'];
            $data['category_id'] = 0;
            $data['recipe_code'] = $recipe_code;
            $data['created_by'] = $request->user()->id;
            $Recipe = new Recipe($data);
            if ($Recipe->save())
                $isSaved = true;
        }
        if ($isSaved) {
            $data_log = [
                'user_type' => $request->user()->type,
                'user_id' => $request->user()->id,
                'ip' => $request->ip(),
                'log' =>  $request->name . ' Recipe created',
                'platform' => 'web'
            ];
            $log_save = SaveLog($data_log);
            if (($log_save)) {
                return response()->json([
                    'message' => 'Recipe Added',
                    'type' => 'success'
                ], 201);
            } else {
                return response()->json([
                    'message' => 'Oops! Operation failed',
                    'type' => 'failed'
                ], 401);
            }
        } else {

            return response()->json([
                'message' => 'Oops! Operation failed',
                'type' => 'failed'
            ], 401);
        }
    }

    //getSales
    public function getSalesList(Request $request)
    {
        $data = Sales::select('brands.name', 'brands.id as brand_id', 'sales.sales_type as type', 'sales.no_btl', 'sales.qty', 'sales.no_peg', 'sales.created_at', 'sales.id')->join('brands', 'brands.id', '=', 'sales.brand_id')->where(['sales.company_id' => $request->company_id, 'sales.status' => 1])->orderBy('id', 'DESC')->get();
        if ($data) {
            return response()->json($data);
        } else {
            return response()->json([
                'message' => 'Oops! operation failed!',
                'type' => 'failed'
            ]);
        }
    }
    public function getPurchase(Request $request)
    {
        if ($request->isInvoice == 0)
            $data = Purchase::select('brands.name', 'no_btl', 'purchases.invoice_no', 'purchases.created_at', 'purchases.id')->join('brands', 'brands.id', '=', 'purchases.brand_id')->where(['purchases.status' => 1, 'purchases.mrp' => null, 'purchases.company_id' => $request->company_id])->orderBy('id', 'DESC')->get();
        else
            $data = Purchase::select('brands.name', 'no_btl', 'purchases.invoice_no', 'purchases.created_at', 'purchases.id')->join('brands', 'brands.id', '=', 'purchases.brand_id')->where(['purchases.status' => 1, ['purchases.mrp', '>=', 0], 'purchases.company_id' => $request->company_id])->orderBy('id', 'DESC')->get();
        if ($data) {
            return response()->json($data);
        } else {
            return response()->json([
                'message' => 'Oops! operation failed!',
                'type' => 'failed'
            ]);
        }
    }
    public function recipeDetails(Request $request)
    {
        $isLinked = Recipe::select('id', 'brand_id', 'recipe_code', 'name as recipe', 'is_cocktail', 'serving_size')->where(['recipes.status' => 1, 'recipe_code' => $request->recipe_code])->first();
        if ($isLinked->brand_id == 0)
            return response()->json([$isLinked]);

        $data = Recipe::select('recipes.id as id', 'brands.name', 'recipes.brand_id', 'recipes.name as recipe', 'recipes.serving_size')->join('brands', 'brands.id', '=', 'recipes.brand_id')->where(['recipes.status' => 1, 'recipe_code' => $request->recipe_code])->get();
        if ($data) {
            return response()->json($data);
        } else {
            return response()->json([
                'message' => 'Oops! operation failed!',
                'type' => 'failed'
            ]);
        }
    }

    public function updateRecipes(Request $request)
    {
        $data = $request->validate([
            'name' => 'required|string',
            'company_id' => 'required',
            'recipe_code' => 'required',
        ]);
        $brand = explode(',', $request->brand_id);
        $serving_size = explode(',', $request->serving_size);
        $B_Id = explode(',', $request->id);
        $isSaved = false;
        foreach ($brand as $key => $id) {
            $data['brand_id'] = $id;
            $category = Brand::select('category_id')->where('id', $id)->first();
            $data['category_id'] = $category->category_id;
            $data['serving_size'] = $serving_size[$key];
            $data['created_by'] = $request->user()->id;
            $recipeId = $B_Id[$key];
            if ($recipeId > 0) {
                if (Recipe::where(['id' => $recipeId])->update($data))
                    $isSaved = true;
            } else {
                $save = new Recipe($data);
                if ($save->save())
                    $isSaved = true;
            }
        }
        if ($isSaved) {
            $data_log = [
                'user_type' => $request->user()->type,
                'user_id' => $request->user()->id,
                'ip' => $request->ip(),
                'log' =>  $request->recipe_code . ' Recipe updated',
                'platform' => 'web'
            ];
            $log_save = SaveLog($data_log);
            if (($log_save)) {
                return response()->json([
                    'message' => 'Recipe Added',
                    'type' => 'success'
                ], 201);
            } else {
                return response()->json([
                    'message' => 'Oops! Operation failed',
                    'type' => 'failed'
                ], 401);
            }
        } else {

            return response()->json([
                'message' => 'Oops! Operation failed',
                'type' => 'failed'
            ], 401);
        }
    }
    public function updateNonCocktail(Request $request)
    {
        $data = $request->validate([
            'name' => 'required|string',
            'company_id' => 'required',
            'recipe_code' => 'required',
        ]);
        $brand = $request->brand;
        $isSaved = false;
        $data['brand_id'] = $brand;
        $data['name'] = $request->name;
        $category = Brand::select('category_id')->where('id', $brand)->first();
        $data['category_id'] = $category->category_id;
        $data['serving_size'] =  $request->serving_size;
        $data['created_by'] = $request->user()->id;
        $data['is_cocktail'] = 0;
        $recipeId = $request->id;
        if ($recipeId > 0) {
            if (Recipe::where(['id' => $recipeId])->update($data))
                $isSaved = true;
        } else {
            $save = new Recipe($data);
            if ($save->save())
                $isSaved = true;
        }
        if ($isSaved) {
            $data_log = [
                'user_type' => $request->user()->type,
                'user_id' => $request->user()->id,
                'ip' => $request->ip(),
                'log' =>  $request->recipe_code . ' Recipe updated',
                'platform' => 'web'
            ];
            $log_save = SaveLog($data_log);
            if (($log_save)) {
                return response()->json([
                    'message' => 'Recipe Added',
                    'type' => 'success'
                ], 201);
            } else {
                return response()->json([
                    'message' => 'Oops! Operation failed',
                    'type' => 'failed'
                ], 401);
            }
        } else {

            return response()->json([
                'message' => 'Oops! Operation failed',
                'type' => 'failed'
            ], 401);
        }
    }
    public function getTopSalesList(Request $request)
    {
        $data = DB::table("sales")
            ->select('brands.name', DB::raw("COUNT(sales.brand_id) as count_row"), DB::raw("sum(sales.qty) as total"))
            ->leftJoin('brands', 'brands.id', '=', 'sales.brand_id')
            ->where('sales.company_id', '=', $request->company_id)
            ->orderBy("total", 'DESC')
            ->groupBy(DB::raw("brand_id"))
            ->get();

        //$data = Sales::select('brands.name','COUNT(sales.brand_id) AS cn','brands.id')->join('brands','brands.id','=','sales.brand_id')->where(['sales.company_id'=>$request->company_id])->groupBy('sales.brand_id')->get();
        //echo "<pre>";print_r($data);exit();

        if ($data) {
            return response()->json($data);
        } else {
            return response()->json([
                'message' => 'Oops! operation failed!',
                'type' => 'failed'
            ]);
        }
    }
    public function fetchPurchaseData(Request $request)
    {
        $data = Purchase::select('brands.name', 'purchases.*')->join('brands', 'brands.id', '=', 'purchases.brand_id')->where(['purchases.status' => 1, 'purchases.id' => $request->id])->orderBy('id', 'DESC')->get();
        if ($data) {
            return response()->json($data);
        } else {
            return response()->json([
                'message' => 'Oops! operation failed!',
                'type' => 'failed'
            ]);
        }
    }
    public function dashboard(Request $request)
    {
        $data = Sales::select('sale_price', DB::raw('date(created_at) as date'))->where(['company_id' => $request->company_id, 'status' => 1])->whereRaw('created_at > DATE_SUB(NOW(), INTERVAL 30 DAY)')->groupBy('created_at')->orderBy('created_at', 'ASC')->get();
        $sale = [];
        $date = [];
        foreach ($data as $row) {
            $sale[] = $row['sale_price'];
            $date[] = $row['date'];
        }
        $res['data'] = $sale;
        $res['series'] = $date;
        if ($data) {
            return response()->json($res);
        } else {
            return response()->json([
                'message' => 'Oops! operation failed!',
                'type' => 'failed'
            ]);
        }
    }
    public function BarVarianceReport(Request $request)
    {



        // $Category = Category::where(['company_id' => $company_id])->get();
        $Category = Category::select('id', 'name')->get();
        $data = [];
        foreach ($Category as $key => $Category_data) {

            $brands_data = DB::table("brands")
                ->select('btl_size', 'category_id')
                ->where('category_id', '=', $Category_data['id'])
                ->groupBy(DB::raw("btl_size"))
                ->get();

            foreach ($brands_data as $key2 => $brandList) {
                $brand_size = $brandList->btl_size;
                $data_cat = $Category_data['name'] . "-" . $brand_size;

                $data[$key][$key2]['cat_name'] = $data_cat;


                $brandName_Data = Brand::where(['category_id' => $brandList->category_id, 'btl_size' => $brand_size])->get();
                $total = '0';
                $arr = [];
                foreach ($brandName_Data as $key1 => $brandListName) {

                    //$data[$key][$key2]['brand'][] = $brandListName['name'];
                    $arr['name'] = $brandListName['name'];

                    $data_daily_opening = DB::table("daily_opening")
                        ->select('qty')
                        ->where('company_id', '=', $request->company_id)
                        ->where('date', '=', $request->from_date)
                        ->where('brand_id', '=', $brandListName['id'])
                        ->get();


                    $balance = DB::table('purchases')->where(['brand_id' => $brandListName['id'], 'company_id' => $request->company_id])->whereBetween('created_at', [$request->from_date, date('Y-m-d')])->sum('qty');




                    $transactions_in = DB::table('transactions')->where(['brand_id' => $brandListName['id'], 'company_id' => $request->company_id])->whereBetween('created_at', [$request->from_date, date('Y-m-d')])->sum('qty');


                    $transactions_out = DB::table('transactions')->where(['brand_id' => $brandListName['id'], 'company_to_id' => $request->company_id])->whereBetween('created_at', [$request->from_date, date('Y-m-d')])->sum('qty');



                    $qty = !empty($data_daily_opening[0]->qty) ? $data_daily_opening[0]->qty : '0';



                    $transaction = '0';
                    if (!empty($transactions_in) && !empty($transactions_out)) {

                        $transaction = ($transactions_in - $transactions_out);
                        //$data[$key][$key2]['brand'][]['transaction'] = $transaction;

                    } else {
                        $transaction = '0';
                        //$data[$key][$key2]['brand'][]['transaction'] = '0';
                    }

                    //$data[$key][$key2]['brand'][]['transaction_ml'] = $transaction;


                    $total = (($qty) + ($balance) + ($transaction));



                    //$data[$key][$key2]['brand'][]['total_ml'] = $total;

                    //$data[$key][$key2]['brand'][]['total'] = (($qty) + ($balance) + ($transaction));

                    $sales = DB::table('sales')->where(['brand_id' => $brandListName['id'], 'company_id' => $request->company_id, 'sales_type' => '1'])->whereBetween('created_at', [$request->from_date, date('Y-m-d')])->sum('qty');

                    //$data[$key][$key2]['brand'][]['sales_ml'] = $sales;

                    $closing = ($total - $sales);
                    $brand_size = $brandListName['btl_size'];


                    $btl_opening = 0;
                    while ($qty >= $brand_size) {
                        $qty = $qty - $brand_size;
                        $btl_opening++;
                    }
                    $peg_opening = intval($qty / $brandListName['peg_size']);

                    //$data[$key][$key2]['brand'][]['open'] = $btl_opening.".".$peg_opening;
                    $arr['open'] = $btl_opening . "." . $peg_opening;


                    //receipt 
                    $btl_receipt  = 0;
                    while ($balance >= $brand_size) {
                        $balance = $balance - $brand_size;
                        $btl_receipt++;
                    }
                    $peg_receipt  = intval($balance / $brandListName['peg_size']);

                    //$data[$key][$key2]['brand'][]['receipt'] = $btl_receipt.".".$peg_receipt;
                    $arr['recipt'] = $btl_receipt . "." . $peg_receipt;

                    //transaction
                    $btl_transaction  = 0;
                    while ($transaction >= $brand_size) {
                        $transaction = $transaction - $brand_size;
                        $btl_transaction++;
                    }
                    $peg_transaction  = intval($transaction / $brandListName['peg_size']);

                    //$data[$key][$key2]['brand'][]['transaction'] = $btl_transaction.".".$peg_transaction;
                    $arr['transaction'] = $btl_transaction . "." . $peg_transaction;

                    //total

                    $btl_total  = 0;
                    while ($total >= $brand_size) {
                        $total = $total - $brand_size;
                        $btl_total++;
                    }
                    $peg_total  = intval($total / $brandListName['peg_size']);

                    //echo "<pre>";print_r($btl_total);
                    $arr['total'] = $btl_total . "." . $peg_total;
                    //$data[$key][$key2]['brand'][]['total'] = $btl_total.".".$peg_total;

                    //sales
                    //echo "<pre>";print_r($sales);
                    $btl_sales  = 0;
                    while ($sales >= $brand_size) {
                        $sales = $sales - $brand_size;
                        $btl_sales++;
                    }
                    $peg_sales  = intval($sales / $brandListName['peg_size']);
                    $arr['sales'] = $btl_sales . "." . $peg_sales;
                    //$data[$key][$key2]['brand'][]['sales'] = $btl_sales.".".$peg_sales;

                    //closing
                    //echo "<pre>";print_r($closing);

                    $btl_closing  = 0;
                    while ($closing >= $brand_size) {
                        $closing = $closing - $brand_size;
                        $btl_closing++;
                    }
                    $peg_closing  = intval($closing / $brandListName['peg_size']);

                    $arr['closing'] = $btl_closing . "." . $peg_closing;
                    //$data[$key][$key2]['brand'][]['closing'] = $btl_closing.".".$peg_closing;
                    //echo "<pre>";print_r($arr);
                    //$data[$key][$key2]['brand'][]['final_data'] = $arr;
                    $data[$key][$key2]['brand'][] = $arr;
                }
            }
        }
        echo json_encode($data);
    }

    public function AddTransaction(Request $request)
    {
        //echo "<pre>";print_r($request);
        $data = $request->validate([
            'company_id' => 'required',
            'company_to_id' => 'required|string',
            'brand_id' => 'required|string',
            'qty' => 'required|string',
        ]);

        $data['created_by'] = $request->user()->id;
        // echo "<pre>";print_r($data);exit();
        $Transaction = new Transaction($data);



        if ($Transaction->save()) {
            // logs
            SaveLog([
                'user_type' => $request->user()->type,
                'user_id' => $request->user()->id,
                'ip' => $request->ip(),
                'log' => 'add transaction',
                'platform' => 'web'
            ]);
            return response()->json([
                'message' => 'TP Added',
                'type' => 'success'
            ], 201);
        } else {
            return response()->json([
                'message' => 'Oops! Operation failed',
                'type' => 'failed'
            ], 401);
        }
    }
    // fetch Tp search
    public function fetchOpeningData(Request $request)
    {
        $data = $request->validate([
            'company_id' => 'required'
        ]);
        if (!empty($request->keyword))
            $data = DailyOpening::select('brands.name', 'btl_size', 'peg_size', 'daily_openings.qty', 'daily_openings.date')->join('brands', 'brands.id', '=', 'daily_openings.brand_id')->where(['company_id' => $data['company_id'], ['brands.name', 'like', '%' . $request->keyword . '%']])->orderBy('daily_openings.id', 'DESC')->groupBy(DB::raw("daily_openings.brand_id"))->get();
        else
            $data = DailyOpening::select('brands.name', 'btl_size', 'peg_size', 'daily_openings.qty', 'daily_openings.date')->join('brands', 'brands.id', '=', 'daily_openings.brand_id')->where(['company_id' => $data['company_id']])->orderBy('daily_openings.id', 'DESC')->groupBy(DB::raw("daily_openings.brand_id"))->get();
        if ($data) {
            return response()->json($data);
        } else {
            return response()->json([
                'message' => 'Oops! operation failed!',
                'type' => 'failed'
            ]);
        }
    }
}
