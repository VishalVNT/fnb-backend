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

class Reports extends Controller
{
    //
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


    public function BarVarianceSummaryReport(Request $request)
    {
        $json = [];
        $comArray = [];
        array_push($comArray, $request->company_id);
        $brands_data = DB::table("brands")
            ->select('id')
            ->get();

        // Initialize the Liquor variable
        $liquor = 0;
        $cost_beverage = 0;
        $shortage_beverage = 0;
        $excess_beverage = 0;
        $adjusted_beverage = 0;
        $total_varience = 0;


        foreach ($brands_data as  $brandList) {
            $brand_id = $brandList->id;
            [$data_daily_opening] = DB::table('daily_openings')
                ->select(DB::raw('SUM(qty) AS qty'))
                ->whereIn('company_id', $comArray)
                ->where('brand_id', $brand_id)
                ->whereDate('date', '>=', $request->from_date)
                ->whereDate('date', '<=', $request->to_date)
                ->get();
            $opening_stock = !empty($data_daily_opening->qty) ? $data_daily_opening->qty : '0';

            [$data_purchases_qty] = DB::table('purchases')
                ->select(DB::raw('SUM(qty) AS qty'))
                ->where('brand_id', $brand_id)
                ->whereIn('company_id', $comArray)
                ->whereDate('invoice_date', '>=', $request->from_date)
                ->whereDate('invoice_date', '<=', $request->to_date)
                ->get();
            $purchase_qty = !empty($data_purchases_qty->qty) ? $data_purchases_qty->qty : 0;

            [$physical_stock] = DB::table('physical_histories')
                ->select(DB::raw('COALESCE(SUM(qty), 0) as physicalQty'))
                ->where('brand_id', $brand_id)
                ->whereIn('company_id', $comArray)
                ->whereDate('date', '>=', $request->from_date)
                ->whereDate('date', '<=', $request->to_date)
                ->get();
            $physical_qty = !empty($physical_stock->physicalQty) ? $physical_stock->physicalQty : 0;

            [$purchase_price] = DB::table('stocks')
                ->select(DB::raw('COALESCE(SUM(cost_price), 0) as cost_price'))
                ->whereIn('company_id', $comArray)
                ->where('brand_id', $brand_id)
                ->whereDate('created_at', '>=', $request->from_date)
                ->whereDate('created_at', '<=', $request->to_date)
                ->get();
            $purchase_price =  !empty($purchase_price->cost_price) ? $purchase_price->cost_price : 0;

            [$salesStocks] = DB::table('sales')
                ->select(DB::raw('COALESCE(SUM(qty), 0) as saleQty'))
                ->whereIn('company_id', $comArray)
                ->where('brand_id', $brand_id)
                ->whereDate('sale_date', '>=', $request->from_date)
                ->whereDate('sale_date', '<=', $request->to_date)
                ->get();
            $sale_qty =  !empty($salesStocks->saleQty) ? $salesStocks->saleQty : 0;
            $total_qty = $opening_stock + $purchase_qty;
            $consumption = $total_qty - $physical_qty;
            $cost_of_consumption = $purchase_price * $consumption;
            $liquor += $cost_of_consumption; // Add the cost_of_consumption to the liquor variable
            $total_costConsumption =  $cost_beverage +  $liquor;
            $closing =  $total_qty - $sale_qty;

            $variance = $physical_qty - $closing;

            $total_varience += $variance;

            if ($total_varience < 0) {
                $shortage = abs($total_varience);
                $excess = 0;
            } else {
                $shortage = 0;
                $excess = $total_varience;
            }
            $shortage_total = $shortage + $shortage_beverage;
            $excess_total = $excess + $excess_beverage;
            $adjusted_variance = $shortage - $excess;
            $total_adjusted_variance = $adjusted_variance + $adjusted_beverage;
        } //end of foreach

        $arr['Net Sales Revenue'] = [
            'Liquor' => $request->liquor !== null ? $request->liquor : 0,
            'Beverage' => $request->beverage ?? 0,
            'Total' => ($request->liquor !== null ? $request->liquor : 0) + ($request->beverage ?? 0)
        ];

        $arr['Cost of Consumption'] = [
            'Liquor' => $liquor, // Add the liquor variable to the array
            'Beverage' => $cost_beverage,
            'Total' => $total_costConsumption
        ];
        $arr['Shortage'] = [
            'Liquor' => $shortage,
            'Beverage' => $shortage_beverage,
            'Total' => $shortage_total
        ];
        $arr['Excess'] = [
            'Liquor' => $excess,
            'Beverage' => $excess_beverage,
            'Total' => $excess_total
        ];
        $arr['Adjusted Variance'] = [
            'Liquor' => $adjusted_variance,
            'Beverage' => $adjusted_beverage,
            'Total' => $total_adjusted_variance
        ];

        array_push($json, $arr);
        return json_encode($json);
    }

    public function TPRegisterReport(Request $request)
    {

        $json = [];
        $from_date = $request->from_date;
        $to_date = $request->to_date;
        $company_id = $request->company_id;
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
            ->whereDate('invoice_date', '>=', $from_date)
            ->whereDate('invoice_date', '<=', $to_date)
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
            //array_push($json, $arr);

        }

        return json_encode($json);
    }



    public function SalesRegisterReport(Request $request)
    {
        $json = [];
        $categories = Category::select('id', 'name')->get();
        $company_id = $request->company_id;
        $cat_array = array();
        foreach ($categories as $category) {
            $name = $category->name;
            $id = $category->id;

            $salesData = DB::table('sales')
                ->where('category_id', $id)
                ->whereDate('sale_date', '>=', $request->from_date)
                ->whereDate('sale_date', '<=', $request->to_date)
                ->where('company_id', $company_id)
                ->get();
            foreach ($salesData as $sale) {
                $cat = array(
                    'category_name' => $name,
                    'sales_date' => '',
                    'brand_name' => '',
                    'btl_size' => '',
                    'qty' => '',
                    'rate' => '',
                    'amount' => ''
                );

                $brandId = $sale->brand_id;
                $salesDate = $sale->sale_date;
                $sales_qty = $sale->qty;
                $no_peg = $sale->no_peg;
                $sale_price = $sale->sale_price;

                $brandDetails = DB::table('brands')
                    ->where('id', $brandId)
                    ->first();

                if ($brandDetails) {
                    if (!in_array($name, $cat_array)) {
                        array_push($json, $cat);
                    }
                    array_push($cat_array, $name);
                    $brand = array(
                        'category_name' => '',
                        'sales_date' => $salesDate,
                        'brand_name' => $brandDetails->name,
                        'btl_size' => $brandDetails->btl_size,
                        'qty' => $no_peg,
                        'rate' => 0,
                        'amount' => $sale_price
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

        foreach ($categories as $category) {
            $name = $category->name;
            $id = $category->id;

            $stockData = DB::table('stocks')
                ->where('category_id', $id)
                ->where('company_id', $company_id)
                ->whereDate('created_at', '>=', $request->from_date)
                ->whereDate('created_at', '<=', $request->to_date)
                ->get();

            $brands = [];
            foreach ($stockData as $stock) {
                $brandId = $stock->brand_id;

                [$data_daily_opening] = DB::table('daily_openings')
                    ->select('qty')
                    ->where('company_id', $company_id)
                    ->where('brand_id', $brandId)
                    ->get();
                $opening_qty = !empty($data_daily_opening->qty) ? $data_daily_opening->qty : '0';

                $data_purchase = DB::table('purchases')
                    ->select('qty')
                    ->where('brand_id', $brandId)
                    ->where('company_id', $company_id)
                    ->first();
                $purchase_qty = !empty($data_purchase->qty) ? $data_purchase->qty : 0;

                $data_sales = DB::table('sales')
                    ->select('qty')
                    ->where('brand_id', $brandId)
                    ->where('company_id', $company_id)
                    ->first();
                $sales_qty = !empty($data_sales->qty) ? $data_sales->qty : 0;



                $brandDetails = DB::table('brands')
                    ->where('id', $brandId)
                    ->first();

                if ($brandDetails) {
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

                    $brand = [
                        'brand_id' => $brandId,
                        'brand_name' => $brandDetails->name,
                        'opening_qty' => $opening_qty,
                        'btl_size' => $btl_size,
                        'peg_size' => $peg_size,
                        'opening_balance' => $opening_balance,
                        'purchase' => $purchase,
                        'total' => $total,
                        'sales' => $sales,
                        'closing_balance' => $closing_balance
                    ];

                    $brands[] = $brand;
                }
            } //foreach


            /* $arr = [
				'Category' => $name,
				'Brands' => $brands
			];
			array_push($json, $arr); */

            if (!empty($brands)) {
                $arr = [
                    'Category' => $name,
                    'Brands' => $brands
                ];

                array_push($json, $arr);
            }
        } //foreach

        return json_encode($json);
    }




    public function SalesSummaryReport(Request $request)
    {
        $json = [];
        //$total_price = 0;
        $company_id = $request->company_id;
        $fromDate = $request->from_date;
        $toDate = $request->to_date;

        $startDate = Carbon::createFromFormat('Y-m-d', $fromDate);
        $endDate = Carbon::createFromFormat('Y-m-d', $toDate);

        $dates = [];
        while ($startDate <= $endDate) {
            $dates[] = $startDate->format('Y-m-d');
            $startDate->addDay();
        }

        foreach ($dates as $date) {

            [$sales] = DB::table('sales')
                ->select(DB::raw('COALESCE(SUM(sale_price), 0) as salePrice'), 'category_id', 'name')
                ->where('company_id', $company_id)
                ->whereDate('sale_date', '=', $date)
                ->join('categories', 'categories.id', '=', 'sales.category_id')
                ->get();
            $category_id = !empty($sales->category_id) ? $sales->category_id : 0;
            $sale_price = !empty($sales->salePrice) ? $sales->salePrice : 0;
            $category_name = !empty($sales->name) ? ($sales->name) : 0;

            $arr[$date] = [
                'Data' => [
                    'Category ID' => $category_id,
                    'Category Name' => $category_name,
                    'Price' => $sale_price
                ]
            ];
            //  $total_price += $sale_price;

        }


        array_push($json, $arr);
        return json_encode($json);
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

        // $fromDate = $request->fromDate;
        // $toDate = $request->toDate;
        $company_id = $request->company_id;
        $categories = Category::where(['status' => 1])->get(); // get all category
        foreach ($categories as $key => $category) {
            $btls = Brand::where(['category_id' => $category->id])->orderBy('btl_size', 'DESC')->groupBy(DB::raw("btl_size"))->get(); // get unique bottle size of that category
            foreach ($btls as $key2 => $btl_size) {
                $brands = Brand::where(['category_id' => $category['id'], 'btl_size' => $btl_size['btl_size']])->get(); // get brand of that category
                $openSum = 0;
                $purchaseSum = 0;
                $totalSum = 0;
                $saleSum = 0;
                $closingSum = 0;
                foreach ($brands as $key => $brand) {
                    // opening section
                    $opening = DailyOpening::where(['brand_id' => $brand['id'], 'company_id' => $company_id])
                        ->select(DB::raw('COALESCE(qty, 0) as qty'))
                        ->first();
                    if ($opening)
                        $open = $opening['qty'];
                    else
                        $open = 0;
                    $openSum = $openSum + $open;
                    //purchase section
                    $purchase = purchase::where(['brand_id' => $brand['id'], 'company_id' => $company_id])
                        ->select(DB::raw('COALESCE(qty, 0) as qty'))
                        ->first();
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
                    $sales = Sales::where(['brand_id' => $brand['id'], 'company_id' => $company_id])
                        ->select(DB::raw('COALESCE(qty, 0) as qty'))
                        ->first();
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
}
