<?php

namespace App\Http\Controllers;

use DateTime;
use App\Models\branch;
use App\Models\Brand;
use App\Models\physical_history;
use App\Models\PurchaseList;
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
use App\Models\Subcategory;
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
    public function bulkImportCategory(Request $request)
    {
        $dataArray = $request->data;
        $success = 0;
        $fail = 0;
        $failedData = [];
        foreach ($dataArray as $dataArr) {
            $category = Category::select('id')->where([['name', 'like', '%' . $dataArr['name'] . '%'], 'status' => 1])->get()->count();
            if ($category == 0) {
                $data['name'] = $dataArr['name'];
                $data['short_name'] = $dataArr['short_name'];
                $data['created_by'] = $request->user()->id;
                $brand = new Category($data);
                if ($brand->save())
                    $success++;
                else {
                    array_push($failedData, $dataArr['name']);
                    $fail++;
                }
            } else {
                array_push($failedData, $dataArr['name']);
                $fail++;
            }
        }
        $data_log = [
            'user_type' => $request->user()->type,
            'user_id' => $request->user()->id,
            'ip' => $request->ip(),
            'log' => 'bulk category imported' . $success . ' category added, ' . $fail . ' failed',
            'platform' => 'web'
        ];
        SaveLog($data_log);
        return [
            'message' => $success . ' category added, ' . $fail . ' failed',
            'type' => 'success',
            'CATEGORY' => $failedData
        ];
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
            // 'code' => 'required',
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
            $data['subcategory_id'] = $request->type_id;
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
    public function bulkImportBrand(Request $request)
    {
        $dataArray = $request->data;
        $success = 0;
        $fail = 0;
        $failedData = [];
        foreach ($dataArray as $dataArr) {
            $brand_id = Brand::select('id')->where([['name', 'like', '%' . $dataArr['brand_name'] . '%'], 'status' => 1])->get()->count();
            if ($brand_id == 0) {
                $category = Category::select('id')->where([['name', 'like', '%' . $dataArr['category'] . '%'], 'status' => 1])->get()->first();
                $type = Subcategory::select('id')->where([['name', 'like', '%' . $dataArr['type'] . '%'], 'status' => 1])->get()->first();
                $data['category_id'] = $category['id'];
                $data['subcategory_id'] = $type['id'];
                $data['name'] = $dataArr['brand_name'];
                $data['short_name'] = $dataArr['short_name'];
                $data['btl_size'] = $dataArr['btl_size'];
                $data['peg_size'] = $dataArr['peg_size'];
                $data['created_by'] = $request->user()->id;
                $brand = new Brand($data);
                if ($brand->save())
                    $success++;
                else {
                    array_push($failedData, $dataArr['brand_name']);
                    $fail++;
                }
            } else {
                array_push($failedData, $dataArr['brand_name']);
                $fail++;
            }
        }
        $data_log = [
            'user_type' => $request->user()->type,
            'user_id' => $request->user()->id,
            'ip' => $request->ip(),
            'log' =>  'bulk brand imported' . $success . ' category added, ' . $fail . ' failed',
            'platform' => 'web'
        ];
        SaveLog($data_log);
        return response()->json([
            'message' => $success . ' brand added, ' . $fail . ' failed',
            'type' => 'success',
            'brand' => $failedData
        ], 201);
    }

    // manage_stock
    public function manage_stock(Request $request)
    {
        $data = $request->validate([
            'company_id' => 'required',
            'category_id' => 'required',
            'brand_id' => 'required',
        ]);
        $data['btl'] = $request->btl;
        $data['peg'] = $request->peg;
        $Pbtl = empty($request->Pbtl) ? 0 : $request->Pbtl;
        $Ppeg = empty($request->Ppeg) ? 0 : $request->Ppeg;
        $data['cost_price'] = $request->cost_price;
        $data['btl_selling_price'] = $request->btl_selling_price;
        $data['peg_selling_price'] = $request->peg_selling_price;

        $count = Stock::where(['company_id' => $request->company_id,  'brand_id' => $request->brand_id])->get()->count();
        if ($count > 0) {
            $brandSize = Brand::select('btl_size', 'peg_size')->where('id', $data['brand_id'])->get();
            $MlSize = ($brandSize[0]['btl_size'] * $data['btl']) + ($brandSize[0]['peg_size'] * $data['peg']);
            $PMlSize = ($brandSize[0]['btl_size'] * $Pbtl) + ($brandSize[0]['peg_size'] * $Ppeg);
            $store_btl = $request->store_btl;
            $store_peg = $request->store_peg;
            $bar1_btl = $request->bar1_btl;
            $bar1_peg = $request->bar1_peg;
            $bar2_btl = $request->bar2_btl;
            $bar2_peg = $request->bar2_peg;
            //update stock
            Stock::where(['company_id' => $request->company_id,  'brand_id' => $request->brand_id])->update(['qty' => $MlSize, 'physical_closing' => $PMlSize, 'cost_price' => $data['cost_price'], 'btl_selling_price' => $data['btl_selling_price'], 'peg_selling_price' => $data['peg_selling_price'], 'store_btl' => $store_btl, 'store_peg' => $store_peg, 'bar1_btl' => $bar1_btl, 'bar1_peg' => $bar1_peg, 'bar2_btl' => $bar2_btl, 'bar2_peg' => $bar2_peg]);
        } else {
            $brandSize = Brand::select('btl_size', 'peg_size')->where('id', $data['brand_id'])->get();
            $MlSize = ($brandSize[0]['btl_size'] * $data['btl']) + ($brandSize[0]['peg_size'] * $data['peg']);
            $PMlSize = ($brandSize[0]['btl_size'] * $Pbtl) + ($brandSize[0]['peg_size'] * $Ppeg);
            $data['qty'] = $MlSize;
            //Stock entry
            $data['physical_closing'] = $PMlSize;
            $manage_stock = new Stock($data);
            $manage_stock->save();
            // update daily opening table for storing entry history
            $opening['company_id'] = $request->company_id;
            $opening['brand_id'] = $request->brand_id;
            $opening['qty'] = $MlSize;
            $opening['date'] = date('Y-m-d', strtotime($request->openingDate));
            $saveOpening = new DailyOpening($opening);
            $saveOpening->save();
        }
        $phy['company_id'] = $request->company_id;
        $phy['brand_id'] = $request->brand_id;
        $phy['qty'] = $PMlSize;
        $phy['date'] = date('Y-m-d', strtotime($request->physicalDate));
        $phy['status'] = 1;
        $phy_save = new physical_history($phy);
        $phy_save->save();

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
        $data['sale_date'] = date('Y-m-d', strtotime($request->created_at));
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
            $stock = Stock::select('id', 'qty', 'btl_selling_price', 'peg_selling_price')->where(['company_id' => $request->company_id,  'brand_id' => $brand])->get();


            $data['brand_id'] = $brand;
            $data['sales_type'] = $sales_type[$key];
            $data['category_id'] = $category_id[$key];

            // get sale quantity in ml
            if (($servingSize[$key]) > 0) {
                $MlSize = ($servingSize[$key] * $no_btl[$key]);
            } else {
                $brandSize = Brand::select('btl_size', 'peg_size')->where('id', $brand)->get();
                $MlSize = ($brandSize[0]['btl_size'] * $no_btl[$key]) + ($brandSize[0]['peg_size'] * $no_peg[$key]);
            }
            if ($stock[0]['qty'] > $MlSize) {
                $data['sale_price'] = ($no_btl[$key] * $stock[0]['btl_selling_price']) + ($no_peg[$key] * $stock[0]['peg_selling_price']);
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
        if ($saved || $skipped > 0) {
            return response()->json([
                'message' => $counter . ' Sales Added, ' . $skipped . ' Entries failed',
                'type' => 'success'
            ], 201);
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
        $data = Company::where(['status' => 1, 'id' => $request->company_id])->get()->first();
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
        $data = User::select('id', 'name', 'mobile', 'email', 'roles')->where(['status' => 1, ['name', 'like', '%' . $request->keyword . '%']])->get()->first();
        if ($data) {
            return response()->json($data);
        } else {
            return response()->json([
                'message' => 'Oops! operation failed!',
                'type' => 'failed'
            ]);
        }
    }
    public function fetchUserId(Request $request)
    {
        $data = User::select('id', 'name', 'mobile', 'email', 'roles')->where(['status' => 1, 'id' => $request->id])->get()->first();
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
        $dateTime2 = new DateTime($request->date2);
        $date2 = $dateTime2->format('Y-m-d');
        if ($request->isInvoice == 0)
            $data = PurchaseList::where(['status' => 1, 'isInvoice' => 0, 'company_id' => $request->company_id])->orderBy('id', 'DESC');
        else
            $data = PurchaseList::where(['status' => 1, 'isInvoice' => 1, 'company_id' => $request->company_id])->orderBy('id', 'DESC');
        if (!empty($request->keyword))
            $data->where('invoice_no', 'like', '%' . $request->keyword . '%');
        if (!empty($request->date2)) {
            $data->where('invoice_date', '>', $date);
            $data->where('invoice_date', '<', $date2);
        }
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
        $data = Sales::select('brands.name', 'sales.no_btl', 'sales.no_peg', 'sales.created_at', 'sales.id')->join('brands', 'brands.id', '=', 'sales.brand_id')->where(['sales.company_id' => $request->company_id, 'sales.status' => 1])->orderBy('id', 'DESC');
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
        $data = Brand::select('categories.name as c_name', 'subcategories.name as s_name', 'brands.*')->join('categories', 'brands.category_id', '=', 'categories.id')->join('subcategories', 'brands.subcategory_id', '=', 'subcategories.id')->where('brands.status', 1)->get();
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
    public function getTypeOptions()
    {
        $data = Subcategory::select('*', 'name as label')->where('status', 1)->get();
        if ($data) {
            return response()->json($data);
        } else {
            return response()->json([
                'message' => 'Oops! operation failed!',
                'type' => 'failed'
            ]);
        }
    }
    public function subcategory(Request $request)
    {
        $data = $request->validate([
            'name' => 'required|string',
            'short_name' => 'required|string',
        ]);
        $isSaved = false;
        $data['created_by'] = $request->user()->id;
        $data['status'] = 1;
        if ($request->update == 1) {
            $data = Subcategory::where('id', $request->id)->update($data);
            if ($data)
                $isSaved = true;
        } else {
            $subcategory = new Subcategory($data);
            if ($subcategory->save())
                $isSaved = true;
        }
        if ($isSaved) {
            $data_log = [
                'user_type' => $request->user()->type,
                'user_id' => $request->user()->id,
                'ip' => $request->ip(),
                'log' => 'Brand Type created',
                'platform' => 'web'
            ];
            $log_save = SaveLog($data_log);
            if (($log_save)) {

                return response()->json([
                    'message' => 'Type Saved',
                    'type' => 'success'
                ], 201);
            }
        }
        return response()->json([
            'message' => 'Oops! Operation failed',
            'type' => 'failed'
        ], 401);
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
                'message' => 'Supplier deleted',
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
    public function deleteSubCategory(Request $request)
    {
        error_reporting(0);
        $data = $request->validate([
            'id' => 'required'
        ]);
        $task = Subcategory::where('id', $data['id'])->update(['status' => 0]);
        if ($task) {
            return response()->json([
                'message' => 'Subcategory deleted',
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
    public function deleteOPApi(Request $request)
    {
        error_reporting(0);
        $data = $request->validate([
            'id' => 'required'
        ]);
        $daily = DailyOpening::where(['id' => $data['id'], 'status' => 1])->get()->first();
        Stock::where(['company_id' => $daily['company_id'], 'brand_id' => $daily['brand_id']])->decrement('qty', intval($daily['qty']));
        $res = DailyOpening::where('id', $data['id'])->update(['status' => 0]);
        if ($res) {
            return response()->json([
                'message' => 'Opening deleted',
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
        $data['total_item'] = count($brand);
        $data['isInvoice'] = $data['total_amount'] > 0 ? 1 : 0;
        $purchase = new PurchaseList($data);
        $purchase->save();
        foreach ($brand as $key => $item) {
            $data['brand_id'] = $item;
            $data['no_btl'] = $nobtl[$key];
            $brandSize = Brand::select('btl_size', 'category_id')->where('id', $data['brand_id'])->get();
            $MlSize = ($brandSize[0]['btl_size'] * $data['no_btl']);
            $data['qty'] = $MlSize;
            $data['category_id'] = $brandSize[0]['category_id'];
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
        PurchaseList::where(['invoice_no' => $request->id])->update(['isInvoice' =>  $request->isInvoice]);
        $log_save = SaveLog([
            'user_type' => $request->user()->type,
            'user_id' => $request->user()->id,
            'ip' => $request->ip(),
            'log' => 'converted purchase entry with invoice no :' . $request->id,
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
            'company_id' => 'required',
        ]);

        $data['court_fees'] = $request->court_fees;
        $data['tcs'] = $request->tcs;
        $data['total_amount'] = $request->total_amount;
        $data['invoice_no'] = $request->invoice_no;
        $data['invoice_date'] = date('Y-m-d', strtotime($request->invoice_date));
        $data['created_by'] = $request->user()->id;
        $data['batch_no'] = $request->batch_no;
        $data['vendor_id'] = $request->vendor_id;
        $brand = explode(',', $request->brand_id);
        $nobtl = explode(',', $request->nobtl);
        $p_Id = explode(',', $request->id);
        $data['total_item'] = count($brand);
        $data['isInvoice'] = $data['total_amount'] > 0 ? 1 : 0;
        $stockEntry = Purchase::select('no_btl')->where(['invoice_no' => $request->invoice_no])->get();
        PurchaseList::where(['invoice_no' => $request->invoice_no])->update($data);
        $data['mrp'] = $request->mrp;
        foreach ($brand as $key => $item) {
            unset($data['total_item']);
            unset($data['isInvoice']);
            $data['brand_id'] = $item;
            $data['no_btl'] = $nobtl[$key];
            $brandSize = Brand::select('btl_size', 'category_id')->where('id', $data['brand_id'])->get();
            $MlSize = ($brandSize[0]['btl_size'] * $data['no_btl']);
            $data['qty'] = $MlSize;
            $data['category_id'] = $brandSize[0]['category_id'];
            $purchaseId = $p_Id[$key];
            if ($purchaseId > 0) {
                Purchase::where(['id' => $purchaseId])->update($data);
                $stockEntry = Purchase::select('no_btl')->where(['invoice_no' => $request->invoice_no, 'brand_id' => $data['brand_id']])->get();
                if (count($stockEntry) > 0) {
                    $count = Stock::where(['company_id' => $request->company_id, 'brand_id' => $request->brand_id])->get()->count();
                    $brandSize = Brand::select('btl_size')->where('id', $data['brand_id'])->get();
                    $MlSize = ($brandSize[0]['btl_size'] * $data['no_btl']);
                    $OldMlSize = ($brandSize[0]['btl_size'] * $stockEntry[0]['no_btl']);
                    if ($count > 0) {
                        Stock::where(['company_id' => $request->company_id,  'brand_id' => $request->brand_id])->decrement('qty', intval($OldMlSize));
                        //update stock
                        Stock::where(['company_id' => $request->company_id,  'brand_id' => $request->brand_id])->increment('qty', intval($MlSize));
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
                }
            } else {
                $save = new Purchase($data);

                if ($save->save()) {
                    $count = Stock::where(['company_id' => $request->company_id, 'brand_id' => $data['brand_id']])->get()->count();
                    if ($count > 0)
                        Stock::where(['company_id' => $request->company_id,  'brand_id' => $data['brand_id']])->increment('qty', intval($MlSize));
                    else {
                        $stock = new Stock(array(
                            'company_id' => $request->company_id,
                            //'branch_id' => $request->branch_id,
                            'category_id' => $data['category_id'],
                            'brand_id' => $data['brand_id'],
                            'qty' => $MlSize,
                        ));
                        $stock->save();
                    }
                }
            }
        }
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
    }
    public function deleteTp(Request $request)
    {
        $data = $request->validate([
            'id' => 'required'
        ]);
        $stockEntry = Purchase::select('no_btl', 'qty', 'company_id', 'invoice_no', 'brand_id')->where(['id' => $request->id])->get();
        if (count($stockEntry) > 0) {
            // check stock
            Stock::where(['company_id' => $stockEntry[0]['company_id'],  'brand_id' => $stockEntry[0]['brand_id']])->decrement('qty', $stockEntry[0]['qty']);
            $data = Purchase::where('id', $data['id'])->update(['status' => 0]);
            if ($data) {
                PurchaseList::where('invoice_no', $stockEntry[0]['invoice_no'])->decrement('total_item', 1);
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
    public function deleteTpList(Request $request)
    {
        $data = $request->validate([
            'invoice' => 'required'
        ]);
        $isDelete = false;
        $stockEntries = Purchase::select('no_btl', 'company_id', 'qty', 'invoice_no', 'brand_id')->where(['invoice_no' => $request->invoice])->get();
        foreach ($stockEntries as $key => $stockEntry) {
            Stock::where(['company_id' => $stockEntry['company_id'],  'brand_id' => $stockEntry['brand_id']])->decrement('qty', $stockEntry['qty']);
            if (Purchase::where('invoice_no', $data['invoice'])->update(['status' => 0]))
                $isDelete = true;
        }
        if ($isDelete) {
            PurchaseList::where('invoice_no', $data['invoice'])->update(['status' => 0]);
            return response()->json([
                'message' => 'Purchase deleted',
                'type' => 'success'
            ], 201);
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
        $brands = Brand::select('name as value', 'id', 'category_id', DB::raw('CONCAT(id," - ",name," - ",btl_size) as label'), DB::raw('0 as recipe'))->where(['status' => 1])->get();
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
        $brands = Brand::select('name as value', DB::raw('CONCAT(brands.id," - ",name," - ",btl_size) as label'), 'brands.id', 'brands.category_id', DB::raw('0 as recipe'))->join('stocks', 'stocks.brand_id', '=', 'brands.id')->where(['brands.status' => 1, 'stocks.company_id' => $request->company_id])->get();
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
    // get transaction
    public function getTransaction(Request $request)
    {
        if ($request->is_sender == 1)
            $data = Transaction::select('transactions.id', 'brands.name', 'brands.id as brand_id', 'transactions.btl', 'transactions.qty', 'transactions.date')->join('brands', 'brands.id', '=', 'transactions.brand_id')->where(['transactions.company_id' => $request->company_id])->orderBy('transactions.id', 'DESC')->get();
        else
            $data = Transaction::select('brands.name', 'brands.id as brand_id', 'transactions.btl', 'transactions.qty', 'transactions.date')->join('brands', 'brands.id', '=', 'transactions.brand_id')->where(['transactions.company_to_id' => $request->company_to_id])->orderBy('transactions.id', 'DESC')->get();
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
        $response = [];
        $data = Stock::where(['company_id' => $req['company_id'], 'brand_id' => $req['brand_id']])->get();
        $qty = !empty($data[0]['qty']) ? $data[0]['qty'] : 0;
        $openingData = DailyOpening::where(['company_id' => $req['company_id'], 'brand_id' => $req['brand_id']])->get()->first();
        $openingQty = !empty($openingData['qty']) ? $openingData['qty'] : 0;
        $result = getBtlPeg($req['brand_id'], $qty);
        $opening = getBtlPeg($req['brand_id'], $openingQty);
        if (empty($result['btl']) && empty($result['peg'])) {
            $brandSize = Brand::select('btl_size', 'peg_size')->where('id', $req['brand_id'])->get();
            array_push($response, array('btl' => 0, 'peg' => 0, 'btl_size' => $brandSize[0]['btl_size'], 'peg_size' => $brandSize[0]['peg_size']));
            return $response;
        }
        $data[0]['op_btl'] = $opening['btl'];
        $data[0]['op_peg'] = intval($opening['peg']);
        $data[0]['date'] = empty($openingData['date']) ? '' : $openingData['date'];

        $data[0]['btl'] = $result['btl'];
        $data[0]['peg'] = intval($result['peg']);
        $data[0]['btl_size'] = $result['btl_size'];
        $data[0]['peg_size'] = $result['peg_size'];
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
        error_reporting(0);
        $dataArray = $request->data;
        $company_id = $dataArray[0]['company_id'];
        // $branch_id = $dataArray[0]['branch_id'];
        $isSaved = false;
        $failed_data = [];
        $skipped = 0;
        $counter = 0;
        foreach ($dataArray as $dataArr) {
            $brandName = $dataArr['brand'];
            $total = explode('.', $dataArr['total']);
            $btl = intval($total[0]);
            $peg = intval($total[1]);
            $data['company_id'] = $company_id;
            //$data['branch_id'] = $branch_id;
            $brandSize = Brand::select('id', 'category_id', 'btl_size', 'peg_size')->where([['name', 'like', '%' . $brandName . '%']])->get();
            if (count($brandSize) > 0) {
                $count = Stock::where(['company_id' => $company_id, 'brand_id' => $brandSize[0]['id']])->get()->count();
                $MlSize = ($brandSize[0]['btl_size'] * $btl) + ($brandSize[0]['peg_size'] * $peg);
                $data['category_id'] = $brandSize[0]['category_id'];
                $data['brand_id'] = $brandSize[0]['id'];
                if ($count === 0) {
                    $data['qty'] = $MlSize;
                    $data['physical_closing'] = $MlSize;
                    $data['cost_price'] = $dataArr['cost_price'];
                    $data['btl_selling_price'] = $dataArr['btl_selling_price'];
                    $data['peg_selling_price'] = $dataArr['peg_selling_price'];
                    // store stock
                    $storeArr = explode('.', $dataArr['store']);
                    $data['store_btl'] = $storeArr[0];
                    $data['store_peg'] = !empty($storeArr[1]) ? $storeArr[1] : 0;
                    // bar1 stock
                    $storeArr1 = explode('.', $dataArr['bar1']);
                    $data['bar1_btl'] = intval($storeArr1[0]);
                    $data['bar1_peg'] = !empty($storeArr1[1]) ? $storeArr1[1] : 0;
                    // bar2 stock
                    $storeArr2 = explode('.', $dataArr['bar2']);
                    $data['bar2_btl'] = $storeArr2[0];
                    $data['bar2_peg'] = !empty($storeArr2[1]) ? $storeArr2[1] : 0;
                    //Stock entry
                    // $data['physical_closing'] = $MlSize;
                    $manage_stock = new Stock($data);
                    if ($manage_stock->save()) {
                        $opening['company_id'] = $company_id;
                        $opening['brand_id'] = $brandSize[0]['id'];
                        $opening['qty'] = $MlSize;
                        $opening['date'] = date('Y-m-d', strtotime($dataArr['date']));
                        $saveOpening = new DailyOpening($opening);
                        $saveOpening->save();
                    } else {
                        array_push($failed_data, $dataArr['brand']);
                        $skipped++;
                    }
                }
                $counter++;
            } else {
                array_push($failed_data, $dataArr['brand']);
                $skipped++;
            }
        }

        if ($counter > 0 || $skipped > 0) {
            $data_log = [
                'user_type' => $request->user()->type,
                'user_id' => $request->user()->id,
                'ip' => $request->ip(),
                'log' => 'Stock created',
                'platform' => 'web'
            ];
            SaveLog($data_log);

            return response()->json([
                'message' => $counter . ' Stock Added, ' . $skipped . ' Entries failed',
                'type' => 'success',
                'brand' => $failed_data
            ], 201);
        } else {
            return response()->json([
                'message' => 'Oops! Operation failed',
                'type' => 'failed'
            ], 401);
        }
    }
    public function PhysicalBulkApi(Request $request)
    {
        error_reporting(0);
        $dataArray = $request->data;
        $company_id = $dataArray[0]['company_id'];
        // $branch_id = $dataArray[0]['branch_id'];
        $isSaved = false;
        $failed_data = [];
        $skipped = 0;
        $counter = 0;
        foreach ($dataArray as $dataArr) {
            $brandName = $dataArr['brand'];
            $total = explode('.', $dataArr['total']);
            $btl = intval($total[0]);
            $peg = intval($total[1]);
            $data['company_id'] = $company_id;
            //$data['branch_id'] = $branch_id;
            $brandSize = Brand::select('id', 'category_id', 'btl_size', 'peg_size')->where([['name', 'like', '%' . $brandName . '%']])->get();
            if (count($brandSize) > 0) {
                $count = Stock::where(['company_id' => $company_id, 'brand_id' => $brandSize[0]['id']])->get()->count();
                $MlSize = ($brandSize[0]['btl_size'] * $btl) + ($brandSize[0]['peg_size'] * $peg);
                $data['category_id'] = $brandSize[0]['category_id'];
                $data['brand_id'] = $brandSize[0]['id'];
                if ($count > 0) {
                    $data['physical_closing'] = $MlSize;
                    Stock::where(['company_id' => $company_id, 'brand_id' => $brandSize[0]['id']])->update($data);

                    $phy['company_id'] = $company_id;
                    $phy['brand_id'] = $brandSize[0]['id'];
                    $phy['qty'] = $request->qty;
                    $phy['date'] = date('Y-m-d');
                    $phy['status'] = 1;
                    $phy_save = new physical_history($phy);
                    $phy_save->save();
                } else {
                    array_push($failed_data, $dataArr['brand']);
                    $skipped++;
                }
                $counter++;
            } else {
                array_push($failed_data, $dataArr['brand']);
                $skipped++;
            }
        }

        if ($counter > 0 || $skipped > 0) {
            $data_log = [
                'user_type' => $request->user()->type,
                'user_id' => $request->user()->id,
                'ip' => $request->ip(),
                'log' => 'Stock Physical updated',
                'platform' => 'web'
            ];
            SaveLog($data_log);

            return response()->json([
                'message' => $counter . ' Successful Update, ' . $skipped . ' Entries failed',
                'type' => 'success',
                'brand' => $failed_data
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
        $invoiceArray = [];
        $purchaseList = [];
        $purchaseCount = [];
        $failedData = [];
        foreach ($dataArray as $dataArr) {
            // $brandName = rtrim(preg_replace("/[^\W\d]*\d\w*/", " ", $dataArr['brand']));
            $brandName = $dataArr['brand'];
            $btl = intval($dataArr['total']);
            $data['invoice_no'] = $dataArr['invoiceNo'];
            $data['invoice_date'] = date('Y-m-d', strtotime($dataArr['date']));
            $supplier = Supplier::select('id')->where([['name', 'like', '%' . $dataArr['supplier'] . '%'], 'company_id' => $company_id])->get();
            if (empty($supplier[0]['id'])) {
                array_push($failedData, $brandName);
                $skipped++;
                continue;
            }
            $data['vendor_id'] = $supplier[0]['id'];
            $data['company_id'] = $company_id;
            // $data['branch_id'] = $branch_id;
            $brandSize = Brand::select('id', 'category_id', 'btl_size', 'peg_size')->where([['name', 'like', '%' . $brandName . '%']])->get();
            if (count($brandSize) < 1) {
                array_push($failedData, $brandName);
                $skipped++;
                continue;
            }
            $data['batch_no'] =  !empty($dataArr['batch_no']) ? $dataArr['batch_no'] : null;
            $data['created_by'] = $request->user()->id;
            $data['total_amount'] = !empty($dataArr['total_amount']) ? $dataArr['total_amount'] : 0;
            $data['isInvoice'] = $data['total_amount'] > 0 ? 1 : 0;
            if (in_array($dataArr['invoiceNo'], $invoiceArray)) {
                $purchaseCount[$dataArr['invoiceNo']]['count'] = $purchaseCount[$dataArr['invoiceNo']]['count'] + 1;
            } else {
                $purchaseCount[$dataArr['invoiceNo']]['count'] = 1;
                array_push($purchaseList, $data);
                array_push($invoiceArray, $dataArr['invoiceNo']);
            }

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
            } else {
                array_push($failedData, $brandName);
                $skipped++;
            }
        }
        foreach ($purchaseList as $key => $purchaseData) {
            $purchaseData['total_item'] = $purchaseCount[$purchaseData['invoice_no']]['count'];
            $save2 = new PurchaseList($purchaseData);
            $save2->save();
        }
        if ($counter > 0 || $skipped > 0) {
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
                'type' => 'success',
                'brand' => $failedData
            ], 201);
        }

        return response()->json([
            'message' => '0 entries added, Operation failed',
            'type' => 'failed'
        ], 401);
    }
    public function bulkSalesImport(Request $request)
    {
        $dataArray = $request->data;
        $counter = 0;
        $skipped = 0;
        $data['company_id'] = $dataArray[0]['company_id'];
        $failed_data = [];
        foreach ($dataArray as $key => $dataAr) {
            $name = $dataAr['name'];

            $success = false;
            $isCocktail = false;
            $brands = Brand::select('id as brand_id', 'category_id')->where(['name' => $name, 'status' => 1])->get();
            if (count($brands) < 1) {
                $brands = Recipe::select('recipe_code', 'brand_id', 'serving_size', 'category_id', 'is_cocktail')->where(['name' => $name, 'company_id' => $data['company_id'], 'status' => 1])->get();
                if (count($brands) < 1) {
                    array_push($failed_data, $dataAr['name']);
                    $skipped++;
                    continue; // if no matching brand or recipe found then skip to next entry
                } else
                    $isCocktail = true;
            }
            foreach ($brands as $brand) {

                $brand_id = $brand['brand_id'];
                $data['category_id'] = $brand['category_id'];
                $data['brand_id'] = $brand['brand_id'];
                $data['sale_date'] = date('Y-m-d', strtotime($dataAr['date']));
                $peg_size = Brand::select('peg_size', 'btl_size')->where(['id' => $brand_id])->get();
                $stock = Stock::select('id', 'qty', 'btl_selling_price', 'peg_selling_price')->where(['company_id' => $data['company_id'],  'brand_id' => $brand_id])->get();
                if ($stock[0]['qty'] > 0) {
                    $data['created_by'] = $request->user()->id;
                    $data['description'] = ' brand id ' . $brand_id . '  sales entry has been done from bulk import by ' . $request->user()->id;
                    $MlSize = 0;
                    $MlSize1 = 0;
                    $MlSize2 = 0;
                    $MlSize3 = 0;
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
                            if ($dataAr['nc'] > 0) {
                                $data['sale'] = $dataAr['nc'];
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
                            if ($dataAr['banquet'] > 0) {
                                $data['sale'] = $dataAr['banquet'];
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
                            if ($dataAr['spoilage'] > 0) {
                                $data['sale'] = $dataAr['spoilage'];
                                // calculate qty for combo
                                $qty = ($brand['serving_size'] * $data['sale']) / $peg_size[0]['peg_size'];
                                $data['sale_price'] = ($qty * $stock[0]['peg_selling_price']);
                                $MlSize2 = ($brand['serving_size'] * $data['sale']);
                                $data['qty'] = $MlSize2;
                                $data['sales_type'] = 4;
                                $result = getBtlPeg($brand_id, $MlSize2);
                                $data['no_btl'] = $result['btl'];
                                $data['no_peg'] = $result['peg'];
                                $Sales = new Sales($data);
                                $Sales->save();
                            }
                        }
                    } else {
                        // liquour section
                        // sale
                        $saleAr = explode('.', $dataAr['sale']);
                        $saleAr1 = !empty($saleAr[0]) ? $saleAr[0] : 0;
                        $saleAr2 = !empty($saleAr[1]) ? $saleAr[1] : 0;

                        // nc
                        $ncAr = explode('.', $dataAr['nc']);
                        $nc1 = !empty($ncAr[0]) ? $ncAr[0] : 0;
                        $nc2 = !empty($ncAr[1]) ? $ncAr[1] : 0;

                        // banquet
                        $banAr = explode('.', $dataAr['banquet']);
                        $banAr1 = !empty($banAr[0]) ? $banAr[0] : 0;
                        $banAr2 = !empty($banAr[1]) ? $banAr[1] : 0;

                        // spoilage
                        $spoAr = explode('.', $dataAr['spoilage']);
                        $spoAr1 = !empty($spoAr[0]) ? $spoAr[0] : 0;
                        $spoAr2 = !empty($spoAr[1]) ? $spoAr[1] : 0;

                        $data['sale_price'] = ($saleAr1 * $stock[0]['btl_selling_price']) + ($saleAr2 * $stock[0]['peg_selling_price']);

                        $MlSize = ($peg_size[0]['btl_size'] * $saleAr1) + ($peg_size[0]['peg_size'] * $saleAr2);
                        $data['qty'] = $MlSize;
                        $data['sales_type'] = 1;
                        $success = true;
                        $data['no_btl'] = $saleAr1;
                        $data['no_peg'] = $saleAr2;
                        $Sales = new Sales($data);
                        if ($Sales->save()) {
                            if ($dataAr['nc'] > 0) {
                                // calculate qty for complimentary
                                $data['sale_price'] = ($nc1 * $stock[0]['btl_selling_price']) + ($nc2 * $stock[0]['peg_selling_price']);
                                $MlSize1 = ($peg_size[0]['btl_size'] * $nc1) + ($peg_size[0]['peg_size'] * $nc2);
                                $data['qty'] = $MlSize1;
                                $data['sales_type'] = 2;
                                $data['no_btl'] = $nc1;
                                $data['no_peg'] = $nc2;
                                $Sales = new Sales($data);
                                $Sales->save();
                            }
                            if ($dataAr['banquet'] > 0) {
                                // calculate qty for combo
                                $data['sale_price'] = ($banAr1 * $stock[0]['btl_selling_price']) + ($banAr2 * $stock[0]['peg_selling_price']);
                                $MlSize2 = ($peg_size[0]['btl_size'] * $banAr1) + ($peg_size[0]['peg_size'] * $banAr2);
                                $data['qty'] = $MlSize2;
                                $data['sales_type'] = 3;
                                $data['no_btl'] = $banAr1;
                                $data['no_peg'] = $banAr2;
                                $Sales = new Sales($data);
                                $Sales->save();
                            }
                            if ($dataAr['spoilage'] > 0) {
                                // calculate qty for combo
                                $data['sale_price'] = ($spoAr1 * $stock[0]['btl_selling_price']) + ($spoAr2 * $stock[0]['peg_selling_price']);
                                $MlSize2 = ($peg_size[0]['btl_size'] * $spoAr1) + ($peg_size[0]['peg_size'] * $spoAr2);
                                $data['qty'] = $MlSize3;
                                $data['sales_type'] = 4;
                                $data['no_btl'] = $spoAr1;
                                $data['no_peg'] = $spoAr2;
                                $Sales = new Sales($data);
                                $Sales->save();
                            }
                        }
                    }
                    $total_qty_sold = $MlSize + $MlSize1 + $MlSize2 + $MlSize3;
                    if ($success) {
                        //update stocks
                        Stock::where(['company_id' => $data['company_id'],  'brand_id' => $brand_id])->decrement('qty', $total_qty_sold);
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
                        array_push($failed_data, $dataAr['name']);
                        $skipped++; // counter for error in entry
                    }
                } else {
                    array_push($failed_data, $dataAr['name']);
                    $skipped++;
                }
            }
        }
        if ($counter > 0 || $skipped > 0) {
            return response()->json([
                'message' => $counter . ' Sales Added, ' . $skipped . ' Entries failed',
                'type' => 'success',
                'brand' => $failed_data
            ], 201);
        }
        return response()->json([
            'message' => 'Oops! Operation failed',
            'type' => 'failed'
        ], 401);
    }
    public function bulkImportRecipes(Request $request)
    {
        $dataArray = ($request->data);
        $skipped = 0;
        $failed_data = [];
        $counter = 0;
        foreach ($dataArray as $key => $dataAr) {
            $found = Recipe::where(['name' => $dataAr['name']])->get()->count();
            if ($found > 0) {
                array_push($failed_data, $dataAr['name']);
                $skipped++;
                continue;
            }
            $name = $dataAr['name'];
            $serving_size = !empty($dataAr['serving_size']) ? empty($dataAr['serving_size']) : 0;
            $isSaved = false;
            do {
                // generate unique recipe_code code
                $recipe_code = $dataArray[0]['company_id'] . rand(11111, 99999);
                $count = Recipe::where(['recipe_code' => $recipe_code])->get()->count();
            } while ($count > 0);

            $data['name'] = $name;
            $data['serving_size'] = $serving_size;
            $data['company_id'] = $dataArray[0]['company_id'];
            $data['is_cocktail'] = $dataArray[0]['is_cocktail'];
            $data['category_id'] = 0;
            $data['recipe_code'] = $recipe_code;
            $data['created_by'] = $request->user()->id;
            $Recipe = new Recipe($data);
            if ($Recipe->save()) {
                $counter++;
            } else {
                array_push($failed_data, $dataAr['name']);
                $skipped++;
            }
        }
        if ($counter > 0 || $skipped > 0) {
            $data_log = [
                'user_type' => $request->user()->type,
                'user_id' => $request->user()->id,
                'ip' => $request->ip(),
                'log' =>  'bulk Recipe created',
                'platform' => 'web'
            ];
            SaveLog($data_log);
            return response()->json([
                'message' => $counter . ' Recipe Added, ' . $skipped . ' Entries failed',
                'type' => 'success',
                'brand' => $failed_data
            ], 201);
        }
        return response()->json([
            'message' => 'Oops! Operation failed',
            'type' => 'failed'
        ], 401);
    }

    //getSales
    public function getSalesList(Request $request)
    {
        $data = Sales::select('brands.name', 'brands.id as brand_id', 'sales.sales_type as type', 'sales.no_btl', 'sales.qty', 'sales.sale_date', 'sales.no_peg', 'sales.created_at', 'sales.id')->join('brands', 'brands.id', '=', 'sales.brand_id')->where(['sales.company_id' => $request->company_id, 'sales.status' => 1])->orderBy('id', 'DESC')->get();
        if ($data) {
            return response()->json($data);
        } else {
            return response()->json([
                'message' => 'Oops! operation failed!',
                'type' => 'failed'
            ]);
        }
    }
    public function ValidateTp(Request $request)
    {
        $data = Purchase::where(['invoice_no' => $request->invoice_no, 'company_id' => $request->company_id, 'status' => 1])->get()->count();
        return response()->json($data);
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
        $data = Purchase::select('brands.name', 'purchases.*')->join('brands', 'brands.id', '=', 'purchases.brand_id')->where(['purchases.status' => 1, 'purchases.invoice_no' => $request->id])->orderBy('id', 'DESC')->get();
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
        $data = Sales::select(DB::raw('sum(sale_price) as sale_price'), DB::raw('date(sale_date) as date'))->where(['company_id' => $request->company_id, 'status' => 1])->whereRaw('created_at > DATE_SUB(NOW(), INTERVAL 30 DAY)')->groupBy('created_at')->orderBy('created_at', 'ASC')->get();
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
        $Category = Category::select('id', 'name')->get();
        $json = [];

        foreach ($Category as $Category_data) {
            // echo "<pre>";print_r($Category_data);

            $brands_data = DB::table("brands")
                ->select('btl_size', 'category_id', 'id', 'peg_size', 'subcategory_id')
                ->where('category_id', '=', $Category_data['id'])->orderBy('btl_size', 'DESC')->groupBy(DB::raw("btl_size"))
                ->get();

            foreach ($brands_data as  $brandList) {

                $brand_size = $brandList->btl_size;
                //$brand_id = $brandList->id;
                $data_cat = $Category_data['name'] . "-" . $brand_size;
                $b_type = Subcategory::select('name')->where('id', $brandList->subcategory_id)->get()->first();
                $brandName_Data = Brand::where(['category_id' => $brandList->category_id, 'btl_size' => $brand_size])->get();
                $total = 0;
                $brand_open_btl = 0;

                $openSum = 0;
                $receiptSum = 0;
                $totalSum = 0;
                $salesSum = 0;
                $closingSum = 0;
                $physicalSum = 0;
                $banquetSum = 0;
                $spoilageSum = 0;
                $ncSalesSum = 0;
                $cocktailSalesSum = 0;
                $selling_variance = 0;
                $cost_variance = 0;

                $arrCat = [
                    'Type' => '',
                    'name' => $data_cat,
                    'btl_size' => '',
                    'open' => '',
                    'receipt' => '',
                    'total' => '',
                    'sales' => '',
                    'nc_sales' => '',
                    'cocktail_sales' => '',
                    'banquet_sales' => '',
                    'spoilage_sales' => '',
                    'closing' => '',
                    'physical' => '',
                    'variance' => '',
                    'comsumption' => '',
                    'selling_variance' => '',
                    'cost_variance' => ''
                ];

                foreach ($brandName_Data as  $brandListName) {
                    $isMinus = false;
                    $arr['Type'] = $b_type['name'];
                    $arr['name'] = $brandListName['name'];
                    $arr['btl_size'] = $brand_size;

                    $data_daily_opening = DB::table("daily_openings")
                        ->select('qty')
                        ->where('company_id', '=', $request->company_id)
                        ->where('date', '=', date('Y-m-d', strtotime($request->from_date . '+1 day')))
                        ->where('brand_id', '=', $brandListName['id'])
                        ->get()->first();
                    $qty = !empty($data_daily_opening->qty) ? $data_daily_opening->qty : '0';
                    $openSum = $openSum + $qty;
                    $balance = DB::table('purchases')->where(['brand_id' => $brandListName['id'], 'company_id' => $request->company_id])->whereBetween('invoice_date', [$request->from_date, $request->to_date])->sum('qty');
                    $receiptSum = $receiptSum + $balance;

                    $total = $qty + $balance;
                    $totalSum = $totalSum + $total;

                    $sales = DB::table('sales')->where(['brand_id' => $brandListName['id'], 'company_id' => $request->company_id, 'sales_type' => '1', 'is_cocktail' => '0'])->whereBetween('created_at', [$request->from_date, $request->to_date])->sum('qty');

                    $nc_sales = DB::table('sales')->where(['brand_id' => $brandListName['id'], 'company_id' => $request->company_id, 'is_cocktail' => '0', 'sales_type' => 2])->whereBetween('created_at', [$request->from_date, $request->to_date])->sum('qty');
                    $cocktail_sales = DB::table('sales')->where(['brand_id' => $brandListName['id'], 'company_id' => $request->company_id, 'is_cocktail' => '1'])->whereBetween('created_at', [$request->from_date, $request->to_date])->sum('qty');

                    $banquet_sales = DB::table('sales')->where(['brand_id' => $brandListName['id'], 'company_id' => $request->company_id, 'sales_type' => '3'])->whereBetween('created_at', [$request->from_date, $request->to_date])->sum('qty');

                    $spoilage_sales = DB::table('sales')->where(['brand_id' => $brandListName['id'], 'company_id' => $request->company_id, 'sales_type' => '4'])->whereBetween('created_at', [$request->from_date, $request->to_date])->sum('qty');

                    $banquetSum = $banquetSum + $banquet_sales;
                    $spoilageSum = $spoilageSum + $spoilage_sales;
                    $ncSalesSum = $ncSalesSum + $nc_sales;
                    $cocktailSalesSum = $cocktailSalesSum + $cocktail_sales;

                    $salesSum = $salesSum + $sales;
                    $closing = ($total - ($sales + $nc_sales + $banquet_sales + $spoilage_sales));
                    $closingSum = $closingSum + $closing;

                    $PhyQty = physical_history::where(['company_id' => $request->company_id, 'brand_id' => $brandListName['id'], 'date' => $request->to_date])->get()->first();

                    $PhyClosing = !empty($PhyQty['qty']) ? $PhyQty['qty'] : 0;

                    $physicalSum = $physicalSum + $PhyClosing;

                    $variance = $PhyClosing - $closing;

                    $brand_size = $brandListName['btl_size'];
                    if ($variance < 0) {
                        $isMinus = true;
                        $variance = abs($variance);
                    }

                    $btl_opening = 0;
                    while ($qty >= $brand_size) {
                        $qty = $qty - $brand_size;
                        $btl_opening++;
                        $brand_open_btl++;
                    }
                    $peg_opening = intval($qty / $brandListName['peg_size']);
                    $arr['open'] = $btl_opening . "." . $peg_opening;
                    //$brand_open_btl = $btl_opening++;


                    $btl_receipt  = 0;
                    while ($balance >= $brand_size) {
                        $balance = $balance - $brand_size;
                        $btl_receipt++;
                    }
                    $peg_receipt  = intval($balance / $brandListName['peg_size']);
                    $arr['receipt'] = $btl_receipt . "." . $peg_receipt;

                    $btl_total  = 0;
                    while ($total >= $brand_size) {
                        $total = $total - $brand_size;
                        $btl_total++;
                    }
                    $peg_total  = intval($total / $brandListName['peg_size']);

                    $arr['total'] = $btl_total . "." . $peg_total;
                    $btl_sales  = 0;
                    while ($sales >= $brand_size) {
                        $sales = $sales - $brand_size;
                        $btl_sales++;
                    }
                    $peg_sales  = intval($sales / $brandListName['peg_size']);
                    $arr['sales'] = $btl_sales . "." . $peg_sales;

                    //nc sale
                    //echo "<pre>";print_r($nc_sales);
                    $btl_nc_sales = 0;
                    while ($nc_sales >= $brand_size) {
                        $nc_sales = $nc_sales - $brand_size;
                        $btl_nc_sales++;
                    }
                    $peg_nc_sales  = intval($nc_sales / $brandListName['peg_size']);
                    $arr['nc_sales'] = $btl_nc_sales . "." . $peg_nc_sales;

                    //bcocktail
                    $btl_cocktail_sales = 0;

                    while ($cocktail_sales >= $brand_size) {
                        $cocktail_sales = $cocktail_sales - $brand_size;
                        $btl_cocktail_sales++;
                    }
                    $peg_cocktail_sales  = intval($cocktail_sales / $brandListName['peg_size']);
                    $arr['cocktail_sales'] = $btl_cocktail_sales . "." . $peg_cocktail_sales;


                    //banquet
                    $btl_banquet_sales = 0;

                    while ($banquet_sales >= $brand_size) {
                        $banquet_sales = $banquet_sales - $brand_size;
                        $btl_banquet_sales++;
                    }
                    $peg_banquet_sales  = intval($banquet_sales / $brandListName['peg_size']);
                    $arr['banquet_sales'] = $btl_banquet_sales . "." . $peg_banquet_sales;

                    //banquet
                    $btl_spoilage_sales = 0;

                    while ($spoilage_sales >= $brand_size) {
                        $spoilage_sales = $spoilage_sales - $brand_size;
                        $btl_spoilage_sales++;
                    }
                    $peg_spoilage_sales  = intval($spoilage_sales / $brandListName['peg_size']);
                    $arr['spoilage_sales'] = $btl_spoilage_sales . "." . $peg_spoilage_sales;

                    //  system qty closing
                    $btl_closing  = 0;
                    while ($closing >= $brand_size) {
                        $closing = $closing - $brand_size;
                        $btl_closing++;
                    }
                    $peg_closing  = intval($closing / $brandListName['peg_size']);
                    // physical qty closing
                    $p_btl_closing  = 0;
                    while ($PhyClosing >= $brand_size) {
                        $PhyClosing = $PhyClosing - $brand_size;
                        $p_btl_closing++;
                    }
                    $p_peg_closing  = intval($PhyClosing / $brandListName['peg_size']);
                    // variance 
                    $v_btl_closing  = 0;

                    while ($variance >= $brand_size) {
                        $variance = $variance - $brand_size;
                        $v_btl_closing++;
                    }
                    $v_peg_closing  = intval($variance / $brandListName['peg_size']);


                    $arr['closing'] = $btl_closing . "." . $peg_closing;




                    $arr['physical'] = $p_btl_closing . "." . $p_peg_closing;
                    $arr['variance'] = ($isMinus == true ? '-' : '') . $v_btl_closing . "." . $v_peg_closing;
                    // comsumption 
                    $comsumption = $total - $closing;
                    $btl_comsumption  = 0;
                    while ($comsumption >= $brand_size) {
                        $comsumption = $comsumption - $brand_size;
                        $btl_comsumption++;
                    }
                    $peg_comsumption  = intval($comsumption / $brandListName['peg_size']);
                    $arr['comsumption'] = ($isMinus == true ? '-' : '') . $btl_comsumption . "." . $peg_comsumption;
                    $btl_selling_price = !empty($PhyQty['btl_selling_price']) ? $PhyQty['btl_selling_price'] : 0;
                    $peg_selling_price = !empty($PhyQty['peg_selling_price']) ? $PhyQty['peg_selling_price'] : 0;


                    $cost_btl_price = !empty($PhyQty['cost_price']) ? $PhyQty['cost_price'] : 0;
                    $cost_peg_price = $cost_btl_price / ($brandListName['btl_size'] / $brandListName['peg_size']); // calculate peg price from btl cost

                    // $arr['selling_price'] = $btl_selling_price;

                    // selling price variance

                    $arr['selling_variance'] = $v_btl_closing * $btl_selling_price + $v_peg_closing * $peg_selling_price;
                    $selling_variance = $selling_variance + $arr['selling_variance'];

                    // cost price variance
                    $arr['cost_variance'] = $v_btl_closing * $cost_btl_price + $v_peg_closing * $cost_peg_price;
                    $cost_variance = $cost_variance + $arr['cost_variance'];

                    if ($arr['total'] != '0.0' || $arr['closing'] != '0.0' || $arr['physical'] != '0.0') {
                        if (!in_array($arrCat, $json)) {
                            array_push($json, $arrCat);
                        }
                        array_push($json, $arr);
                    }
                }

                if (count($brandName_Data) > 0) {

                    $peg_size = $brandList->peg_size;
                    //open all
                    $open_btl_all = 0;
                    while ($openSum >= $brand_size) {
                        $openSum = $openSum - $brand_size;
                        $open_btl_all++;
                    }
                    $peg_open_all  = intval($openSum / $peg_size);
                    $open_all = $open_btl_all . "." . $peg_open_all;

                    //receipt
                    $receipt_btl_all = 0;
                    while ($receiptSum >= $brand_size) {
                        $receiptSum = $receiptSum - $brand_size;
                        $receipt_btl_all++;
                    }
                    $peg_receipt_all  = intval($receiptSum / $peg_size);
                    $receipt_all = $receipt_btl_all . "." . $peg_receipt_all;

                    //total
                    $total_btl_all = 0;
                    while ($totalSum >= $brand_size) {
                        $totalSum = $totalSum - $brand_size;
                        $total_btl_all++;
                    }
                    $peg_total_all  = intval($totalSum / $peg_size);
                    $total_all = $total_btl_all . "." . $peg_total_all;

                    //sales
                    $sales_btl_all = 0;
                    while ($salesSum >= $brand_size) {
                        $salesSum = $salesSum - $brand_size;
                        $sales_btl_all++;
                    }
                    $peg_sales_all  = intval($salesSum / $peg_size);
                    $sales_all = $sales_btl_all . "." . $peg_sales_all;

                    //closing
                    $closing_btl_all = 0;
                    while ($closingSum >= $brand_size) {
                        $closingSum = $closingSum - $brand_size;
                        $closing_btl_all++;
                    }
                    $peg_closing_all  = intval($closingSum / $peg_size);
                    $closing_all = $closing_btl_all . "." . $peg_closing_all;

                    //physical
                    $physical_btl_all = 0;
                    while ($physicalSum >= $brand_size) {
                        $physicalSum = $physicalSum - $brand_size;
                        $physical_btl_all++;
                    }
                    $peg_physical_all  = intval($physicalSum / $peg_size);
                    $physical_all = $physical_btl_all . "." . $peg_physical_all;

                    //spoilageSum
                    $spoilage_btl_all = 0;
                    while ($spoilageSum >= $brand_size) {
                        $spoilageSum = $spoilageSum - $brand_size;
                        $spoilage_btl_all++;
                    }
                    $peg_spoilage_all  = intval($spoilageSum / $peg_size);
                    $spoilage_all = $spoilage_btl_all . "." . $peg_spoilage_all;


                    //ncSalesSum
                    $ncSales_btl_all = 0;
                    while ($ncSalesSum >= $brand_size) {
                        $ncSalesSum = $ncSalesSum - $brand_size;
                        $ncSales_btl_all++;
                    }
                    $peg_ncSales_all  = intval($ncSalesSum / $peg_size);
                    $ncSales_all = $ncSales_btl_all . "." . $peg_ncSales_all;


                    //cocktailSalesSum
                    $cocktail_btl_all = 0;
                    while ($cocktailSalesSum >= $brand_size) {
                        $cocktailSalesSum = $cocktailSalesSum - $brand_size;
                        $cocktail_btl_all++;
                    }
                    $peg_cocktail_all  = intval($cocktailSalesSum / $peg_size);
                    $cocktail_all = $cocktail_btl_all . "." . $peg_cocktail_all;

                    //banquetSum
                    $banquet_btl_all = 0;
                    while ($banquetSum >= $brand_size) {
                        $banquetSum = $banquetSum - $brand_size;
                        $banquet_btl_all++;
                    }
                    $peg_banquet_all  = $banquetSum / $peg_size;
                    $banquet_all = $banquet_btl_all . "." . $peg_banquet_all;

                    // comsumption 
                    $comsumptionSum = $totalSum - $closingSum;
                    $btl_comsumption_all  = 0;
                    while ($comsumptionSum >= $brand_size) {
                        $comsumptionSum = $comsumptionSum - $brand_size;
                        $btl_comsumption_all++;
                    }
                    $peg_comsumption_all  = intval($comsumptionSum / $brandListName['peg_size']);
                    $arr = [
                        'Type' => '',
                        'name' => 'SUBTOTAL',
                        'btl_size' => '',
                        'open' => $open_all,
                        'receipt' => $receipt_all,
                        'total' => $total_all,
                        'sales' => $sales_all,
                        'nc_sales' => $ncSales_all,
                        'cocktail_sales' => $cocktail_all,
                        'banquet_sales' => $banquet_all,
                        'spoilage_sales' => $spoilage_all,
                        'closing' => $closing_all,
                        'physical' => $physical_all,
                        'variance' => intval($physical_all) - intval($closing_all),
                        'comsumption' => $btl_comsumption_all . "." . $peg_comsumption_all,
                        'selling_variance' => $selling_variance,
                        'cost_variance' => $cost_variance
                    ];
                    if ($arr['total'] != '0.0' || $arr['closing'] != '0.0' || $arr['physical'] != '0.0')
                        array_push($json, $arr);
                }
            }
        }
        //exit();
        return json_encode($json);
    }
    public function BarVarianceReportMl(Request $request)
    {
        $Category = Category::select('id', 'name')->get();
        $json = [];

        foreach ($Category as $Category_data) {
            // echo "<pre>";print_r($Category_data);

            $brands_data = DB::table("brands")
                ->select('btl_size', 'category_id', 'id', 'peg_size')
                ->where('category_id', '=', $Category_data['id'])->orderBy('btl_size', 'DESC')->groupBy(DB::raw("btl_size"))
                ->get();

            foreach ($brands_data as  $brandList) {

                $brand_size = $brandList->btl_size;
                //$brand_id = $brandList->id;
                $data_cat = $Category_data['name'] . "-" . $brand_size;

                $brandName_Data = Brand::where(['category_id' => $brandList->category_id, 'btl_size' => $brand_size])->get();
                $total = 0;
                $brand_open_btl = 0;

                $openSum = 0;
                $receiptSum = 0;
                $totalSum = 0;
                $salesSum = 0;
                $closingSum = 0;
                $physicalSum = 0;
                $banquetSum = 0;
                $spoilageSum = 0;
                $ncSalesSum = 0;
                $cocktailSalesSum = 0;
                $selling_variance = 0;
                $cost_variance = 0;

                $arrCat = [
                    'name' => $data_cat,
                    'open' => '',
                    'receipt' => '',
                    'total' => '',
                    'sales' => '',
                    'nc_sales' => '',
                    'cocktail_sales' => '',
                    'banquet_sales' => '',
                    'spoilage_sales' => '',
                    'closing' => '',
                    'physical' => '',
                    'variance' => '',
                    'selling_variance' => '',
                    'cost_variance' => ''
                ];

                foreach ($brandName_Data as  $brandListName) {
                    $isMinus = false;

                    $arr['name'] = $brandListName['name'];


                    $data_daily_opening = DB::table("daily_openings")
                        ->select('qty')
                        ->where('company_id', '=', $request->company_id)
                        ->where('date', '=', date('Y-m-d', strtotime($request->from_date . '+1 day')))
                        ->where('brand_id', '=', $brandListName['id'])
                        ->get()->first();
                    $qty = !empty($data_daily_opening->qty) ? $data_daily_opening->qty : '0';
                    $openSum = $openSum + $qty;
                    $balance = DB::table('purchases')->where(['brand_id' => $brandListName['id'], 'company_id' => $request->company_id])->whereBetween('invoice_date', [$request->from_date, $request->to_date])->sum('qty');
                    $receiptSum = $receiptSum + $balance;

                    $total = $qty + $balance;
                    $totalSum = $totalSum + $total;

                    $sales = DB::table('sales')->where(['brand_id' => $brandListName['id'], 'company_id' => $request->company_id, 'sales_type' => '1', 'is_cocktail' => '0'])->whereBetween('created_at', [$request->from_date, $request->to_date])->sum('qty');

                    $nc_sales = DB::table('sales')->where(['brand_id' => $brandListName['id'], 'company_id' => $request->company_id, 'is_cocktail' => '0', 'sales_type' => 2])->whereBetween('created_at', [$request->from_date, $request->to_date])->sum('qty');
                    $cocktail_sales = DB::table('sales')->where(['brand_id' => $brandListName['id'], 'company_id' => $request->company_id, 'is_cocktail' => '1'])->whereBetween('created_at', [$request->from_date, $request->to_date])->sum('qty');

                    $banquet_sales = DB::table('sales')->where(['brand_id' => $brandListName['id'], 'company_id' => $request->company_id, 'sales_type' => '3'])->whereBetween('created_at', [$request->from_date, $request->to_date])->sum('qty');

                    $spoilage_sales = DB::table('sales')->where(['brand_id' => $brandListName['id'], 'company_id' => $request->company_id, 'sales_type' => '4'])->whereBetween('created_at', [$request->from_date, $request->to_date])->sum('qty');

                    $banquetSum = $banquetSum + $banquet_sales;
                    $spoilageSum = $spoilageSum + $spoilage_sales;
                    $ncSalesSum = $ncSalesSum + $nc_sales;
                    $cocktailSalesSum = $cocktailSalesSum + $cocktail_sales;



                    $salesSum = $salesSum + $sales;
                    $closing = ($total - ($sales + $nc_sales + $banquet_sales + $spoilage_sales));
                    $closingSum = $closingSum + $closing;

                    $PhyQty = Stock::where(['company_id' => $request->company_id, 'brand_id' => $brandListName['id']])->get()->first();

                    $PhyClosing = !empty($PhyQty['qty']) ? $PhyQty['qty'] : 0;

                    $physicalSum = $physicalSum + $PhyClosing;

                    $variance = $PhyClosing - $closing;

                    $brand_size = $brandListName['btl_size'];
                    if ($variance < 0) {
                        $isMinus = true;
                        $variance = abs($variance);
                    }

                    $arr['open'] = $qty;
                    //$brand_open_btl = $btl_opening++;

                    $arr['receipt'] = $balance;


                    $arr['total'] = $total;
                    $arr['sales'] = $sales;

                    $arr['nc_sales'] = $nc_sales;


                    $arr['cocktail_sales'] = $cocktail_sales;

                    $arr['banquet_sales'] = $banquet_sales;
                    $arr['spoilage_sales'] = $spoilage_sales;


                    $arr['closing'] = $closing;

                    $arr['physical'] = $PhyClosing;
                    $arr['variance'] = $variance;

                    // $arr['selling_price'] = $btl_selling_price;

                    // selling price variance

                    if ($arr['total'] != '0.0' || $arr['closing'] != '0.0' || $arr['physical'] != '0.0') {
                        if (!in_array($arrCat, $json)) {
                            array_push($json, $arrCat);
                        }
                        array_push($json, $arr);
                    }
                }

                if (count($brandName_Data) > 0) {
                    $openSum = 0;
                    $receiptSum = 0;
                    $totalSum = 0;
                    $salesSum = 0;
                    $closingSum = 0;
                    $physicalSum = 0;
                    $banquetSum = 0;
                    $spoilageSum = 0;
                    $ncSalesSum = 0;
                    $cocktailSalesSum = 0;
                    $arr = [
                        'name' => 'SUBTOTAL',
                        'open' => $openSum,
                        'receipt' => $receiptSum,
                        'total' => $totalSum,
                        'sales' => $salesSum,
                        'nc_sales' => $ncSalesSum,
                        'cocktail_sales' => $cocktailSalesSum,
                        'banquet_sales' => $banquetSum,
                        'spoilage_sales' => $spoilageSum,
                        'closing' => $closingSum,
                        'physical' => $physicalSum,
                        'variance' => $physicalSum - $closingSum
                    ];
                    if ($arr['total'] != '0.0' || $arr['closing'] != '0.0' || $arr['physical'] != '0.0')
                        array_push($json, $arr);
                }
            }
        }
        //exit();
        return json_encode($json);
    }
    public function AddTransaction(Request $request)
    {
        //echo "<pre>";print_r($request);
        $data = $request->validate([
            'company_id' => 'required',
            'company_to_id' => 'required'
        ]);
        $data['created_by'] = $request->user()->id;
        $brand = explode(',', $request->brand_id);
        $nobtl = explode(',', $request->nobtl);
        $saved = false;
        $counter = 0;
        $skipped = 0;
        foreach ($brand as $key => $item) {
            $data['brand_id'] = $item;
            $brandSize = Brand::select('btl_size', 'category_id')->where('id', $data['brand_id'])->get()->first();
            $MlSize = ($brandSize['btl_size'] * $nobtl[$key]);
            $data['qty'] = $MlSize;
            $data['btl'] = $nobtl[$key];
            $data['date'] = date('Y-m-d', strtotime($request->date));
            $Transaction = new Transaction($data);
            if ($Transaction->save()) {
                if (Stock::where(['company_id' => $request->company_id,  'brand_id' => $data['brand_id']])->decrement('qty', $MlSize)) {
                    // add item in stocks of receiver company
                    $stockNum = Stock::where(['company_id' => $request->company_to_id,  'brand_id' => $data['brand_id']])->get()->count();
                    
                    if ($stockNum > 0)
                        Stock::where(['company_id' => $request->company_to_id,  'brand_id' => $data['brand_id']])->increment('qty', $MlSize); // if item already exist in store
                    else {
                        // if item is new in store
                        $data['company_id'] = $request->company_to_id;
                        $data['category_id'] = $brandSize['category_id'];
                        $data['brand_id'] = $data['brand_id'];
                        $data['qty'] = $MlSize;
                        $manage_stock = new Stock($data);
                        $manage_stock->save();
                    }
                }
                $saved = true;
                $counter++;
            } else {
                $skipped++;
            }
        }
        if ($saved) {
            // logs
            SaveLog([
                'user_type' => $request->user()->type,
                'user_id' => $request->user()->id,
                'ip' => $request->ip(),
                'log' => 'add transaction',
                'platform' => 'web'
            ]);
            return response()->json([
                'message' => $counter . ' successful, ' . $skipped . ' Entries failed',
                'type' => 'success'
            ], 201);
        }
        return response()->json([
            'message' => 'Oops! Operation failed',
            'type' => 'failed'
        ], 401);
    }
    // fetch Tp search
    public function fetchOpeningData(Request $request)
    {
        $data = $request->validate([
            'company_id' => 'required'
        ]);
        if (!empty($request->keyword))
            $res = DailyOpening::select('brands.name', 'btl_size', 'peg_size', 'daily_openings.id', 'daily_openings.qty', 'daily_openings.date', 'categories.name as category', 'stocks.*')->join('brands', 'brands.id', '=', 'daily_openings.brand_id')->join('categories', 'brands.category_id', '=', 'categories.id')->join('stocks', 'brands.id', '=', 'stocks.brand_id')->where(['daily_openings.company_id' => $data['company_id'], ['brands.name', 'like', '%' . $request->keyword . '%'], 'daily_openings.status' => 1])->groupBy(DB::raw("daily_openings.brand_id"))->get();
        else
            $res = DailyOpening::select('brands.name', 'btl_size', 'peg_size', 'daily_openings.id', 'categories.name as category', 'daily_openings.qty', 'daily_openings.date', 'stocks.*')->join('brands', 'brands.id', '=', 'daily_openings.brand_id')->join('categories', 'brands.category_id', '=', 'categories.id')->join('stocks', 'brands.id', '=', 'stocks.brand_id')->where(['daily_openings.company_id' => $data['company_id'], 'daily_openings.status' => 1])->groupBy(DB::raw("daily_openings.brand_id"))->get();
        if ($res) {
            return response()->json($res);
        } else {
            return response()->json([
                'message' => 'Oops! operation failed!',
                'type' => 'failed'
            ]);
        }
    }
    public function getPhysicalData(Request $request)
    {
        $data = $request->validate([
            'company_id' => 'required'
        ]);
        if (!empty($request->keyword))
            $res = Stock::join('brands', 'stocks.brand_id', '=', 'brands.id', 'physical_closing as qty', 'brands.btl_size', 'brands.peg_size')->join('categories', 'categories.id', '=', 'brands.category_id')
                ->where('stocks.company_id', $data['company_id'])->where('stocks.physical_closing', '>', 0)
                ->where('brands.name', 'like', '%' . $request->keyword . '%')
                ->get();

        else
            $res = Stock::select('stocks.*', 'brands.name', 'categories.name as category', 'physical_closing as qty', 'brands.btl_size', 'brands.peg_size')->join('brands', 'stocks.brand_id', '=', 'brands.id')->join('categories', 'categories.id', '=', 'brands.category_id')
                ->where('stocks.company_id', $data['company_id'])->where('stocks.physical_closing', '>', 0)
                ->get();

        if ($res) {
            return response()->json($res);
        } else {
            return response()->json([
                'message' => 'Oops! operation failed!',
                'type' => 'failed'
            ]);
        }
    }
}
