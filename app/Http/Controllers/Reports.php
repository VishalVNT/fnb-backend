<?php

namespace App\Http\Controllers;

use App\Models\Brand;
use App\Models\Category;
use App\Models\DailyOpening;
use App\Models\LinkCompany;
use App\Models\physical_history;
use App\Models\purchase;
use App\Models\Sales;
use App\Models\Stock;
use App\Models\Subcategory;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;
use DateTime;

class Reports extends Controller
{
    public function BarVarianceSummaryReport(Request $request)
    {
        $json = [];
        $comArray = [$request->company_id];
        
        $brands_data = DB::table("brands")
            ->select('id', 'btl_size', 'peg_size')
            ->where('status', 1)
            ->get();

        // Initialize the Liquor variable
        $price = 0;
        $shortage = 0;
        $excess = 0;
        $cost_beverage = 0;
        $shortage_beverage = 0;
        $excess_beverage = 0;
        $adjusted_beverage = 0;
        $adjusted_variance = 0;
        $consumption_sum = 0;

        // variables for current month
        $price2 = 0;
        $shortage2 = 0;
        $excess2 = 0;
        $cost_beverage2 = 0;
        $shortage_beverage2 = 0;
        $excess_beverage2 = 0;
        $adjusted_beverage2 = 0;
        $adjusted_variance2 = 0;
        $consumption_sum2 = 0;


        foreach ($brands_data as  $brandList) {
            $brand_id = $brandList->id;
            
            // Common date range filters for all queries
            $dateRangeFilter = [
                ['date', '>=', $request->from_date],
                ['date', '<=', $request->to_date]
            ];
        
            // FROM DATE TO DATE DATA
            $data_daily_opening = DB::table('daily_openings')
            ->selectRaw('SUM(qty) AS qty')
            ->whereIn('company_id', $comArray)
            ->where(['brand_id' => $brand_id, 'status' => 1])
            ->whereDate('date', '>=', $request->from_date)
            ->whereDate('date', '<=', $request->to_date)
            ->first();
            $opening_stock = $data_daily_opening->qty ?? 0;

            $data_purchases_qty = DB::table('purchases')
            ->selectRaw('SUM(qty) AS qty')
            ->where(['brand_id' => $brand_id, 'status' => 1])
            ->whereIn('company_id', $comArray)
            ->whereDate('invoice_date', '>=', $request->from_date)
            ->whereDate('invoice_date', '<=', $request->to_date)
            ->first();
            $purchase_qty = $data_purchases_qty->qty ?? 0;

            $physical_stock = DB::table('physical_histories')
            ->selectRaw('COALESCE(SUM(qty), 0) as physicalQty')
            ->where(['brand_id' => $brand_id, 'status' => 1])
            ->whereIn('company_id', $comArray)
            ->whereDate('date', '>=', $request->from_date)
            ->whereDate('date', '<=', $request->to_date)
            ->first();
            $physical_qty = $physical_stock->physicalQty ?? 0;

            $salesStocks = DB::table('sales')
            ->selectRaw('COALESCE(SUM(qty), 0) as saleQty')
            ->whereIn('company_id', $comArray)
            ->where(['brand_id' => $brand_id, 'status' => 1])
            ->whereDate('sale_date', '>=', $request->from_date)
            ->whereDate('sale_date', '<=', $request->to_date)
            ->first();

            $nc_sale = DB::table('sales')
            ->selectRaw('COALESCE(SUM(qty), 0) as saleQty')
            ->whereIn('company_id', $comArray)
            ->where(['brand_id' => $brand_id, 'status' => 1, 'sales_type' => 2])
            ->whereDate('sale_date', '>=', $request->from_date)
            ->whereDate('sale_date', '<=', $request->to_date)
            ->first();
            
            // CURRENT MONTH DATA
           
            $data_daily_opening2 = DB::table('daily_openings')
            ->selectRaw('SUM(qty) AS qty')
            ->whereIn('company_id', $comArray)
            ->where(['brand_id' => $brand_id, 'status' => 1])
            ->whereMonth('date', '>=', date('m', strtotime($request->from_date)))
            ->whereYear('date', '<=', date('Y', strtotime($request->to_date)))
            ->first();
            $opening_stock2 = $data_daily_opening2->qty ?? 0;

            $data_purchases_qty2 = DB::table('purchases')
                ->selectRaw('SUM(qty) AS qty')
                ->where(['brand_id' => $brand_id, 'status' => 1])
                ->whereIn('company_id', $comArray)
                ->whereDate('invoice_date', '>=',  date('m', strtotime($request->from_date)))
                ->whereDate('invoice_date', '<=',  date('Y', strtotime($request->to_date)))
                ->first();
            $purchase_qty2 = $data_purchases_qty2->qty ?? 0;

            $physical_stock2 = DB::table('physical_histories')
                ->selectRaw('COALESCE(SUM(qty), 0) as physicalQty')
                ->where(['brand_id' => $brand_id, 'status' => 1])
                ->whereIn('company_id', $comArray)
                ->whereDate('date', '>=',  date('m', strtotime($request->from_date)))
                ->whereDate('date', '<=',  date('Y', strtotime($request->to_date)))
                ->first();
            $physical_qty2 = $physical_stock2->physicalQty ?? 0;

            $salesStocks2 = DB::table('sales')
            ->selectRaw('COALESCE(SUM(qty), 0) as saleQty')
            ->whereIn('company_id', $comArray)
            ->where(['brand_id' => $brand_id, 'status' => 1])
            ->whereDate('sale_date', '>=',  date('m', strtotime($request->from_date)))
            ->whereDate('sale_date', '<=',  date('Y', strtotime($request->to_date)))
            ->first();

            $nc_sale2 = DB::table('sales')
                ->selectRaw('COALESCE(SUM(qty), 0) as saleQty')
                ->whereIn('company_id', $comArray)
                ->where(['brand_id' => $brand_id, 'status' => 1, 'sales_type' => 2])
                ->whereDate('sale_date', '>=',  date('m', strtotime($request->from_date)))
                ->whereDate('sale_date', '<=',  date('Y', strtotime($request->to_date)))
                ->first();

            
            $rate = getrateamount($brand_id); // rate of brand
            $total = $opening_stock + $purchase_qty;
            $closing =  $total - $salesStocks->saleQty;
            $variance = $physical_qty - $closing;
            $consumption = $total - $physical_qty;
            $btl_peg = convertBtlPeg($consumption, $brandList->btl_size, $brandList->btl_size); // consumption
            $consumption_cost = intval($btl_peg['btl'] * $rate['amount'] + $btl_peg['peg'] * $rate['pegprice']);
            $consumption_sum += $consumption_cost;
            $btl_sht = convertBtlPeg($variance, $brandList->btl_size, $brandList->btl_size); // variance
            if ($variance < 0) {
                $costing = $btl_sht['btl'] * $rate['amount'] + $btl_sht['peg'] * $rate['pegprice'];
                $shortage += $costing;
                $adjusted_variance += $costing - 0;
            } else {
                $costing = $btl_sht['btl'] * $rate['amount'] + $btl_sht['peg'] * $rate['pegprice'];
                $excess +=  $costing;
                $adjusted_variance += 0 - $costing;
            }
            // MTD CALCULATION
            $total2 = $opening_stock2 + $purchase_qty2;
            $closing2 =  $total2 - $salesStocks2->saleQty;
            $variance2 = $physical_qty2 - $closing2;
            $consumption2 = $total2 - $physical_qty2;
            $btl_peg2 = convertBtlPeg($consumption2, $brandList->btl_size, $brandList->btl_size); // consumption
            $consumption_cost2 = intval($btl_peg2['btl'] * $rate['amount'] + $btl_peg2['peg'] * $rate['pegprice']);
            $consumption_sum2 += $consumption_cost2;
            $btl_sht2 = convertBtlPeg($variance2, $brandList->btl_size, $brandList->btl_size); // variance
            if ($variance2 < 0) {
                $costing2 = $btl_sht2['btl'] * $rate['amount'] + $btl_sht2['peg'] * $rate['pegprice'];
                $shortage2 += $costing2;
                $adjusted_variance2 += $costing2 - 0;
            } else {
                $costing2 = $btl_sht2['btl'] * $rate['amount'] + $btl_sht2['peg'] * $rate['pegprice'];
                $excess2 +=  $costing2;
                $adjusted_variance2 += 0 - $costing2;
            }
        } //end of foreach

        $sales = array(
            'Title' => 'Net Sales Revenue',
            'Liquor' => $request->liquor !== null ? $request->liquor : 0,
            'Beverage' => $request->beverage ?? 0,
            'Total' => ($request->liquor !== null ? $request->liquor : 0) + ($request->beverage ?? 0),
            'MTD Liquor' => $request->liquor !== null ? $request->liquor : 0,
            'MTD Beverage' => $request->beverage ?? 0,
            'MTD Total' => ($request->liquor !== null ? $request->liquor : 0) + ($request->beverage ?? 0)
        );
        array_push($json, $sales);
        $consump = array(
            'Title' => 'Cost of Consumption',
            'Liquor' => $consumption_sum, // Add the liquor variable to the array
            'Beverage' => $cost_beverage,
            'Total' => $consumption_sum + $cost_beverage,
            'MTD Liquor' => $consumption_sum2,
            'MTD Beverage' =>  $cost_beverage2,
            'MTD Total' => $consumption_sum2 + $cost_beverage2,
        );
        array_push($json, $consump);
        $Shortage = array(
            'Title' => 'Shortage',
            'Liquor' => $shortage,
            'Beverage' => $shortage_beverage,
            'Total' => $shortage + $shortage_beverage,
            'MTD Liquor' => $shortage2,
            'MTD Beverage' => $shortage_beverage2,
            'MTD Total' =>  $shortage2 + $shortage_beverage2
        );
        array_push($json, $Shortage);
        $Excess = array(
            'Title' => 'Excess',
            'Liquor' => $excess,
            'Beverage' => $excess_beverage,
            'Total' => $excess + $excess_beverage,
            'MTD Liquor' =>  $excess2,
            'MTD Beverage' => $excess_beverage2,
            'MTD Total' => $excess2 + $excess_beverage2,
        );
        array_push($json, $Excess);
        $Adjusted = array(
            'Title' => 'Adjusted Variance',
            'Liquor' => $adjusted_variance,
            'Beverage' => $adjusted_beverage,
            'Total' => $adjusted_variance + $adjusted_beverage,
            'MTD Liquor' => $adjusted_variance2,
            'MTD Beverage' =>  $adjusted_beverage2,
            'MTD Total' => $adjusted_variance2 + $adjusted_beverage2,
        );
        array_push($json, $Adjusted);
        return json_encode($json);
    }
    
    public function TPRegisterReport(Request $request)
    {
        $json = [];
        $company_id = $request->company_id;
        // Common date range filters
        $dateRangeFilter = [
            ['invoice_date', '>=', $request->from_date],
            ['invoice_date', '<=', $request->to_date]
        ];
        $result = DB::table('brands')
            ->select(
                'purchases.brand_id',
                'invoice_no',
                'invoice_date',
                'categories.name as category_group',
                'brands.name as brand_name',
                'btl_size',
                DB::raw('COALESCE(qty, 0) as qty'),
                DB::raw('COALESCE(mrp, 0) as mrp'),
                DB::raw('COALESCE(total_amount, 0) as total_amount'),
                'vendor_id',
                'suppliers.name as vendor_name'
            )
            ->join('purchases', 'purchases.brand_id', '=', 'brands.id')
            ->join('categories', 'categories.id', '=', 'brands.category_id')
            ->join('suppliers', 'suppliers.id', '=', 'purchases.vendor_id')
            ->where($dateRangeFilter)
            ->where('purchases.company_id', $company_id)
            ->get();


        foreach ($result as $row) {
            $invoiceNo = $row->invoice_no;
            $invoiceDate = $row->invoice_date;
            $categoryGroup = $row->category_group;
            $brandName = $row->brand_name;
            $btlSize = $row->btl_size;
            $quantity = $row->qty;
            $rate = $row->mrp;
            $amount = $row->total_amount;
            $vendor_name = $row->vendor_name;
            $brand_id = $row->brand_id;

            $stock = getBtlPeg($brand_id, $quantity);
            $quantity1 = $stock['btl'] . "." . $stock['peg'];

            $arr = [
                'TP No.' => $invoiceNo,
                'TP Date' => $invoiceDate,
                'Group' => $categoryGroup,
                'Brand Name' => $brandName,
                'BTL Size' => $btlSize,
                'Qty' => $quantity1,
                'Rate' => $rate,
                'Amount' => $amount,
                'Vendor Name' => $vendor_name
            ];
            if (!empty($arr)) {
                array_push($json, $arr);
            }
        }
        return json_encode($json);
    }
    
    public function SalesRegisterReport(Request $request)
    {
        $json = [];
        $company_id = $request->company_id;
        $categories = Category::select('id', 'name')->get()->keyBy('id');
        $cat_array = array();
        foreach ($categories as $category) {
            $name = $category->name;
            $id = $category->id;
            
        // Common date range filters
        $dateRangeFilter = [
            ['sale_date', '>=', $request->from_date],
            ['sale_date', '<=', $request->to_date]
        ];

            $salesData = DB::table('sales')
                ->where('category_id', $id)
                ->where($dateRangeFilter)
                ->where('company_id', $company_id)
                ->get();
            foreach ($salesData as $sale) {
                $cat = array(
                    'category_name' => $name,
                    'sales_date' => '',
                    'brand_name' => '',
                    'btl_size' => '',
                    'qty_inpeg' => '',
                    'rate' => '',
                    'amount' => ''
                );
                $brandId = $sale->brand_id;
                $salesDate = $sale->sale_date;
                $sales_qty = $sale->qty;
                $no_peg = $sale->no_peg;
                $sale_price = $sale->sale_price;
                
                $brandDetails = DB::table('brands')
                    ->join('stocks', 'stocks.brand_id', '=', 'brands.id')
                    ->where('brands.id', $brandId)
                    ->first();

                if ($brandDetails) {

                    if (!in_array($name, $cat_array)) {
                        array_push($json, $cat);
                    }
                    array_push($cat_array, $name);
                    $price = getrateamount($brandId);
                    $pegprice = $price['pegprice'];
                    $amount = $price['amount'];
                    $btl_size = $brandDetails->btl_size;
                    $brand = array(
                        'category_name' => '',
                        'sales_date' => $salesDate,
                        'brand_name' => $brandDetails->name,
                        'btl_size' => $btl_size,
                        'qty_inpeg' => $no_peg,
                        'rate' => $pegprice,
                        'amount' => $amount
                    );
                    array_push($json, $brand);
                }
            }
        }
        return json_encode($json);
    }
    
    public function StockRegisterReport(Request $request)
    {
        $json = [];
        $company_id = $request->company_id;
        $categories = Category::select('id', 'name')->get();
        $cat_array = array();
       // Common date range filters
        $dateRangeFilter = [
            ['created_at', '>=', $request->from_date],
            ['created_at', '<=', $request->to_date]
        ];
        
        foreach ($categories as $category) {
            $name = $category->name;
            $id = $category->id;
            $categoryOpeningBalance = 0;
            $categoryPurchase = 0;
            $categoryTotal = 0;
            $categorySales = 0;
            $categoryClosingBalance = 0;
            $catReset = 0;
            $stockData = DB::table('stocks')
                ->where('category_id', $id)
                ->where('company_id', $company_id)
                ->where($dateRangeFilter)
                ->get();

            foreach ($stockData as $stock) {
                $brandId = $stock->brand_id;
                
                $cat = array(
                    'category_name' => $name,
                    'brand_name' => '',
                    'btl_size' => '',
                    'opening_balance' => '',
                    'purchase' => '',
                    'total' => '',
                    'sales' => '',
                    'closing_balance' => ''
                );

                [$data_daily_opening] = DB::table('daily_openings')
                    ->select(DB::raw('SUM(COALESCE(qty, 0)) as qty'))
                    ->where('company_id', $company_id)
                    ->where('brand_id', $brandId)
                    ->get();
                $opening_qty = !empty($data_daily_opening->qty) ? $data_daily_opening->qty : '0';

                $data_purchase = DB::table('purchases')
                    ->select(DB::raw('SUM(COALESCE(qty, 0)) as qty'))
                    ->where('brand_id', $brandId)
                    ->where('company_id', $company_id)
                    ->first();
                $purchase_qty = !empty($data_purchase->qty) ? $data_purchase->qty : 0;

                $data_sales = DB::table('sales')
                    ->select(DB::raw('SUM(COALESCE(qty, 0)) as qty'))
                    ->where('brand_id', $brandId)
                    ->where('company_id', $company_id)
                    ->first();
                $sales_qty = !empty($data_sales->qty) ? $data_sales->qty : 0;

                $brandDetails = DB::table('brands')
                    ->where('id', $brandId)
                    ->first();

                if ($brandDetails) {
                    $catReset = 1;
                    if (!in_array($name, $cat_array)) {
                        array_push($json, $cat);
                    }
                    array_push($cat_array, $name);

                    $btl_size = $brandDetails->btl_size;
                    $peg_size = $brandDetails->peg_size;

                    $opening_stock = convertBtlPeg($opening_qty, $btl_size, $peg_size);
                    $opening_balance = $opening_stock['btl'] . "." . $opening_stock['peg'];

                    $purchase_stock = convertBtlPeg($purchase_qty, $btl_size, $peg_size);
                    $purchase = $purchase_stock['btl'] . "." . $purchase_stock['peg'];

                    $sales_stock = convertBtlPeg($sales_qty, $btl_size, $peg_size);
                    $sales = $sales_stock['btl'] . "." . $sales_stock['peg'];

                    $total = floatval($opening_balance) + floatval($purchase);

                    $closing_balance = floatval($total) - floatval($sales);

                    $categoryOpeningBalance += floatval($opening_balance);
                    $categoryPurchase += floatval($purchase);
                    $categoryTotal += floatval($total);
                    $categorySales += floatval($sales);
                    $categoryClosingBalance += floatval($closing_balance);


                    $brand = array(
                        'category_name' => '',
                        'brand_name' => $brandDetails->name,
                        'btl_size' => $btl_size,
                        'opening_balance' => $opening_balance,
                        'purchase' => $purchase,
                        'total' => $total,
                        'sales' => $sales,
                        'closing_balance' => $closing_balance
                    );

                    array_push($json, $brand);
                }
            }
            if ($catReset > 0) {
                $brand = array(
                    'category_name' => '',
                    'brand_name' => 'SUBTOTAL',
                    'btl_size' => $btl_size,
                    'opening_balance' => $categoryOpeningBalance,
                    'purchase' => $categoryPurchase,
                    'total' => $categoryTotal,
                    'sales' => $categorySales,
                    'closing_balance' => $categoryClosingBalance,
                );
                array_push($json, $brand);
            }
        }
        return json_encode($json);
    }
    
    public function SalesSummaryReport(Request $request)
    {
        $json = [];
        $data = [];

        $company_id = $request->company_id;
        // Common date range filters
        $dateRangeFilter = [
            ['sale_date', '>=', $request->from_date],
            ['sale_date', '<=', $request->to_date]
        ];
        $categories = Category::where(['status' => 1])->get();

        foreach ($categories as $key => $category) {
            $sales = DB::table('sales')
                ->select(
                    DB::raw('COALESCE(SUM(sale_price), 0) as salePrice'),
                    'sales.category_id',
                    'categories.name',
                    'sales.sale_date'
                )
                ->join('categories', 'categories.id', '=', 'sales.category_id')
                ->where('sales.company_id', $company_id)
                ->where('sales.category_id', $category->id)
                ->where($dateRangeFilter)
                ->groupBy('sales.category_id', 'categories.name', 'sales.sale_date')
                ->get();

            foreach ($sales as $sale) {
                $category_id = $sale->category_id;
                $sale_price = $sale->salePrice;
                $category_name = $sale->name;
                $sale_date = $sale->sale_date;

                if (!isset($data[$sale_date])) {
                    $data[$sale_date] = [];
                }
                
                foreach ($categories as $cat)
                {
                   
                    if (!isset($data[$sale_date][$cat->name])) {
                         $data[$sale_date][$cat->name] = 0;
                    }
                    
                }

                // if (!isset($data[$sale_date][$category_name])) {
                //     $data[$sale_date][$category_name] = 0;
                // }

                $data[$sale_date][$category_name] += $sale_price;
            }
        }

        // Generate the JSON structure
        $total = [];
        foreach ($data as $sale_date => $values) {
            $entry = ['' => $sale_date] + $values;
            $entry['Total'] = array_sum($values);
            $json[] = $entry;

            foreach ($values as $key => $value) {
                if (!isset($total[$key])) {
                    $total[$key] = 0;
                }
                $total[$key] += $value;
            }
        }

        if (!empty($total)) {
            $totalEntry = ['' => 'Total'] + $total;
            $totalEntry['Total'] = array_sum($total);
            $json[] = $totalEntry;
        }
        return response()->json($json);
    }
    
    public function AbstractReport(Request $request)
    {
        $json = [];
        $data = [];
        $company_id = $request->company_id;
        // Common date range filters
        $dateRangeFilter = [
            ['invoice_date', '>=', $request->from_date],
            ['invoice_date', '<=', $request->to_date]
        ];
         $btlSizes = Brand::distinct()
        ->orderBy('btl_size', 'DESC')
        ->pluck('btl_size')
        ->toArray();


        $categories = Category::where(['status' => 1])->get();
        foreach ($categories as $key => $category) {
            $btls = Brand::where(['category_id' => $category->id])->orderBy('btl_size', 'DESC')->groupBy(DB::raw("btl_size"))->get();
            foreach ($btls as $key2 => $btl_size) {
                $brands = DB::table('brands')
                    ->join('purchases', 'purchases.brand_id', '=', 'brands.id')
                    ->select('brands.*', 'purchases.invoice_no', 'purchases.invoice_date', 'purchases.no_btl')
                    ->where('purchases.category_id', '=', $category->id)
                    ->where('purchases.company_id', $company_id)
                    ->where('brands.btl_size', '=', $btl_size->btl_size)
                    ->where($dateRangeFilter)
                    ->orderBy('brands.btl_size', 'DESC')
                    ->get();

                foreach ($brands as $key3 => $brand) {
              
                    $btl_size =  $brand->btl_size;
                    $no_btl  =  $brand->no_btl;
                    $invoice_no = $brand->invoice_no;

                    if (!isset($data[$invoice_no])) {
                        $data[$invoice_no] = [];
                    }
                    
                    foreach ($categories as $cat)
                    {
                        foreach ($btlSizes as $size) {
                        
                            if (!isset($data[$invoice_no][$cat->name . '-' . $size])) {
                                $data[$invoice_no][$cat->name . '-' . $size] = 0;
                            } 
                       
                        } 
                    }
                    
                    $data[$invoice_no][$category->name . '-' . $btl_size] += $no_btl;
                }
            }
        }
      
        $total = [];
        foreach ($data as $invoice_no => $values) {
            $entry = ['TP No.' => $invoice_no] + $values;
            array_push($json, $entry);

            foreach ($values as $key => $value) {
                if (!isset($total[$key])) {
                    $total[$key] = 0;
                }
                $total[$key] += $value;
            }
        }
        $totalEntry = ['TP No.' => 'Total'] + $total;
        array_push($json, $totalEntry);
        return response()->json($json);
    }
    
    public function MonthlyReport(Request $request)
    {
        $json = [];
        $data = [];

        $data['opening']['Title'] = 'Opening';
        $data['purchase']['Title'] = 'Purchase';
        $data['total']['Title'] = 'total';
        $data['sale']['Title'] = 'sales';
        $data['closing']['Title'] = 'closing';

        $company_id = $request->company_id;
       // $categories = Category::select('id','name')->where(['status' => 1])->get(); // get all category
        $categories = Category::where(function ($query) {
        $query->where('status', 1);
        })->select('id', 'name')->get();
        foreach ($categories as $key => $category) {
          //  $btls = Brand::where(['category_id' => $category->id])->orderBy('btl_size', 'DESC')->groupBy(DB::raw("btl_size"))->get(); // get unique bottle size of that category
          
          $btls = Brand::select('btl_size')
          ->where('category_id', $category->id)
          ->orderBy('btl_size', 'DESC')
          ->distinct()
          ->get();
            
            foreach ($btls as $key2 => $btl_size) {
                $brands = Brand::select('id','btl_size','peg_size')->where(['category_id' => $category['id'], 'btl_size' => $btl_size['btl_size']])->get(); // get brand of that category
                $openSum = 0;
                $purchaseSum = 0;
                $totalSum = 0;
                $saleSum = 0;
                $closingSum = 0;
                foreach ($brands as $key => $brand) {
                    // opening section
                    // $opening = DailyOpening::where(['brand_id' => $brand['id'], 'company_id' => $company_id])
                    //     ->select(DB::raw('COALESCE(qty, 0) as qty'))
                    //     ->first();
                    
                //     $opening = DailyOpening::where(['brand_id' => $brand['id'], 'company_id' => $company_id])
                //    ->value('qty');
                
                $opening = DailyOpening::where(['brand_id' => $brand['id'], 'company_id' => $company_id])
                       ->selectRaw('COALESCE(qty, 0) as qty')
                       ->value('qty');

                    if ($opening !== null)
                        // $open = $opening['qty'];
                        $open = $opening;
                    else
                        $open = 0;
                    $openSum = $openSum + $open;
                    //purchase section
                    /* $purchase = purchase::where(['brand_id' => $brand['id'], 'company_id' => $company_id])
                        ->select(DB::raw('COALESCE(qty, 0) as qty'))
                        ->first(); */
                        
                    $purchase = purchase::where(['brand_id' => $brand['id'], 'company_id' => $company_id])
                        ->selectRaw('COALESCE(qty, 0) as qty')
                        ->value('qty');
                        
                    if ($purchase !== null)
                        // $purchaseQty = $purchase['qty'];
                        $purchaseQty = $purchase;
                    else
                        $purchaseQty = 0;
                    $purchaseSum = $purchaseSum + $purchaseQty;
                    //total section
                    $total = $purchaseQty + $open;
                    if ($total)
                        $totalSum = $totalSum + $total;

                    // sales
                    /* $sales = Sales::where(['brand_id' => $brand['id'], 'company_id' => $company_id])
                        ->select(DB::raw('COALESCE(qty, 0) as qty'))
                        ->first(); */
                        
                    $sales = Sales::where(['brand_id' => $brand['id'], 'company_id' => $company_id])
                        ->selectRaw('COALESCE(qty, 0) as qty')
                        ->value('qty');
                        
                    if ($sales !== null)
                        // $saleQty = $sales['qty'];
                        $saleQty = $sales;
                    else
                        $saleQty = 0;
                    $saleSum = $saleSum + $saleQty;

                    //total section
                    $closing = $total - $saleQty;
                    if ($total)
                        $closingSum = $closingSum + $closing;
                }
                //conversion
                $c_open = convertBtlPeg($openSum, $brand['btl_size'], $brand['peg_size']);
                $c_purchase = convertBtlPeg($purchaseSum, $brand['btl_size'], $brand['peg_size']);
                $c_total = convertBtlPeg($totalSum, $brand['btl_size'], $brand['peg_size']);
                $c_sale = convertBtlPeg($saleSum, $brand['btl_size'], $brand['peg_size']);
                $c_closing = convertBtlPeg($closingSum, $brand['btl_size'], $brand['peg_size']);

                $data['opening'][$category['name'] . '-' . $brand['btl_size']] = $c_open['btl'] . '.' . $c_open['peg'];
                $data['purchase'][$category['name'] . '-' . $brand['btl_size']] = $c_purchase['btl'] . '.' . $c_purchase['peg'];
                $data['total'][$category['name'] . '-' . $brand['btl_size']] = $c_total['btl'] . '.' . $c_total['peg'];
                $data['sale'][$category['name'] . '-' . $brand['btl_size']] = $c_sale['btl'] . '.' . $c_sale['peg'];
                $data['closing'][$category['name'] . '-' . $brand['btl_size']] = $c_closing['btl'] . '.' . $c_closing['peg'];
            }
        }
        array_push($json, $data['opening']);
        array_push($json, $data['purchase']);
        array_push($json, $data['total']);
        array_push($json, $data['sale']);
        array_push($json, $data['closing']);
        return response()->json($json);
    }
    
    public function DailyReport(Request $request)
    {
        $json = [];

        $fromDate = $request->from_date;
        $toDate = $request->to_date;
        $company_id = $request->company_id;
        $categories = Category::where(['status' => 1])->get(); // Get all categories

        // Iterate over each date between fromDate and toDate
        $currentDate = $fromDate;
        while ($currentDate <= $toDate) {
            $data = [];
            $data['opening'][$currentDate] = 'Opening';
            $data['purchase'][$currentDate] = 'Purchase';
            $data['total'][$currentDate] = 'Total';
            $data['sale'][$currentDate] = 'Sales';
            $data['closing'][$currentDate] = 'Closing';

            foreach ($categories as $key => $category) {
                $btls = Brand::where(['category_id' => $category->id])
                    ->orderBy('btl_size', 'DESC')
                    ->groupBy(DB::raw("btl_size"))
                    ->get(); // Get unique bottle sizes of that category

                foreach ($btls as $key2 => $btl_size) {
                    $brands = Brand::where(['category_id' => $category['id'], 'btl_size' => $btl_size['btl_size']])
                        ->get(); // Get brands of that category

                    $openSum = 0;
                    $purchaseSum = 0;
                    $totalSum = 0;
                    $saleSum = 0;
                    $closingSum = 0;
                    $purchaseNO = '';

                    foreach ($brands as $key => $brand) {
                        // Opening section
                        $opening = DailyOpening::where(['brand_id' => $brand['id'], 'company_id' => $company_id])
                            ->whereDate('date', '=', $currentDate)
                            ->select(DB::raw('COALESCE(qty, 0) as qty'))
                            ->first();

                        if ($opening) {
                            $open = $opening['qty'];
                        } else {
                            $open = 0;
                        }

                        $openSum += $open;

                        $purchase = Purchase::where(['brand_id' => $brand['id'], 'company_id' => $company_id])
                            ->whereDate('invoice_date', '=', $currentDate)
                            ->select(DB::raw('COALESCE(qty, 0) as qty, GROUP_CONCAT(COALESCE(invoice_no, 0) SEPARATOR ",") as invoice_no'))
                            ->first();

                        if ($purchase) {

                            $purchaseQty = $purchase['qty'];
                            $purchaseNO = $purchase['invoice_no'];
                        } else {
                            $purchaseQty = 0;
                            $purchaseNO = 0;
                        }

                        $purchaseSum += $purchaseQty;
                        $total = $purchaseQty + $open;

                        // Total section
                        $totalSum += $total;

                        // Sales
                        $sales = Sales::where(['brand_id' => $brand['id'], 'company_id' => $company_id])
                            ->whereDate('sale_date', '=', $currentDate)
                            ->select(DB::raw('COALESCE(qty, 0) as qty'))
                            ->first();

                        if ($sales) {
                            $saleQty = $sales['qty'];
                        } else {
                            $saleQty = 0;
                        }

                        $saleSum += $saleQty;

                        // Total section
                        $closing = $total - $saleQty;
                        $closingSum += $closing;
                    }

                    // Conversion
                    $c_open = convertBtlPeg($openSum, $brand['btl_size'], $brand['peg_size']);
                    $c_purchase = convertBtlPeg($purchaseSum, $brand['btl_size'], $brand['peg_size']);
                    $c_total = convertBtlPeg($totalSum, $brand['btl_size'], $brand['peg_size']);
                    $c_sale = convertBtlPeg($saleSum, $brand['btl_size'], $brand['peg_size']);
                    $c_closing = convertBtlPeg($closingSum, $brand['btl_size'], $brand['peg_size']);


                    $data['opening']['TPNo'] = '';
                    $data['opening'][$category['name'] . '-' . $brand['btl_size']] = $c_open['btl'] . '.' . $c_open['peg'];
                    $data['purchase']['TPNo'] = !empty($purchaseNO) ? $purchaseNO : '0';
                    $data['purchase'][$category['name'] . '-' . $brand['btl_size']] = $c_purchase['btl'] . '.' . $c_purchase['peg'];
                    $data['total']['TPNo'] = '';
                    $data['total'][$category['name'] . '-' . $brand['btl_size']] = $c_total['btl'] . '.' . $c_total['peg'];
                    $data['sale']['TPNo'] = '';
                    $data['sale'][$category['name'] . '-' . $brand['btl_size']] = $c_sale['btl'] . '.' . $c_sale['peg'];
                    $data['closing']['TPNo'] = '';
                    $data['closing'][$category['name'] . '-' . $brand['btl_size']] = $c_closing['btl'] . '.' . $c_closing['peg'];
                }
            }

            array_push($json, $data['opening']);
            array_push($json, $data['purchase']);
            array_push($json, $data['total']);
            array_push($json, $data['sale']);
            array_push($json, $data['closing']);

            // Increment current date
            $currentDate = date('Y-m-d', strtotime($currentDate . '+1 day'));
        }

        return response()->json($json);
    }
    
    public function YearlyReport(Request $request)
    {
        $json = [];
        $months = array();
        $company_id = $request->company_id;
        $months = $this->getCurrentFinancialYearMonths();
        foreach ($months as $month) {
            $newMonth = explode(' ', $month);
           // $categories = Category::select('id','name')->where('status', 1)->get();
           $categories = Category::where(function ($query) {
            $query->where('status', 1);
            })->select('id', 'name')->get();
            foreach ($categories as $category) {
              //  $btls = Brand::select('btl_size')->where(['category_id' => $category->id])->orderBy('btl_size', 'DESC')->groupBy(DB::raw("btl_size"))->get(); // get unique bottle size of that category
              
              $btls = Brand::select('btl_size')
                    ->where('category_id', $category->id)
                    ->orderBy('btl_size', 'DESC')
                    ->distinct()
                    ->get();
    
                foreach ($btls as $key2 => $btl_size) {
                    $brands = Brand::select('id')->where(['category_id' => $category['id'], 'btl_size' => $btl_size['btl_size']])->get(); // get brand of that category
                    $openSum = 0;
                    $purchaseSum = 0;
                    $totalSum = 0;
                    $saleSum = 0;
                    $closingSum = 0;
                    foreach ($brands as $key => $brand) {
                        // opening section
                        [$opening] = DailyOpening::select('qty')->where(['brand_id' => $brand['id'], 'company_id' => $company_id, ['date', 'like', '%-' . $newMonth[0] . '-' . $newMonth[1]]])
                            ->select(DB::raw('SUM(COALESCE(qty, 0)) as qty'))
                            ->get();

                        if ($opening)
                            $open = $opening['qty'];
                        else
                            $open = 0;
                        $openSum = $openSum + $open;
                        //purchase section
                        [$purchase] = purchase::select('qty')->where(['brand_id' => $brand['id'], 'company_id' => $company_id, ['invoice_date', 'like', '%-' . $newMonth[0] . '-' . $newMonth[1]]])
                            ->select(DB::raw('SUM(COALESCE(qty, 0)) as qty'))
                            ->get();
                        if ($purchase)
                            $purchaseQty = $purchase['qty'];
                        else
                            $purchaseQty = 0;
                        $purchaseSum = $purchaseSum + $purchaseQty;
                        //total section
                        $total = $purchaseQty + $open;
                        if ($total)
                            $totalSum = $totalSum + $total;

                        // sales
                        [$sales] = Sales::select('qty')->where(['brand_id' => $brand['id'], 'company_id' => $company_id, ['sale_date', 'like', '%-' . $newMonth[0] . '-' . $newMonth[1]]])
                            ->select(DB::raw('SUM(COALESCE(qty, 0)) as qty'))
                            ->get();
                        if ($sales)
                            $saleQty = $sales['qty'];
                        else
                            $saleQty = 0;
                        $saleSum = $saleSum + $saleQty;

                        //total section
                        $closing = $total - $saleQty;
                        if ($total)
                            $closingSum = $closingSum + $closing;
                    }

                    $data[$month]['Title'] = $month;
                    $data[$month][$category['name'] . '-' . 'opening'] = $openSum / 1000;
                    $data[$month][$category['name'] . '-' . 'purchase'] = $purchaseSum / 1000;
                    $data[$month][$category['name'] . '-' . 'sale'] = $saleSum / 1000;
                    $data[$month][$category['name'] . '-' . 'closing'] = $closingSum / 1000;
                }
            }
            array_push($json, $data[$month]);
        }
        return response()->json($json);
    }
    
    public function YearlyComparisonReport(Request $request)
    {
        $json = [];
        $months = array();
        $company_id = $request->company_id;
        $months = $this->getCurrentFinancialYearMonths();
        foreach ($months as $month) {
            $newMonth = explode(' ', $month);
            $categories = Category::select('id','name')->where('status', 1)->get();
            foreach ($categories as $category) {
              /*  $btls = Brand::where(['category_id' => $category->id])->orderBy('btl_size', 'DESC')->groupBy(DB::raw("btl_size"))->get(); */ // get unique bottle size of that category
              
              $btls = Brand::select('btl_size')
              ->where('category_id', $category->id)
              ->orderBy('btl_size', 'DESC')
              ->distinct()
              ->get();
              
                foreach ($btls as $key2 => $btl_size) {
                    $brands = Brand::select('id')->where(['category_id' => $category['id'], 'btl_size' => $btl_size['btl_size']])->get(); // get brand of that category
                    $openSum = 0;
                    $purchaseSum = 0;
                    $totalSum = 0;
                    $saleSum = 0;
                    $closingSum = 0;


                    $openSum2 = 0;
                    $purchaseSum2 = 0;
                    $totalSum2 = 0;
                    $saleSum2 = 0;
                    $closingSum2 = 0;
                    foreach ($brands as $key => $brand) {
                        // current year opening section
                       /* [$opening] = DailyOpening::select('qty')->where(['brand_id' => $brand['id'], 'company_id' => $company_id])
                            ->whereMonth('date', $newMonth[0])
                            ->whereYear('date', $newMonth[1])
                            ->select(DB::raw('SUM(COALESCE(qty, 0)) as qty'))
                            ->get();

                        if ($opening)
                            $open = $opening['qty'];
                        else
                            $open = 0; */
                            
                        $opening = DailyOpening::where(['brand_id' => $brand['id'], 'company_id' => $company_id])
                        ->whereMonth('date', $newMonth[0])
                        ->whereYear('date', $newMonth[1])
                        ->selectRaw('SUM(COALESCE(qty, 0)) as qty')
                        ->value('qty');
                        
                        $open = $opening ?? 0;
                        
                        $openSum = $openSum + $open;
                        
                        // current year opening section end
                        //current year purchase section
                       /*  [$purchase] = purchase::select('qty')->where(['brand_id' => $brand['id'], 'company_id' => $company_id])
                            ->whereMonth('invoice_date', $newMonth[0])
                            ->whereYear('invoice_date', $newMonth[1])
                            ->select(DB::raw('SUM(COALESCE(qty, 0)) as qty'))
                            ->get();
                        if ($purchase)
                            $purchaseQty = $purchase['qty'];
                        else
                            $purchaseQty = 0; */
                            
                        $purchase = purchase::where(['brand_id' => $brand['id'], 'company_id' => $company_id])
                        ->whereMonth('invoice_date', $newMonth[0])
                        ->whereYear('invoice_date', $newMonth[1])
                        ->selectRaw('SUM(COALESCE(qty, 0)) as qty')
                        ->value('qty');
                        
                        $purchaseQty = $purchase ?? 0;
                            
                        $purchaseSum = $purchaseSum + $purchaseQty;
                        //total section
                        $total = $purchaseQty + $open;
                        if ($total)
                            $totalSum = $totalSum + $total;
                        //current year purchase section end 

                        // current year sales start
                       /* [$sales] = Sales::select('qty')->where(['brand_id' => $brand['id'], 'company_id' => $company_id])
                            ->select(DB::raw('SUM(COALESCE(qty, 0)) as qty'))
                            ->whereMonth('sale_date', $newMonth[0])
                            ->whereYear('sale_date', $newMonth[1])
                            ->get();
                        if ($sales)
                            $saleQty = $sales['qty'];
                        else
                            $saleQty = 0;*/
                        
                        $sales = Sales::where(['brand_id' => $brand['id'], 'company_id' => $company_id])
                        ->whereMonth('sale_date', $newMonth[0])
                        ->whereYear('sale_date', $newMonth[1])
                        ->selectRaw('SUM(COALESCE(qty, 0)) as qty')
                        ->value('qty');
                        
                        $saleQty = $sales ?? 0;
                            
                        $saleSum = $saleSum + $saleQty;

                        //total section
                        $closing = $total - $saleQty;
                        if ($total)
                            $closingSum = $closingSum + $closing;
                        // current year sales end



                        // last year opening section
                       /* [$opening2] = DailyOpening::select('qty')->where(['brand_id' => $brand['id'], 'company_id' => $company_id])
                            ->whereMonth('date', $newMonth[0])
                            ->whereYear('date', $newMonth[1] - 1)
                            ->select(DB::raw('SUM(COALESCE(qty, 0)) as qty'))
                            ->get();

                        if ($opening2)
                            $open2 = $opening2['qty'];
                        else
                            $open2 = 0; */
                            
                        $opening2 = DailyOpening::where(['brand_id' => $brand['id'], 'company_id' => $company_id])
                        ->whereMonth('date', $newMonth[0])
                        ->whereYear('date', $newMonth[1] - 1)
                        ->selectRaw('SUM(COALESCE(qty, 0)) as qty')
                        ->value('qty');
                        
                        $open2 = $opening2 ?? 0;
                        
                        $openSum2 = $openSum2 + $open2;
                        // last year opening section end
                        //last year purchase section start 
                       /* [$purchase2] = purchase::select('qty')->where(['brand_id' => $brand['id'], 'company_id' => $company_id])
                            ->whereMonth('invoice_date', $newMonth[0])
                            ->whereYear('invoice_date', $newMonth[1] - 1)
                            ->select(DB::raw('SUM(COALESCE(qty, 0)) as qty'))
                            ->get();
                        if ($purchase2)
                            $purchaseQty2 = $purchase2['qty'];
                        else
                            $purchaseQty2 = 0; */
                            
                        $purchase2 = purchase::where(['brand_id' => $brand['id'], 'company_id' => $company_id])
                        ->whereMonth('invoice_date', $newMonth[0])
                        ->whereYear('invoice_date', $newMonth[1] -1)
                        ->selectRaw('SUM(COALESCE(qty, 0)) as qty')
                        ->value('qty');
                        
                        $purchaseQty2 = $purchase2 ?? 0;
                            
                        $purchaseSum2 = $purchaseSum2 + $purchaseQty2;
                        //total section
                        $total2 = $purchaseQty2 + $open2;
                        if ($total2)
                            $totalSum2 = $totalSum2 + $total2;
                        //last year purchase section end
                        // last year sales start

                        /* [$sales2] = Sales::select('qty')->where(['brand_id' => $brand['id'], 'company_id' => $company_id])
                            ->select(DB::raw('SUM(COALESCE(qty, 0)) as qty'))
                            ->whereMonth('sale_date', $newMonth[0])
                            ->whereYear('sale_date', $newMonth[1] - 1)
                            ->get();
                        if ($sales2)
                            $saleQty2 = $sales2['qty'];
                        else
                            $saleQty2 = 0; */
                            
                        $sales2 = Sales::where(['brand_id' => $brand['id'], 'company_id' => $company_id])
                        ->whereMonth('sale_date', $newMonth[0])
                        ->whereYear('sale_date', $newMonth[1] -1)
                        ->selectRaw('SUM(COALESCE(qty, 0)) as qty')
                        ->value('qty');
                        
                        $saleQty2 = $sales2 ?? 0;
                        $saleSum2 = $saleSum2 + $saleQty2;

                        //total section
                        $closing2 = $total2 - $saleQty2;
                        if ($total2)
                            $closingSum2 = $closingSum2 + $closing2;
                        // last year sales end
                    }
                    // current year
                    $data[$month]['Title'] = $month;
                    $data[$month][$category['name'] . '-' . 'opening'] = $openSum / 1000;
                    $data[$month][$category['name'] . '-' . 'purchase'] = $purchaseSum / 1000;
                    $data[$month][$category['name'] . '-' . 'sale'] = $saleSum / 1000;
                    $data[$month][$category['name'] . '-' . 'closing'] = $closingSum / 1000;
                    // last year
                    $data[$newMonth[0] . $newMonth[1] - 1]['Title'] =  $newMonth[0]  . ' ' . $newMonth[1] - 1;
                    $data[$newMonth[0] . $newMonth[1] - 1][$category['name'] . '-' . 'opening'] = $openSum2 / 1000;
                    $data[$newMonth[0] . $newMonth[1] - 1][$category['name'] . '-' . 'purchase'] = $purchaseSum2 / 1000;
                    $data[$newMonth[0] . $newMonth[1] - 1][$category['name'] . '-' . 'sale'] = $saleSum2 / 1000;
                    $data[$newMonth[0] . $newMonth[1] - 1][$category['name'] . '-' . 'closing'] = $closingSum2 / 1000;
                    //blank
                    $data[$newMonth[0]]['Title'] = '';
                    $data[$newMonth[0]][$category['name'] . '-' . 'opening'] = '';
                    $data[$newMonth[0]][$category['name'] . '-' . 'purchase'] = '';
                    $data[$newMonth[0]][$category['name'] . '-' . 'sale'] = '';
                    $data[$newMonth[0]][$category['name'] . '-' . 'closing'] = '';
                }
            }
            array_push($json, $data[$month]);
            array_push($json, $data[$newMonth[0] . $newMonth[1] - 1]);
            array_push($json, $data[$newMonth[0]]);
        }
        return response()->json($json);
    }

    public function BarVarianceReport(Request $request)
    {
        $Category = Category::select('id', 'name')->get();
        $json = [];
        $comArray = [];
        array_push($comArray, $request->company_id);
        // linked companies loop start here
        $company = LinkCompany::select('link_company_id')->where('company_id', $request->company_id)
            ->get();
        foreach ($company as $com_data) {
            array_push($comArray, $com_data->link_company_id);
        }
        foreach ($Category as $Category_data) {
            // echo "<pre>";print_r($Category_data);

            $brands_data = DB::table("brands")
                ->select('btl_size', 'btl_size', 'category_id', 'id', 'peg_size', 'subcategory_id')
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
                $ncSalesSum = 0;
                $cocktailSalesSum = 0;
                $banquetSum = 0;
                $spoilageSum = 0;
                $transferInSum = 0;
                $transferOutSum = 0;
                $closingSum = 0;
                $physicalSum = 0;
                $totalConsumtion = 0;
                $selling_variance = 0;
                $cost_variance = 0;
                $consumption_cost = 0;
                $physical_valuation = 0;

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
                    'transfer_in' => '',
                    'transfer_out' => '',
                    'closing' => '',
                    'physical' => '',
                    'variance' => '',
                    'total_consumption' => '',
                    'consumption' => '',
                    'selling_variance' => '',
                    'cost_variance' => '',
                    'consumption_cost' => '',
                    'physical_valuation' => ''
                ];

                foreach ($brandName_Data as  $brandListName) {
                    $isMinus = false;
                    $arr['Type'] = $b_type['name'];
                    $arr['name'] = $brandListName['name'];
                    $arr['btl_size'] = $brand_size;

                    // opening 
                    [$data_daily_opening] = DB::table('daily_openings')
                        ->select(DB::raw('SUM(qty) AS qty'))
                        ->whereIn('company_id', $comArray)
                        ->where('date', '=', date('Y-m-d', strtotime($request->from_date)))
                        ->where('brand_id', $brandListName['id'])
                        ->get();
                    $qty = !empty($data_daily_opening->qty) ? $data_daily_opening->qty : '0';
                    $openSum = $openSum + $qty;

                    // purchase - receipt
                    [$balance] = DB::table('purchases')
                        ->select(DB::raw('SUM(qty) AS qty'))
                        ->where('brand_id', $brandListName['id'])
                        ->whereIn('company_id', $comArray)
                        ->whereBetween('invoice_date', [$request->from_date, $request->to_date])
                        ->get();
                    $balance = !empty($balance->qty) ? $balance->qty : 0;
                    $receiptSum = $receiptSum + $balance;

                    // total
                    $total = $qty + $balance;
                    $totalSum = $totalSum + $total;

                    // sales
                    [$sales] = DB::table('sales')->select(DB::raw('SUM(qty) AS qty'))->whereIn('company_id', $comArray)->where(['brand_id' => $brandListName['id'], 'sales_type' => '1', 'is_cocktail' => '0'])->whereBetween('created_at', [$request->from_date, $request->to_date])->get();
                    $sales = $sales->qty;

                    // nc sales
                    [$nc_sales] = DB::table('sales')->select(DB::raw('SUM(qty) AS qty'))->whereIn('company_id', $comArray)->where(['brand_id' => $brandListName['id'], 'is_cocktail' => '0', 'sales_type' => 2])->whereBetween('created_at', [$request->from_date, $request->to_date])->get();
                    $nc_sales = $nc_sales->qty;

                    // cocktail
                    [$cocktail_sales] = DB::table('sales')->select(DB::raw('SUM(qty) AS qty'))->whereIn('company_id', $comArray)->where(['brand_id' => $brandListName['id'], 'is_cocktail' => '1'])->whereBetween('created_at', [$request->from_date, $request->to_date])->get();
                    $cocktail_sales = $cocktail_sales->qty;

                    [$banquet_sales] = DB::table('sales')->select(DB::raw('SUM(qty) AS qty'))->whereIn('company_id', $comArray)->where(['brand_id' => $brandListName['id'], 'sales_type' => '3'])->whereBetween('created_at', [$request->from_date, $request->to_date])->get();
                    $banquet_sales = $banquet_sales->qty;

                    [$spoilage_sales] = DB::table('sales')->select(DB::raw('SUM(qty) AS qty'))->whereIn('company_id', $comArray)->where(['brand_id' => $brandListName['id'], 'sales_type' => '4'])->whereBetween('created_at', [$request->from_date, $request->to_date])->get();
                    $spoilage_sales = $spoilage_sales->qty;

                    // transfer In & Out

                    [$transferIn] = DB::table('transactions')->select(DB::raw('SUM(qty) AS qty'))->whereIn('company_to_id', $comArray)->where(['brand_id' => $brandListName['id']])->whereBetween('date', [$request->from_date, $request->to_date])->get(); // transfer in
                    $transferIn = $transferIn->qty;

                    [$transferOut] = DB::table('transactions')->select(DB::raw('SUM(qty) AS qty'))->whereIn('company_id', $comArray)->where(['brand_id' => $brandListName['id']])->whereBetween('date', [$request->from_date, $request->to_date])->get(); // transfer out
                    $transferOut = $transferOut->qty;


                    $banquetSum = $banquetSum + $banquet_sales; // sum of banquet
                    $spoilageSum = $spoilageSum + $spoilage_sales; // sum of spoilage
                    $ncSalesSum = $ncSalesSum + $nc_sales; // sum of non chargeable
                    $cocktailSalesSum = $cocktailSalesSum + $cocktail_sales; // sum of cocktails
                    $transferInSum = $transferInSum + $transferIn; // sum of cocktails
                    $transferOutSum = $transferOutSum + $transferOut; // sum of cocktails
                    $salesSum = $salesSum + $sales; // sum of sales
                    $closing = ($total + $transferOut) - ($sales + $nc_sales + $banquet_sales + $spoilage_sales + $transferIn); // closing formula
                    $closingSum = $closingSum + $closing;   // closing sum


                    // costing & selling price
                    [$ItemCost] = Stock::select(DB::raw('AVG(cost_price) AS cost_price'), DB::raw('AVG(btl_selling_price) AS btl_selling_price'))->whereIn('company_id', $comArray)->where(['brand_id' => $brandListName['id']])->get();

                    $cost_price = !empty($ItemCost['cost_price']) ? $ItemCost['cost_price'] : 0;
                    $btl_selling_price = !empty($ItemCost['btl_selling_price']) ? $ItemCost['btl_selling_price'] : 0; // bottle selling price
                    $peg_selling_price = $btl_selling_price / ($brandListName['btl_size'] / $brandListName['peg_size']); // calculate peg price from btl cost
                    $cost_peg_price = $cost_price / ($brandListName['btl_size'] / $brandListName['peg_size']); // calculate peg price from btl cost

                    // physical 
                    [$PhyQty] = physical_history::select(DB::raw('SUM(qty) AS qty'))->whereIn('company_id', $comArray)->where(['brand_id' => $brandListName['id']])->whereDate('date', '=', $request->to_date)->get();
                    $PhyClosing = !empty($PhyQty['qty']) ? $PhyQty['qty'] : 0;
                    $physicalSum = $physicalSum + $PhyClosing;

                    $variance = $PhyClosing - $closing;
                    $brand_size = $brandListName['btl_size'];
                    if ($variance < 0) {
                        $isMinus = true;
                        $variance = abs($variance);
                    }
                    // open
                    $c_opening = convertBtlPeg($qty, $brand_size, $brandListName['peg_size']);
                    $arr['open'] = $c_opening['btl'] . "." . $c_opening['peg'];
                    //receipt
                    $c_receipt  = convertBtlPeg($balance, $brand_size, $brandListName['peg_size']);
                    $arr['receipt'] = $c_receipt['btl'] . "." . $c_receipt['peg'];

                    // total
                    $c_total = convertBtlPeg($total, $brand_size, $brandListName['peg_size']);
                    $arr['total'] = $c_total['btl'] . "." . $c_total['peg'];

                    // sales
                    $c_sales  = convertBtlPeg($sales, $brand_size, $brandListName['peg_size']);
                    $arr['sales'] = $c_sales['btl'] . "." . $c_sales['peg'];

                    //nc sale
                    $c_nc_sales = convertBtlPeg($nc_sales, $brand_size, $brandListName['peg_size']);
                    $arr['nc_sales'] = $c_nc_sales['btl'] . "." . $c_nc_sales['peg'];

                    //bcocktail
                    $c_cocktail_sales = convertBtlPeg($cocktail_sales, $brand_size, $brandListName['peg_size']);
                    $arr['cocktail_sales'] = $c_cocktail_sales['btl'] . "." . $c_cocktail_sales['peg'];

                    //banquet
                    $c_banquet_sales = convertBtlPeg($banquet_sales, $brand_size, $brandListName['peg_size']);
                    $arr['banquet_sales'] = $c_banquet_sales['btl'] . "." . $c_banquet_sales['peg'];

                    //banquet
                    $c_spoilage_sales = convertBtlPeg($spoilage_sales, $brand_size, $brandListName['peg_size']);
                    $arr['spoilage_sales'] = $c_spoilage_sales['btl'] . "." . $c_spoilage_sales['peg'];

                    //transfer in btl peg calculation start
                    $transfer = convertBtlPeg($transferIn, $brand_size, $brandListName['peg_size']);
                    $arr['transfer_in'] = $transfer['btl'] . "." . $transfer['peg'];

                    //transfer out btl peg calculation start
                    $transferO = convertBtlPeg($transferOut, $brand_size, $brandListName['peg_size']);
                    $arr['transfer_out'] = $transferO['btl'] . "." . $transferO['peg'];

                    //  system qty closing
                    $c_closing  = convertBtlPeg($closing, $brand_size, $brandListName['peg_size']);
                    $arr['closing'] = $c_closing['btl'] . "." . $c_closing['peg'];

                    // physical qty closing
                    $c_physical  = convertBtlPeg($PhyClosing, $brand_size, $brandListName['peg_size']);
                    $arr['physical'] = $c_physical['btl'] . "." . $c_physical['peg'];
                    // variance 
                    $c_variance  = convertBtlPeg($variance, $brand_size, $brandListName['peg_size']);
                    $arr['variance'] = ($isMinus == true ? '-' : '') . $c_variance['btl'] . "." . $c_variance['peg'];

                    // total consumption 
                    $total_consumption = intval($sales + $nc_sales + $cocktail_sales + $banquet_sales + $spoilage_sales);
                    $totalConsumtion = $totalConsumtion + $total_consumption;

                    $c_consumption = convertBtlPeg($total_consumption, $brand_size, $brandList->peg_size);
                    $arr['total_consumption'] = $c_consumption['btl'] . "." . $c_consumption['peg'];


                    // consumption 
                    $consumption = $total - $closing;
                    $c_comsumption  = convertBtlPeg($consumption, $brand_size, $brandList->peg_size);
                    $arr['consumption'] = ($isMinus == true ? '-' : '') . $c_comsumption['btl'] . "." . $c_comsumption['peg'];


                    $arr['selling_variance'] = $c_variance['btl'] * $btl_selling_price + $c_variance['peg'] * $peg_selling_price;
                    $selling_variance = $selling_variance + $arr['selling_variance'];

                    // cost price variance
                    $arr['cost_variance'] = $c_variance['btl'] * $cost_price + $c_variance['peg'] * $cost_peg_price;
                    $cost_variance = $cost_variance + $arr['cost_variance'];

                    // cost price variance
                    $arr['consumption_cost'] = $c_comsumption['btl'] * $cost_price + $c_comsumption['peg'] * $cost_peg_price;
                    $consumption_cost = $consumption_cost + $arr['consumption_cost'];

                    // cost price variance
                    $arr['physical_valuation'] = $c_physical['btl'] * $cost_price + $c_physical['peg'] * $cost_peg_price;
                    $physical_valuation = $physical_valuation + $arr['physical_valuation'];


                    if ($arr['total'] != '0.0' || $arr['closing'] != '0.0' || $arr['physical'] != '0.0') {
                        if (!in_array($arrCat, $json)) {
                            array_push($json, $arrCat);
                        }
                        array_push($json, $arr);
                    }
                }

                if (count($brandName_Data) > 0) {

                    //open all
                    $open_all = convertBtlPeg($openSum, $brand_size, $brandList->peg_size);
                    //receipt
                    $receipt_all =  convertBtlPeg($receiptSum, $brand_size, $brandList->peg_size);
                    //total
                    $total_all = convertBtlPeg($totalSum, $brand_size, $brandList->peg_size);
                    //sales
                    $sales_all = convertBtlPeg($salesSum, $brand_size, $brandList->peg_size);
                    //ncSalesSum                   
                    $ncSales_all = convertBtlPeg($ncSalesSum, $brand_size, $brandList->peg_size);
                    //cocktailSalesSum
                    $cocktail_all = convertBtlPeg($cocktailSalesSum, $brand_size, $brandList->peg_size);
                    //banquetSum
                    $banquet_all = convertBtlPeg($banquetSum, $brand_size, $brandList->peg_size);
                    //spoilageSum
                    $spoilage_all = convertBtlPeg($spoilageSum, $brand_size, $brandList->peg_size);

                    //closing
                    $closing_all = convertBtlPeg($closingSum, $brand_size, $brandList->peg_size);
                    //physical                   
                    $physical_all = convertBtlPeg($physicalSum, $brand_size, $brandList->peg_size);
                    // variance
                    $variance_all = convertBtlPeg(abs($physicalSum - $closingSum), $brand_size, $brandList->peg_size);

                    // TOTAL CONSUMPTION 
                    $ConsumptionSUM = convertBtlPeg($totalConsumtion, $brand_size, $brandListName['peg_size']);
                    // comsumption 
                    $comsumptionSum = $totalSum - $closingSum;
                    $comsumption_all  = convertBtlPeg($comsumptionSum, $brand_size, $brandList->peg_size);
                    // transfer in
                    $alltransferIn = convertBtlPeg($transferInSum, $brand_size, $brandListName['peg_size']);
                    // transfer out
                    $alltransferOut = convertBtlPeg($transferOutSum, $brand_size, $brandListName['peg_size']);

                    $arr = [
                        'Type' => '',
                        'name' => 'SUBTOTAL',
                        'btl_size' => '',
                        'open' => $open_all['btl'] . "." . $open_all['peg'],
                        'receipt' =>  $receipt_all['btl'] . "." . $receipt_all['peg'],
                        'total' => $total_all['btl'] . "." . $total_all['peg'],
                        'sales' =>  $sales_all['btl'] . "." . $sales_all['peg'],
                        'nc_sales' =>  $ncSales_all['btl'] . "." . $ncSales_all['peg'],
                        'cocktail_sales' =>  $cocktail_all['btl'] . "." . $cocktail_all['peg'],
                        'banquet_sales' =>  $banquet_all['btl'] . "." . $banquet_all['peg'],
                        'spoilage_sales' =>  $spoilage_all['btl'] . "." . $spoilage_all['peg'],
                        'transfer_in' => $alltransferIn['btl'] . "." . $alltransferIn['peg'],
                        'transfer_out' => $alltransferOut['btl'] . "." . $alltransferOut['peg'],
                        'closing' =>  $closing_all['btl'] . "." . $closing_all['peg'],
                        'physical' =>  $physical_all['btl'] . "." . $physical_all['peg'],
                        'variance' => ($physicalSum - $closingSum) < 0 ? '-' . $variance_all['btl'] . "." . $variance_all['peg'] : $variance_all['btl'] . "." . $variance_all['peg'],
                        'total_consumption' =>  $ConsumptionSUM['btl'] . "." . $ConsumptionSUM['peg'],
                        'consumption' => $comsumption_all['btl'] . "." . $comsumption_all['peg'],
                        'selling_variance' => $selling_variance,
                        'cost_variance' => $cost_variance,
                        'consumption_cost' => $consumption_cost,
                        'physical_valuation' => $physical_valuation
                    ];
                    if ($arr['total'] != '0.0' || $arr['closing'] != '0.0' || $arr['physical'] != '0.0')
                        array_push($json, $arr);
                }
            }
        }
        return json_encode($json);
    }
    public function getCurrentFinancialYearMonths()
    {
        // Get the current year
        $currentYear = date('Y');

        // Define the start date of the financial year (Assuming April 1st)
        $startMonth = 4; // April
        $startDay = 1;

        // Create the start date object
        $startDate = new DateTime("$currentYear-$startMonth-$startDay");

        // Create an array to store the months and years
        $months = [];

        // Iterate through 12 months and add them to the array
        for ($i = 0; $i < 12; $i++) {
            $month = $startDate->format('m');
            $year = $startDate->format('Y');
            $months[] = "$month $year";

            // Move to the next month
            $startDate->modify('+1 month');
        }

        return $months;
    }
    public function BrandwiseReport1(Request $request)
    {
        $json = [];
        $data = [];
        $subtotals = [];
        $categories = Category::where(['status' => 1])->get();
        $company_id = $request->company_id;
        $currentDate = $request->to_date;
        $pageno =  $request->pageno;
        

        // Retrieve all unique btl_size values from the Brand table
       // $btlSizes = Brand::distinct()->pluck('btl_size')->toArray();
       
       $perPage = 6; // Number of records per page
       $pageOffset = ($pageno - 1) * $perPage; // Calculate the offset based on the page number
     

        $btlSizes = Brand::distinct()
            ->pluck('btl_size')
            ->skip($pageOffset)
            ->take($perPage)
            ->toArray();
        //print_r($btlSizes); exit;
        
        foreach ($categories as $category) {
            $cat_name = $category->name;
            $btls = Brand::where(['category_id' => $category->id])->get();
            //total
            $subtotalOpening = 0;
            $subtotalPurchase = 0;
            $subtotalSales     = 0;
            $subtotalClosing = 0;
            $openSum = 0;
            $open = 0;
            $purchaseSum = 0;
            $totalSum = 0;
            $saleSum = 0;
            $closingSum = 0;
            foreach ($btls as $key2 => $brand) {
                $brand_name = $brand['name'];
                $btl_size = $brand['btl_size'];


                // opening section
                $opening = DailyOpening::where(['brand_id' => $brand['id'], 'company_id' => $company_id])
                    ->whereDate('date', $currentDate)
                    ->select(DB::raw('COALESCE(qty, 0) as qty'))
                    ->first();
                if ($opening)
                    $open = $opening['qty'];
                else
                    $open = 0;
                $openSum = $openSum + $open;

                // purchase section
                $purchase = Purchase::where(['brand_id' => $brand['id'], 'company_id' => $company_id])
                    ->whereDate('invoice_date', $currentDate)

                    ->select(DB::raw('COALESCE(qty, 0) as qty'))
                    ->first();
                if ($purchase)
                    $purchaseQty = $purchase['qty'];
                else
                    $purchaseQty = 0;
                $purchaseSum = $purchaseSum + $purchaseQty;

                $total = $purchaseQty + $open;
                if ($total)
                    $totalSum = $totalSum + $total;

                // sales
                $sales = Sales::where(['brand_id' => $brand['id'], 'company_id' => $company_id])
                    ->whereDate('sale_date', $currentDate)

                    ->select(DB::raw('COALESCE(qty, 0) as qty'))
                    ->first();
                if ($sales)
                    $saleQty = $sales['qty'];
                else
                    $saleQty = 0;
                $saleSum = $saleSum + $saleQty;

                // total section
                $closing = $total - $saleQty;
                if ($total)
                    $closingSum = $closingSum + $closing;


                $open_btl = convertBtlPeg($open, $brand['btl_size'], $brand['peg_size']);
                // total calculation
                $purchase_btl = convertBtlPeg($purchaseQty, $brand['btl_size'], $brand['peg_size']);
                $sale_btl = convertBtlPeg($saleQty, $brand['btl_size'], $brand['peg_size']);
                $closing_btl = convertBtlPeg($closing, $brand['btl_size'], $brand['peg_size']);

                $categoryData = [
                    'Category' => $cat_name,
                    'Brand Name' => $brand_name,
                    'TPNo' => '',
                ];

                // Add btl_size data to the categoryData array
                foreach ($btlSizes as $size) {
                    if ($size == $btl_size) {
                        $categoryData['o-' . $size] = $open_btl['btl'] . '.' . $open_btl['peg'];
                    } else {
                        $categoryData['o-' . $size] = '';
                    }
                }
                foreach ($btlSizes as $size) {
                    if ($size == $btl_size) {
                        $categoryData['p-' . $size] = $purchase_btl['btl'] . '.' . $purchase_btl['peg'];
                    } else {
                        $categoryData['p-' . $size] = '';
                    }
                }
                foreach ($btlSizes as $size) {
                    if ($size == $btl_size) {
                        $categoryData['s-' . $size] = $sale_btl['btl'] . '.' . $sale_btl['peg'];
                    } else {
                        $categoryData['s-' . $size] = '';
                    }
                }
                foreach ($btlSizes as $size) {
                    if ($size == $btl_size) {
                        $categoryData['c-' . $size] = $closing_btl['btl'] . '.' . $closing_btl['peg'];
                    } else {
                        $categoryData['c-' . $size] = '';
                    }
                }

                $data[] = $categoryData;
            }

            // Calculate subtotals for each btl_size within the category
            $categorySubtotal = [
                'Category' => $cat_name,
                'Brand Name' => 'SUBTOTAL',
                'TPNo' => '',
            ];

            // total calculation
            $c_open = convertBtlPeg($openSum, $brand['btl_size'], $brand['peg_size']);
            $c_purchase = convertBtlPeg($purchaseSum, $brand['btl_size'], $brand['peg_size']);
            $c_sale = convertBtlPeg($saleSum, $brand['btl_size'], $brand['peg_size']);
            $c_closing = convertBtlPeg($closingSum, $brand['btl_size'], $brand['peg_size']);

            $categoryData = [
                'Category' => $cat_name,
                'Brand Name' => $brand_name,
                'TPNo' => '',
            ];

            // Add btl_size data to the categoryData array
            foreach ($btlSizes as $size) {
                if ($size == $btl_size) {
                    $categorySubtotal['o-' . $size] = $c_open['btl'] . '.' . $c_open['peg'];
                } else {
                    $categorySubtotal['o-' . $size] = '';
                }
            }
            foreach ($btlSizes as $size) {
                if ($size == $btl_size) {
                    $categorySubtotal['p-' . $size] = $c_purchase['btl'] . '.' . $c_purchase['peg'];
                } else {
                    $categorySubtotal['p-' . $size] = '';
                }
            }
            foreach ($btlSizes as $size) {
                if ($size == $btl_size) {
                    $categorySubtotal['s-' . $size] = $c_sale['btl'] . '.' . $c_sale['peg'];
                } else {
                    $categorySubtotal['s-' . $size] = '';
                }
            }
            foreach ($btlSizes as $size) {
                if ($size == $btl_size) {
                    $categorySubtotal['c-' . $size] = $c_closing['btl'] . '.' . $c_closing['peg'];
                } else {
                    $categorySubtotal['c-' . $size] = '';
                }
            }

            $data[] = $categorySubtotal;
        }

        $json = $data;

        return response()->json($json);
    }
}
