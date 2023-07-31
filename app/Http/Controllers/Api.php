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
use App\Models\LinkCompany;
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
        $data['company_id'] = $request->company_id ? $request->company_id : 0;
        $data['read'] = json_encode($request->read);
        $data['write_module'] = json_encode($request->write);
        $writeArr = [];
        foreach ($request->read as $read) {
            if ($read == 'company')
                array_push($writeArr, "manage company");
            if ($read == 'supplier')
                array_push($writeArr, "manage supplier");
            if ($read == 'category')
                array_push($writeArr, "category");
            if ($read == 'brand')
                array_push($writeArr, "manage brand");
            if ($read == 'tp')
                array_push($writeArr, "manage tp");
            if ($read == 'sale')
                array_push($writeArr, "manage sale");
            if ($read == 'transfer')
                array_push($writeArr, "Manage Transfer");
            if ($read == 'menu master')
                array_push($writeArr, "Manage Menu");
            if ($read == 'stocks')
                array_push($writeArr, "stocks");
            if ($read == 'user')
                array_push($writeArr, "Manage User");
        }
        // if the count of manage pages are 2 than user will get edit and delete option
        foreach ($request->write as $write) {
            if ($write == 'company')
                array_push($writeArr, "create companies", "link companies", "manage company");
            if ($write == 'supplier')
                array_push($writeArr, "create supplier", "manage supplier");
            if ($write == 'category')
                array_push($writeArr, "category");
            if ($write == 'brand')
                array_push($writeArr, "type master", "create brand", "manage brand");
            if ($write == 'tp')
                array_push($writeArr, "Tp entry", "manage tp");
            if ($write == 'sale')
                array_push($writeArr, "create sale", "manage sale");
            if ($write == 'transfer')
                array_push($writeArr, "Transfer Entry", "Manage Transfer");
            if ($write == 'menu master')
                array_push($writeArr, "Create Menu", "Manage Menu");
            if ($write == 'stocks')
                array_push($writeArr, "stocks");
            if ($write == 'user')
                array_push($writeArr, "Create User", "Manage User");
        }
        $data['write'] = json_encode($writeArr);
        $data['password'] = bcrypt($data['password']);
        $data['type'] = $request->type; // type client
        $data['created_by'] = $request->user()->id;
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
        $writeArr = [];
        if ($request->type == 1) {
            $data['read'] = json_encode($request->read);
            foreach ($request->read as $read) {
                if ($read == 'company')
                    array_push($writeArr, "manage company");
                if ($read == 'supplier')
                    array_push($writeArr, "manage supplier");
                if ($read == 'brand')
                    array_push($writeArr, "manage brand");
                if ($read == 'tp')
                    array_push($writeArr, "manage tp");
                if ($read == 'category')
                    array_push($writeArr, "category");
                if ($read == 'sales')
                    array_push($writeArr, "manage sale");
                if ($read == 'transfer')
                    array_push($writeArr, "Manage Transfer");
                if ($read == 'menu master')
                    array_push($writeArr, "Manage Menu");
                if ($read == 'stocks')
                    array_push($writeArr, "stocks");
                if ($read == 'reports')
                    array_push($writeArr, "reports", "flr");
                if ($read == 'user')
                    array_push($writeArr, "Manage User");
            }
            // if the count of manage pages are 2 than user will get edit and delete option
            foreach ($request->write as $write) {
                if ($write == 'company')
                    array_push($writeArr, "create companies", "link companies", "manage company");
                if ($write == 'supplier')
                    array_push($writeArr, "create supplier", "manage supplier");
                if ($write == 'brand')
                    array_push($writeArr, "type master", "create brand", "manage brand");
                if ($write == 'category')
                    array_push($writeArr, "category");
                if ($write == 'tp')
                    array_push($writeArr, "Tp entry", "manage tp");
                if ($write == 'sales')
                    array_push($writeArr, "create sale", "manage sale");
                if ($write == 'transfer')
                    array_push($writeArr, "Transfer Entry", "Manage Transfer");
                if ($write == 'menu master')
                    array_push($writeArr, "Create Menu", "Manage Menu");
                if ($write == 'stocks')
                    array_push($writeArr, "stocks");
                if ($write == 'user')
                    array_push($writeArr, "Create User", "Manage User");
            }
            $data['write'] = json_encode($writeArr);
            $data['write_module'] = json_encode($request->write);
        }
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

    public function getPermission(Request $request)
    {
        $user = User::select('read', 'write', 'type', 'company_id')->where('id', $request->user()->id)->get()->first();
        if ($user) {
            if (!empty($user['company_id'])) {
                $company_name = Company::select('name')->where('id', $user['company_id'])->get()->first();
                $user['company_name'] = $company_name['name'];
                return response()->json($user);
            }
            return response()->json($user);
        }
        return response()->json([
            'message' => 'Oops! Operation failed',
            'type' => 'failed'
        ], 401);
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
        $data['name'] = strtoupper($data['name']);
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
        $data['name'] = strtoupper($data['name']);
        $data['short_name'] = strtoupper($data['short_name']);
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
            }
        }
        return response()->json([
            'message' => 'Oops! Operation failed',
            'type' => 'failed'
        ], 401);
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
            $data['name'] = strtoupper($data['name']);
            $data['short_name'] = strtoupper($data['short_name']);
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
            $data['short_name'] = strtoupper($data['short_name']);
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
        $brandSize = Brand::select('btl_size', 'peg_size')->where('id', $data['brand_id'])->get();
        $MlSize = ($brandSize[0]['btl_size'] * $data['btl']) + ($brandSize[0]['peg_size'] * $data['peg']);
        $PMlSize = ($brandSize[0]['btl_size'] * $Pbtl) + ($brandSize[0]['peg_size'] * $Ppeg);
        $count = Stock::where(['company_id' => $request->company_id,  'brand_id' => $request->brand_id])->get()->count();
        if ($count > 0) {

            $store_btl = $request->store_btl;
            $store_peg = $request->store_peg;
            $bar1_btl = $request->bar1_btl;
            $bar1_peg = $request->bar1_peg;
            $bar2_btl = $request->bar2_btl;
            $bar2_peg = $request->bar2_peg;
            //update stock
            if ($request->handleType == 1) {
                Stock::where(['company_id' => $request->company_id,  'brand_id' => $request->brand_id])->update(['qty' => $PMlSize, 'physical_closing' => $PMlSize, 'cost_price' => $data['cost_price'], 'btl_selling_price' => $data['btl_selling_price'], 'peg_selling_price' => $data['peg_selling_price'], 'store_btl' => $store_btl, 'store_peg' => $store_peg, 'bar1_btl' => $bar1_btl, 'bar1_peg' => $bar1_peg, 'bar2_btl' => $bar2_btl, 'bar2_peg' => $bar2_peg]);
            } else {
                Stock::where(['company_id' => $request->company_id,  'brand_id' => $request->brand_id])->update(['qty' => $MlSize, 'physical_closing' => $PMlSize, 'cost_price' => $data['cost_price'], 'btl_selling_price' => $data['btl_selling_price'], 'peg_selling_price' => $data['peg_selling_price'], 'store_btl' => $store_btl, 'store_peg' => $store_peg, 'bar1_btl' => $bar1_btl, 'bar1_peg' => $bar1_peg, 'bar2_btl' => $bar2_btl, 'bar2_peg' => $bar2_peg]);
            }
        } else {
            $data['qty'] = $MlSize;
            //Stock entry
            $data['physical_closing'] = $PMlSize;
            $manage_stock = new Stock($data);
            if ($manage_stock->save() && $request->handleType == 0) {
                $opening['company_id'] = $request->company_id;
                $opening['brand_id'] = $request->brand_id;
                $opening['qty'] = $MlSize;
                $opening['date'] = date('Y-m-d', strtotime($request->openingDate));
                $saveOpening = new DailyOpening($opening);
                $saveOpening->save();
            }
            // update daily opening table for storing entry history

        }
        if ($request->handleType == 1) {
            $phy['company_id'] = $request->company_id;
            $phy['brand_id'] = $request->brand_id;
            $phy['qty'] = $PMlSize;
            $phy['date'] = date('Y-m-d', strtotime($request->physicalDate));
            $phy['status'] = 1;
            $phy_save = new physical_history($phy);
            if ($phy_save->save()) {
                $opening['company_id'] = $request->company_id;
                $opening['brand_id'] = $request->brand_id;
                $opening['qty'] = $PMlSize;
                $opening['date'] = date('Y-m-d', strtotime($request->physicalDate . '+1 day'));
                $saveOpening = new DailyOpening($opening);
                $saveOpening->save();
            }
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
    // manage opening
    public function manage_opening(Request $request)
    {
        $brands = explode(',', $request->brand_id);
        $no_btl = explode(',', $request->btl);
        $no_peg = explode(',', $request->peg);
        $isSaved = false;
        $total = 0;
        foreach ($brands as $key => $brand) {
            $brandSize = Brand::select('btl_size', 'category_id', 'peg_size')->where('id', $brand)->get();
            if (isset($brandSize)) {
                $MlSize = ($brandSize[0]['btl_size'] * intval($no_btl[$key])) + ($brandSize[0]['peg_size'] * intval($no_peg[$key]));
                $count = DailyOpening::where(['company_id' => $request->company_id,  'brand_id' => $brand, 'status' => 1])->get()->count();
                if ($count > 0) {
                    // update existing entry
                    Stock::where(['company_id' => $request->company_id,  'brand_id' => $brand])->update(['qty' => $MlSize]);
                    // update daily opening
                    DailyOpening::where(['company_id' => $request->company_id,  'brand_id' => $brand])->update(['qty' => $MlSize]);
                    $isSaved = true;
                    $total++;
                } else {
                    // add new stock
                    $stock = new Stock(array(
                        'company_id' => $request->company_id,
                        'category_id' => $brandSize[0]['category_id'],
                        'brand_id' => $brand,
                        'qty' => $MlSize,
                    ));
                    if ($stock->save())
                        $isSaved = true;
                    //opening
                    $opening['company_id'] = $request->company_id;
                    $opening['brand_id'] = $brand;
                    $opening['qty'] = $MlSize;
                    $opening['date'] = date('Y-m-d', strtotime($request->openingDate));
                    $saveOpening = new DailyOpening($opening);
                    $saveOpening->save();
                    $total++;
                }
            }
        }
        if (($isSaved)) {
            return response()->json([
                'message' => $total . ' Item opening added',
                'type' => 'success'
            ], 201);
        } else {
            return response()->json([
                'message' => 'Oops! Operation failed',
                'type' => 'failed'
            ], 401);
        }
    }
    // manage physical
    public function manage_physical(Request $request)
    {
        $brands = explode(',', $request->brand_id);
        // store
        $store_btl = explode(',', $request->store_btl);
        $store_peg = explode(',', $request->store_peg);
        // bar 1
        $bar1_btl = explode(',', $request->bar1_btl);
        $bar1_peg = explode(',', $request->bar1_peg);
        // bar 2
        $bar2_btl = explode(',', $request->bar2_btl);
        $bar2_peg = explode(',', $request->bar2_peg);
        $isSaved = false;
        $total = 0;
        foreach ($brands as $key => $brand) {
            $brandSize = Brand::select('btl_size', 'category_id', 'peg_size')->where('id', $brand)->get();
            if (isset($brandSize)) {
                $MlStore = ($brandSize[0]['btl_size'] * intval($store_btl[$key])) + ($brandSize[0]['peg_size'] * intval($store_peg[$key]));
                $MlBar1 = ($brandSize[0]['btl_size'] * intval($bar1_btl[$key])) + ($brandSize[0]['peg_size'] * intval($bar1_peg[$key]));
                $MlBar2 = ($brandSize[0]['btl_size'] * intval($bar2_btl[$key])) + ($brandSize[0]['peg_size'] * intval($bar2_peg[$key]));
                $MlSize = $MlStore + $MlBar1 + $MlBar2;
                $count = physical_history::where(['company_id' => $request->company_id,  'brand_id' => $brand])->whereDate('date', '=', $request->physicalDate)->get()->count();
                if ($count == 0) {
                    $phy['company_id'] = $request->company_id;
                    $phy['brand_id'] = $brand;
                    $phy['qty'] = $MlSize;
                    $phy['date'] = date('Y-m-d', strtotime($request->physicalDate));
                    $phy['status'] = 1;
                    $phy_save = new physical_history($phy);
                    if ($phy_save->save()) {
                        $opening['company_id'] = $request->company_id;
                        $opening['brand_id'] = $brand;
                        $opening['qty'] = $MlSize;
                        $opening['date'] = date('Y-m-d', strtotime($request->physicalDate . '+1 day'));
                        $saveOpening = new DailyOpening($opening);
                        $saveOpening->save();
                    }
                    // update existing entry
                    Stock::where(['company_id' => $request->company_id,  'brand_id' => $brand])->update(['qty' => $MlSize, 'physical_closing' => $MlSize]);
                    $isSaved = true;
                    $total++;
                }
            }
        }
        if (($isSaved)) {
            return response()->json([
                'message' => $total . ' physical updated',
                'type' => 'success'
            ], 201);
        } else {
            return response()->json([
                'message' => 'Oops! Operation failed',
                'type' => 'failed'
            ], 401);
        }
    }
    // manage physical
    public function manage_price(Request $request)
    {
        $brands = explode(',', $request->brand_id);
        $cost = explode(',', $request->cost);
        $btl_sell = explode(',', $request->selling);
        $isSaved = false;
        $total = 0;
        foreach ($brands as $key => $brand) {
            $brandSize = Stock::where(['company_id' => $request->company_id,  'brand_id' => $brand])->get()->count();
            if ($brandSize > 0) {
                // update existing entry
                Stock::where(['company_id' => $request->company_id,  'brand_id' => $brand])->update(['cost_price' => $cost[$key], 'btl_selling_price' => $btl_sell[$key]]);
                $isSaved = true;
                $total++;
            } else {
                $brandData = Brand::select('category_id')->where('id', $brand)->get()->first();
                $data['category_id'] = $brandData['category_id'];
                $data['company_id'] = $request->company_id;
                $data['qty'] = 0;
                $data['physical_closing'] = 0;
                $data['brand_id'] = $brand;
                $data['cost_price'] = $cost[$key];
                $data['btl_selling_price'] = $btl_sell[$key];
                $save = new Stock($data);
                if ($save->save()) {
                    $isSaved = true;
                    $total++;
                }
            }
        }
        if (($isSaved)) {
            return response()->json([
                'message' => $total . ' price updated',
                'type' => 'success'
            ], 201);
        }
        return response()->json([
            'message' => 'Oops! Operation failed',
            'type' => 'failed'
        ], 401);
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
                    while ($qty >= $peg_size['btl_size']) {
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
        $data = User::select('id', 'name', 'mobile', 'email', 'type', 'read', 'write', 'write_module')->where(['status' => 1, 'id' => $request->id])->get()->first();
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
        $data = Sales::select('brands.name', 'sales.no_btl', 'sales.no_peg', 'sales.sale_date', 'sales.id')->join('brands', 'brands.id', '=', 'sales.brand_id')->where(['sales.company_id' => $request->company_id, 'sales.status' => 1])->orderBy('id', 'DESC');
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
    public function getSupplier(Request $request)
    {
        $data = Supplier::where(['status' => 1])->get();
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
    public function getSupplierOptions(Request $request)
    {
        $data = Supplier::select('id', 'name as label', 'name as value')->where(['status' => 1])->get();
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
        if ($data) {
            return response()->json($data);
        } else {
            return response()->json([
                'message' => 'Oops! operation failed!',
                'type' => 'failed'
            ]);
        }
    }
    public function getlinkedList(Request $request)
    {
        $data = LinkCompany::select('companies.name', 'companies.license_no', 'link_companies.id')->join('companies', 'companies.id', 'link_companies.link_company_id')->where(['link_companies.status' => 1, 'company_id' => $request->company_id])->get();
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
    public function deleteLinkApi(Request $request)
    {
        $data = $request->validate([
            'id' => 'required'
        ]);
        $data = LinkCompany::where('id', $data['id'])->update(['status' => 0]);
        if ($data) {
            return response()->json([
                'message' => 'Company Unlinked',
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
       /* $data = $request->validate([
            'company_id' => 'required',
            'invoice_no' => Rule::unique('purchases')->where(function ($query) {
                $query->where('status', 1);
            }),
        ]); */
        
        $data = $request->validate([
            'company_id' => 'required',
            'vendor_id' => 'required',
            'invoice_no' => Rule::unique('purchases')->where(function ($query) use ($request) {
                $query->where('status', 1)->where('vendor_id', $request->vendor_id);
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
        $data['isInvoice'] = $request->isInvoice;
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
        $brands = Brand::select('name as value', DB::raw('CONCAT(brands.id," - ",name," - ",btl_size) as label'), 'brands.id', 'brands.category_id', DB::raw('0 as recipe'))->join('stocks', 'stocks.brand_id', '=', 'brands.id')->where(['brands.status' => 1, 'stocks.company_id' => $request->company_id, ['stocks.qty', '>', 0]])->get();
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
            $data = Transaction::select('transactions.id', 'companies.name as company', 'brands.name', 'brands.id as brand_id', 'transactions.btl', 'transactions.qty', 'brands.btl_size',  'transactions.date')->join('brands', 'brands.id', '=', 'transactions.brand_id')->join('companies', 'companies.id', 'transactions.company_to_id')->where(['transactions.company_id' => $request->company_id])->orderBy('transactions.id', 'DESC')->get();
        else
            $data = Transaction::select('transactions.id', 'brands.name', 'companies.name as company', 'brands.id as brand_id', 'transactions.btl', 'brands.btl_size', 'transactions.qty', 'transactions.date')->join('companies', 'companies.id', 'transactions.company_id')->join('brands', 'brands.id', '=', 'transactions.brand_id')->where(['transactions.company_to_id' => $request->company_id])->orderBy('transactions.id', 'DESC')->get();
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
        $count = Stock::where(['company_id' => $req['company_id'], 'brand_id' => $req['brand_id']])->get()->count();
        $brandSize = Brand::where('id', $req['brand_id'])->get();
        if ($count == 0) {
            array_push($response, array('btl' => 0, 'peg' => 0, 'btl_size' => $brandSize[0]['btl_size'], 'peg_size' => $brandSize[0]['peg_size'], 'cost_price' => 0, 'btl_selling_price' => 0));
            return $response;
        }
        $data = Stock::where(['company_id' => $req['company_id'], 'brand_id' => $req['brand_id']])->get();

        if ($data) {
            $qty = !empty($data[0]['qty']) ? $data[0]['qty'] : 0;
            $openingData = DailyOpening::where(['company_id' => $req['company_id'], 'brand_id' => $req['brand_id']])->orderBy('id', 'DESC')->get()->first();
            $openingQty = !empty($openingData['qty']) ? $openingData['qty'] : 0;
            $result = getBtlPeg($req['brand_id'], $qty);
            $opening = getBtlPeg($req['brand_id'], $openingQty);
            if (empty($result['btl']) && empty($result['peg'])) {
                array_push($response, array('btl' => 0, 'peg' => 0, 'btl_size' => $brandSize[0]['btl_size'], 'peg_size' => $brandSize[0]['peg_size'], 'cost_price' => $data[0]['cost_price'], 'btl_selling_price' => $data[0]['btl_selling_price']));
                return $response;
            }
            $data[0]['op_btl'] = $opening['btl'];
            $data[0]['op_peg'] = intval($opening['peg']);
            $data[0]['date'] = empty($openingData['date']) ? '' : $openingData['date'];

            $data[0]['btl'] = $result['btl'];
            $data[0]['peg'] = intval($result['peg']);
            $data[0]['btl_size'] = $result['btl_size'];
            $data[0]['peg_size'] = $result['peg_size'];
        } else {
            array_push($response, array('btl' => 0, 'peg' => 0, 'btl_size' => $brandSize[0]['btl_size'], 'peg_size' => $brandSize[0]['peg_size']));
            return $response;
        }

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
            $brandSize = Brand::select('id', 'category_id', 'btl_size', 'peg_size')->where([['name', 'like', '%' . $brandName . '%'], 'btl_size' => $dataArr['btl_size']])->get();
            if (count($brandSize) > 0) {
                $count = Stock::where(['company_id' => $company_id, 'brand_id' => $brandSize[0]['id'], 'status' => 1])->get()->count();
                $MlSize = ($brandSize[0]['btl_size'] * $btl) + ($brandSize[0]['peg_size'] * $peg);
                $data['category_id'] = $brandSize[0]['category_id'];
                $data['brand_id'] = $brandSize[0]['id'];
                if ($count === 0) {
                    $data['qty'] = $MlSize;
                    $data['physical_closing'] = $MlSize;
                    $data['cost_price'] = $dataArr['cost_price'] ? $dataArr['cost_price'] : 0;
                    $data['btl_selling_price'] = $dataArr['btl_selling_price'] ? $dataArr['btl_selling_price'] : 0;
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
            $brandSize = Brand::select('id', 'category_id', 'btl_size', 'peg_size')->where([['name', 'like', '%' . $brandName . '%'], 'btl_size' => $dataArr['btl_size']])->get();
            if (count($brandSize) > 0) {
                $count = Stock::where(['company_id' => $company_id, 'brand_id' => $brandSize[0]['id']])->get()->count();
                $MlSize = ($brandSize[0]['btl_size'] * $btl) + ($brandSize[0]['peg_size'] * $peg);
                $data['category_id'] = $brandSize[0]['category_id'];
                $data['brand_id'] = $brandSize[0]['id'];
                $data['qty'] = $MlSize;
                if ($count > 0) {
                    $data['physical_closing'] = $MlSize;
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
                    Stock::where(['company_id' => $company_id, 'brand_id' => $brandSize[0]['id']])->update($data);

                    $phy['company_id'] = $company_id;
                    $phy['brand_id'] = $brandSize[0]['id'];
                    $phy['qty'] = $MlSize;
                    $phy['date'] =  date('Y-m-d', strtotime($dataArr['date']));
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
            $supplier = Supplier::select('id')->where([['name', 'like', '%' . $dataArr['supplier'] . '%']])->get();
            if (empty($supplier[0]['id'])) {
                array_push($failedData, $brandName);
                $skipped++;
                continue;
            }
            $data['vendor_id'] = $supplier[0]['id'];
            $data['company_id'] = $company_id;
            // $data['branch_id'] = $branch_id;
            $brandSize = Brand::select('id', 'category_id', 'btl_size', 'peg_size')->where([['name', 'like', '%' . $brandName . '%'], 'btl_size' => $dataArr['btl_size']])->get();
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
            $brands = Brand::select('id as brand_id', 'category_id')->where(['name' => $name, 'status' => 1, 'btl_size' => $dataAr['btl_size']])->get();
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
        $data = Sales::select('brands.name', 'brands.id as brand_id', 'sales.sales_type as type', 'sales.no_btl', 'sales.qty', 'sales.no_peg', 'sales.sale_date', 'sales.id')->join('brands', 'brands.id', '=', 'sales.brand_id')->where(['sales.company_id' => $request->company_id, 'sales.status' => 1])->orderBy('id', 'DESC')->get();
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
        //$data = Purchase::where(['invoice_no' => $request->invoice_no, 'company_id' => $request->company_id, 'status' => 1])->get()->count();
       $data = Purchase::where(['invoice_no' => $request->invoice_no, 'company_id' => $request->company_id, 'status' => 1 , 'vendor_id' => $request->vendor_id])->get()->count();
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
        $data = Purchase::select('brands.name', 'purchases.*')->join('brands', 'brands.id', '=', 'purchases.brand_id')->where(['purchases.status' => 1, 'purchases.invoice_no' => $request->id , 'purchases.vendor_id' => $request->vendor_id])->orderBy('id', 'DESC')->get();
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
        $data = Sales::select(DB::raw('sum(sale_price) as sale_price'), DB::raw('date(sale_date) as date'))->where(['company_id' => $request->company_id, 'status' => 1])->whereRaw('sale_date > DATE_SUB(NOW(), INTERVAL 30 DAY)')->groupBy('sale_date')->orderBy('sale_date', 'ASC')->get();
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
            if (Stock::where(['company_id' => $data['company_id'], 'brand_id' => $data['brand_id'], ['qty', '>', $MlSize]])->get()->count() > 0) {
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
            'message' => 'Oops! Operation failed Or Stock unavailable',
            'type' => 'failed'
        ], 401);
    }


    public function LinkCompany(Request $request)
    {
        $data = $request->validate([
            'company_id' => 'required',
            'link_company_id' => 'required',

        ]);
        $data['created_by'] = $request->user()->id;
        $company = new LinkCompany($data);
        if ($company->save()) {
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
                    'message' => 'Company Linked',
                    'type' => 'success'
                ], 201);
            }
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
            $res = DailyOpening::select('brands.name', 'btl_size', 'peg_size', 'daily_openings.id', 'daily_openings.qty', 'daily_openings.date', 'categories.name as category')->join('brands', 'brands.id', '=', 'daily_openings.brand_id')->join('categories', 'brands.category_id', '=', 'categories.id')->where(['daily_openings.company_id' => $data['company_id'], ['brands.name', 'like', '%' . $request->keyword . '%'], 'daily_openings.status' => 1])->get();
        else
            $res = DailyOpening::select('brands.name', 'btl_size', 'peg_size', 'daily_openings.id', 'categories.name as category', 'daily_openings.qty', 'daily_openings.date')->join('brands', 'brands.id', '=', 'daily_openings.brand_id')->join('categories', 'brands.category_id', '=', 'categories.id')->where(['daily_openings.company_id' => $data['company_id'], 'daily_openings.status' => 1])->get();
        if ($res) {
            return response()->json($res);
        } else {
            return response()->json([
                'message' => 'Oops! operation failed!',
                'type' => 'failed'
            ]);
        }
    }
    public function getPriceList(Request $request)
    {
        $data = $request->validate([
            'company_id' => 'required'
        ]);
        if (!empty($request->keyword))
            $res = Stock::join('brands', 'stocks.brand_id', '=', 'brands.id', 'cost_price', 'btl_selling_price', 'brands.btl_size', 'brands.peg_size')->join('categories', 'categories.id', '=', 'brands.category_id')
                ->where('stocks.company_id', $data['company_id'])
                ->where('brands.name', 'like', '%' . $request->keyword . '%')
                ->get();

        else
            $res = Stock::select('stocks.*', 'brands.name', 'categories.name as category', 'cost_price', 'btl_selling_price', 'brands.btl_size', 'brands.peg_size')->join('brands', 'stocks.brand_id', '=', 'brands.id')->join('categories', 'categories.id', '=', 'brands.category_id')
                ->where('stocks.company_id', $data['company_id'])
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
    public function downloadBrands()
    {
        $brands = Brand::select('name', 'btl_size', 'peg_size')->where(['status' => 1])->get();
        if ($brands) {
            return response()->json($brands);
        } else {
            return response()->json([
                'message' => 'Oops! Operation failed',
                'type' => 'failed'
            ], 401);
        }
    }
}
