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
use Illuminate\Support\Facades\Schema;

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
            ->select('id', 'btl_size', 'peg_size')
            ->where('status', 1)
            ->get();

        // Initialize the Liquor variable

        $shortage = 0;
        $excess = 0;
        $cost_beverage = 0;
        $shortage_beverage = 0;
        $excess_beverage = 0;
        $adjusted_beverage = 0;
        $adjusted_variance = 0;
        $consumption_sum = 0;
        $ncSaleqty_beverage = 0;
        $ideal = 0;
        $gross = 0;
        $net = 0;
        $gross_beverage = 0;
        $net_beverage = 0;

        // variables for current month
        $shortage2 = 0;
        $excess2 = 0;
        $cost_beverage2 = 0;
        $shortage_beverage2 = 0;
        $excess_beverage2 = 0;
        $adjusted_beverage2 = 0;
        $adjusted_variance2 = 0;
        $consumption_sum2 = 0;
        $ncSaleqty_beverage2 = 0;
        $ideal2 = 0;
        $gross2 = 0;
        $net2 = 0;
        $gross_beverage2 = 0;
        $net_beverage2 = 0;


        foreach ($brands_data as  $brandList) {
            $brand_id = $brandList->id;
            // FROM DATE TO DATE DATA
            [$data_daily_opening] = DB::table('daily_openings')
                ->select(DB::raw('SUM(qty) AS qty'))
                ->whereIn('company_id', $comArray)
                ->where(['brand_id' => $brand_id, 'status' => 1])
                ->whereDate('date', '>=', $request->from_date)
                ->whereDate('date', '<=', $request->to_date)
                ->get();
            $opening_stock = !empty($data_daily_opening->qty) ? $data_daily_opening->qty : '0';

            [$data_purchases_qty] = DB::table('purchases')
                ->select(DB::raw('SUM(qty) AS qty'))
                ->where(['brand_id' => $brand_id, 'status' => 1])
                ->whereIn('company_id', $comArray)
                ->whereDate('invoice_date', '>=', $request->from_date)
                ->whereDate('invoice_date', '<=', $request->to_date)
                ->get();
            $purchase_qty = !empty($data_purchases_qty->qty) ? $data_purchases_qty->qty : 0;

            [$physical_stock] = DB::table('physical_histories')
                ->select(DB::raw('COALESCE(SUM(qty), 0) as physicalQty'))
                ->where(['brand_id' => $brand_id, 'status' => 1])
                ->whereIn('company_id', $comArray)
                ->whereDate('date', '>=', $request->from_date)
                ->whereDate('date', '<=', $request->to_date)
                ->get();
            $physical_qty = !empty($physical_stock->physicalQty) ? $physical_stock->physicalQty : 0;

            [$salesStocks] = DB::table('sales')
                ->select(DB::raw('COALESCE(SUM(qty), 0) as saleQty'))
                ->whereIn('company_id', $comArray)
                ->where(['brand_id' => $brand_id, 'status' => 1])
                ->whereDate('sale_date', '>=', $request->from_date)
                ->whereDate('sale_date', '<=', $request->to_date)
                ->get();
            [$nc_sale] = DB::table('sales')
                ->select(DB::raw('COALESCE(SUM(qty), 0) as saleQty'))
                ->whereIn('company_id', $comArray)
                ->where(['brand_id' => $brand_id, 'status' => 1, 'sales_type' => 2])
                ->whereDate('sale_date', '>=', $request->from_date)
                ->whereDate('sale_date', '<=', $request->to_date)
                ->get();
            $ncSaleqty = !empty($nc_sale->qty) ? $nc_sale->qty : '0';
            // CURRENT MONTH DATA
            [$data_daily_opening2] = DB::table('daily_openings')
                ->select(DB::raw('SUM(qty) AS qty'))
                ->whereIn('company_id', $comArray)
                ->where(['brand_id' => $brand_id, 'status' => 1])
                ->whereMonth('date', '>=', date('m', strtotime($request->from_date)))
                ->whereYear('date', '<=', date('Y', strtotime($request->to_date)))
                ->get();
            $opening_stock2 = !empty($data_daily_opening2->qty) ? $data_daily_opening->qty : '0';

            [$data_purchases_qty2] = DB::table('purchases')
                ->select(DB::raw('SUM(qty) AS qty'))
                ->where(['brand_id' => $brand_id, 'status' => 1])
                ->whereIn('company_id', $comArray)
                ->whereDate('invoice_date', '>=',  date('m', strtotime($request->from_date)))
                ->whereDate('invoice_date', '<=',  date('Y', strtotime($request->to_date)))
                ->get();
            $purchase_qty2 = !empty($data_purchases_qty2->qty) ? $data_purchases_qty2->qty : 0;

            [$physical_stock2] = DB::table('physical_histories')
                ->select(DB::raw('COALESCE(SUM(qty), 0) as physicalQty'))
                ->where(['brand_id' => $brand_id, 'status' => 1])
                ->whereIn('company_id', $comArray)
                ->whereDate('date', '>=',  date('m', strtotime($request->from_date)))
                ->whereDate('date', '<=',  date('Y', strtotime($request->to_date)))
                ->get();
            $physical_qty2 = !empty($physical_stock2->physicalQty) ? $physical_stock2->physicalQty : 0;


            [$salesStocks2] = DB::table('sales')
                ->select(DB::raw('COALESCE(SUM(qty), 0) as saleQty'))
                ->whereIn('company_id', $comArray)
                ->where(['brand_id' => $brand_id, 'status' => 1])
                ->whereDate('sale_date', '>=',  date('m', strtotime($request->from_date)))
                ->whereDate('sale_date', '<=',  date('Y', strtotime($request->to_date)))
                ->get();

            [$nc_sale2] = DB::table('sales')
                ->select(DB::raw('COALESCE(SUM(qty), 0) as saleQty'))
                ->whereIn('company_id', $comArray)
                ->where(['brand_id' => $brand_id, 'status' => 1, 'sales_type' => 2])
                ->whereDate('sale_date', '>=', $request->from_date)
                ->whereDate('sale_date', '<=', $request->to_date)
                ->get();
            $ncSaleqty2 = !empty($nc_sale2->qty) ? $nc_sale2->qty : '0';

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
                $adjusted = $costing - 0;
                $adjusted_variance += $adjusted;
            } else {
                $costing = $btl_sht['btl'] * $rate['amount'] + $btl_sht['peg'] * $rate['pegprice'];
                $excess +=  $costing;
                $adjusted = 0 - $costing;
                $adjusted_variance += $adjusted;
            }
            $idealPer = ($consumption_cost + $adjusted_variance) / $request->liquor;
            $ideal += $idealPer;
            $grossPer = ($consumption_cost + $ncSaleqty) / $request->liquor;
            $gross += $grossPer;
            $netPer = $consumption_cost / $request->liquor;
            $net += $netPer;
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
            $idealPer2 = ($consumption_cost2 + $adjusted_variance2) / $request->liquor2;
            $ideal2 += $idealPer2;
            $grossPer2 = ($consumption_cost2 + $ncSaleqty2) / $request->liquor2;
            $gross2 += $grossPer2;
            $netPer2 = $consumption_cost2 / $request->liquor2;
            $net2 += $netPer2;
        } //end of foreach

        $sales = array(
            'Title' => 'Net Sales Revenue',
            'Liquor' => $request->liquor !== null ? $request->liquor : 0,
            'Beverage' => $request->beverage ?? 0,
            'Total' => ($request->liquor !== null ? $request->liquor : 0) + ($request->beverage ?? 0),
            'MTD Liquor' => $request->liquor2 !== null ? $request->liquor2 : 0,
            'MTD Beverage' => $request->beverage2 ?? 0,
            'MTD Total' => ($request->liquor2 !== null ? $request->liquor2 : 0) + ($request->beverage2 ?? 0)
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
        $ncArr = array(
            'Title' => 'NC Cost',
            'Liquor' => $ncSaleqty,
            'Beverage' => $ncSaleqty_beverage,
            'Total' => $ncSaleqty + $ncSaleqty_beverage,
            'MTD Liquor' => $ncSaleqty2,
            'MTD Beverage' =>  $ncSaleqty_beverage2,
            'MTD Total' => $ncSaleqty2 + $ncSaleqty2,
        );
        array_push($json, $ncArr);
        $idealArr = array(
            'Title' => 'Ideal Cost %',
            'Liquor' => $ideal,
            'Beverage' => $ncSaleqty_beverage, // need to be calculated
            'Total' => $ncSaleqty + $ncSaleqty_beverage,
            'MTD Liquor' => $ideal2,
            'MTD Beverage' =>  $ncSaleqty_beverage2,
            'MTD Total' => $ncSaleqty2 + $ncSaleqty2,
        );
        array_push($json, $idealArr);
        $grossArr = array(
            'Title' => 'Gross Cost',
            'Liquor' => $gross,
            'Beverage' => $gross_beverage,
            'Total' => $gross + $gross_beverage,
            'MTD Liquor' => $gross2,
            'MTD Beverage' =>  $gross_beverage2,
            'MTD Total' => $gross2 + $gross_beverage2,
        );
        array_push($json, $grossArr);
        $netArr = array(
            'Title' => 'Net Cost',
            'Liquor' => $net,
            'Beverage' => $net_beverage,
            'Total' => $net + $net_beverage,
            'MTD Liquor' => $net2,
            'MTD Beverage' =>  $net_beverage2,
            'MTD Total' => $net2 + $net_beverage2,
        );
        array_push($json, $netArr);
        return json_encode($json);
    }

    public function TPRegisterReport(Request $request)
    {
        $json = [];
        $from_date = $request->from_date;
        $to_date = $request->to_date;
        $company_id = $request->company_id;

        $from_date_table_year = date('Y', strtotime($from_date));
        $from_date_table_month = date('m', strtotime($from_date));

        $to_date_table_year = date('Y', strtotime($to_date));
        $to_date_table_month = date('m', strtotime($to_date));

        // Get all stock prices for the company and brands in one go
        $mrpData = DB::table('stocks')
                    ->where('company_id', $company_id)
                    ->select('brand_id', 'cost_price')
                    ->orderBy('id', 'desc')
                    ->get()
                    ->keyBy('brand_id');

        if ($from_date_table_year === $to_date_table_year) {
            // Both years are the same
            if ($from_date_table_month === $to_date_table_month) {
                // If months are the same, keep your original logic
                $table_name = $from_date_table_year . '_' . $from_date_table_month . '_' . 'log_data';

                $result = DB::table($table_name)
                            ->select('company_id', 'log_date', 'data')
                            ->whereDate('log_date', '>=', $from_date)
                            ->whereDate('log_date', '<=', $to_date)
                            ->where('company_id', $company_id)
                            ->orderBy('log_date', 'asc')
                            ->get();

                $this->processLogDataForPurchase($result, $mrpData, $json);
            } else {
                // If months are different but years are the same
                for ($month = (int)$from_date_table_month; $month <= (int)$to_date_table_month; $month++) {
                    $month_str = str_pad($month, 2, '0', STR_PAD_LEFT); // format the month to 2 digits
                    $table_name = $from_date_table_year . '_' . $month_str . '_' . 'log_data';

                    // Check if the table exists in the database
                    if (Schema::hasTable($table_name)) {
                        $result = DB::table($table_name)
                                    ->select('company_id', 'log_date', 'data')
                                    ->whereDate('log_date', '>=', $from_date)
                                    ->whereDate('log_date', '<=', $to_date)
                                    ->where('company_id', $company_id)
                                    ->orderBy('log_date', 'asc')
                                    ->get();

                        $this->processLogDataForPurchase($result, $mrpData, $json);
                    }
                }
            }
        } else {
            // Years are different
            for ($year = (int)$from_date_table_year; $year <= (int)$to_date_table_year; $year++) {
                $start_month = ($year === (int)$from_date_table_year) ? (int)$from_date_table_month : 1;
                $end_month = ($year === (int)$to_date_table_year) ? (int)$to_date_table_month : 12;

                for ($month = $start_month; $month <= $end_month; $month++) {
                    $month_str = str_pad($month, 2, '0', STR_PAD_LEFT); // format the month to 2 digits
                    $table_name = $year . '_' . $month_str . '_' . 'log_data';

                    // Check if the table exists in the database
                    if (Schema::hasTable($table_name)) {
                        $result = DB::table($table_name)
                                    ->select('company_id', 'log_date', 'data')
                                    ->whereDate('log_date', '>=', $from_date)
                                    ->whereDate('log_date', '<=', $to_date)
                                    ->where('company_id', $company_id)
                                    ->orderBy('log_date', 'asc')
                                    ->get();

                        $this->processLogDataForPurchase($result, $mrpData, $json);
                    }
                }
            }
        }
        return array_values($json);
    }

    // Helper function to process the log data and populate the JSON
    private function processLogDataForPurchase($result, $mrpData, &$json)
    {
        if (!empty($result)) {
            foreach ($result as $value) {
                $data = json_decode($value->data, true);
        
                if (!empty($data)) {
                    // Step 1: Group entries based on the specified criteria
                    $tempData = [];
                    foreach ($data as $dkey => $dvalue) {
                        $uniqueKey = $value->log_date . '-' . $dvalue['tp_no'] . '-' . $dvalue['transaction_type'] . '-' .
                                     $dvalue['transaction_table_id'] . '-' . $dvalue['brand_id'] . '-' . $dvalue['qty'] . '-' . 
                                     $dvalue['category_id'] . '-' . $dvalue['btl_size'] . '-' . $dvalue['peg_size'];
        
                        if (!isset($tempData[$uniqueKey])) {
                            $tempData[$uniqueKey] = [];
                        }
                        $tempData[$uniqueKey][] = $dvalue;
                    }
                    // Step 2: Cancel out matching credit and debit pairs
                    foreach ($tempData as $key => &$entries) {
                        $credits = [];
                        $debits = [];
        
                        foreach ($entries as $entry) {
                            if ($entry['transaction_category'] === 'credit') {
                                $credits[] = $entry;
                            } elseif ($entry['transaction_category'] === 'debit') {
                                $debits[] = $entry;
                            }
                        }
        
                        // Cancel out credit and debit pairs
                        $remainingCredits = max(count($credits) - count($debits), 0);
                        $remainingDebits = max(count($debits) - count($credits), 0);
        
                        // Retain remaining unmatched credits or debits
                        $entries = array_merge(
                            array_slice($credits, 0, $remainingCredits),
                            array_slice($debits, 0, $remainingDebits)
                        );
        
                        // Remove the group if no entries remain
                        if (empty($entries)) {
                            unset($tempData[$key]);
                        }
                    }
                    unset($entries);  // Unset reference to avoid side effects
        
                    // Step 3: Flatten tempData into a single array of remaining entries
                    $filteredData = [];
                    foreach ($tempData as $entries) {
                        foreach ($entries as $entry) {
                            $filteredData[] = $entry;
                        }
                    }
        
                    // Step 4: Process the filtered data
                    foreach ($filteredData as $dvalue) {
                        $uniqueKey = $value->log_date . '-' . $dvalue['brand_id'] . '-' . $dvalue['tp_no'];

                        if(!empty($dvalue['purchase_price']))
                        {
                            $mrp = (int)$dvalue['purchase_price'];
                        }else{
                            $mrp = isset($mrpData[$dvalue['brand_id']]) ? $mrpData[$dvalue['brand_id']]->cost_price : 0;
                        }
        
                        if ($dvalue['transaction_type'] == 'purchase') {
                            if (!isset($json[$uniqueKey])) {
                                $json[$uniqueKey] = [
                                    'invoice_no' => $dvalue['tp_no'],
                                    'invoice_date' => $value->log_date,
                                    'category_group' => $dvalue['category_name'],
                                    'brand_name' => $dvalue['brand_name'],
                                    'btl_size' => $dvalue['btl_size'],
                                    'qty' => 0,
                                    'no_btl' => '',
                                    'mrp' => $mrp,
                                    'total_amount' => '',
                                    'vendor_name' => $dvalue['vendor_name']
                                ];
                            }
        
                            if ($dvalue['transaction_category'] == 'credit') {
                                $json[$uniqueKey]['qty'] += $dvalue['qty'];
                            } elseif ($dvalue['transaction_category'] == 'debit') {
                                $json[$uniqueKey]['qty'] -= $dvalue['qty'];
                            }
        
                            $qtyInBtlPeg = convertBtlPeg(abs($json[$uniqueKey]['qty']), $dvalue['btl_size'], $dvalue['peg_size']);
        
                            $json[$uniqueKey]['no_btl'] = $qtyInBtlPeg['btl'];
                            $json[$uniqueKey]['total_amount'] = $mrp * (int)$qtyInBtlPeg['btl'];
                        }
                    }
                }
            }
        }
                
        
        foreach ($json as &$entry) {
            unset($entry['qty']);
        }
    }



    public function SalesRegisterReport(Request $request)
    {
        $json = [];
        $from_date = $request->from_date;
        $to_date = $request->to_date;
        $company_id = $request->company_id;

        $from_date_table_year = date('Y', strtotime($from_date));
        $from_date_table_month = date('m', strtotime($from_date));

        $to_date_table_year = date('Y', strtotime($to_date));
        $to_date_table_month = date('m', strtotime($to_date));

        // Get all stock prices for the company and brands in one go
        $mrpData = DB::table('stocks')
                    ->where('company_id', $company_id)
                    ->select('brand_id', 'btl_selling_price', 'peg_selling_price')
                    ->orderBy('id', 'desc')
                    ->get()
                    ->keyBy('brand_id');

        if ($from_date_table_year === $to_date_table_year) {
            // Both years are the same
            if ($from_date_table_month === $to_date_table_month) {
                // If months are the same, keep your original logic
                $table_name = $from_date_table_year . '_' . $from_date_table_month . '_' . 'log_data';

                $result = DB::table($table_name)
                            ->select('company_id', 'log_date', 'data')
                            ->whereDate('log_date', '>=', $from_date)
                            ->whereDate('log_date', '<=', $to_date)
                            ->where('company_id', $company_id)
                            ->orderBy('log_date', 'asc')
                            ->get();

                $this->processLogDataForSales($result, $mrpData, $json);
            } else {
                // If months are different but years are the same
                for ($month = (int)$from_date_table_month; $month <= (int)$to_date_table_month; $month++) {
                    $month_str = str_pad($month, 2, '0', STR_PAD_LEFT); // format the month to 2 digits
                    $table_name = $from_date_table_year . '_' . $month_str . '_' . 'log_data';

                    // Check if the table exists in the database
                    if (Schema::hasTable($table_name)) {
                        $result = DB::table($table_name)
                                    ->select('company_id', 'log_date', 'data')
                                    ->whereDate('log_date', '>=', $from_date)
                                    ->whereDate('log_date', '<=', $to_date)
                                    ->where('company_id', $company_id)
                                    ->orderBy('log_date', 'asc')
                                    ->get();
                        $this->processLogDataForSales($result, $mrpData, $json);
                    }
                }
            }
        } else {
            // Years are different
            for ($year = (int)$from_date_table_year; $year <= (int)$to_date_table_year; $year++) {
                $start_month = ($year === (int)$from_date_table_year) ? (int)$from_date_table_month : 1;
                $end_month = ($year === (int)$to_date_table_year) ? (int)$to_date_table_month : 12;

                for ($month = $start_month; $month <= $end_month; $month++) {
                    $month_str = str_pad($month, 2, '0', STR_PAD_LEFT); // format the month to 2 digits
                    $table_name = $year . '_' . $month_str . '_' . 'log_data';

                    // Check if the table exists in the database
                    if (Schema::hasTable($table_name)) {
                        $result = DB::table($table_name)
                                    ->select('company_id', 'log_date', 'data')
                                    ->whereDate('log_date', '>=', $from_date)
                                    ->whereDate('log_date', '<=', $to_date)
                                    ->where('company_id', $company_id)
                                    ->orderBy('log_date', 'asc')
                                    ->get();

                        $this->processLogDataForSales($result, $mrpData, $json);
                    }
                }
            }
        }
        return array_values($json);
    }

    

    // Helper function to process the log data and populate the JSON
    private function processLogDataForSales($result, $mrpData, &$json)
    {
        $processedCategories = []; // Track already processed categories
        $potentiallyDeletedEntries = []; // Track entries to detect deletion pairs

        if (!empty($result)) {
            foreach ($result as $value) {
                $data = json_decode($value->data, true);

                if (!empty($data)) {
                    foreach ($data as $dkey => $dvalue) {
                        if ($dvalue['transaction_type'] == 'sales') {
                            // Ensure all necessary keys exist
                            if (!isset($dvalue['transaction_category'], $dvalue['transaction_table_id'], $dvalue['qty'], $dvalue['brand_id'])) {
                                continue; // Skip if any key is missing
                            }
    
                            $uniqueKey = $dvalue['transaction_table_id'] . '-' . $dvalue['transaction_type'] . '-' . $dvalue['qty'] . '-' . $dvalue['brand_id'];
    
                            // Check if this entry forms a pair with an opposite category (credit/debit)
                            if (isset($potentiallyDeletedEntries[$uniqueKey]) &&
                                $potentiallyDeletedEntries[$uniqueKey]['transaction_category'] != $dvalue['transaction_category']) {
                                // Found a pair, remove the matching entry from potentiallyDeletedEntries
                                unset($potentiallyDeletedEntries[$uniqueKey]);
                                continue; // Skip this entry as it's part of a deletion pair
                            }
    
                            // Otherwise, store this entry as it doesn't yet have a pair
                            $potentiallyDeletedEntries[$uniqueKey] = [
                                'entry_data' => $dvalue,
                                'log_date' => $value->log_date,
                                'transaction_category' => $dvalue['transaction_category']
                            ];
                        }
                    }
                }
            }
        }

        // Process remaining entries that are not part of any deletion pair
        foreach ($potentiallyDeletedEntries as $entryKey => $entryInfo) {
            $dvalue = $entryInfo['entry_data'];
            $log_date = $entryInfo['log_date'];
            $categoryName = $dvalue['category_name'];

            $btl_selling_price = isset($mrpData[$dvalue['brand_id']]) ? $mrpData[$dvalue['brand_id']]->btl_selling_price : 0;
            $peg_selling_price = isset($mrpData[$dvalue['brand_id']]) ? $mrpData[$dvalue['brand_id']]->peg_selling_price : 0;
            $mrp = $peg_selling_price;

            // Check if category has already been added
            if (!in_array($categoryName, $processedCategories)) {
                $json[] = [
                    'category_name' => $categoryName,
                    'category_name_for_grouping' => '',
                    'sale_date' => '',
                    'brand_name' => '',
                    'btl_size' => '',
                    'qty_inpeg' => '',
                    'rate' => '',
                    'amount' => ''
                ];
                $processedCategories[] = $categoryName;
            }

            // Add the brand row under the correct category
            $json[] = [
                'category_name' => '',
                'category_name_for_grouping' => $dvalue['category_name'],
                'sale_date' => $log_date,
                'brand_name' => $dvalue['brand_name'],
                'btl_size' => $dvalue['btl_size'],
                'qty' => 0,
                'qty_inpeg' => '',
                'rate' => $mrp,
                'amount' => ''
            ];

            // Adjust qty based on transaction category
            if ($dvalue['transaction_category'] == 'credit') {
                $json[array_key_last($json)]['qty'] += $dvalue['qty'];
            } elseif ($dvalue['transaction_category'] == 'debit') {
                $json[array_key_last($json)]['qty'] -= $dvalue['qty'];
            }

            // Calculate qty in pegs and amount
            $qtyInPeg = abs($json[array_key_last($json)]['qty']) / $dvalue['peg_size'];
            $json[array_key_last($json)]['qty_inpeg'] = (int)$qtyInPeg;
            $json[array_key_last($json)]['amount'] = $peg_selling_price * (int)$qtyInPeg;
        }

        // Further steps to group and format data as previously
        $all_cat = [];
        $grouped_data = [];

        if (!empty($json)) {
            foreach ($json as $key => $value) {
                if (!empty($value['category_name']) && !in_array($value['category_name'], $all_cat)) {
                    $all_cat[] = $value['category_name'];
                }
            }

            foreach ($all_cat as $cat_value) {
                $grouped_data[$cat_value] = [];

                foreach ($json as $data_value) {
                    if (empty($data_value['category_name'])) {
                        if ($data_value['category_name_for_grouping'] == $cat_value) {
                            $grouped_data[$cat_value][] = $data_value;
                        }
                    } else if ($data_value['category_name'] == $cat_value) {
                        $grouped_data[$cat_value][] = $data_value;
                    }
                }
            }
        }

        $all_categories = DB::table('categories')
                        ->select('id', 'name')
                        ->get();

        $categoryOrder = $all_categories->pluck('name')->all();

        // Sort the $grouped_data based on the order of categories in $categoryOrder
        uksort($grouped_data, function($a, $b) use ($categoryOrder) {
            $posA = array_search($a, $categoryOrder);
            $posB = array_search($b, $categoryOrder);
            return $posA <=> $posB;
        });

        $flattened_data = [];
        if (!empty($grouped_data)) {
            foreach ($grouped_data as $category_group) {
                foreach ($category_group as $entry) {
                    $flattened_data[] = $entry;
                }
            }
        }

        $json = $flattened_data;

        // Remove the 'qty' field and 'category_name_for_grouping' for the final output
        foreach ($json as &$entry) {
            unset($entry['qty']);
            unset($entry['category_name_for_grouping']);
        }
    }


    private function getAllLogDataTables()
    {
        // Fetch all table names in the database
        $databaseName = env('DB_DATABASE');

        // Run a query to fetch tables with names ending in 'log_data'
        $tables = DB::select("SHOW TABLES LIKE '%log_data'");

        // Extract the table names dynamically
        $tableNames = [];
        foreach ($tables as $table) {
            $tableNames[] = current((array) $table);  // Convert object to array and get the first value
        }

        return $tableNames;
    }

    public function StockRegisterReport(Request $request)
    {
        $json = [];
        $from_date = $request->from_date;
        $to_date = $request->to_date;
        $company_id = $request->company_id;

        // get all the tables
        $allLogTables = $this->getAllLogDataTables();

        $openingData = $this->getOpeningData($allLogTables, $from_date, $company_id);

        $from_date_table_year = date('Y', strtotime($from_date));
        $from_date_table_month = date('m', strtotime($from_date));

        $to_date_table_year = date('Y', strtotime($to_date));
        $to_date_table_month = date('m', strtotime($to_date));

        if ($from_date_table_year === $to_date_table_year) {
            // Both years are the same
            if ($from_date_table_month === $to_date_table_month) {
                // If months are the same, keep your original logic
                $table_name = $from_date_table_year . '_' . $from_date_table_month . '_' . 'log_data';

                $result = DB::table($table_name)
                            ->select('company_id', 'log_date', 'data')
                            ->where('company_id', $company_id)
                            ->whereDate('log_date', '>=', $from_date)
                            ->whereDate('log_date', '<=', $to_date)
                            ->orderBy('log_date', 'asc')
                            ->get();

                $this->processLogDataForStockRegister($table_name, $openingData, $company_id, $from_date, $result, $json);
            } else {
                // If months are different but years are the same
                for ($month = (int)$from_date_table_month; $month <= (int)$to_date_table_month; $month++) {
                    $month_str = str_pad($month, 2, '0', STR_PAD_LEFT); // format the month to 2 digits
                    $table_name = $from_date_table_year . '_' . $month_str . '_' . 'log_data';

                    // Check if the table exists in the database
                    if (Schema::hasTable($table_name)) {
                        $result = DB::table($table_name)
                                    ->select('company_id', 'log_date', 'data')
                                    ->whereDate('log_date', '>=', $from_date)
                                    ->whereDate('log_date', '<=', $to_date)
                                    ->where('company_id', $company_id)
                                    ->orderBy('log_date', 'asc')
                                    ->get();

                        $this->processLogDataForStockRegister($table_name, $openingData, $company_id, $from_date, $result, $json);
                    }
                }
            }
        } else {
            // Years are different
            for ($year = (int)$from_date_table_year; $year <= (int)$to_date_table_year; $year++) {
                $start_month = ($year === (int)$from_date_table_year) ? (int)$from_date_table_month : 1;
                $end_month = ($year === (int)$to_date_table_year) ? (int)$to_date_table_month : 12;

                for ($month = $start_month; $month <= $end_month; $month++) {
                    $month_str = str_pad($month, 2, '0', STR_PAD_LEFT); // format the month to 2 digits
                    $table_name = $year . '_' . $month_str . '_' . 'log_data';

                    // Check if the table exists in the database
                    if (Schema::hasTable($table_name)) {
                        $result = DB::table($table_name)
                                    ->select('company_id', 'log_date', 'data')
                                    ->whereDate('log_date', '>=', $from_date)
                                    ->whereDate('log_date', '<=', $to_date)
                                    ->where('company_id', $company_id)
                                    ->orderBy('log_date', 'asc')
                                    ->get();

                        $this->processLogDataForStockRegister($table_name, $openingData, $company_id, $from_date, $result,$json);
                    }
                }
            }
        }

        
        $finalJson = [];
        $allCategories = [];
        $finalCategories = []; // This will hold the categories with their respective btl_size arrays

        if (!empty($json)) {
            // Initialize array to hold data categorized by category and bottle size
            $groupedData = [];
        
            // First pass to group data by category and bottle size, and to accumulate totals
            foreach ($json as $entry) {
                $category = $entry['category_name'];
                $btl_size = $entry['btl_size'];
        
                // Initialize if not already present
                if (!isset($groupedData[$category])) {
                    $groupedData[$category] = [];
                }
                if (!isset($groupedData[$category][$btl_size])) {
                    $groupedData[$category][$btl_size] = [
                        'brands' => [],
                        'final_opening' => 0,
                        'final_purchase' => 0,
                        'final_transfer' => 0,
                        'final_total' => 0,
                        'final_sales' => 0,
                        'final_closing' => 0,
                    ];
                }
        
                // Accumulate totals
                $groupedData[$category][$btl_size]['final_opening'] += $entry['opening_balance'];
                $groupedData[$category][$btl_size]['final_purchase'] += $entry['purchase'];
                $groupedData[$category][$btl_size]['final_transfer'] += $entry['transfer'];
                $groupedData[$category][$btl_size]['final_total'] += $entry['total'];
                $groupedData[$category][$btl_size]['final_sales'] += $entry['sales'];
                $groupedData[$category][$btl_size]['final_closing'] += $entry['closing_balance'];
        
                // Store individual brand details
                $groupedData[$category][$btl_size]['brands'][] = $entry;
            }
        
            // Prepare final JSON data
            foreach ($groupedData as $category_name => $btlSizes) {
                // First, add category row with empty values
                $finalJson[] = [
                    'category_name' => $category_name,
                    'brand_name' => '',
                    'btl_size' => '',
                    'opening_balance' => '',
                    'purchase' => '',
                    'transfer' => '',
                    'total' => '',
                    'sales' => '',
                    'closing_balance' => '',
                ];
        
                foreach ($btlSizes as $btl_size => $data) {
                    // Add each brand under this category + bottle size
                    foreach ($data['brands'] as $brand) {
                        $openingQty = convertBtlPeg(abs($brand['opening_balance']), $brand['btl_size'], $brand['peg_size']);
                        $purchaseQty = convertBtlPeg(abs($brand['purchase']), $brand['btl_size'], $brand['peg_size']);
                        $transferQty = convertBtlPeg(abs($brand['transfer']), $brand['btl_size'], $brand['peg_size']);
                        $totalQty = convertBtlPeg(abs($brand['total']), $brand['btl_size'], $brand['peg_size']);
                        $salesQty = convertBtlPeg(abs($brand['sales']), $brand['btl_size'], $brand['peg_size']);
                        $closingQty = convertBtlPeg(abs($brand['closing_balance']), $brand['btl_size'], $brand['peg_size']);
        
                        // Properly format the values
                        $finalJson[] = [
                            'category_name' => '',
                            'brand_name' => $brand['brand_name'],
                            'btl_size' => $btl_size,
                            'opening_balance' => $brand['opening_balance'] >= 0 ? $openingQty['btl'] . '.' . $openingQty['peg'] : '-' . $openingQty['btl'] . '.' . $openingQty['peg'],
                            'purchase' => $brand['purchase'] >= 0 ? $purchaseQty['btl'] . '.' . $purchaseQty['peg'] : '-' . $purchaseQty['btl'] . '.' . $purchaseQty['peg'],
                            'transfer' => $brand['transfer'] >= 0 ? $transferQty['btl'] . '.' . $transferQty['peg'] : '-' . $transferQty['btl'] . '.' . $transferQty['peg'],
                            'total' => $brand['total'] >= 0 ? $totalQty['btl'] . '.' . $totalQty['peg'] : '-' . $totalQty['btl'] . '.' . $totalQty['peg'],
                            'sales' => $brand['sales'] >= 0 ? $salesQty['btl'] . '.' . $salesQty['peg'] : '-' . $salesQty['btl'] . '.' . $salesQty['peg'],
                            'closing_balance' => $brand['closing_balance'] >= 0 ? $closingQty['btl'] . '.' . $closingQty['peg'] : '-' . $closingQty['btl'] . '.' . $closingQty['peg'],
                        ];
                    }
        
                    // Add subtotal row for each bottle size
                    $finalOpeningQty = convertBtlPeg(abs($data['final_opening']), $btl_size, $brand['peg_size']);
                    $finalPurchaseQty = convertBtlPeg(abs($data['final_purchase']), $btl_size, $brand['peg_size']);
                    $finalTransferQty = convertBtlPeg(abs($data['final_transfer']), $btl_size, $brand['peg_size']);
                    $finalTotalQty = convertBtlPeg(abs($data['final_total']), $btl_size, $brand['peg_size']);
                    $finalSalesQty = convertBtlPeg(abs($data['final_sales']), $btl_size, $brand['peg_size']);
                    $finalClosingQty = convertBtlPeg(abs($data['final_closing']), $btl_size, $brand['peg_size']);
        
                    $finalJson[] = [
                        'category_name' => '',
                        'brand_name' => 'SUBTOTAL(' . $btl_size . ' ML)',
                        'btl_size' => $btl_size,
                        'opening_balance' => $data['final_opening'] >= 0 ? $finalOpeningQty['btl'] . '.' . $finalOpeningQty['peg'] : '-' . $finalOpeningQty['btl'] . '.' . $finalOpeningQty['peg'],
                        'purchase' => $data['final_purchase'] >= 0 ? $finalPurchaseQty['btl'] . '.' . $finalPurchaseQty['peg'] : '-' . $finalPurchaseQty['btl'] . '.' . $finalPurchaseQty['peg'],
                        'transfer' => $data['final_transfer'] >= 0 ? $finalTransferQty['btl'] . '.' . $finalTransferQty['peg'] : '-' . $finalTransferQty['btl'] . '.' . $finalTransferQty['peg'],
                        'total' => $data['final_total'] >= 0 ? $finalTotalQty['btl'] . '.' . $finalTotalQty['peg'] : '-' . $finalTotalQty['btl'] . '.' . $finalTotalQty['peg'],
                        'sales' => $data['final_sales'] >= 0 ? $finalSalesQty['btl'] . '.' . $finalSalesQty['peg'] : '-' . $finalSalesQty['btl'] . '.' . $finalSalesQty['peg'],
                        'closing_balance' => $data['final_closing'] >= 0 ? $finalClosingQty['btl'] . '.' . $finalClosingQty['peg'] : '-' . $finalClosingQty['btl'] . '.' . $finalClosingQty['peg'],
                    ];
                }
            }
        }
        
        
        return array_values($finalJson);
    }

    private function getOpeningData($allLogTables, $from_date, $company_id)
    {
        $opening_qty_data = [];
        foreach($allLogTables as $tkey => $tval){
            $opening_qty_data_per_table = DB::table($tval)
                                            ->select('data')
                                            ->where('company_id', $company_id)
                                            ->whereDate('log_date', '<', $from_date)
                                            ->get()
                                            ->toArray();

            array_push($opening_qty_data, $opening_qty_data_per_table);
        }

        $opening_qty_map = [];

        foreach ($opening_qty_data as $opening_qty_entry) {
            foreach($opening_qty_entry as $qty_key => $qty_val){
                $data = json_decode($qty_val->data, true);
        
                if (!empty($data)) {
                    foreach ($data as $dvalue) {
                        $brandId = $dvalue['brand_id'];
                        $qty = $dvalue['qty'];
        
                        if (!isset($opening_qty_map[$brandId])) {
                            $opening_qty_map[$brandId] = 0;
                        }
        
                        // Sum credit and debit quantities
                        if ($dvalue['transaction_category'] == 'credit') {
                            $opening_qty_map[$brandId] += $qty;
                        } elseif ($dvalue['transaction_category'] == 'debit') {
                            $opening_qty_map[$brandId] -= $qty;
                        }
                    }
                }
            }
        }

        return $opening_qty_map;
    }

    // Helper function to process the log data and populate the JSON
    private function processLogDataForStockRegister($table_name, $openingData, $company_id, $from_date, $result, &$json)
    {

        $conflicting_entries = [];

        $brandAggregatedData = []; // Array to hold aggregated data for each brand
        
        if (!empty($result) && !empty($result[0])) {
            foreach ($result as $data_value) {
                $json_data = json_decode($data_value->data);
                if (!empty($json_data)) {
                    foreach ($json_data as $json_value) {
                        // Create a unique key for the current entry
                        $unique_key = "{$json_value->transaction_type}_{$json_value->transaction_table_id}_{$json_value->brand_name}_{$json_value->qty}";
        
                        // Track the entry in the conflicting_entries array
                        if (isset($conflicting_entries[$unique_key])) {
                            // If we already have this key, it means we found a conflict
                            $conflicting_entries[$unique_key]['conflict'] = true;
                        } else {
                            // Initialize with the transaction category
                            $conflicting_entries[$unique_key] = [
                                'transaction_category' => $json_value->transaction_category,
                                'conflict' => false
                            ];
                        }
                    }
                }
            }
        
            foreach ($result as $value) {
                $data = json_decode($value->data, true);
                if (!empty($data)) {
                    foreach ($data as $dkey => $dvalue) {
                        $unique_key = "{$dvalue['transaction_type']}_{$dvalue['transaction_table_id']}_{$dvalue['brand_name']}_{$dvalue['qty']}";
        
                        // Check if there's a conflict for this entry
                        if (isset($conflicting_entries[$unique_key]) && $conflicting_entries[$unique_key]['conflict']) {
                            continue; // Skip this entry due to conflict
                        }
        
                        $categoryName = $dvalue['category_name'];
                        $brandId = $dvalue['brand_id'];
        
                        // Initialize brand data if it doesn't exist
                        if (!isset($brandAggregatedData[$categoryName][$brandId])) {
                            $brandAggregatedData[$categoryName][$brandId] = [
                                'brand_name' => $dvalue['brand_name'],
                                'btl_size' => $dvalue['btl_size'],
                                'peg_size' => $dvalue['peg_size'],
                                'opening_balance' => isset($openingData[$brandId]) ? $openingData[$brandId] : 0,
                                'purchase' => 0,
                                'transfer' => 0,
                                'total' => isset($openingData[$brandId]) ? $openingData[$brandId] : 0,
                                'sales' => 0,
                                'closing_balance' => isset($openingData[$brandId]) ? $openingData[$brandId] : 0,
                            ];
                        }
        
                        // Calculate Opening
                        if(isset($openingData[$brandId])){
                            $brandAggregatedData[$categoryName][$brandId]['opening_balance'] = $openingData[$brandId];
                        }else{
                            $brandAggregatedData[$categoryName][$brandId]['opening_balance'] = "0.00";
                        }
        
                        // Calculate Purchase
                        if ($dvalue['transaction_type'] == 'purchase' || $dvalue['transaction_type'] == 'opening') {
                            if ($dvalue['transaction_category'] == 'credit') {
                                $brandAggregatedData[$categoryName][$brandId]['purchase'] += (int)$dvalue['qty'];
                            } elseif ($dvalue['transaction_category'] == 'debit') {
                                $brandAggregatedData[$categoryName][$brandId]['purchase'] -= (int)$dvalue['qty'];
                            }
                        }
        
                        // Calculate Transfer
                        if ($dvalue['transaction_type'] == 'transfer') {
                            if ($dvalue['transaction_category'] == 'credit') {
                                $brandAggregatedData[$categoryName][$brandId]['transfer'] += (int)$dvalue['qty'];
                            } elseif ($dvalue['transaction_category'] == 'debit') {
                                $brandAggregatedData[$categoryName][$brandId]['transfer'] -= (int)$dvalue['qty'];
                            }
                        }
        
                        // Calculate Sales
                        if ($dvalue['transaction_type'] == 'sales') {
                            if ($dvalue['transaction_category'] == 'debit') {
                                $brandAggregatedData[$categoryName][$brandId]['sales'] += (int)$dvalue['qty'];
                            } elseif ($dvalue['transaction_category'] == 'credit') {
                                $brandAggregatedData[$categoryName][$brandId]['sales'] -= (int)$dvalue['qty'];
                            }
                        }
        
                        // Update total and closing balance based on the aggregated values
                        $brandAggregatedData[$categoryName][$brandId]['total'] = $brandAggregatedData[$categoryName][$brandId]['opening_balance'] +
                            $brandAggregatedData[$categoryName][$brandId]['purchase'] +
                            $brandAggregatedData[$categoryName][$brandId]['transfer'];
        
                        $brandAggregatedData[$categoryName][$brandId]['closing_balance'] = $brandAggregatedData[$categoryName][$brandId]['total'] - $brandAggregatedData[$categoryName][$brandId]['sales'];
                    }
                }
            }
        }else{
            foreach($openingData as $key => $value)
            {
                $brand_details = DB::table('brands')->where('id', $key)->select('category_id', 'id','name','btl_size', 'peg_size')->first();

                $catDetails = DB::table('categories')->where('id', $brand_details->category_id)->select('name')->first();

                $brandAggregatedData[$catDetails->name][$key] = [
                    'brand_name' => $brand_details->name,
                    'btl_size' => $brand_details->btl_size,
                    'peg_size' => $brand_details->peg_size,
                    'opening_balance' => isset($value) ? $value : 0,
                    'purchase' => 0,
                    'transfer' => 0,
                    'total' => isset($value) ? $value : 0,
                    'sales' => 0,
                    'closing_balance' => isset($value) ? $value : 0,
                ];
            }
        }
        
        // Ensure all brands in openingData are added to brandAggregatedData if missing
        $brandIds = array_keys($openingData);
        $brands = DB::table('brands')
                    ->whereIn('id', $brandIds)
                    ->select('id', 'name', 'category_id', 'btl_size', 'peg_size')
                    ->get()
                    ->keyBy('id');
        
        $categoryIds = $brands->pluck('category_id')->unique();
        $categories = DB::table('categories')
                        ->whereIn('id', $categoryIds)
                        ->select('id', 'name')
                        ->get()
                        ->keyBy('id');
        
        foreach ($openingData as $brandId => $qty) {
            $categoryName = isset($brands[$brandId]) && isset($categories[$brands[$brandId]->category_id])
                ? $categories[$brands[$brandId]->category_id]->name 
                : 'Unknown Category';
                
            if (!isset($brandAggregatedData[$categoryName][$brandId])) {
                if (isset($brands[$brandId])) {
                    $brand = $brands[$brandId];
                    
                    // Add brand to brandAggregatedData with the required structure
                    $brandAggregatedData[$categoryName][$brandId] = [
                        'brand_name' => $brand->name,
                        'btl_size' => $brand->btl_size,
                        'peg_size' => $brand->peg_size,
                        'opening_balance' => $qty ?? 0,
                        'purchase' => 0,
                        'transfer' => 0,
                        'total' => $qty ?? 0,
                        'sales' => 0,
                        'closing_balance' => $qty ?? 0,
                    ];
                }
            }
        }
        

        $all_categories = DB::table('categories')
                        ->select('id', 'name')
                        ->get();

        $categoryOrder = $all_categories->pluck('name')->all();

        // Sort the $brandAggregatedData based on the order of categories in $categoryOrder
        uksort($brandAggregatedData, function($a, $b) use ($categoryOrder) {
            $posA = array_search($a, $categoryOrder);
            $posB = array_search($b, $categoryOrder);
            return $posA <=> $posB;
        });
        // Finally, push aggregated data to the JSON array
        foreach ($brandAggregatedData as $category => $brands) {
            foreach ($brands as $brandId => $data) {

                $json[] = [
                    'category_name' => $category,
                    'brand_name' => $data['brand_name'],
                    'btl_size' => $data['btl_size'],
                    'peg_size' => $data['peg_size'],
                    'opening_balance' => $data['opening_balance'],
                    'purchase' => $data['purchase'],
                    'transfer' => $data['transfer'],
                    'total' => $data['total'],
                    'sales' => $data['sales'],
                    'closing_balance' => $data['closing_balance'],
                ];
            }
        }
    }

    // public function StockRegisterReport(Request $request)
    // {
    //     $json = [];
    //     $company_id = $request->company_id;
    //     $categories = Category::where('status', 1)->select('id', 'name')->get();
    //     $cat_array = array();

    //     foreach ($categories as $category) {
    //         $name = $category->name;
    //         $id = $category->id;
    //         $categoryData = [];

    //         $stockData = DB::table('daily_opening_closing_log')
    //                         ->where('category_id', $id)
    //                         ->where('company_id', $company_id)
    //                         // ->whereDate('log_date', '>=', $request->from_date)
    //                         ->whereDate('log_date', '<=', $request->to_date)
    //                         ->groupBy('brand_id')
    //                         ->join('brands','daily_opening_closing_log.brand_id', 'brands.id')
    //                         ->join('categories','brands.category_id', 'categories.id')
    //                         ->get();

    //         foreach ($stockData as $stock) {
    //             $brandId = $stock->brand_id;
    //             $brandDetails = DB::table('brands')->where('id', $brandId)->first();

    //             if ($brandDetails) {
    //                 $btl_size = $brandDetails->btl_size;

    //                 // Initialize or increment values for each bottle size
    //                 if (!isset($categoryData[$btl_size])) {
    //                     $categoryData[$btl_size] = [
    //                         'category_name' => $name,
    //                         'brands' => [],
    //                         'opening_balance' => 0,
    //                         'purchase' => 0,
    //                         'transfer' => 0,
    //                         'total' => 0,
    //                         'sales' => 0,
    //                         'closing_balance' => 0,
    //                     ];
    //                 }

    //                 $allCreditQty = 0;
    //                 $allDebitQty = 0;
    //                 $opening_qty = 0;
    //                 $allCreditQty = DB::table('daily_opening_closing_log')
    //                                 ->where('transaction_type', 'credit')
    //                                 ->where('company_id', $company_id)
    //                                 ->where('brand_id', $brandId)
    //                                 ->whereDate('log_date', '<', date('Y-m-d', strtotime($request->from_date)))
    //                                 ->sum('qty');

    //                 $allDebitQty = DB::table('daily_opening_closing_log')
    //                                 ->where('transaction_type', 'debit')
    //                                 ->where('company_id', $company_id)
    //                                 ->where('brand_id', $brandId)
    //                                 ->whereDate('log_date', '<', date('Y-m-d', strtotime($request->from_date)))
    //                                 ->sum('qty');

                                            
    //                 $opening_qty = (int)$allCreditQty - (int)$allDebitQty;
    //                 $purchase_qty = $this->getPurchaseQty($company_id, $brandId, $request->from_date, $request->to_date);
    //                 $sales_qty = $this->getSalesQty($company_id, $brandId, $request->from_date, $request->to_date);

    //                 // transfer received qty
    //                 $transfer_received_qty = DB::table('daily_opening_closing_log')
    //                                             ->where('transaction_type', 'credit')
    //                                             ->where('transaction_category', 'transfer')
    //                                             ->where('company_id', $company_id)
    //                                             ->where('brand_id', $brandId)
    //                                             ->whereDate('log_date', '>=', date('Y-m-d', strtotime($request->from_date)))
    //                                             ->whereDate('log_date', '<=', date('Y-m-d', strtotime($request->to_date)))
    //                                             ->where('status', 'active')
    //                                             ->sum('qty');

    //                 // transfer sent qty
    //                 $transfer_sent_qty = DB::table('daily_opening_closing_log')
    //                                             ->where('transaction_type', 'debit')
    //                                             ->where('transaction_category', 'transfer')
    //                                             ->where('company_id', $company_id)
    //                                             ->where('brand_id', $brandId)
    //                                             ->whereDate('log_date', '>=', date('Y-m-d', strtotime($request->from_date)))
    //                                             ->whereDate('log_date', '<=', date('Y-m-d', strtotime($request->to_date)))
    //                                             ->where('status', 'active')
    //                                             ->sum('qty');

    //                 if($transfer_received_qty >= $transfer_sent_qty){
    //                     $transaction_total_qty = $transfer_received_qty - $transfer_sent_qty;
    //                 }else{
    //                     $transaction_total_qty = abs($transfer_received_qty - $transfer_sent_qty);
    //                 }
    //                 $transfer_received_stock = convertBtlPeg($transfer_received_qty, $btl_size, $brandDetails->peg_size);
    //                 $transfer_sent_stock = convertBtlPeg($transfer_sent_qty, $btl_size, $brandDetails->peg_size);
    //                 $totalTransferStock = convertBtlPeg($transaction_total_qty, $btl_size, $brandDetails->peg_size);
    //                 $opening_stock = convertBtlPeg($opening_qty, $btl_size, $brandDetails->peg_size);
    //                 $purchase_stock = convertBtlPeg($purchase_qty, $btl_size, $brandDetails->peg_size);
    //                 $sales_stock = convertBtlPeg($sales_qty, $btl_size, $brandDetails->peg_size);

    //                 $totalInMl = $opening_qty + $purchase_qty + $transfer_received_qty;
    //                 $closing_balance = $totalInMl - $sales_qty - $transfer_sent_qty;
    //                 $closing_balance_in_btl_peg = convertBtlPeg($closing_balance, $btl_size, $brandDetails->peg_size);
                    
    //                 $transferReceived = $transfer_received_stock['btl'] . "." . $transfer_received_stock['peg'];
    //                 $transferSent = $transfer_sent_stock['btl'] . "." . $transfer_sent_stock['peg'];
    //                 $totalTransfer = $totalTransferStock['btl'] . "." . $totalTransferStock['peg'];
    //                 $opening_balance = $opening_stock['btl'] . "." . $opening_stock['peg'];
    //                 $purchase = $purchase_stock['btl'] . "." . $purchase_stock['peg'];
    //                 $sales = $sales_stock['btl'] . "." . $sales_stock['peg'];
    //                 $final_closing_balance = $closing_balance_in_btl_peg['btl'] . "." . $closing_balance_in_btl_peg['peg'];

    //                 $total = floatval($opening_balance) + floatval($purchase) + floatval($transferReceived) - floatval($transferSent);
                    


    //                 // Update the brand details
    //                 $brand = [
    //                     'category_name' => '',
    //                     'brand_name' => $brandDetails->name,
    //                     'btl_size' => $btl_size,
    //                     'opening_balance' => $opening_balance,
    //                     'purchase' => $purchase,
    //                     'transfer' => $transfer_received_qty >= $transfer_sent_qty ? $totalTransfer : -$totalTransfer,
    //                     'total' => $total,
    //                     'sales' => $sales,
    //                     'closing_balance' => $final_closing_balance
    //                 ];

    //                 // Push brand to the array
    //                 $categoryData[$btl_size]['brands'][] = $brand;

    //                 // Update subtotals
    //                 $categoryData[$btl_size]['opening_balance'] += floatval($opening_balance);
    //                 $categoryData[$btl_size]['purchase'] += floatval($purchase);
    //                 if($transfer_received_qty >= $transfer_sent_qty){
    //                     $categoryData[$btl_size]['transfer'] += floatval($totalTransfer);
    //                 }else{
    //                     $categoryData[$btl_size]['transfer'] -= floatval($totalTransfer);
    //                 }
    //                 $categoryData[$btl_size]['total'] += floatval($total);
    //                 $categoryData[$btl_size]['sales'] += floatval($sales);
    //                 $categoryData[$btl_size]['closing_balance'] += floatval($final_closing_balance);
    //             }
    //         }

    //         $currentIndex = 0;

    //         // Add the subtotal for each bottle size
    //         foreach ($categoryData as $btl_size => $data) {
    //             $currentIndex++;
    //             // Push category row if not added
    //             if (!in_array($data['category_name'], $cat_array)) {
    //                 array_push($json, [
    //                     'category_name' => $data['category_name'],
    //                     'brand_name' => '',
    //                     'btl_size' => '',
    //                     'opening_balance' => '',
    //                     'purchase' => '',
    //                     'transfer' => '',
    //                     'total' => '',
    //                     'sales' => '',
    //                     'closing_balance' => ''
    //                 ]);
    //                 array_push($cat_array, $data['category_name']);
    //             }

    //             // Push all brand data
    //             foreach ($data['brands'] as $brand) {
    //                 array_push($json, $brand);
    //             }

    //             $category_id = DB::table('categories')->where('name', $data['category_name'])->select('id')->first();

    //             $peg_size = DB::table('brands')->where('category_id',$category_id->id)->select('peg_size')->first();

    //             if(!empty($peg_size)){

    //                 $salesCalculationInPeg = $data['sales'];
    //                 $transferCalculationInPeg = $data['transfer'];
    //                 $closingBalanceCalculationInPeg = $data['closing_balance'];

    //                 $sales_btl_and_peg = explode('.', $salesCalculationInPeg);
    //                 $transfer_btl_and_peg = explode('.', $transferCalculationInPeg);
    //                 $closing_btl_and_peg = explode('.', $closingBalanceCalculationInPeg);

    //                 $salesBtl = $sales_btl_and_peg[0];

    //                 if(!empty($sales_btl_and_peg[1])){
    //                     $salesPeg = $sales_btl_and_peg[1];
    //                 }else{
    //                     $salesPeg = 0;
    //                 }

    //                 $transferBtl = $transfer_btl_and_peg[0];

    //                 if(!empty($transfer_btl_and_peg[1])){
    //                     $transferPeg = $transfer_btl_and_peg[1];
    //                 }else{
    //                     $transferPeg = 0;
    //                 }

    //                 $closingBtl = $closing_btl_and_peg[0];

    //                 if(!empty($closing_btl_and_peg[1])){
    //                     $closingPeg = $closing_btl_and_peg[1];
    //                 }else{
    //                     $closingPeg = 0;
    //                 }

    //                 $totalSalesQtyInMl = $btl_size * $salesBtl + $peg_size->peg_size * $salesPeg;
    //                 $totalTransferQtyInMl = $btl_size * $transferBtl + $peg_size->peg_size * $transferPeg;
    //                 $totalClosingQtyInMl = $btl_size * $closingBtl + $peg_size->peg_size * $closingPeg;

    //                 $salesQtyInBtlPeg = convertBtlPeg($totalSalesQtyInMl, $btl_size, $peg_size->peg_size);
    //                 $transferQtyInBtlPeg = convertBtlPeg($totalTransferQtyInMl, $btl_size, $peg_size->peg_size);
    //                 $closingQtyInBtlPeg = convertBtlPeg($totalClosingQtyInMl, $btl_size, $peg_size->peg_size);
                    
    //                 $salesCalculationInPeg = $salesQtyInBtlPeg['btl'] . "." . $salesQtyInBtlPeg['peg'];
    //                 $transferCalculationInPeg = $transferQtyInBtlPeg['btl'] . "." . $transferQtyInBtlPeg['peg'];
    //                 $closingBalanceCalculationInPeg = $closingQtyInBtlPeg['btl'] . "." . $closingQtyInBtlPeg['peg'];
    //             }else{
    //                 $salesCalculationInPeg = $data['sales'];
    //                 $transferCalculationInPeg = $data['transfer'];
    //                 $closingBalanceCalculationInPeg = $data['closing_balance'];
    //             }
    //             // Push subtotal for current bottle size
    //             array_push($json, [
    //                 'category_name' => '',
    //                 'brand_name' => 'SUBTOTAL (' . $btl_size . 'ml)',
    //                 'btl_size' => $btl_size,
    //                 'opening_balance' => $data['opening_balance'],
    //                 'purchase' => $data['purchase'],
    //                 'transfer' => $transferCalculationInPeg,
    //                 'total' => $data['total'],
    //                 'sales' => $salesCalculationInPeg,
    //                 'closing_balance' => $closingBalanceCalculationInPeg,
    //             ]);

    //             $totalItems = count($categoryData);

    //             if($currentIndex !== $totalItems){
    //                 array_push($json, [
    //                     'category_name' => $data['category_name'],
    //                     'brand_name' => '',
    //                     'btl_size' => '',
    //                     'opening_balance' => '',
    //                     'purchase' => '',
    //                     'transfer' => '',
    //                     'total' => '',
    //                     'sales' => '',
    //                     'closing_balance' => ''
    //                 ]);
    //             }

    //         }
    //     }
    //     return json_encode($json);
    // }

    private function getPurchaseQty($company_id, $brandId, $from_date, $to_date)
    {
        return DB::table('purchases')
            ->select(DB::raw('SUM(COALESCE(qty, 0)) as qty'))
            ->where('brand_id', $brandId)
            ->where('company_id', $company_id)
            ->whereDate('invoice_date', '>=', date('Y-m-d', strtotime($from_date)))
            ->whereDate('invoice_date', '<=', date('Y-m-d', strtotime($to_date)))
            ->value('qty') ?: 0;
    }

    private function getSalesQty($company_id, $brandId, $from_date, $to_date)
    {
        return DB::table('sales')
            ->select(DB::raw('SUM(COALESCE(qty, 0)) as qty'))
            ->where('brand_id', $brandId)
            ->where('company_id', $company_id)
            ->whereDate('sale_date', '>=', date('Y-m-d', strtotime($from_date)))
            ->whereDate('sale_date', '<=', date('Y-m-d', strtotime($to_date)))
            ->value('qty') ?: 0;
    }


    public function SalesSummaryReport(Request $request)
    {
        $json = [];
        $data = [];

        $company_id = $request->company_id;
        $fromDate = $request->from_date;
        $toDate = $request->to_date;

        // Fetch all active categories
        $categories = Category::where(['status' => 1])->get();

        // Collect all unique category names
        $allCategories = $categories->pluck('name', 'id')->toArray();
        
        if (!empty($categories)) {
            foreach ($categories as $key => $value) {
                // Fetch sales data based on category and date range
                $sales = DB::table('sales')
                            ->select(
                                'sales.id',
                                'sales.category_id',
                                'categories.name',
                                'sales.brand_id',
                                'sales.sale_date',
                                'sales.qty'
                            )
                            ->join('categories', 'categories.id', '=', 'sales.category_id')
                            ->where('sales.company_id', $company_id)
                            ->where('sales.status', 1)
                            ->where('categories.status', 1)
                            ->where('sales.category_id', $value->id)
                            ->whereDate('sales.sale_date', '>=', $fromDate)
                            ->whereDate('sales.sale_date', '<=', $toDate)
                            ->get();

                foreach ($sales as $sale) {
                    if (!empty($sale)) {
                        // Fetch the selling price for bottle and peg
                        $peg_and_btl_selling_price = DB::table('stocks')
                                                        ->where('company_id', $company_id)
                                                        ->where('brand_id', $sale->brand_id)
                                                        ->where('category_id', $value->id)
                                                        ->select('btl_selling_price', 'peg_selling_price')
                                                        ->orderBy('id', 'desc')
                                                        ->first();

                        if (!empty($peg_and_btl_selling_price)) {
                            // Fetch bottle and peg size to calculate total price
                            $btl_peg_size = DB::table('brands')->where('id', $sale->brand_id)->select('btl_size', 'peg_size')->first();

                            if (!empty($btl_peg_size)) {
                                $stockInBtlPeg = convertBtlPeg((int)$sale->qty, $btl_peg_size->btl_size, $btl_peg_size->peg_size);
                                $sale_price = intval($peg_and_btl_selling_price->btl_selling_price) * intval($stockInBtlPeg['btl']) 
                                            + intval($peg_and_btl_selling_price->peg_selling_price) * intval($stockInBtlPeg['peg']);
                            }
                        }

                        // Accumulate data for each sale date and category
                        $category_name = $sale->name;
                        $sale_date = $sale->sale_date;

                        if (!isset($data[$sale_date])) {
                            $data[$sale_date] = [];
                        }

                        if (!isset($data[$sale_date][$category_name])) {
                            $data[$sale_date][$category_name] = 0;
                        }

                        // Sum the sale prices for each date/category
                        $data[$sale_date][$category_name] += $sale_price;
                    }
                }
            }
        }

        // Ensure all dates have all categories with 0 if not present
        foreach ($data as $sale_date => $values) {
            foreach ($allCategories as $category_name) {
                if (!isset($data[$sale_date][$category_name])) {
                    $data[$sale_date][$category_name] = 0;
                }
            }
        }

        // Remove dates with total sales of 0 across all categories
        $data = array_filter($data, function ($values) {
            return array_sum($values) > 0;
        });

        // Calculate totals
        $categoryTotals = array_fill_keys(array_values($allCategories), 0);

        foreach ($data as $sale_date => $values) {
            // Sort values to maintain category order
            ksort($values);

            // Track non-zero category totals
            foreach ($values as $category_name => $sale_price) {
                if ($sale_price > 0) {
                    $categoryTotals[$category_name] += $sale_price;
                }
            }
        }

        // Filter categories with zero sales across all dates
        $categoryTotals = array_filter($categoryTotals, function ($total) {
            return $total > 0;
        });
        
        // Prepare final data for response
        $finalData = [];
        foreach ($data as $sale_date => $values) {
            $filteredValues = array_intersect_key($values, $categoryTotals);

            if (!empty($filteredValues)) {
                $filteredValues = array_map(function ($value) {
                    return $value > 0 ? number_format($value, 2, '.', '') : '';
                }, $filteredValues);

                $entry = ['' => $sale_date] + $filteredValues;
                $entry['Total'] = number_format(array_sum(array_map('intval', $filteredValues)), 2, '.', '');
                $finalData[] = $entry;
            }
        }
        
        return response()->json($finalData);
    }





    public function AbstractReport(Request $request)
    {
        $json = [];
        $data = [];
        $from_date = $request->from_date;
        $to_date = $request->to_date;
        $company_id = $request->company_id;

        $btlSizes = Brand::distinct()
            ->orderBy('btl_size', 'DESC')
            ->pluck('btl_size')
            ->toArray();

        $categories = Category::where(['status' => 1])->get();
        foreach ($categories as $key => $category) {
            $btls = Brand::where(['category_id' => $category->id])
                ->orderBy('btl_size', 'DESC')
                ->groupBy(DB::raw("btl_size"))
                ->get();

            foreach ($btls as $key2 => $btl_size) {
                $brands = DB::table('brands')
                        ->join('purchases', 'purchases.brand_id', '=', 'brands.id')
                        ->join('categories', 'categories.id', '=', 'purchases.category_id')
                        ->select('brands.*', 'purchases.invoice_no', 'purchases.invoice_date', 'purchases.no_btl')
                        ->where('categories.status', 1)
                        ->where('purchases.status', 1)
                        ->where('brands.status', 1)
                        ->where('purchases.category_id', '=', $category->id)
                        ->where('purchases.company_id', $company_id)
                        ->where('brands.btl_size', '=', $btl_size->btl_size)
                        ->whereDate('purchases.invoice_date', '>=', $from_date)
                        ->whereDate('purchases.invoice_date', '<=', $to_date)
                        ->where('categories.status', 1)
                        ->orderBy('brands.btl_size', 'DESC')
                        ->get();

                foreach ($brands as $key3 => $brand) {
                    $btl_size = $brand->btl_size;
                    $no_btl  = $brand->no_btl;
                    $invoice_no = $brand->invoice_no;

                    if (!isset($data[$invoice_no])) {
                        $data[$invoice_no] = [];
                    }

                    foreach ($categories as $cat) {
                        foreach ($btlSizes as $size) {
                            if (!isset($data[$invoice_no][$cat->short_name . '-' . $size])) {
                                $data[$invoice_no][$cat->short_name . '-' . $size] = 0;
                            }
                        }
                    }

                    $data[$invoice_no][$category->short_name . '-' . $btl_size] += $no_btl;
                }
            }
        }

        // Filter out btl_size categories that have all 0 values across invoices
        $filteredData = [];
        foreach ($data as $invoice_no => $values) {
            foreach ($values as $key => $value) {
                if (!isset($filteredData[$key])) {
                    $filteredData[$key] = 0;
                }
                $filteredData[$key] += $value;
            }
        }

        foreach ($data as $invoice_no => $values) {
            foreach ($values as $key => $value) {
                if ($filteredData[$key] === 0) {
                    unset($values[$key]); // Remove this btl_size for this invoice_no if it's all zero
                }
            }

            // Check if the row has at least one value greater than 0
            $hasNonZeroValue = false;
            foreach ($values as $key => $value) {
                if ($value > 0) {
                    $hasNonZeroValue = true;
                    break;
                }
            }

            // If the row has any non-zero values, add it to $json
            if ($hasNonZeroValue) {
                // Replace 0 quantities with a blank string
                $entry = ['TP No.' => $invoice_no];
                foreach ($values as $key => $value) {
                    $entry[$key] = $value === 0 ? '' : $value; // Replace 0 with an empty string
                }
                array_push($json, $entry);
            }
        }

        $total = [];
        foreach ($json as $invoice) {
            foreach ($invoice as $key => $value) {
                if ($key !== 'TP No.') {
                    if (!isset($total[$key])) {
                        $total[$key] = 0;
                    }
                    $total[$key] += (int)$value;
                }
            }
        }

        // Replace 0 quantities in the total row with blank string
        $totalEntry = ['TP No.' => 'Total'];
        foreach ($total as $key => $value) {
            $totalEntry[$key] = $value === 0 ? '' : $value; // Replace 0 with an empty string
        }
        array_push($json, $totalEntry);

        return response()->json($json);
    }


    
    public function MonthlyReport(Request $request)
    {
        $json = [];
        $fromDate = $request->from_date;
        $toDate = $request->to_date;
        $company_id = $request->company_id;

        // Get all active categories
        $categories = Category::where('status', 1)->get();

        // Get brands data for all categories in one go
        $brands = Brand::whereIn('category_id', $categories->pluck('id'))
            ->select('id', 'category_id', 'btl_size', 'peg_size')
            ->orderBy('btl_size', 'DESC')
            ->get()
            ->groupBy('category_id');

        // Fetch purchase data for the date range
        $purchasesData = Purchase::where('company_id', $company_id)
            ->whereBetween('invoice_date', [$fromDate, $toDate])
            ->where('status', 1)
            ->select('brand_id', DB::raw('SUM(qty) as qty'))
            ->groupBy('brand_id')
            ->get()
            ->keyBy('brand_id');

        $from_date = $request->from_date;
        $to_date = $request->to_date;

        $from_date_table_year = date('Y', strtotime($from_date));
        $from_date_table_month = date('m', strtotime($from_date));

        $to_date_table_year = date('Y', strtotime($to_date));
        $to_date_table_month = date('m', strtotime($to_date));

        if ($from_date_table_year === $to_date_table_year) {
            // Both years are the same
            if ($from_date_table_month === $to_date_table_month) {
                // If months are the same, keep your original logic
                $table_name = $from_date_table_year . '_' . $from_date_table_month . '_' . 'log_data';

                
                $transferData = DB::table($table_name)
                                ->where('company_id', $company_id)
                                ->whereBetween('log_date', [$fromDate, $toDate])
                                ->select('log_date', 'data')
                                ->get();

                $transferIn = [];
                $transferOut = [];

                if (!empty($transferData)) {
                    foreach ($transferData as $tVal) {
                        $decodedData = json_decode($tVal->data, true);  // Decode JSON once

                        if (!empty($decodedData)) {
                            foreach ($decodedData as $t_d_val) {
                                $brandId = $t_d_val['brand_id'];
                                $qty = $t_d_val['qty'];

                                // Handle Transfer In (credit)
                                if ($t_d_val['transaction_category'] === 'credit' && $t_d_val['transaction_type'] === 'transfer') {
                                    if (isset($transferIn[$brandId])) {
                                        $transferIn[$brandId]['qty'] += $qty;
                                    } else {
                                        $transferIn[$brandId] = [
                                            'brand_id' => $brandId,
                                            'log_date' => $tVal->log_date,
                                            'qty' => $qty
                                        ];
                                    }
                                }

                                // Handle Transfer Out (debit)
                                if ($t_d_val['transaction_category'] === 'debit' && $t_d_val['transaction_type'] === 'transfer') {
                                    if (isset($transferOut[$brandId])) {
                                        $transferOut[$brandId]['qty'] += $qty;
                                    } else {
                                        $transferOut[$brandId] = [
                                            'brand_id' => $brandId,
                                            'log_date' => $tVal->log_date,
                                            'qty' => $qty
                                        ];
                                    }
                                }
                            }
                        }
                    }
                }

                // Convert associative arrays to standard arrays (optional)
                $transferIn = array_values($transferIn);
                $transferOut = array_values($transferOut);

            } else {
                $transferIn = [];  // To store 'credit' (Transfer In) results
                $transferOut = []; // To store 'debit' (Transfer Out) results

                // Loop through each month's table
                for ($month = (int)$from_date_table_month; $month <= (int)$to_date_table_month; $month++) {
                    $month_str = str_pad($month, 2, '0', STR_PAD_LEFT); // format the month to 2 digits
                    $table_name = $from_date_table_year . '_' . $month_str . '_' . 'log_data';

                    // Fetch data for both 'credit' (Transfer In) and 'debit' (Transfer Out) at once
                    $transferData = DB::table($table_name)
                        ->where('company_id', $company_id)
                        ->whereBetween('log_date', [$fromDate, $toDate])
                        ->select('log_date', 'data')  // Select necessary fields
                        ->get();

                    // Process data
                    if (!empty($transferData)) {
                        foreach ($transferData as $tVal) {
                            $decodedData = json_decode($tVal->data, true);  // Decode JSON once

                            if (!empty($decodedData)) {
                                foreach ($decodedData as $t_d_val) {
                                    $brandId = $t_d_val['brand_id'];

                                    // Process Transfer In (credit)
                                    if ($t_d_val['transaction_category'] === 'credit' && $t_d_val['transaction_type'] === 'transfer') {
                                        if (isset($transferIn[$brandId])) {
                                            $transferIn[$brandId]['qty'] += $t_d_val['qty'];  // Sum quantity for the same brand
                                        } else {
                                            $transferIn[$brandId] = [
                                                'brand_id' => $brandId,
                                                'log_date' => $tVal->log_date,
                                                'qty' => $t_d_val['qty']
                                            ];
                                        }
                                    }

                                    // Process Transfer Out (debit)
                                    if ($t_d_val['transaction_category'] === 'debit' && $t_d_val['transaction_type'] === 'transfer') {
                                        if (isset($transferOut[$brandId])) {
                                            $transferOut[$brandId]['qty'] += $t_d_val['qty'];  // Sum quantity for the same brand
                                        } else {
                                            $transferOut[$brandId] = [
                                                'brand_id' => $brandId,
                                                'log_date' => $tVal->log_date,
                                                'qty' => $t_d_val['qty']
                                            ];
                                        }
                                    }
                                }
                            }
                        }
                    }
                }

                // Convert the associative arrays back to standard collections if needed
                $transferIn = collect(array_values($transferIn));  // Optional: Collect transferIn as a standard Laravel collection
                $transferOut = collect(array_values($transferOut));  // Optional: Collect transferOut as a standard Laravel collection


                // Now $transferInCombined holds the combined "Transfer In" data (credit), and
                // $transferOutCombined holds the combined "Transfer Out" data (debit)

            }
        } else {

            
            $transferIn = [];  // To store 'credit' (Transfer In) results
            $transferOut = []; // To store 'debit' (Transfer Out) results

            // Years are different
            for ($year = (int)$from_date_table_year; $year <= (int)$to_date_table_year; $year++) {
                $start_month = ($year === (int)$from_date_table_year) ? (int)$from_date_table_month : 1;
                $end_month = ($year === (int)$to_date_table_year) ? (int)$to_date_table_month : 12;

                for ($month = $start_month; $month <= $end_month; $month++) {
                    $month_str = str_pad($month, 2, '0', STR_PAD_LEFT); // format the month to 2 digits
                    $table_name = $year . '_' . $month_str . '_' . 'log_data';

                    // Fetch data for both 'credit' (Transfer In) and 'debit' (Transfer Out) at once
                    $transferData = DB::table($table_name)
                        ->where('company_id', $company_id)
                        ->whereBetween('log_date', [$fromDate, $toDate])
                        ->select('log_date', 'data')  // Select necessary fields
                        ->get();

                    // Process data
                    if (!empty($transferData)) {
                        foreach ($transferData as $tVal) {
                            $decodedData = json_decode($tVal->data, true);  // Decode JSON once

                            if (!empty($decodedData)) {
                                foreach ($decodedData as $t_d_val) {
                                    $brandId = $t_d_val['brand_id'];

                                    // Process Transfer In (credit)
                                    if ($t_d_val['transaction_category'] === 'credit' && $t_d_val['transaction_type'] === 'transfer') {
                                        if (isset($transferIn[$brandId])) {
                                            $transferIn[$brandId]['qty'] += $t_d_val['qty'];  // Sum quantity for the same brand
                                        } else {
                                            $transferIn[$brandId] = [
                                                'brand_id' => $brandId,
                                                'log_date' => $tVal->log_date,
                                                'qty' => $t_d_val['qty']
                                            ];
                                        }
                                    }

                                    // Process Transfer Out (debit)
                                    if ($t_d_val['transaction_category'] === 'debit' && $t_d_val['transaction_type'] === 'transfer') {
                                        if (isset($transferOut[$brandId])) {
                                            $transferOut[$brandId]['qty'] += $t_d_val['qty'];  // Sum quantity for the same brand
                                        } else {
                                            $transferOut[$brandId] = [
                                                'brand_id' => $brandId,
                                                'log_date' => $tVal->log_date,
                                                'qty' => $t_d_val['qty']
                                            ];
                                        }
                                    }
                                }
                            }
                        }
                    }
                }
            }

            // Convert the associative arrays back to standard collections if needed
            $transferIn = collect(array_values($transferIn));  // Optional: Collect transferIn as a standard Laravel collection
            $transferOut = collect(array_values($transferOut));  // Optional: Collect transferOut as a standard Laravel collection
        }

        // Fetch sales data for the date range
        $salesData = Sales::where('company_id', $company_id)
            ->whereBetween('sale_date', [$fromDate, $toDate])
            ->where('status', 1)
            ->select('brand_id', DB::raw('SUM(qty) as qty'))
            ->groupBy('brand_id')
            ->get()
            ->keyBy('brand_id');

        $data = [
            'opening' => ['Opening'],
            'purchase' => ['Purchase'],
            'total' => ['Total'],
            'sale' => ['Sales'],
            'closing' => ['Closing'],
        ];

        foreach ($categories as $category) {
            if (!isset($brands[$category->id])) {
                continue;
            }
            $categoryBrands = $brands[$category->id];
            $brandIds = $categoryBrands->pluck('id');
            $btlSizes = $categoryBrands->pluck('btl_size')->unique();

            $all_tables = $this->getAllLogDataTables();

            $opening_qty_data = [];
            foreach ($all_tables as $tkey => $tval) {
                // Fetch relevant data from each table
                $opening_qty_data_per_table = DB::table($tval)
                    ->select('data')
                    ->where('company_id', $company_id)
                    ->whereDate('log_date', '<', $from_date)
                    ->get()
                    ->toArray();

                array_push($opening_qty_data, $opening_qty_data_per_table);
            }

            $opening_qty_map = [];

            // Process each row of data
            foreach ($opening_qty_data as $opening_qty_entry) {
                foreach ($opening_qty_entry as $qty_key => $qty_val) {
                    // Decode JSON from the 'data' column
                    $dataOpening = json_decode($qty_val->data, true);

                    if (!empty($dataOpening)) {
                        foreach ($dataOpening as $dvalue) {
                            if($dvalue['category_id'] == $category->id)
                            {
                                $brandId = $dvalue['brand_id'];
                                $qty = $dvalue['qty'];
    
                                // Initialize brand entry if not set
                                if (!isset($opening_qty_map[$brandId])) {
                                    $opening_qty_map[$brandId] = 0;
                                }
    
                                // Sum based on transaction_category (credit or debit)
                                if ($dvalue['transaction_category'] == 'credit') {
                                    $opening_qty_map[$brandId] += $qty;  // Add qty for 'credit'
                                } elseif ($dvalue['transaction_category'] == 'debit') {
                                    $opening_qty_map[$brandId] -= $qty;  // Subtract qty for 'debit'
                                }
                            }
                        }
                    }
                }
            }
            // Convert the map to a flat array with brand_id and total qty
            $openingLogs = [];
            foreach ($opening_qty_map as $brandId => $totalQty) {
                $openingLogs[] = [
                    'brand_id' => $brandId,
                    'qty' => $totalQty
                ];
            }

            foreach ($btlSizes as $btl_size) {
                $openSum = $purchaseSum = $saleSum = $closingSum = 0;

                foreach ($categoryBrands->where('btl_size', $btl_size) as $brand) {
                    $brandId = $brand->id;

                    // Calculate opening stock from the log table
                    $openingQty = array_sum(array_column(array_filter($openingLogs, function($log) use ($brandId) {
                        return $log['brand_id'] == $brandId; // Filter by brand_id
                    }), 'qty')); // Get the 'qty' field of the filtered logs
                    
                    $open = (int)$openingQty;
                    $openSum += $open;

                    // Get purchase data for the brand
                    $purchaseQty = isset($purchasesData[$brandId]) ? $purchasesData[$brandId]->qty : 0;
                    $purchaseSum += $purchaseQty;

                    $transferInQty = 0; // Default value

                    // Loop through the $transferOut array
                    foreach ($transferIn as $item) {
                        if ($item['brand_id'] === $brandId) {
                            $transferInQty = $item['qty'];
                            break; // Exit loop once the match is found
                        }
                    }
                    $purchaseSum += $transferInQty;

                    $total = $open + $purchaseQty;

                    // Get sales data for the brand
                    $saleQty = isset($salesData[$brandId]) ? $salesData[$brandId]->qty : 0;
                    $saleSum += $saleQty;

                    $transferOutQty = 0; // Default value

                    // Loop through the $transferOut array
                    foreach ($transferOut as $item) {
                        if ($item['brand_id'] === $brandId) {
                            $transferOutQty = $item['qty'];
                            break; // Exit loop once the match is found
                        }
                    }
                    $saleSum += $transferOutQty;
                    // Calculate closing stock
                    $closing = $total - $saleQty;
                    $closingSum += $closing;
                }

                // Skip if all sums are zero
                if ($openSum == 0 && $purchaseSum == 0 && $saleSum == 0 && $closingSum == 0) {
                    continue;
                }

                $peg_size = $categoryBrands->where('btl_size', $btl_size)->first()->peg_size;

                // Convert bottle and peg sizes
                $openSumFinal = $openSum ? convertBtlPeg(abs($openSum), $btl_size, $peg_size) : ['btl' => 0, 'peg' => 0];
                $purchaseSumFinal = $purchaseSum ? convertBtlPeg(abs($purchaseSum), $btl_size, $peg_size) : ['btl' => 0, 'peg' => 0];
                $totalSumFinal = ($openSum + $purchaseSum) ? convertBtlPeg(abs($openSum + $purchaseSum), $btl_size, $peg_size) : ['btl' => 0, 'peg' => 0];
                $saleSumFinal = $saleSum ? convertBtlPeg(abs($saleSum), $btl_size, $peg_size) : ['btl' => 0, 'peg' => 0];
                $closingSumFinal = $closingSum ? convertBtlPeg(abs($closingSum), $btl_size, $peg_size) : ['btl' => 0, 'peg' => 0];

                // Add the calculated data
                $data['opening'][$category->name . '-' . $btl_size] = $openSum >= 0 ? $openSumFinal['btl'] . '.' . $openSumFinal['peg'] :  -($openSumFinal['btl']) . '.' . $openSumFinal['peg'];
                $data['purchase'][$category->name . '-' . $btl_size] = $purchaseSumFinal >= 0 ? $purchaseSumFinal['btl'] . '.' . $purchaseSumFinal['peg'] : -($purchaseSumFinal['btl']) . '.' . $purchaseSumFinal['peg'];
                $data['total'][$category->name . '-' . $btl_size] = ($openSum + $purchaseSum) >= 0 ? $totalSumFinal['btl'] . '.' . $totalSumFinal['peg'] : -($totalSumFinal['btl']) . '.' . $totalSumFinal['peg'];
                $data['sale'][$category->name . '-' . $btl_size] = $saleSumFinal >= 0 ? $saleSumFinal['btl'] . '.' . $saleSumFinal['peg'] : -($saleSumFinal['btl']) . '.' . $saleSumFinal['peg'];
                $data['closing'][$category->name . '-' . $btl_size] = $closingSumFinal >= 0 ? $closingSumFinal['btl'] . '.' . $closingSumFinal['peg'] : -($closingSumFinal['btl']) . '.' . $closingSumFinal['peg'];
            }
        }
        // Prepare the final JSON response
        $json[] = $data['opening'];
        $json[] = $data['purchase'];
        $json[] = $data['total'];
        $json[] = $data['sale'];
        $json[] = $data['closing'];

        return response()->json($json);
    }

    public function DailyReport(Request $request)
    {
        $json = [];
        $fromDate = $request->from_date;
        $toDate = $request->to_date;
        $company_id = $request->company_id;

        // Get all active categories
        $categories = Category::where('status', 1)->get();

        // Get brands data for all categories in one go
        $brands = Brand::whereIn('category_id', $categories->pluck('id'))
            ->join('categories', 'categories.id', 'brands.category_id')
            ->select('brands.id', 'category_id', 'btl_size', 'categories.short_name as category_name')
            ->orderBy('btl_size', 'DESC')
            ->get()
            ->groupBy('category_id');

        $currentDate = $fromDate;

        // Skip dates with no entries in purchase or sales table
        while ($currentDate <= $toDate) {
            $hasEntries = DB::table('purchases')
                ->where('company_id', $company_id)
                ->where('status', 1)
                ->where('invoice_date', $currentDate)
                ->exists() || DB::table('sales')
                ->where('company_id', $company_id)
                ->where('status', 1)
                ->where('sale_date', $currentDate)
                ->exists();

            if ($hasEntries) {
                break;
            } else {
                $currentDate = date('Y-m-d', strtotime($currentDate . '+1 day'));
            }
        }

        // Fetch purchase and sales data for all dates in the range
        $purchasesData = Purchase::where('company_id', $company_id)
            ->whereBetween('invoice_date', [$fromDate, $toDate])
            ->select('brand_id', 'invoice_no', DB::raw('COALESCE(SUM(qty), 0) as qty'), 'invoice_date')
            ->groupBy('invoice_date', 'brand_id')
            ->get()
            ->groupBy('invoice_date');

        $transferIn = DB::table('daily_opening_closing_log')
                        ->where('company_id', $company_id)
                        ->whereBetween('log_date', [$fromDate, $toDate])
                        ->where('transaction_type','credit')
                        ->where('transaction_category','transfer')
                        ->select('brand_id', 'transaction_type', DB::raw('SUM(qty) as qty'),'log_date')
                        ->get()
                        ->groupBy('log_date');

        $transferOut = DB::table('daily_opening_closing_log')
                        ->where('company_id', $company_id)
                        ->whereBetween('log_date', [$fromDate, $toDate])
                        ->where('transaction_type','debit')
                        ->where('transaction_category','transfer')
                        ->select('brand_id', 'transaction_type', DB::raw('SUM(qty) as qty'),'log_date')
                        ->get()
                        ->groupBy('log_date');

        $salesData = Sales::where('company_id', $company_id)
            ->whereBetween('sale_date', [$fromDate, $toDate])
            ->where('status', 1)
            ->select('brand_id', DB::raw('COALESCE(SUM(qty), 0) as qty'), 'sale_date')
            ->groupBy('sale_date', 'brand_id')
            ->get()
            ->groupBy('sale_date');

        $previousClosing = [];

        $categoryBrands = [];

        while ($currentDate <= $toDate) {
            $data = [
                'opening' => [$currentDate => 'Opening'],
                'purchase' => [$currentDate => 'Purchase'],
                'total' => [$currentDate => 'Total'],
                'sale' => [$currentDate => 'Sales'],
                'closing' => [$currentDate => 'Closing'],
            ];

            $hasPurchaseOrSaleData = false;

            
            foreach ($categories as $category) {
                if (!isset($brands[$category->id])) {
                    continue;
                }

                $categoryBrands = $brands[$category->id];
                $brandIds = $categoryBrands->pluck('id');
                $btlSizes = $categoryBrands->pluck('btl_size')->unique();

                $all_tables = $this->getAllLogDataTables();

                $opening_qty_data = [];
                foreach($all_tables as $tkey => $tval){
                    $opening_qty_data_per_table = DB::table($tval)
                                                    ->select('data')
                                                    ->where('company_id', $company_id)
                                                    ->whereDate('log_date', '<', $fromDate)
                                                    ->get()
                                                    ->toArray();

                    array_push($opening_qty_data, $opening_qty_data_per_table);
                }

                $opening_qty_map = [];

                foreach ($opening_qty_data as $opening_qty_entry) {
                    foreach($opening_qty_entry as $qty_key => $qty_val){
                        $dataForStocks = json_decode($qty_val->data, true);
                
                        if (!empty($dataForStocks)) {
                            foreach ($dataForStocks as $dvalue) {
                                if($dvalue['category_id'] == $category->id)
                                {
                                    $brandId = $dvalue['brand_id'];
                                    $qty = $dvalue['qty'];
                    
                                    if (!isset($opening_qty_map[$brandId])) {
                                        $opening_qty_map[$brandId] = 0;
                                    }
                    
                                    // Sum credit and debit quantities
                                    if ($dvalue['transaction_category'] == 'credit') {
                                        $opening_qty_map[$brandId] += $qty;
                                    } elseif ($dvalue['transaction_category'] == 'debit') {
                                        $opening_qty_map[$brandId] -= $qty;
                                    }
                                }
                            }
                        }
                    }
                }

                foreach ($btlSizes as $btl_size) {
                    $openSum = $purchaseSum = $saleSum = $closingSum = 0;

                    foreach ($categoryBrands->where('btl_size', $btl_size) as $brand) {
                        $brandId = $brand->id;

                        // Calculate opening stock
                        $open = isset($opening_qty_map[$brandId]) ? $opening_qty_map[$brandId] : 0;

                        $openSum += $open;

                        // Get purchase and sale data for the brand
                        $purchaseQty = isset($purchasesData[$currentDate]) ? $purchasesData[$currentDate]->where('brand_id', $brandId)->sum('qty') : 0;
                        $purchaseSum += $purchaseQty;

                        $transferInQty = isset($transferIn[$currentDate]) ? $transferIn[$currentDate]->where('brand_id', $brandId)->sum('qty') : 0;
                        $purchaseSum += $transferInQty;
                        $total = $open + $purchaseQty;

                        $saleQty = isset($salesData[$currentDate]) ? $salesData[$currentDate]->where('brand_id', $brandId)->sum('qty') : 0;

                        $saleSum += $saleQty;

                        $transferOutQty = isset($transferOut[$currentDate]) ? $transferOut[$currentDate]->where('brand_id', $brandId)->sum('qty') : 0;

                        $saleSum += $transferOutQty;

                        // Calculate closing stock
                        $closing = $total - $saleQty;
                        $closingSum += $closing;
                    }

                    // Skip if all sums are zero
                    if ($openSum == 0 && $purchaseSum == 0 && $saleSum == 0 && $closingSum == 0) {
                        continue;
                    }

                    if(!empty($previousClosing))
                    {
                        foreach($previousClosing as $cat_key => $cat_val)
                        {
                            if($cat_key == $category->short_name)
                            {
                                foreach($cat_val as $btl_size_key => $btl_size_val)
                                {
                                    if($btl_size_key == $btl_size)
                                    {
                                        $openSum = ($btl_size_val['btl'] * $btl_size_val['btl_size']) + ($btl_size_val['peg'] * $btl_size_val['peg_size']);
                                    }
                                }
                            }
                        }
                    }else{
                        $openSum = $openSum;
                    }


                    $peg_size = DB::table('brands')->where('category_id', $category->id)->where('btl_size', $btl_size)->select('peg_size')->first();
                    $openSumFinal = $openSum ? convertBtlPeg(abs($openSum), $btl_size, $peg_size->peg_size) : ['btl' => 0, 'peg' => 0];
                    $purchaseSumFinal = $purchaseSum ? convertBtlPeg(abs($purchaseSum), $btl_size, $peg_size->peg_size) : ['btl' => 0, 'peg' => 0];
                    $totalSumFinal = convertBtlPeg(abs($openSum + $purchaseSum), $btl_size, $peg_size->peg_size);
                    $saleSumFinal = $saleSum ? convertBtlPeg(abs($saleSum), $btl_size, $peg_size->peg_size) : ['btl' => 0, 'peg' => 0];
                    $closingSumFinal = $closingSum ? convertBtlPeg(abs($openSum + $purchaseSum - $saleSum), $btl_size, $peg_size->peg_size) : ['btl' => 0, 'peg' => 0];
                    
                    // Add the calculated data
                    $data['opening'][$category->short_name . '-' . $btl_size] = $openSum >= 0 ? $openSumFinal['btl'] . '.' . $openSumFinal['peg'] : -($openSumFinal['btl']) . '.' . $openSumFinal['peg'];
                    $data['purchase'][$category->short_name . '-' . $btl_size] = $purchaseSum >= 0 ? $purchaseSumFinal['btl'] . '.' . $purchaseSumFinal['peg'] : -($purchaseSumFinal['btl']) . '.' . $purchaseSumFinal['peg'];
                    $data['total'][$category->short_name . '-' . $btl_size] = ($openSum + $purchaseSum) >= 0 ? $totalSumFinal['btl'] . '.' . $totalSumFinal['peg'] : -($totalSumFinal['btl']) . '.' . $totalSumFinal['peg'];
                    $data['sale'][$category->short_name . '-' . $btl_size] = $saleSum >= 0 ? $saleSumFinal['btl'] . '.' . $saleSumFinal['peg'] : -($saleSumFinal['btl']) . '.' . $saleSumFinal['peg'];
                    $data['closing'][$category->short_name . '-' . $btl_size] = ($openSum + $purchaseSum - $saleSum) >= 0 ? $closingSumFinal['btl'] . '.' . $closingSumFinal['peg'] : -($closingSumFinal['btl']) . '.' . $closingSumFinal['peg'];

                    $previousClosing[$category->short_name][$btl_size] = $closingSumFinal;

                    if ($purchaseSum || $saleSum) {
                        $hasPurchaseOrSaleData = true;
                    }
                }
            }

            if ($hasPurchaseOrSaleData) {
                $json[] = $data['opening'];
                $json[] = $data['purchase'];
                
                $tpNos = '';
                // Add TP No information
                $tpNos = isset($purchasesData[$currentDate]) ? $purchasesData[$currentDate]->pluck('invoice_no')->unique()->implode(', ') : '';
                $json[] = [$currentDate => 'TP No', 'TP No' => $tpNos ?: ''];
                
                $json[] = $data['total'];
                $json[] = $data['sale'];
                $json[] = $data['closing'];
            }

            // Move to the next date
            $currentDate = date('Y-m-d', strtotime($currentDate . '+1 day'));
        }
        
        $get_all_categories = [];
        $get_all_btl_size = [];

        // Extract categories and bottle sizes
        if (!empty($json)) {
            foreach ($json as $json_value) {
                foreach ($json_value as $cat_btl_key => $cat_btl_value) {
                    if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $cat_btl_key)) {
                        // Split the key and extract category and btl_size
                        $parts = explode('-', $cat_btl_key);
                        if (count($parts) === 2) {
                            $get_all_categories[$parts[0]] = true;
                            $get_all_btl_size[$parts[1]] = true;
                        }
                    }
                }
            }
        }

        // Convert keys to indexed arrays
        $get_all_categories = array_keys($get_all_categories);
        $get_all_btl_size = array_keys($get_all_btl_size);

        // Add missing keys to each $json[$json_key] value
        foreach ($json as &$json_value) {
            $array_keys = array_keys($json_value);
            foreach ($get_all_categories as $category) {
                foreach ($get_all_btl_size as $btl_size) {
                    $key_to_check = "{$category}-{$btl_size}";
                    // Only add if $key_to_check and 'TP No' are both absent
                    if (!in_array($key_to_check, $array_keys) && !in_array('TP No', $array_keys)) {
                        $json_value[$key_to_check] = "0.00";
                    }
                }
            }
        }
        unset($json_value); // Break reference to avoid unintended modifications

        // Track zero categories
        $zeroCategories = [];
        foreach ($json as $entry) {
            foreach ($entry as $category => $value) {
                if ($category !== array_keys($entry)[0] && $value === "0.00") {
                    $zeroCategories[$category] = ($zeroCategories[$category] ?? 0) + 1; // Count occurrences
                }
            }
        }

        // Remove categories with non-zero values
        foreach ($zeroCategories as $category => $count) {
            foreach ($json as $entry) {
                if (isset($entry[$category]) && $entry[$category] !== "0.00") {
                    unset($zeroCategories[$category]);
                    break;
                }
            }
        }

        // Step 2: Remove those categories from all entries
        foreach ($json as &$entry) {
            foreach (array_keys($zeroCategories) as $category) {
                unset($entry[$category]); // Remove category if it exists
            }
        }
        unset($entry);

        // Fetch bottle sizes and format the result
        $btlSizes = Brand::select('btl_size', 'categories.short_name')
            ->join('categories', 'brands.category_id', '=', 'categories.id')
            ->where('brands.status', 1)
            ->orderBy('categories.id', 'asc')
            ->orderBy('brands.btl_size', 'desc')
            ->distinct()
            ->get();

        $formattedResult = $btlSizes->map(function($item) {
            return $item->short_name . '-' . $item->btl_size;
        })->toArray();

        $final_json = [];
        if (!empty($json)) {
            foreach ($json as $json_value) {
                $sortedJsonValue = [];
                // Include the first date key if present
                foreach ($json_value as $key => $value) {
                    if (strtotime($key) !== false) {
                        $sortedJsonValue[$key] = $value;
                        break; // Add the first date found
                    }
                }
                
                // Add formatted result keys that exist in $json_value
                foreach ($formattedResult as $key) {
                    if (isset($json_value[$key])) {
                        $sortedJsonValue[$key] = $json_value[$key];
                    }
                }
                
                // Include other keys from $json_value that are not in $formattedResult
                foreach ($json_value as $key => $value) {
                    if (!isset($sortedJsonValue[$key])) {
                        $sortedJsonValue[$key] = $value;
                    }
                }

                $final_json[] = $sortedJsonValue; // Directly push to array
            }
        }

        $from_date = date('Y-m-d', strtotime($request->from_date));
        $to_date = date('Y-m-d', strtotime($request->to_date));

        // Collect all dates between from_date and to_date
        $get_array_keys = [];
        $all_dates = [];
        $currentDate = $from_date;

        while ($currentDate <= $to_date) {
            $all_dates[] = $currentDate;
            $get_array_keys[] = $currentDate;
            $currentDate = date('Y-m-d', strtotime($currentDate . ' +1 day'));
        }

        $previousClosing = [];

        // Get previous closing data
        $previous_closing_data = $final_json[0] ?? [];
        foreach ($previous_closing_data as $prev_key => $prev_val) {
            if (!strtotime($prev_key)) {
                $previousClosing[$prev_key] = $prev_val;
            }
        }

        $final_json_to_pass = [];
        foreach ($get_array_keys as $array_val) {
            $exists_in_final_json = false;
            $counter = 0;

            foreach ($final_json as $final_json_val) {

                if (isset($final_json_val[$array_val])) {
                    $exists_in_final_json = true;
                    // Check if the value at `$array_val` is 'Opening'
                    if ($final_json_val[$array_val] === 'Opening') {
                        $data = [$array_val => 'Opening'] + $previousClosing; // Merge arrays
                    } else {
                        $data = $final_json_val;
                    }

                    $final_json_to_pass[] = $data;

                    if ($final_json_val[$array_val] !== 'TP No') {
                        // Update `previousClosing` with non-date keys
                        foreach ($final_json_val as $prev_key => $prev_val) {
                            if (!strtotime($prev_key)) {
                                $previousClosing[$prev_key] = $prev_val;
                            }
                        }
                    }

                    if (++$counter == 6) {
                        break;
                    }
                }
            }

            if (!$exists_in_final_json) {
                // Create entries for Opening, Purchase, TP No, Total, Sales, and Closing
                $entries = [
                    [$array_val => "Opening"] + $previousClosing,
                    [$array_val => "Purchase"] + array_fill_keys(array_keys($previousClosing), "0.00"),
                    [$array_val => "TP No", "TP No" => ""],
                    [$array_val => "Total"] + $previousClosing,
                    [$array_val => "Sales"] + array_fill_keys(array_keys($previousClosing), "0.00"),
                    [$array_val => "Closing"] + $previousClosing,
                ];

                foreach ($entries as $data) {
                    $final_json_to_pass[] = $data;
                }
            }
        }

        return response()->json($final_json_to_pass);
    }

    // public function YearlyReport(Request $request)
    // {
    //     $json = [];
    //     $months = array();
    //     $company_id = $request->company_id;
    //     // Get the current year and month
    //     $currentDate = Carbon::now();

    //     // Determine the financial year start and end
    //     $financialYearStart = $currentDate->month >= 4 ? $currentDate->year : $currentDate->year - 1;
    //     $financialYearEnd = $financialYearStart + 1;

    //     // Financial year months (April to March)
    //     $months = ['04', '05', '06', '07', '08', '09', '10', '11', '12', '01', '02', '03'];

    //     // Create patterns for the tables you want to match
    //     $patterns = [];
    //     foreach ($months as $month) {
    //         $year = ($month >= '04') ? $financialYearStart : $financialYearEnd;
    //         $patterns[] = "{$year}_{$month}_%";
    //     }

    //     // Build the SQL query
    //     $query = "SHOW TABLES WHERE ";
    //     $queryParts = [];
    //     foreach ($patterns as $pattern) {
    //         $queryParts[] = "Tables_in_" . DB::getDatabaseName() . " LIKE '" . $pattern . "'";
    //     }
    //     $query .= implode(" OR ", $queryParts);

    //     // Execute the query
    //     $tables = DB::select($query);

    //     // Extract table names from the result
    //     $tableNames = array_map(function($table) {
    //         return array_values((array)$table)[0];
    //     }, $tables);


    //     if(!empty($tableNames)){
    //         foreach($tableNames as $tab)
    //     }
    //     foreach ($months as $month) {
    //         $newMonth = explode(' ', $month);
    //         $categories = Category::where('status', 1)->get();
    //         foreach ($categories as $category) {
    //             $btls = Brand::where(['category_id' => $category->id])->orderBy('btl_size', 'DESC')->groupBy(DB::raw("btl_size"))->get(); // get unique bottle size of that category
    //             foreach ($btls as $key2 => $btl_size) {
    //                 $brands = Brand::where(['category_id' => $category['id'], 'btl_size' => $btl_size['btl_size']])->get(); // get brand of that category
    //                 $openSum = 0;
    //                 $purchaseSum = 0;
    //                 $totalSum = 0;
    //                 $saleSum = 0;
    //                 $closingSum = 0;
    //                 foreach ($brands as $key => $brand) {
    //                     // opening section
    //                     [$opening] = DailyOpening::where(['brand_id' => $brand['id'], 'company_id' => $company_id, ['date', 'like', '%-' . $newMonth[0] . '-' . $newMonth[1]]])
    //                         ->select(DB::raw('SUM(COALESCE(qty, 0)) as qty'))
    //                         ->get();

    //                     if ($opening)
    //                         $open = $opening['qty'];
    //                     else
    //                         $open = 0;
    //                     $openSum = $openSum + $open;
    //                     //purchase section
    //                     [$purchase] = purchase::where(['brand_id' => $brand['id'], 'company_id' => $company_id, ['invoice_date', 'like', '%-' . $newMonth[0] . '-' . $newMonth[1]]])
    //                         ->select(DB::raw('SUM(COALESCE(qty, 0)) as qty'))
    //                         ->get();
    //                     if ($purchase)
    //                         $purchaseQty = $purchase['qty'];
    //                     else
    //                         $purchaseQty = 0;
    //                     $purchaseSum = $purchaseSum + $purchaseQty;
    //                     //total section
    //                     $total = $purchaseQty + $open;
    //                     if ($total)
    //                         $totalSum = $totalSum + $total;

    //                     // sales
    //                     [$sales] = Sales::where(['brand_id' => $brand['id'], 'company_id' => $company_id, ['sale_date', 'like', '%-' . $newMonth[0] . '-' . $newMonth[1]]])
    //                         ->select(DB::raw('SUM(COALESCE(qty, 0)) as qty'))
    //                         ->get();
    //                     if ($sales)
    //                         $saleQty = $sales['qty'];
    //                     else
    //                         $saleQty = 0;
    //                     $saleSum = $saleSum + $saleQty;

    //                     //total section
    //                     $closing = $total - $saleQty;
    //                     if ($total)
    //                         $closingSum = $closingSum + $closing;
    //                 }

    //                 $data[$month]['Title'] = $month;
    //                 $data[$month][$category['name'] . '-' . 'opening'] = $openSum / 1000;
    //                 $data[$month][$category['name'] . '-' . 'purchase'] = $purchaseSum / 1000;
    //                 $data[$month][$category['name'] . '-' . 'sale'] = $saleSum / 1000;
    //                 $data[$month][$category['name'] . '-' . 'closing'] = $closingSum / 1000;
    //             }
    //         }
    //         array_push($json, $data[$month]);
    //     }
    //     return response()->json($json);
    // }

    protected function getFinancialYearTables()
    {
        // Get the current year and month
        $currentDate = Carbon::now();

        // Determine the financial year start and end
        $financialYearStart = $currentDate->month >= 4 ? $currentDate->year : $currentDate->year - 1;
        $financialYearEnd = $financialYearStart + 1;

        // Financial year months (April to March)
        $months = ['04', '05', '06', '07', '08', '09', '10', '11', '12', '01', '02', '03'];

        // Create patterns for the tables you want to match
        $patterns = [];
        foreach ($months as $month) {
            $year = ($month >= '04') ? $financialYearStart : $financialYearEnd;
            $patterns[] = "{$year}_{$month}_%";
        }

        // Build the SQL query
        $query = "SHOW TABLES WHERE ";
        $queryParts = [];
        foreach ($patterns as $pattern) {
            $queryParts[] = "Tables_in_" . DB::getDatabaseName() . " LIKE '" . $pattern . "'";
        }
        $query .= implode(" OR ", $queryParts);

        // Execute the query
        $tables = DB::select($query);

        // Extract table names from the result
        $tableNames = array_map(function($table) {
            return array_values((array)$table)[0];
        }, $tables);

        return $tableNames;
    }

    protected function getLastFinancialYearTables()
    {
        // Get the current year and month
        $currentDate = Carbon::now();

        // Determine the financial year start and end
        $financialYearStart = $currentDate->month >= 4 ? $currentDate->year - 1 : $currentDate->year - 2;
        $financialYearEnd = $financialYearStart + 1;

        // Financial year months (April to March)
        $months = ['04', '05', '06', '07', '08', '09', '10', '11', '12', '01', '02', '03'];

        // Create patterns for the tables you want to match
        $patterns = [];
        foreach ($months as $month) {
            $year = ($month >= '04') ? $financialYearStart : $financialYearEnd;
            $patterns[] = "{$year}_{$month}_%";
        }

        // Build the SQL query
        $query = "SHOW TABLES WHERE ";
        $queryParts = [];
        foreach ($patterns as $pattern) {
            $queryParts[] = "Tables_in_" . DB::getDatabaseName() . " LIKE '" . $pattern . "'";
        }
        $query .= implode(" OR ", $queryParts);

        // Execute the query
        $tables = DB::select($query);

        // Extract table names from the result
        $tableNames = array_map(function($table) {
            return array_values((array)$table)[0];
        }, $tables);

        return $tableNames;
    }

    // public function YearlyReport(Request $request)
    // {
    //     $companyId = $request->company_id;
    //     $fromDate = $request->from_date; // Replace with $request->from_date if dynamic
    //     $toDate = $request->to_date;   // Replace with $request->to_date if dynamic

    //     // Get the financial year based on from_date
    //     $financialYearStart = date('Y', strtotime($fromDate));
    //     if (date('m', strtotime($fromDate)) < 4) {
    //         $financialYearStart -= 1; // Adjust if the month is before April
    //     }

    //     // Extract month and year for dynamic calculations
    //     $fromMonth = date('m', strtotime($fromDate));
    //     $fromYear = date('Y', strtotime($fromDate));
    //     $toMonth = date('m', strtotime($toDate));
    //     $toYear = date('Y', strtotime($toDate));

    //     // Get all categories
    //     $categories = DB::table('categories')->orderBy('id', 'asc')->get(['id', 'name']);

    //     $results = [];
    //     $tempResults = []; // Temporary storage for zero filtering

    //     // Loop through each month in the range
    //     for ($year = $fromYear; $year <= $toYear; $year++) {
    //         $startMonth = ($year === $fromYear) ? $fromMonth : 4; // April of the starting year
    //         $endMonth = ($year === $toYear) ? $toMonth : 12; // December of the ending year

    //         foreach ($categories as $category) {
    //             $categoryId = $category->id;
    //             $categoryName = $category->name;

    //             for ($month = $startMonth; $month <= $endMonth; $month++) {
    //                 // Format month
    //                 $formattedMonth = sprintf('%02d', $month);
    //                 // Determine the opening date range for the month
    //                 $openingDate = ($month === $fromMonth) ? date('Y-m-d', strtotime("$fromYear-$formattedMonth-01")) : date('Y-m-d', strtotime("$year-$formattedMonth-01"));

    //                 // Calculate Opening Stock: All credit - all debit before fromDate for this category
    //                 $openingStock = 0;
    //                 $openingStockTableNames = [];

    //                 // Get opening stock table names
    //                 for ($m = 4; $m <= 12; $m++) {
    //                     $formattedOpeningMonth = sprintf('%02d', $m);
    //                     if (Schema::hasTable("{$financialYearStart}_{$formattedOpeningMonth}_log_data")) {
    //                         $openingStockTableNames[] = "{$financialYearStart}_{$formattedOpeningMonth}_log_data";
    //                     }
    //                 }

    //                 $nextYear = $financialYearStart + 1;

    //                 for ($m = 1; $m <= 3; $m++) {
    //                     $formattedNextYearMonth = sprintf('%02d', $m);
    //                     if (Schema::hasTable("{$nextYear}_{$formattedNextYearMonth}_log_data")) {
    //                         $openingStockTableNames[] = "{$nextYear}_{$formattedNextYearMonth}_log_data";
    //                     }
    //                 }

    //                 // Iterate through each opening stock table to calculate quantities
    //                 foreach ($openingStockTableNames as $table) {
    //                     $openingData = DB::table($table)
    //                         ->where('company_id', $companyId)
    //                         ->where('log_date', '<', $fromDate)
    //                         ->get();

    //                     foreach ($openingData as $entry) {
    //                         $data = json_decode($entry->data, true);
    //                         foreach ($data as $item) {
    //                             if (isset($item['category_id'], $item['transaction_category'], $item['qty'])) {
    //                                 $transactionCategory = $item['transaction_category'];
    //                                 $qty = (int)$item['qty'] / 1000; // Convert ml to l

    //                                 // Adjust opening stock based on credit/debit for this category
    //                                 if ($item['category_id'] === $categoryId) {
    //                                     if ($transactionCategory === 'credit') {
    //                                         $openingStock += $qty;
    //                                     } elseif ($transactionCategory === 'debit') {
    //                                         $openingStock -= $qty;
    //                                     }
    //                                 }
    //                             }
    //                         }
    //                     }
    //                 }

    //                 // Calculate Purchases and Sales for the current month for this category
    //                 $purchases = 0;
    //                 $sales = 0;
    //                 $purchasesTableNames = $salesTableNames = $openingStockTableNames;

    //                 foreach ($purchasesTableNames as $table) {
    //                     if (DB::getSchemaBuilder()->hasTable($table)) {
    //                         $inventoryData = DB::table($table)
    //                             ->where('company_id', $companyId)
    //                             ->whereBetween('log_date', [$openingDate, $toDate])
    //                             ->get();

    //                         foreach ($inventoryData as $entry) {
    //                             $data = json_decode($entry->data, true);
    //                             foreach ($data as $item) {
    //                                 if (isset($item['category_id'], $item['transaction_category'], $item['transaction_type'], $item['qty'])) {
    //                                     $transactionCategory = $item['transaction_category'];
    //                                     $transactionType = $item['transaction_type'];
    //                                     $qty = (int)$item['qty'] / 1000; // Convert ml to l

    //                                     // Calculate purchases for this category
    //                                     if ($item['category_id'] === $categoryId && $transactionCategory === 'credit' && $transactionType === 'purchase' && date('Y-m', strtotime($entry->log_date)) === "$year-$formattedMonth") {
    //                                         $purchases += $qty;
    //                                     }

    //                                     if ($item['category_id'] === $categoryId && $transactionCategory === 'debit' && $transactionType === 'purchase' && date('Y-m', strtotime($entry->log_date)) === "$year-$formattedMonth") {
    //                                         $purchases -= $qty;
    //                                     }

    //                                     if ($item['category_id'] === $categoryId && $transactionCategory === 'credit' && $transactionType === 'opening' && date('Y-m', strtotime($entry->log_date)) === "$year-$formattedMonth") {
    //                                         $purchases += $qty;
    //                                     }

    //                                     if ($item['category_id'] === $categoryId && $transactionCategory === 'debit' && $transactionType === 'opening' && date('Y-m', strtotime($entry->log_date)) === "$year-$formattedMonth") {
    //                                         $purchases -= $qty;
    //                                     }

    //                                     if ($item['category_id'] === $categoryId && $transactionCategory === 'credit' && $transactionType === 'transfer' && date('Y-m', strtotime($entry->log_date)) === "$year-$formattedMonth") {
    //                                         $purchases += $qty;
    //                                     }

    //                                     // Calculate sales for this category
    //                                     if ($item['category_id'] === $categoryId && $transactionCategory === 'debit' && $transactionType === 'sales' && date('Y-m', strtotime($entry->log_date)) === "$year-$formattedMonth") {
    //                                         $sales += $qty;
    //                                     }

    //                                     if ($item['category_id'] === $categoryId && $transactionCategory === 'credit' && $transactionType === 'sales' && date('Y-m', strtotime($entry->log_date)) === "$year-$formattedMonth") {
    //                                         $sales -= $qty;
    //                                     }

    //                                     if ($item['category_id'] === $categoryId && $transactionCategory === 'debit' && $transactionType === 'transfer' && date('Y-m', strtotime($entry->log_date)) === "$year-$formattedMonth") {
    //                                         $sales += $qty;
    //                                     }
    //                                 }
    //                             }
    //                         }
    //                     }
    //                 }

    //                 // Calculate Closing Stock
    //                 $closingStock = $openingStock + $purchases - $sales;

    //                 // Store results in a temporary array
    //                 $tempResults[$formattedMonth][$categoryName] = [
    //                     'Opening' => $openingStock,
    //                     'Purchases' => $purchases,
    //                     'Sales' => $sales,
    //                     'Closing' => $closingStock,
    //                 ];
    //             }
    //         }
    //     }

    //     // Prepare final results and filter out categories with all zero values
    //     foreach ($tempResults as $month => $data) {
    //         $result = ['Date' => date('M - Y', strtotime("$fromYear-$month-01"))];

    //         foreach ($data as $categoryName => $values) {
    //             if ($values['Opening'] > 0 || $values['Purchases'] > 0 || $values['Sales'] > 0 || $values['Closing'] > 0) {
    //                 $result["{$categoryName} - Opening"] = $values['Opening'];
    //                 $result["{$categoryName} - Purchase"] = $values['Purchases'];
    //                 $result["{$categoryName} - Sales"] = $values['Sales'];
    //                 $result["{$categoryName} - Closing"] = $values['Closing'];
    //             }
    //         }

    //         // Add to results only if there are non-zero values
    //         if (count($result) > 1) { // At least the Date should be present
    //             $results[] = $result;
    //         }
    //     }

    //     $total = [];
    //     $all_unique_categories = [];

    //     // Check if results exist
    //     if (!empty($results[0])) {
    //         // Collect unique categories based on keys in results[0]
    //         foreach ($results[0] as $key => $value) {
    //             if ($key !== 'Date') {
    //                 $cat_name = explode(' -', $key)[0]; // Get the category name directly
    //                 if (!in_array($cat_name, $all_unique_categories)) {
    //                     $all_unique_categories[] = $cat_name; // Add to unique categories
    //                 }
    //             }
    //         }
    //     }

    //     // Initialize totals for each category
    //     foreach ($all_unique_categories as $category) {
    //         $opening_key = "$category - Opening";
    //         $purchase_key = "$category - Purchase";
    //         $sales_key = "$category - Sales";

    //         // Set opening, purchase, and sales values
    //         $total[$category] = [
    //             'opening' => $results[0][$opening_key] ?? 0, // Default to 0 if not found
    //             'purchase' => 0,
    //             'sales' => 0
    //         ];

    //         // Calculate purchase and sales totals across all results
    //         foreach ($results as $result) {
    //             $total[$category]['purchase'] += $result[$purchase_key] ?? 0; // Add if exists
    //             $total[$category]['sales'] += $result[$sales_key] ?? 0; // Add if exists
    //         }

    //         // Calculate closing amount
    //         $total[$category]['closing'] = $total[$category]['opening'] + $total[$category]['purchase'] - $total[$category]['sales'];
    //     }

    //     // Flatten the totals into a final array
    //     $final_total = [];
    //     foreach ($total as $values) {
    //         $final_total = array_merge($final_total, array_values($values)); // Merging values into final total
    //     }

    //     $responseData = [
    //         'results' => $results,
    //         'final_total' => $final_total,
    //     ];
        
    //     // Return the JSON response
    //     return response()->json($responseData);
    // }

    public function YearlyReport(Request $request)
    {
        $companyId = $request->company_id;
        $fromDate = $request->from_date;
        $toDate = $request->to_date;
        $allLogTables = $this->getAllLogDataTables();

        // Fetch opening data for the first month
        $openingData = $this->getOpeningData($allLogTables, $fromDate, $companyId);

        // Fetch categories with parent categories
        $categoriesData = DB::table('categories')
            ->select('id', 'name', 'parent_category_name')
            ->get()
            ->keyBy('id'); // Use id as key for easy lookup

        // Get unique parent categories
        $parentCategories = $categoriesData->pluck('parent_category_name')->unique();
        $monthlyData = [];

        // Initialize opening data array with parent categories
        $opening_data = [];
        foreach ($parentCategories as $parentCategory) {
            $opening_data[$parentCategory] = 0;
        }

        // Populate initial opening data
        if (!empty($openingData)) {
            $brandCategoryMapping = DB::table('brands')
                ->whereIn('id', array_keys($openingData))
                ->pluck('category_id', 'id');

            foreach ($openingData as $brandId => $qty) {
                $categoryId = $brandCategoryMapping[$brandId] ?? null;

                if ($categoryId && isset($categoriesData[$categoryId])) {
                    $parentCategoryName = $categoriesData[$categoryId]->parent_category_name;
                    $opening_data[$parentCategoryName] += round($qty / 1000, 2); // Convert to liters
                }
            }
        }

        // Calculate month range
        $fromYear = explode('-', $fromDate)[0];
        $toYear = explode('-', $toDate)[0];
        $fromMonth = explode('-', $fromDate)[1];
        $toMonth = explode('-', $toDate)[1];

        // Loop through each month
        for ($year = $fromYear; $year <= $toYear; $year++) {
            $startMonth = ($year == $fromYear) ? $fromMonth : 1;
            $endMonth = ($year == $toYear) ? $toMonth : 12;

            for ($month = $startMonth; $month <= $endMonth; $month++) {
                $formattedMonth = str_pad($month, 2, '0', STR_PAD_LEFT);
                $tableName = "{$year}_{$formattedMonth}_log_data";
                $monthlyRecord = [
                    'Date' => date("M - Y", strtotime("$year-$formattedMonth-01"))
                ];

                foreach ($parentCategories as $parentCategory) {
                    // Set initial values
                    $monthlyRecord["{$parentCategory} - Opening Balance"] = $opening_data[$parentCategory];
                    $monthlyRecord["{$parentCategory} - Purchase"] = 0;
                    $monthlyRecord["{$parentCategory} - Sales"] = 0;
                    $monthlyRecord["{$parentCategory} - Closing Balance"] = 0;
                }

                if (Schema::hasTable($tableName)) {
                    $data = DB::table($tableName)
                        ->where('company_id', $companyId)
                        ->whereBetween('log_date', ["$year-$formattedMonth-01", date("Y-m-t", strtotime("$year-$formattedMonth-01"))])
                        ->select('data')
                        ->get();

                    if (!empty($data)) {
                        foreach ($data as $data_value) {
                            $log_data = json_decode($data_value->data, true);

                            if (!empty($log_data)) {
                                foreach ($log_data as $log_value) {
                                    $parentCategoryName = $categoriesData[$log_value['category_id']]->parent_category_name;

                                    $qty = $log_value['qty'] / 1000;

                                    if ($log_value['transaction_type'] == 'purchase') {
                                        $monthlyRecord["{$parentCategoryName} - Purchase"] += 
                                            $log_value['transaction_category'] == 'credit' ? $qty : -$qty;
                                    }

                                    if ($log_value['transaction_type'] == 'opening') {
                                        $monthlyRecord["{$parentCategoryName} - Purchase"] += 
                                            $log_value['transaction_category'] == 'credit' ? $qty : -$qty;
                                    }

                                    if ($log_value['transaction_type'] == 'sales') {
                                        $monthlyRecord["{$parentCategoryName} - Sales"] += 
                                            $log_value['transaction_category'] == 'credit' ? -$qty : $qty;
                                    }

                                    if ($log_value['transaction_type'] == 'transfer') {
                                        if ($log_value['transaction_category'] == 'credit') {
                                            $monthlyRecord["{$parentCategoryName} - Purchase"] += $qty;
                                        } else {
                                            $monthlyRecord["{$parentCategoryName} - Sales"] += $qty;
                                        }
                                    }
                                }
                            }
                        }
                    }
                }

                // Calculate closing balance for each category and update next month's opening
                foreach ($parentCategories as $parentCategory) {
                    $monthlyRecord["{$parentCategory} - Closing Balance"] = round(
                        $monthlyRecord["{$parentCategory} - Opening Balance"]
                        + $monthlyRecord["{$parentCategory} - Purchase"]
                        - $monthlyRecord["{$parentCategory} - Sales"], 
                        2
                    );

                    // Update the opening balance for the next month
                    $opening_data[$parentCategory] = $monthlyRecord["{$parentCategory} - Closing Balance"];
                }

                // Append the monthly record
                $monthlyData[] = $monthlyRecord;
            }
        }

        if(!empty($monthlyData))
        {
            foreach($monthlyData as $key => $value)
            {
                foreach($value as $cat => $qty)
                {
                    if($cat !== 'Date')
                    {
                        $monthlyData[$key][$cat] = round($qty, 2);
                    }
                }
            }
        }

        $final_total = [];

        // Initialize final totals with "Opening Balance" and "Closing Balance"
        foreach ($parentCategories as $parentCategory) {
            $final_total["{$parentCategory} - opening balance"] = 0;
            $final_total["{$parentCategory} - purchase"] = 0;
            $final_total["{$parentCategory} - sales"] = 0;
            $final_total["{$parentCategory} - closing balance"] = 0;
        }

        if (!empty($monthlyData)) {
            foreach ($monthlyData as $index => $record) {
                foreach ($record as $category => $qty) {
                    $matches = [];
                    if (preg_match('/^(.*?) - (opening balance|purchase|sales|closing balance)$/i', $category, $matches)) {
                        $parentCategory = $matches[1];
                        $type = strtolower($matches[2]);
                        switch ($type) {
                            case 'opening balance':
                                if ($index === 0) {
                                    $final_total["{$parentCategory} - opening balance"] += $qty;
                                }
                                break;

                            case 'purchase':
                                $final_total["{$parentCategory} - purchase"] += $qty;
                                break;

                            case 'sales':
                                $final_total["{$parentCategory} - sales"] += $qty;
                                break;

                            case 'closing balance':
                                $final_total["{$parentCategory} - closing balance"] = $qty;
                                break;
                        }
                    }
                }
            }
            // Round values for final totals
            foreach ($final_total as $key => $value) {
                if ($value != 'Total') {
                    $final_total[$key] = round($value, 2);
                }
            }
        }

        $responseData = [
            'results' => $monthlyData,
            'final_total' => $final_total,
        ];

        // Return the JSON response
        return response()->json($responseData);
    }


    // Helper function to get the month title
    protected function getMonthTitle($month, $year)
    {
        // Convert month number to a short name (e.g. 04 -> Apr)
        $monthName = DateTime::createFromFormat('!m', $month)->format('M');
        return "{$monthName} - {$year}";
    }

    // public function YearlyComparisonReport(Request $request)
    // {
    //     $companyId = $request->input('company_id');
    //     $currentYear = date('Y');
    //     $currentMonth = date('m');

    //     // Define the financial year
    //     if ($currentMonth >= 4) {
    //         // Current financial year starts in April of the current year
    //         $financialYearStart = $currentYear;
    //     } else {
    //         // Current financial year starts in April of the previous year
    //         $financialYearStart = $currentYear - 1;
    //     }
        
    //     $previousFinancialYearStart = $financialYearStart - 1;

    //     // Fetch category names
    //     $categories = $this->getCategories();

    //     // Get tables for both financial years with data for this company
    //     $tables = $this->getFinancialYearTablesForComparison($companyId, $previousFinancialYearStart, $financialYearStart);

    //     $lastMonthPreviousFinancialYear = '';
    //     $firstMonthPreviousFinancialYear = '';
    //     $firstMonthCurrentFinancialYear = '';

    //     if (!empty($tables)) {
    //         $last_month = '';
    //         $first_month = '';
        
    //         foreach ($tables as $year => $months) {
    //             if ($year == ($financialYearStart - 1)) {
    //                 $month_keys = array_keys($months);
    //                 $last_month = end($month_keys);
    //                 $first_month_of_previous_year = reset($month_keys);
    //             }
                
    //             if ($year == $financialYearStart) {
    //                 $month_keys = array_keys($months);
    //                 $first_month = reset($month_keys);
    //             }
    //         }
        
    //         // Assign the last month of the previous financial year
    //         if ($last_month) {
    //             $lastMonthPreviousFinancialYear = (int)$last_month;
    //         }
        
    //         // Assign the last month of the previous financial year
    //         if ($first_month_of_previous_year) {
    //             $firstMonthPreviousFinancialYear = (int)$first_month_of_previous_year;
    //         }
        
    //         if ($first_month) {
    //             $firstMonthCurrentFinancialYear = (int)$first_month;
    //         }
    //     }

    //     // Initialize data storage for the report
    //     $reportData = [];
    //     $allCategories = [];

    //     // Track closing for monthly opening in the next month
    //     $monthlyClosingByCategory = [];

    //     // Process data for each financial year
    //     foreach ($tables as $year => $monthlyTables) {

    //         foreach ($monthlyTables as $month => $tableName) {
    //             // Query data for the company in the table
    //             $records = DB::table($tableName)
    //                 ->where('company_id', $companyId)
    //                 ->get();

    //             // Call updated calculateMonthlyData function with month and year
    //             $monthlyData = $this->calculateMonthlyData($records, $monthlyClosingByCategory, $categories, $month, $year, $firstMonthCurrentFinancialYear, $lastMonthPreviousFinancialYear);
    //             // Format month name
    //             $monthName = date('M', mktime(0, 0, 0, $month, 1)) . " - " . $year;

    //             // Organize monthly data under the formatted month name
    //             foreach ($monthlyData as $categoryName => $data) {
    //                 $reportData[] = [
    //                     "Date" => $monthName,
    //                     "{$categoryName} - Opening" => $data['opening'],
    //                     "{$categoryName} - Purchase" => $data['purchase'],
    //                     "{$categoryName} - Sales" => $data['sales'],
    //                     "{$categoryName} - Closing" => $data['closing'],
    //                 ];

    //                 if (!in_array($categoryName, $allCategories)) {
    //                     $allCategories[] = $categoryName;
    //                 }
    //             }
    //         }
    //     }

    //     $aggregatedReportData = [];

    //     foreach ($reportData as $entry) {
    //         $date = $entry['Date'];

    //         // Initialize the month entry if it doesn't exist
    //         if (!isset($aggregatedReportData[$date])) {
    //             $aggregatedReportData[$date] = [
    //                 'Date' => $date,
    //             ];
    //         }

    //         // Aggregate the quantities for each category
    //         foreach ($entry as $key => $value) {
    //             if ($key !== 'Date') {
    //                 if (!isset($aggregatedReportData[$date][$key])) {
    //                     $aggregatedReportData[$date][$key] = 0; // Initialize if not set
    //                 }
    //                 // Add the quantity
    //                 $aggregatedReportData[$date][$key] += $value;
    //             }
    //         }
    //     }

    //     // Final report with all categories for each month
    //     $finalReport = [];
    //     foreach ($aggregatedReportData as $date => $data) {
    //         // Initialize the month entry
    //         $finalReport[$date] = ['Date' => $date];

    //         // Set values for all categories, using 0 if they don't exist in the current month
    //         foreach ($allCategories as $category) {
    //             $finalReport[$date]["{$category} - Opening"] = $data["{$category} - Opening"] ?? 0;
    //             $finalReport[$date]["{$category} - Purchase"] = $data["{$category} - Purchase"] ?? 0;
    //             $finalReport[$date]["{$category} - Sales"] = $data["{$category} - Sales"] ?? 0;
    //             $finalReport[$date]["{$category} - Closing"] = $data["{$category} - Closing"] ?? 0;
    //         }
    //     }

    //     $orderedReport = [];

    //     // Get the number of months in the previous financial year from first month to December
    //     $totalMonths = 12;

    //     // Alternate between previous and current financial years
    //     for ($month = 1; $month <= $totalMonths; $month++) {
    //         // Determine the month name for the previous financial year
    //         $prevMonthName = date('M', mktime(0, 0, 0, $month, 1)) . " - " . ($previousFinancialYearStart);
    //         // Determine the month name for the current financial year
    //         $currentMonthName = date('M', mktime(0, 0, 0, $month, 1)) . " - " . ($financialYearStart);
            
    //         // Add the previous financial year data if it exists
    //         if (isset($finalReport[$prevMonthName])) {
    //             $orderedReport[] = $finalReport[$prevMonthName];
    //         }
            
    //         // Add the current financial year data if it exists
    //         if (isset($finalReport[$currentMonthName])) {
    //             $orderedReport[] = $finalReport[$currentMonthName];
    //         }
    //     }

    //     // Reindex the result array to get a numeric array instead of an associative one
    //     $orderedReport = array_values($orderedReport);

    //     return response()->json($orderedReport);
    // }

    /**
     * Fetch all categories and return them as an associative array
     */
    private function getCategories()
    {
        $categories = DB::table('categories')->pluck('name', 'id')->toArray();
        return $categories;
    }

    /**
     * Get all tables dynamically for both financial years that have entries for the company
     */
    private function getFinancialYearTablesForComparison($companyId, $financialYearStart, $financialYearEnd)
    {
        $tables = [];
        for ($year = $financialYearStart - 1; $year <= $financialYearEnd; $year++) {
            for ($month = 1; $month <= 12; $month++) {
                $month = str_pad($month, 2, '0', STR_PAD_LEFT);
                $tableName = "{$year}_{$month}_log_data";

                if (Schema::hasTable($tableName)) {
                    $hasCompanyData = DB::table($tableName)
                        ->where('company_id', $companyId)
                        ->exists();
                    if ($hasCompanyData) {
                        $tables[$year][$month] = $tableName;
                    }
                }
            }
        }
        return $tables;
    }

    private function calculateMonthlyData($records, &$monthlyClosingByCategory, $categories, $month, $year, $firstMonthCurrentFinancialYear, $lastMonthPreviousFinancialYear)
    {
        $monthlyData = [];

        // Iterate over each record's JSON data to aggregate quantities by category
        foreach ($records as $record) {
            $data = json_decode($record->data, true);

            foreach ($data as $entry) {
                $categoryId = $entry['category_id'];
                $categoryName = $entry['category_name'];
                $transactionType = $entry['transaction_type'];
                $transactionCategory = $entry['transaction_category'];
                $qty = (int)$entry['qty'];

                // Initialize category data structure if not exists
                if (!isset($monthlyData[$categoryName])) {
                    $monthlyData[$categoryName] = [
                        'opening' => 0, // Default opening to 0 initially
                        'purchase' => 0,
                        'sales' => 0,
                        'closing' => 0
                    ];
                }

                // Aggregate opening transactions
                if ($transactionType === 'opening') {
                    // Add to purchases for the current month
                    $monthlyData[$categoryName]['purchase'] += ($transactionCategory === 'credit') ? $qty : -$qty;
                }

                // Calculate purchase and sales
                if ($transactionType === 'purchase') {
                    $monthlyData[$categoryName]['purchase'] += ($transactionCategory === 'credit') ? $qty : -$qty;
                } elseif ($transactionType === 'transfer') {
                    $monthlyData[$categoryName]['purchase'] += ($transactionCategory === 'credit') ? $qty : 0;
                    $monthlyData[$categoryName]['sales'] += ($transactionCategory === 'debit') ? $qty : 0;
                } elseif ($transactionType === 'sales') {
                    $monthlyData[$categoryName]['sales'] += ($transactionCategory === 'debit') ? $qty : -$qty;
                }
            }
        }

        // Set the opening for the first month of the current financial year
        if ($month == $firstMonthCurrentFinancialYear && $year == date('Y')) {
            foreach ($monthlyData as $categoryName => &$categoryData) {
                // Assign the opening using the last month's closing from the previous year
                $categoryData['opening'] = $monthlyClosingByCategory[$categoryName] ?? 0; // Use closing of last month as opening
            }
        } else {
            // Set opening for subsequent months to be the closing of the previous month
            foreach ($monthlyData as $categoryName => &$categoryData) {
                $previousMonthClosing = $monthlyClosingByCategory[$categoryName] ?? 0;
                $categoryData['opening'] = $previousMonthClosing; // Closing of last month becomes opening
            }
        }

        // Finalize closing calculations and update closing for use as the opening of the next month
        foreach ($monthlyData as $categoryName => &$categoryData) {
            $categoryData['closing'] = $categoryData['opening'] + $categoryData['purchase'] - $categoryData['sales'];
            $monthlyClosingByCategory[$categoryName] = $categoryData['closing']; // Update for next month opening
        }

        // Convert quantities from ml to liters (1 liter = 1000 ml)
        foreach ($monthlyData as &$categoryData) {
            $categoryData['opening'] = $categoryData['opening'] / 1000;
            $categoryData['purchase'] = $categoryData['purchase'] / 1000;
            $categoryData['sales'] = $categoryData['sales'] / 1000;
            $categoryData['closing'] = $categoryData['closing'] / 1000;
        }

        return $monthlyData;
    }

    // public function YearlyComparisonReport(Request $request)
    // {
    //     $json = [];
    //     $months = array();
    //     $company_id = $request->company_id;
    //     $months = $this->getCurrentFinancialYearMonths();
    //     foreach ($months as $month) {
    //         $newMonth = explode(' ', $month);
    //         $categories = Category::where('status', 1)->get();
    //         foreach ($categories as $category) {
    //             $btls = Brand::where(['category_id' => $category->id])->orderBy('btl_size', 'DESC')->groupBy(DB::raw("btl_si-okjnb ze"))->get(); // get unique bottle size of that category
    //             foreach ($btls as $key2 => $btl_size) {
    //                 $brands = Brand::where(['category_id' => $category['id'], 'btl_size' => $btl_size['btl_size']])->get(); // get brand of that category
    //                 $openSum = 0;
    //                 $purchaseSum = 0;
    //                 $totalSum = 0;
    //                 $saleSum = 0;
    //                 $closingSum = 0;


    //                 $openSum2 = 0;
    //                 $purchaseSum2 = 0;
    //                 $totalSum2 = 0;
    //                 $saleSum2 = 0;
    //                 $closingSum2 = 0;
    //                 foreach ($brands as $key => $brand) {
    //                     // current year opening section
    //                     [$opening] = DailyOpening::where(['brand_id' => $brand['id'], 'company_id' => $company_id])
    //                         ->whereMonth('date', $newMonth[0])
    //                         ->whereYear('date', $newMonth[1])
    //                         ->select(DB::raw('SUM(COALESCE(qty, 0)) as qty'))
    //                         ->get();

    //                     if ($opening)
    //                         $open = $opening['qty'];
    //                     else
    //                         $open = 0;
    //                     $openSum = $openSum + $open;
    //                     // current year opening section end
    //                     //current year purchase section
    //                     [$purchase] = purchase::where(['brand_id' => $brand['id'], 'compa4ny_id' => $company_id])
    //                         ->whereMonth('invoice_date', $newMonth[0])
    //                         ->whereYear('invoice_date', $newMonth[1])
    //                         ->select(DB::raw('SUM(COALESCE(qty, 0)) as qty'))
    //                         ->get();
    //                     if ($purchase)
    //                         $purchaseQty = $purchase['qty'];
    //                     else
    //                         $purchaseQty = 0;
    //                     $purchaseSum = $purchaseSum + $purchaseQty;
    //                     //total section
    //                     $total = $purchaseQty + $open;
    //                     if ($total)
    //                         $totalSum = $totalSum + $total;
    //                     //current year purchase section end 

    //                     // current year sales start
    //                     [$sales] = Sales::where(['brand_id' => $brand['id'], 'company_id' => $company_id])
    //                         ->select(DB::raw('SUM(COALESCE(qty, 0)) as qty'))
    //                         ->whereMonth('sale_date', $newMonth[0])
    //                         ->whereYear('sale_date', $newMonth[1])
    //                         ->get();
    //                     if ($sales)
    //                         $saleQty = $sales['qty'];
    //                     else
    //                         $saleQty = 0;
    //                     $saleSum = $saleSum + $saleQty;

    //                     //total section
    //                     $closing = $total - $saleQty;
    //                     if ($total)
    //                         $closingSum = $closingSum + $closing;
    //                     // current year sales end



    //                     // last year opening section
    //                     [$opening2] = DailyOpening::where(['brand_id' => $brand['id'], 'company_id' => $company_id])
    //                         ->whereMonth('date', $newMonth[0])
    //                         ->whereYear('date', $newMonth[1] - 1)
    //                         ->select(DB::raw('SUM(COALESCE(qty, 0)) as qty'))
    //                         ->get();

    //                     if ($opening2)
    //                         $open2 = $opening2['qty'];
    //                     else
    //                         $open2 = 0;
    //                     $openSum2 = $openSum2 + $open2;
    //                     // last year opening section end
    //                     //last year purchase section start 
    //                     [$purchase2] = purchase::where(['brand_id' => $brand['id'], 'company_id' => $company_id])
    //                         ->whereMonth('invoice_date', $newMonth[0])
    //                         ->whereYear('invoice_date', $newMonth[1] - 1)
    //                         ->select(DB::raw('SUM(COALESCE(qty, 0)) as qty'))
    //                         ->get();
    //                     if ($purchase2)
    //                         $purchaseQty2 = $purchase2['qty'];
    //                     else
    //                         $purchaseQty2 = 0;
    //                     $purchaseSum2 = $purchaseSum2 + $purchaseQty2;
    //                     //total section
    //                     $total2 = $purchaseQty2 + $open2;
    //                     if ($total2)
    //                         $totalSum2 = $totalSum2 + $total2;
    //                     //last year purchase section end
    //                     // last year sales start

    //                     [$sales2] = Sales::where(['brand_id' => $brand['id'], 'company_id' => $company_id])
    //                         ->select(DB::raw('SUM(COALESCE(qty, 0)) as qty'))
    //                         ->whereMonth('sale_date', $newMonth[0])
    //                         ->whereYear('sale_date', $newMonth[1] - 1)
    //                         ->get();
    //                     if ($sales2)
    //                         $saleQty2 = $sales2['qty'];
    //                     else
    //                         $saleQty2 = 0;
    //                     $saleSum2 = $saleSum2 + $saleQty2;

    //                     //total section
    //                     $closing2 = $total2 - $saleQty2;
    //                     if ($total2)
    //                         $closingSum2 = $closingSum2 + $closing2;
    //                     // last year sales end
    //                 }
    //                 // current year
    //                 $data[$month]['Title'] = $month;
    //                 $data[$month][$category['name'] . '-' . 'opening'] = $openSum / 1000;
    //                 $data[$month][$category['name'] . '-' . 'purchase'] = $purchaseSum / 1000;
    //                 $data[$month][$category['name'] . '-' . 'sale'] = $saleSum / 1000;
    //                 $data[$month][$category['name'] . '-' . 'closing'] = $closingSum / 1000;
    //                 // last year
    //                 $data[$newMonth[0] . $newMonth[1] - 1]['Title'] =  $newMonth[0]  . ' ' . $newMonth[1] - 1;
    //                 $data[$newMonth[0] . $newMonth[1] - 1][$category['name'] . '-' . 'opening'] = $openSum2 / 1000;
    //                 $data[$newMonth[0] . $newMonth[1] - 1][$category['name'] . '-' . 'purchase'] = $purchaseSum2 / 1000;
    //                 $data[$newMonth[0] . $newMonth[1] - 1][$category['name'] . '-' . 'sale'] = $saleSum2 / 1000;
    //                 $data[$newMonth[0] . $newMonth[1] - 1][$category['name'] . '-' . 'closing'] = $closingSum2 / 1000;
    //                 //blank
    //                 $data[$newMonth[0]]['Title'] = '';
    //                 $data[$newMonth[0]][$category['name'] . '-' . 'opening'] = '';
    //                 $data[$newMonth[0]][$category['name'] . '-' . 'purchase'] = '';
    //                 $data[$newMonth[0]][$category['name'] . '-' . 'sale'] = '';
    //                 $data[$newMonth[0]][$category['name'] . '-' . 'closing'] = '';
    //             }
    //         }
    //         array_push($json, $data[$month]);
    //         array_push($json, $data[$newMonth[0] . $newMonth[1] - 1]);
    //         array_push($json, $data[$newMonth[0]]);
    //     }
    //     return response()->json($json);
    // }
    
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

    public function BrandwiseReport(Request $request)
    {
        $json = [];
        $all_row_data = [];
        $company_id = $request->company_id;
        $currentDate = $request->to_date;

        $tables = $this->getAllLogDataTables();

        // Initialize data arrays
        $opening_data = [];
        $purchase_data = [];
        $sales_data = [];
        $closing_data = [];

        // Fetch tables

        $to_date_table = null;

        // Fetch current financial year data
        if (!empty($tables)) {
            // Find the table for the current month
            $to_date_table = $this->findToDateTable($tables, $currentDate);
            // Fetch data for all months except the current one
            $openingData = $this->fetchDataFromTables($tables, $company_id, $currentDate);
            $all_row_data = [];
            // Fetch data for the current month up to the specified date
            if ($to_date_table) {
                $currentMonthData = DB::table($to_date_table)
                    ->where('company_id', $company_id)
                    ->whereDate('log_date', '=', $currentDate)
                    ->select('data')
                    ->get()
                    ->pluck('data')
                    ->toArray();

                $all_row_data = $currentMonthData;
            }
        }
        // Process current and last financial year data
        $all_data_for_category_and_brands = array_merge($all_row_data, $openingData);

        $all_category_brands = $this->processRowData($all_data_for_category_and_brands);
        $opening_data_all_category_brands = $this->processRowData($openingData);

        // Calculate opening, purchase, sales, and closing data
        $this->calculateBrandwiseData($all_category_brands, $all_row_data, $opening_data_all_category_brands, $openingData, $opening_data, $purchase_data, $sales_data, $closing_data);
        
        $all_brands_and_tp_no = $this->getAllBrandsAndTpNo($all_row_data);
            

        $zero_in_opening = [];
        $zero_in_purchase = [];
        $zero_in_sales = [];
        $zero_in_closing = [];

        foreach ($opening_data as $category => $sizes) {
            foreach ($sizes as $size => $brands) {
                foreach ($brands as $brand => $qty) {
                    if ($qty == 0) {
                        $zero_in_opening[] = $category . '_' . $size . '_' . $brand;
                    }
                }
            }
        }

        foreach ($purchase_data as $category => $sizes) {
            foreach ($sizes as $size => $brands) {
                foreach ($brands as $brand => $qty) {
                    if ($qty == 0) {
                        $zero_in_purchase[] = $category . '_' . $size . '_' . $brand;
                    }
                }
            }
        }

        foreach ($sales_data as $category => $sizes) {
            foreach ($sizes as $size => $brands) {
                foreach ($brands as $brand => $qty) {
                    if ($qty == 0) {
                        $zero_in_sales[] = $category . '_' . $size . '_' . $brand;
                    }
                }
            }
        }

        foreach ($closing_data as $category => $sizes) {
            foreach ($sizes as $size => $brands) {
                foreach ($brands as $brand => $qty) {
                    if ($qty == 0) {
                        $zero_in_closing[] = $category . '_' . $size . '_' . $brand;
                    }
                }
            }
        }
        $common_zeros = array_intersect($zero_in_opening, $zero_in_purchase, $zero_in_sales, $zero_in_closing);

        // Function to remove common zeros from a dataset
        function removeCommonZeros(&$data, $common_zeros) {
            foreach ($data as $category => &$sizes) {
                foreach ($sizes as $size => &$brands) {
                    foreach ($brands as $brand => $qty) {
                        $key = $category . '_' . $size . '_' . $brand;
                        if (in_array($key, $common_zeros)) {
                            unset($brands[$brand]);
                        }
                    }
                    if (empty($brands)) {
                        unset($sizes[$size]);
                    }
                }
                if (empty($sizes)) {
                    unset($data[$category]);
                }
            }
        }

        // Remove common zeros from all datasets
        removeCommonZeros($opening_data, $common_zeros);
        removeCommonZeros($purchase_data, $common_zeros);
        removeCommonZeros($sales_data, $common_zeros);
        removeCommonZeros($closing_data, $common_zeros);

        foreach ($opening_data as $category => $sizes) {
            // Sort the sizes in descending order
            krsort($sizes); // Sort by key in descending order
            
            $opening_data[$category] = $sizes;
        }
        foreach ($purchase_data as $category => $sizes) {
            // Sort the sizes in descending order
            krsort($sizes); // Sort by key in descending order
            
            $purchase_data[$category] = $sizes;
        }
        foreach ($sales_data as $category => $sizes) {
            // Sort the sizes in descending order
            krsort($sizes); // Sort by key in descending order
            
            $sales_data[$category] = $sizes;
        }
        foreach ($closing_data as $category => $sizes) {
            // Sort the sizes in descending order
            krsort($sizes); // Sort by key in descending order
            
            $closing_data[$category] = $sizes;
        }

        foreach ($opening_data as $category => $sizes) {
            foreach($sizes as $sizesKey => $sizesVal)
            {
                foreach ($sizesVal as $brands => $qty) {
                    // Get brand details
                    $brand_details = DB::table('brands')->where('name', $brands)->select('peg_size', 'btl_size')->first();
                    
                    // Remove the negative sign temporarily for conversion
                    $absoluteQty = abs($qty);
                
                    // Convert into bottles and pegs
                    $convertIntoBtlPeg = convertBtlPeg($absoluteQty, $brand_details->btl_size, $brand_details->peg_size);
                    
                    // Reapply the negative sign if original quantity was negative
                    $convertedBtlPeg = ($qty < 0 ? '-' : '') . $convertIntoBtlPeg['btl'] . '.' . $convertIntoBtlPeg['peg'];
                    
                    // Assign to opening data
                    $opening_data[$category][$sizesKey][$brands] = $convertedBtlPeg;
                }
            }
        }
        foreach ($purchase_data as $category => $sizes) {
            foreach($sizes as $sizesKey => $sizesVal)
            {
                foreach ($sizesVal as $brands => $qty) {
                    // Get brand details
                    $brand_details = DB::table('brands')->where('name', $brands)->select('peg_size', 'btl_size')->first();
                    
                    // Remove the negative sign temporarily for conversion
                    $absoluteQty = abs($qty);
                
                    // Convert into bottles and pegs
                    $convertIntoBtlPeg = convertBtlPeg($absoluteQty, $brand_details->btl_size, $brand_details->peg_size);
                    
                    // Reapply the negative sign if original quantity was negative
                    $convertedBtlPeg = ($qty < 0 ? '-' : '') . $convertIntoBtlPeg['btl'] . '.' . $convertIntoBtlPeg['peg'];
                    
                    // Assign to opening data
                    $purchase_data[$category][$sizesKey][$brands] = $convertedBtlPeg;
                }
            }
        }
        foreach ($sales_data as $category => $sizes) {
            foreach($sizes as $sizesKey => $sizesVal)
            {
                foreach ($sizesVal as $brands => $qty) {
                    // Get brand details
                    $brand_details = DB::table('brands')->where('name', $brands)->select('peg_size', 'btl_size')->first();
                    
                    // Remove the negative sign temporarily for conversion
                    $absoluteQty = abs($qty);
                
                    // Convert into bottles and pegs
                    $convertIntoBtlPeg = convertBtlPeg($absoluteQty, $brand_details->btl_size, $brand_details->peg_size);
                    
                    // Reapply the negative sign if original quantity was negative
                    $convertedBtlPeg = ($qty < 0 ? '-' : '') . $convertIntoBtlPeg['btl'] . '.' . $convertIntoBtlPeg['peg'];
                    
                    // Assign to opening data
                    $sales_data[$category][$sizesKey][$brands] = $convertedBtlPeg;
                }
            }
        }
        foreach ($closing_data as $category => $sizes) {
            foreach($sizes as $sizesKey => $sizesVal)
            {
                foreach ($sizesVal as $brands => $qty) {
                    // Get brand details
                    $brand_details = DB::table('brands')->where('name', $brands)->select('peg_size', 'btl_size')->first();
                    
                    // Remove the negative sign temporarily for conversion
                    $absoluteQty = abs($qty);
                
                    // Convert into bottles and pegs
                    $convertIntoBtlPeg = convertBtlPeg($absoluteQty, $brand_details->btl_size, $brand_details->peg_size);
                    
                    // Reapply the negative sign if original quantity was negative
                    $convertedBtlPeg = ($qty < 0 ? '-' : '') . $convertIntoBtlPeg['btl'] . '.' . $convertIntoBtlPeg['peg'];
                    
                    // Assign to opening data
                    $closing_data[$category][$sizesKey][$brands] = $convertedBtlPeg;
                }
            }
        }
        
        $data = [];
        $data['opening'] = $opening_data;
        $data['purchase'] = $purchase_data;
        $data['sales'] = $sales_data;
        $data['closing'] = $closing_data;

        $uniqueSizes = $this->getUniqueSizes($data);

        $result = [];

        if(!empty($data))
        {
            // all data
            foreach($data as $key => $value)
            {
                // divide in types
                if(!empty($value)){
                    foreach($value as $b_key => $b_val)
                    {
                        // divide in bottles
                        if(!empty($b_val))
                        {
                            foreach($b_val as $brand_key => $brand_val)
                            {
                                // divide in brands
                                if(!empty($brand_val))
                                {
                                    foreach($brand_val as $final_key => $final_val)
                                    {
                                        // divide in qty
                                        if(!empty($uniqueSizes)){
                                            foreach($uniqueSizes as $uniqueKey => $uniqueValue)
                                            {
                                                if($uniqueValue == $brand_key)
                                                {
                                                    $result[$final_key][$key][$uniqueValue] = $final_val;
                                                }else{
                                                    $result[$final_key][$key][$uniqueValue] = '';
                                                }
                                            }
                                        }
                                    }
                                }
                            }
                        }
                    }
                }

            }
        }

        if (!empty($opening_data)) {
            $this->convertSizeKeysToBrandNames($opening_data);
        }

        if (!empty($purchase_data)) {
            $this->convertSizeKeysToBrandNames($purchase_data);
        }
        
        if (!empty($sales_data)) {
            $this->convertSizeKeysToBrandNames($sales_data);
        }
        
        if (!empty($closing_data)) {
            $this->convertSizeKeysToBrandNames($closing_data);
        }
        
        if (!empty($result)) {
            $this->convertSimpleKeysToBrandNames($result);
        }
        
        if (!empty($all_brands_and_tp_no)) {
            $this->convertSimpleKeysToBrandNames($all_brands_and_tp_no);
        }

        $all_categories = DB::table('categories')
                        ->select('id', 'name')
                        ->get();

        $categoryOrder = $all_categories->pluck('name')->all();

        // Sort the $brandAggregatedData based on the order of categories in $categoryOrder
        uksort($opening_data, function($a, $b) use ($categoryOrder) {
            $posA = array_search($a, $categoryOrder);
            $posB = array_search($b, $categoryOrder);
            return $posA <=> $posB;
        });

        uksort($purchase_data, function($a, $b) use ($categoryOrder) {
            $posA = array_search($a, $categoryOrder);
            $posB = array_search($b, $categoryOrder);
            return $posA <=> $posB;
        });

        uksort($sales_data, function($a, $b) use ($categoryOrder) {
            $posA = array_search($a, $categoryOrder);
            $posB = array_search($b, $categoryOrder);
            return $posA <=> $posB;
        });

        uksort($closing_data, function($a, $b) use ($categoryOrder) {
            $posA = array_search($a, $categoryOrder);
            $posB = array_search($b, $categoryOrder);
            return $posA <=> $posB;
        });

        return [
            'opening' => $opening_data,
            'purchase' => $purchase_data,
            'sales' => $sales_data,
            'closing' => $closing_data,
            'quantities' => $result,
            'all_brands_and_tp_no' => $all_brands_and_tp_no
        ];
    }

    private function convertSizeKeysToBrandNames(&$data) {
        // Collect all size keys from the data structure
        $size_keys = [];
        foreach ($data as $val) {
            foreach ($val as $cat_val) {
                foreach ($cat_val as $size_key => $size_val) {
                    $size_keys[] = $size_key; // Collect size keys
                }
            }
        }
    
        // Fetch brand names in a single query
        $brands = DB::table('brands')
            ->whereIn('name', array_unique($size_keys))
            ->pluck('short_name', 'name');
    
        // Replace size keys with brand names
        foreach ($data as $key => $val) {
            foreach ($val as $cat_key => $cat_val) {
                $modified_cat_val = [];
                foreach ($cat_val as $size_key => $size_val) {
                    // Replace size_key with the brand name if it exists
                    $brand_name = $brands[$size_key] ?? $size_key;
                    $modified_cat_val[$brand_name] = $size_val;
                }
                $data[$key][$cat_key] = $modified_cat_val; // Update with modified values
            }
        }
    }
    
    // Function to modify 1D arrays (for $result and $all_brands_and_tp_no)
    private function convertSimpleKeysToBrandNames(&$data) {
        $keys = array_keys($data);
        $brands = DB::table('brands')
            ->whereIn('name', array_unique($keys))
            ->pluck('short_name', 'name');
    
        $modified_data = [];
        foreach ($data as $key => $value) {
            $brand_name = $brands[$key] ?? $key;
            $modified_data[$brand_name] = $value;
        }
    
        $data = $modified_data;
    }

    private function getUniqueSizes($data)
    {
        $sizes = [];
        foreach ($data as $type => $categories) {
            foreach ($categories as $category => $sizesData) {
                // Merge all sizes from different categories
                $sizes = array_merge($sizes, array_keys($sizesData));
            }
        }
        
        // Remove duplicates and sort in descending order
        $uniqueSizes = array_unique($sizes);
        
        // Sort the sizes numerically in descending order
        rsort($uniqueSizes, SORT_NUMERIC);
        
        return $uniqueSizes;
    }

    /**
     * Fetch data from financial year tables
     */
    protected function fetchDataFromTables($tables, $company_id, $currentDate,)
    {
        return !empty($tables) ? collect($tables)->flatMap(function ($table_value) use ($company_id, $currentDate) {
            return DB::table($table_value)
                ->where('company_id', $company_id)
                ->whereDate('log_date', '<', $currentDate)
                ->select('data')
                ->get()
                ->pluck('data')
                ->toArray();
        })->toArray() : [];
    }

    /**
     * Find the table for the current month
     */
    protected function findToDateTable(&$tables, $currentDate)
    {
        
        return array_reduce($tables, function ($carry, $table_value) use ($currentDate, &$tables) {
            $current_date_month = explode('-', $currentDate)[1];
            $current_date_year = explode('-', $currentDate)[0];
            $month = explode('_', $table_value)[1];
            $year = explode('_', $table_value)[0];
            if ($month == $current_date_month && $year == $current_date_year) {
                $carry = $table_value;
            }
            return $carry;
        });
    }

    /**
     * Process row data into categories, bottle sizes, and brands
     */
    protected function processRowData($row_data)
    {
        $category_brands = [];

        foreach ($row_data as $data_value) {
            $json_data = json_decode($data_value);
            if (!empty($json_data)) {
                foreach ($json_data as $json_value) {
                    // Collect brand names and bottle sizes under categories
                    $category_brands[$json_value->category_name][$json_value->btl_size][$json_value->brand_name] = 0.00; // Initialize quantity
                }
            }
        }

        return $category_brands;
    }

    /**
     * Calculate brandwise opening, purchase, sales, and closing data
     */
    protected function calculateBrandwiseData($all_category_brands, $all_row_data, $openingData_category_brands, $openingData, &$opening_data, &$purchase_data, &$sales_data, &$closing_data)
    {
        foreach ($all_category_brands as $category => $btl_sizes) {
            foreach ($btl_sizes as $btl_size => $brands) {
                foreach ($brands as $brand => $_) {
                    // Initialize data
                    $opening_data[$category][$btl_size][$brand] = 0.00;
                    $purchase_data[$category][$btl_size][$brand] = 0.00;
                    $sales_data[$category][$btl_size][$brand] = 0.00;

                    // Calculate opening data from last financial year
                    $this->calculateOpeningData($category, $btl_size, $brand, $openingData, $opening_data);

                    // Process current financial year data
                    $this->processTransactionData($all_row_data, $category, $btl_size, $brand, $opening_data, $purchase_data, $sales_data);
                    
                    // Calculate closing data as opening + purchase - sales
                    $closing_data[$category][$btl_size][$brand] = $opening_data[$category][$btl_size][$brand] + $purchase_data[$category][$btl_size][$brand] - $sales_data[$category][$btl_size][$brand];
                }
            }
        }
    }

    protected function getAllBrandsAndTpNo($all_row_data)
    {
        $brand_tp_data = [];
        $tp_no_tracker = []; // To track occurrences of tp_no with transaction_category for each brand

        foreach ($all_row_data as $data) {
            $json_data = json_decode($data, true); // Decode JSON as an associative array
            if (!empty($json_data)) {
                foreach ($json_data as $json_value) {
                    $brand_name = $json_value['brand_name'];
                    $tp_no = $json_value['tp_no'];
                    $transaction_category = $json_value['transaction_category']; // credit or debit
                    $transaction_type = $json_value['transaction_type']; // purchase or others

                    // Skip if the transaction type is not 'purchase'
                    if ($transaction_type !== 'purchase') {
                        continue;
                    }

                    // Track tp_no occurrences by brand and category
                    if (!isset($tp_no_tracker[$brand_name][$tp_no])) {
                        $tp_no_tracker[$brand_name][$tp_no] = ['credit' => 0, 'debit' => 0];
                    }
                    $tp_no_tracker[$brand_name][$tp_no][$transaction_category]++;
                }
            }
        }

        // Build brand_tp_data excluding tp_no with both credit and debit
        foreach ($tp_no_tracker as $brand_name => $tp_data) {
            foreach ($tp_data as $tp_no => $categories) {
                // Exclude tp_no with both credit and debit
                if ($categories['credit'] > 0 && $categories['debit'] > 0) {
                    continue;
                }

                // Add valid tp_no to brand_tp_data
                if (!isset($brand_tp_data[$brand_name])) {
                    $brand_tp_data[$brand_name] = '';
                }
                if (!empty($brand_tp_data[$brand_name])) {
                    $brand_tp_data[$brand_name] .= ', ';
                }
                $brand_tp_data[$brand_name] .= $tp_no;
            }
        }

        return $brand_tp_data;
    }

    


    /**
     * Calculate opening data from last financial year
     */
    protected function calculateOpeningData($category, $btl_size, $brand, $openingData, &$opening_data)
    {
        foreach ($openingData as $data_value) {
            $json_data = json_decode($data_value);
            if (!empty($json_data)) {
                foreach ($json_data as $json_value) {
                    if ($json_value->category_name == $category && $json_value->btl_size == $btl_size && $json_value->brand_name == $brand) {
                        $opening_data[$category][$btl_size][$brand] += ($json_value->transaction_category == 'credit') ? $json_value->qty : -$json_value->qty;
                    }
                }
            }
        }
    }

    /**
     * Process transaction data f00or the current financial year
     */
    protected function processTransactionData($row_data, $category, $btl_size, $brand, &$opening_data, &$purchase_data, &$sales_data)
    {
        $transaction_counts = [];

        // First pass to aggregate counts of credits and debits for each unique key
        foreach ($row_data as $data_value) {
            $json_data = json_decode($data_value);
            if (!empty($json_data)) {
                foreach ($json_data as $json_value) {
                    // Create a unique key based on transaction details
                    $unique_key = "{$json_value->transaction_type}_{$json_value->transaction_table_id}_{$json_value->brand_name}_{$json_value->qty}";

                    // Initialize or update the counts for credit/debit per unique key
                    if (!isset($transaction_counts[$unique_key])) {
                        $transaction_counts[$unique_key] = [
                            'credit' => 0,
                            'debit' => 0,
                            'transaction_category' => $json_value->transaction_category,
                            'json_value' => $json_value
                        ];
                    }

                    // Increment credit or debit count based on transaction category
                    if ($json_value->transaction_category === 'credit') {
                        $transaction_counts[$unique_key]['credit']++;
                    } elseif ($json_value->transaction_category === 'debit') {
                        $transaction_counts[$unique_key]['debit']++;
                    }
                }
            }
        }

        // Second pass to process non-cancelled transactions
        foreach ($transaction_counts as $unique_key => $count_data) {
            $json_value = $count_data['json_value'];

            // Determine the net count after canceling pairs of credits and debits
            $net_count = $count_data['credit'] - $count_data['debit'];

            // Only process if there's a remaining quantity after canceling out pairs
            if ($net_count !== 0 && $json_value->category_name == $category && $json_value->btl_size == $btl_size && $json_value->brand_name == $brand) {
                $remaining_qty = abs($net_count) * $json_value->qty;
                $transaction_type = $json_value->transaction_type;

                // Proceed with calculation logic based on remaining category (credit or debit)
                switch ($transaction_type) {
                    case 'purchase':
                    case 'opening':
                        $purchase_data[$category][$btl_size][$brand] += ($net_count > 0) ? $remaining_qty : -$remaining_qty;
                        break;
                    case 'sales':
                        $sales_data[$category][$btl_size][$brand] += ($net_count < 0) ? $remaining_qty : -$remaining_qty;
                        break;
                    case 'transfer':
                        if ($net_count > 0) {
                            $purchase_data[$category][$btl_size][$brand] += $remaining_qty;
                        } else {
                            $sales_data[$category][$btl_size][$brand] += $remaining_qty;
                        }
                        break;
                }
            }
        }
    }

    // public function BrandwiseReport(Request $request)
    // {
    //     $json = [];
    //     $data = [];
    //     $subtotals = [];
    //     $categories = Category::where(['status' => 1])->get();
    //     $company_id = $request->company_id;
    //     $currentDate = $request->to_date;

    //     // Retrieve all unique btl_size values from the Brand table
    //     $btlSizes = Brand::distinct()->pluck('btl_size')->toArray();


    //     foreach ($categories as $category) {
    //         $cat_name = $category->name;
    //         $btls = Brand::where(['category_id' => $category->id])->get();
    //         //total
    //         $subtotalOpening = 0;
    //         $subtotalPurchase = 0;
    //         $subtotalSales     = 0;
    //         $subtotalClosing = 0;
    //         $openSum = 0;
    //         $open = 0;
    //         $purchaseSum = 0;
    //         $totalSum = 0;
    //         $saleSum = 0;
    //         $closingSum = 0;
    //         foreach ($btls as $key2 => $brand) {
    //             $brand_name = $brand['name'];
    //             $btl_size = $brand['btl_size'];


    //             // opening section
    //             $opening = DailyOpening::where(['brand_id' => $brand['id'], 'company_id' => $company_id])
    //                 ->whereDate('date', $currentDate)
    //                 ->select(DB::raw('COALESCE(qty, 0) as qty'))
    //                 ->first();
    //             if ($opening)
    //                 $open = $opening['qty'];
    //             else
    //                 $open = 0;
    //             $openSum = $openSum + $open;

    //             // purchase section
    //             $purchase = Purchase::where(['brand_id' => $brand['id'], 'company_id' => $company_id])
    //                 ->whereDate('invoice_date', $currentDate)

    //                 ->select(DB::raw('COALESCE(qty, 0) as qty'))
    //                 ->first();
    //             if ($purchase)
    //                 $purchaseQty = $purchase['qty'];
    //             else
    //                 $purchaseQty = 0;
    //             $purchaseSum = $purchaseSum + $purchaseQty;

    //             $total = $purchaseQty + $open;
    //             if ($total)
    //                 $totalSum = $totalSum + $total;

    //             // sales
    //             $sales = Sales::where(['brand_id' => $brand['id'], 'company_id' => $company_id])
    //                 ->whereDate('sale_date', $currentDate)

    //                 ->select(DB::raw('COALESCE(qty, 0) as qty'))
    //                 ->first();
    //             if ($sales)
    //                 $saleQty = $sales['qty'];
    //             else
    //                 $saleQty = 0;
    //             $saleSum = $saleSum + $saleQty;

    //             // total section
    //             $closing = $total - $saleQty;
    //             if ($total)
    //                 $closingSum = $closingSum + $closing;


    //             $open_btl = convertBtlPeg($open, $brand['btl_size'], $brand['peg_size']);
    //             // total calculation
    //             $purchase_btl = convertBtlPeg($purchaseQty, $brand['btl_size'], $brand['peg_size']);
    //             $sale_btl = convertBtlPeg($saleQty, $brand['btl_size'], $brand['peg_size']);
    //             $closing_btl = convertBtlPeg($closing, $brand['btl_size'], $brand['peg_size']);

    //             $categoryData = [
    //                 'Category' => $cat_name,
    //                 'Brand Name' => $brand_name,
    //                 'TPNo' => '',
    //             ];

    //             // Add btl_size data to the categoryData array
    //             foreach ($btlSizes as $size) {
    //                 if ($size == $btl_size) {
    //                     $categoryData['opening-' . $size] = $open_btl['btl'] . '.' . $open_btl['peg'];
    //                 } else {
    //                     $categoryData['opening-' . $size] = '';
    //                 }
    //             }
    //             foreach ($btlSizes as $size) {
    //                 if ($size == $btl_size) {
    //                     $categoryData['purchase-' . $size] = $purchase_btl['btl'] . '.' . $purchase_btl['peg'];
    //                 } else {
    //                     $categoryData['purchase-' . $size] = '';
    //                 }
    //             }
    //             foreach ($btlSizes as $size) {
    //                 if ($size == $btl_size) {
    //                     $categoryData['sales-' . $size] = $sale_btl['btl'] . '.' . $sale_btl['peg'];
    //                 } else {
    //                     $categoryData['sales-' . $size] = '';
    //                 }
    //             }
    //             foreach ($btlSizes as $size) {
    //                 if ($size == $btl_size) {
    //                     $categoryData['closingstock-' . $size] = $closing_btl['btl'] . '.' . $closing_btl['peg'];
    //                 } else {
    //                     $categoryData['closingstock-' . $size] = '';
    //                 }
    //             }

    //             $data[] = $categoryData;
    //         }

    //         // Calculate subtotals for each btl_size within the category
    //         $categorySubtotal = [
    //             'Category' => $cat_name,
    //             'Brand Name' => 'SUBTOTAL',
    //             'TPNo' => '',
    //         ];

    //         // total calculation
    //         $c_open = convertBtlPeg($openSum, $brand['btl_size'], $brand['peg_size']);
    //         $c_purchase = convertBtlPeg($purchaseSum, $brand['btl_size'], $brand['peg_size']);
    //         $c_sale = convertBtlPeg($saleSum, $brand['btl_size'], $brand['peg_size']);
    //         $c_closing = convertBtlPeg($closingSum, $brand['btl_size'], $brand['peg_size']);

    //         $categoryData = [
    //             'Category' => $cat_name,
    //             'Brand Name' => $brand_name,
    //             'TPNo' => '',
    //         ];

    //         // Add btl_size data to the categoryData array
    //         foreach ($btlSizes as $size) {
    //             if ($size == $btl_size) {
    //                 $categorySubtotal['opening-' . $size] = $c_open['btl'] . '.' . $c_open['peg'];
    //             } else {
    //                 $categorySubtotal['opening-' . $size] = '';
    //             }
    //         }
    //         foreach ($btlSizes as $size) {
    //             if ($size == $btl_size) {
    //                 $categorySubtotal['purchase-' . $size] = $c_purchase['btl'] . '.' . $c_purchase['peg'];
    //             } else {
    //                 $categorySubtotal['purchase-' . $size] = '';
    //             }
    //         }
    //         foreach ($btlSizes as $size) {
    //             if ($size == $btl_size) {
    //                 $categorySubtotal['sales-' . $size] = $c_sale['btl'] . '.' . $c_sale['peg'];
    //             } else {
    //                 $categorySubtotal['sales-' . $size] = '';
    //             }
    //         }
    //         foreach ($btlSizes as $size) {
    //             if ($size == $btl_size) {
    //                 $categorySubtotal['closingstock-' . $size] = $c_closing['btl'] . '.' . $c_closing['peg'];
    //             } else {
    //                 $categorySubtotal['closingstock-' . $size] = '';
    //             }
    //         }

    //         $data[] = $categorySubtotal;
    //     }

    //     $json = $data;

    //     return response()->json($json);
    // }

    public function YearlyComparisonReport(Request $request)
    {
        $company_id = $request->input('company_id');
        $from_date = date('Y-m-d', strtotime($request->input('from_date')));
        $to_date = date('Y-m-d', strtotime($request->input('to_date')));

        // Validate inputs
        if (is_null($company_id) || is_null($from_date) || is_null($to_date)) {
            return response()->json(['error' => 'Invalid input'], 400);
        }

        // Step 1: Fetch all categories
        $categories = Category::all();

        // Get financial year start for current and previous year
        $allLogTables = $this->getAllLogDataTables();

        $currentYearOpening = $this->calculateOpeningBalances($allLogTables, $company_id, $from_date, $categories);
        $previousYearOpening = $this->calculateOpeningBalances($allLogTables, $company_id, date('Y-m-d', strtotime("$from_date -1 year")), $categories);
        
        // Step 4: Fetch purchase and sales data for both years
        $currentYearPurchase = $this->getPurchaseDataForDateRange($allLogTables, $company_id, $from_date, $to_date, $categories);
        $currentYearSales = $this->getSalesDataForDateRange($allLogTables, $company_id, $from_date, $to_date, $categories);

        $previousYearPurchase = $this->getPurchaseDataForDateRange($allLogTables, $company_id, date('Y-m-d', strtotime("$from_date -1 year")), date('Y-m-d', strtotime("$to_date -1 year")), $categories);
        $previousYearSales = $this->getSalesDataForDateRange($allLogTables, $company_id, date('Y-m-d', strtotime("$from_date -1 year")), date('Y-m-d', strtotime("$to_date -1 year")), $categories);

        // Step 5: Generate daily reports with closing for both years
        $currentYearData = $this->generateDailyReportWithClosing($from_date, $to_date, $currentYearOpening, $currentYearPurchase, $currentYearSales, $categories);
        $previousYearData = $this->generateDailyReportWithClosing(date('Y-m-d', strtotime("$from_date -1 year")), date('Y-m-d', strtotime("$to_date -1 year")), $previousYearOpening, $previousYearPurchase, $previousYearSales, $categories);

        // Step 6: Identify and remove categories with only zero values in both years
        $categoriesToRemove = $this->identifyCategoriesToRemove($categories, $currentYearData, $previousYearData);

        // Remove identified categories from both years
        $this->removeZeroCategories($currentYearData, $categoriesToRemove);
        $this->removeZeroCategories($previousYearData, $categoriesToRemove);

        $final_data = $this->formatYearlyComparisonData($currentYearData, $previousYearData);

        $final_data_with_parent_category = [];

        // Get the parent category data from the database
        $parentCategoriesData = DB::table('categories')
            ->select('id', 'name', 'parent_category_name')
            ->get()
            ->keyBy('name');  // Map category names to parent category names for quick lookup

        if (!empty($final_data)) {
            // Loop through each entry in the final data
            foreach ($final_data as $entry) {
                // Initialize the data structure for the current entry
                $data = [];
                $data['date'] = $entry['Date']; // Store the date

                // Loop through the entry and sum the quantities based on parent category and type
                foreach ($entry as $category => $qty) {
                    // Skip the 'Date' field as it's already handled
                    if ($category == 'Date') continue;

                    // Split the category into parent category and type (Opening, Purchase, Sales, Closing)
                    $categoryParts = explode(' - ', $category);
                    if (count($categoryParts) < 2) continue; // Skip if category doesn't match expected format

                    $categoryName = $categoryParts[0]; // Parent category name
                    $type = $categoryParts[1]; // Type: Opening, Purchase, Sales, Closing

                    // Ensure that the category exists in parentCategoriesData
                    if (isset($parentCategoriesData[$categoryName])) {
                        $parentCategoryName = $parentCategoriesData[$categoryName]->parent_category_name;
                        $key = $parentCategoryName . ' - ' . $type;

                        // Initialize the key if it doesn't exist
                        if (!isset($data[$key])) {
                            $data[$key] = 0;
                        }

                        // Add the quantity to the respective key
                        $data[$key] += $qty;
                    }
                }

                // Push the aggregated data for this entry into the result array
                $final_data_with_parent_category[] = $data;
            }
        }

        $currentYearTotal = [];
        $previousYearTotal = [];

        // Opening and closing data
        $data_for_opening_of_current_financial_year = $final_data_with_parent_category[0];
        $data_for_opening_of_last_financial_year = $final_data_with_parent_category[1];

        $total_entries = count($final_data_with_parent_category);
        $data_for_closing_of_current_financial_year = $final_data_with_parent_category[$total_entries - 2];
        $data_for_closing_of_last_financial_year = $final_data_with_parent_category[$total_entries - 1];

        if (!empty($parentCategoriesData)) {
            // Initialize totals
            $currentYearTotal['date'] = 'Total '. explode('-', $from_date)[0];
            $previousYearTotal['date'] = 'Total '. explode('-', $from_date)[0]-1;
            foreach ($parentCategoriesData as $parentCategory) {
                $parentCategoryName = $parentCategory->parent_category_name;

                $currentYearTotal[$parentCategoryName . ' - Opening'] = $data_for_opening_of_current_financial_year[$parentCategoryName . ' - Opening'] ?? 0;
                $currentYearTotal[$parentCategoryName . ' - Purchase'] = 0;
                $currentYearTotal[$parentCategoryName . ' - Sales'] = 0;
                $currentYearTotal[$parentCategoryName . ' - Closing'] = $data_for_closing_of_current_financial_year[$parentCategoryName . ' - Closing'] ?? 0;

                $previousYearTotal[$parentCategoryName . ' - Opening'] = $data_for_opening_of_last_financial_year[$parentCategoryName . ' - Opening'] ?? 0;
                $previousYearTotal[$parentCategoryName . ' - Purchase'] = 0;
                $previousYearTotal[$parentCategoryName . ' - Sales'] = 0;
                $previousYearTotal[$parentCategoryName . ' - Closing'] = $data_for_closing_of_last_financial_year[$parentCategoryName . ' - Closing'] ?? 0;
            }

            // Process purchase and sales
            foreach ($final_data_with_parent_category as $catVal) {
                $isCurrentYear = str_contains($catVal["date"], explode('-', $from_date)[0]);

                foreach ($catVal as $category => $qty) {
                    if ($category === 'date' || str_contains($category, 'Opening') || str_contains($category, 'Closing')) {
                        continue; // Skip date, opening, and closing categories
                    }

                    // Add quantities for purchases and sales
                    if ($isCurrentYear) {
                        $currentYearTotal[$category] = ($currentYearTotal[$category] ?? 0) + $qty;
                    } else {
                        $previousYearTotal[$category] = ($previousYearTotal[$category] ?? 0) + $qty;
                    }
                }
            }
        }

        array_push($final_data_with_parent_category, $currentYearTotal);
        array_push($final_data_with_parent_category, $previousYearTotal);
        
        return response()->json($final_data_with_parent_category);
    }

    private function identifyCategoriesToRemove($categories, $currentYearData, $previousYearData)
    {
        $categoriesToRemove = [];
        foreach ($categories as $category) {
            $categoryName = $category->name;
            $isAllZero = true;

            // Check if the category has non-zero values in the current year
            $isAllZero = $this->checkCategoryValues($currentYearData, $categoryName);
            
            // Check if the category has non-zero values in the previous year
            if ($isAllZero) {
                $isAllZero = $this->checkCategoryValues($previousYearData, $categoryName);
            }

            if ($isAllZero) {
                $categoriesToRemove[] = $categoryName;
            }
        }
        return $categoriesToRemove;
    }

    private function checkCategoryValues($yearData, $categoryName)
    {
        foreach ($yearData as $dateData) {
            if (isset($dateData['categories'][$categoryName]) &&
                ($dateData['categories'][$categoryName]['opening'] != 0 ||
                $dateData['categories'][$categoryName]['purchase'] != 0 ||
                $dateData['categories'][$categoryName]['sales'] != 0 ||
                $dateData['categories'][$categoryName]['closing'] != 0)
            ) {
                return false; // Non-zero value found
            }
        }
        return true; // All zero
    }

    private function removeZeroCategories(&$yearData, $categoriesToRemove)
    {
        foreach ($yearData as &$dateData) {
            foreach ($categoriesToRemove as $categoryName) {
                unset($dateData['categories'][$categoryName]);
            }
        }
    }

    private function getFinancialYearTablesForComparisonReport($financialYearStart, $to_date)
    {
        $tables = [];
        
        // Start from April 1st of the given financial year
        $current = date('Y-m-d', strtotime("$financialYearStart-04-01"));
        $to_date = date('Y-m-d', strtotime($to_date));

        while ($current <= $to_date) {
            // Format the table name as 'YYYY_MM_log_data'
            $tableName = date('Y_m', strtotime($current)) . '_log_data';

            // Check if the table exists in the database
            if (Schema::hasTable($tableName)) {
                $tables[] = $tableName;
            }

            // Move to the next month
            $current = date('Y-m-d', strtotime("$current +1 month"));
        }

        return $tables;
    }

    private function calculateOpeningBalances($tables, $company_id, $from_date, $categories)
    {
        $openingBalances = [];

        foreach ($categories as $category) {
            $openingQty = 0;

            foreach ($tables as $table) {
                $records = DB::table($table)
                    ->where('company_id', $company_id)
                    ->where('log_date', '<', $from_date)
                    ->select('data')
                    ->get();

                foreach ($records as $record) {
                    $dataEntries = json_decode($record->data, true);

                    foreach ($dataEntries as $entry) {
                        if ($entry['category_id'] == $category->id) {
                            $qty = $entry['qty'];
                            if ($entry['transaction_category'] == 'credit') {
                                $openingQty += $qty;
                            } elseif ($entry['transaction_category'] == 'debit') {
                                $openingQty -= $qty;
                            }
                        }
                    }
                }
            }

            // Store the result for this category
            $openingBalances[$category->name] = $openingQty;
        }

        return $openingBalances;
    }

    private function getPurchaseDataForDateRange($tables, $company_id, $from_date, $to_date, $categories)
    {
        $purchaseData = [];
        $currentDate = $from_date;

        while ($currentDate <= $to_date) {
            $dateData = [];

            foreach ($categories as $category) {
                $purchaseQty = 0;

                foreach ($tables as $table) {
                    $records = DB::table($table)
                        ->where('company_id', $company_id)
                        ->where('log_date', $currentDate)
                        ->select('data')
                        ->get();

                    foreach ($records as $record) {
                        $dataEntries = json_decode($record->data, true);

                        foreach ($dataEntries as $entry) {
                            if ($entry['category_id'] == $category->id)
                            {
                                if($entry['transaction_type'] == 'purchase' || $entry['transaction_type'] == 'opening')
                                {
                                    if($entry['transaction_category'] == 'credit')
                                    {
                                        $purchaseQty += $entry['qty'];
                                    }else{
                                        $purchaseQty -= $entry['qty'];
                                    }
                                }

                                if($entry['transaction_type'] == 'transfer' && $entry['transaction_category'] == 'credit')
                                {
                                    $purchaseQty += $entry['qty'];
                                }
                            }
                        }
                    }
                }

                // Add purchase quantity for the category on the current date
                $dateData[$category->name] = $purchaseQty;
            }

            // Store the data for the current date
            $purchaseData[$currentDate] = $dateData;

            // Move to the next date
            $currentDate = date('Y-m-d', strtotime("$currentDate +1 day"));
        }

        return $purchaseData;
    }

    private function getSalesDataForDateRange($tables, $company_id, $from_date, $to_date, $categories)
    {
        $salesData = [];
        $currentDate = $from_date;

        while ($currentDate <= $to_date) {
            $dateData = [];

            foreach ($categories as $category) {
                $salesQty = 0;

                foreach ($tables as $table) {
                    $records = DB::table($table)
                        ->where('company_id', $company_id)
                        ->where('log_date', $currentDate)
                        ->select('data')
                        ->get();

                    foreach ($records as $record) {
                        $dataEntries = json_decode($record->data, true);
                        
                        foreach ($dataEntries as $entry) {
                            if ($entry['category_id'] == $category->id)
                            {
                                if($entry['transaction_type'] == 'sales')
                                {
                                    if($entry['transaction_category'] == 'debit')
                                    {
                                        $salesQty += $entry['qty'];
                                    }else{
                                        $salesQty -= $entry['qty'];
                                    }
                                }

                                if($entry['transaction_type'] == 'transfer' && $entry['transaction_category'] == 'debit')
                                {
                                    $salesQty += $entry['qty'];
                                }
                            }
                        }
                    }
                }

                // Add purchase quantity for the category on the current date
                $dateData[$category->name] = $salesQty;
            }

            // Store the data for the current date
            $salesData[$currentDate] = $dateData;

            // Move to the next date
            $currentDate = date('Y-m-d', strtotime("$currentDate +1 day"));
        }

        return $salesData;
    }

    private function generateDailyReportWithClosing($from_date, $to_date, $openingBalances, $purchaseData, $salesData, $categories)
    {
        $dailyReport = [];
        $currentDate = $from_date;
        $previousClosingBalances = $openingBalances;

        while ($currentDate <= $to_date) {
            $dateData = ['date' => $currentDate];

            foreach ($categories as $category) {
                $categoryName = $category->name;
                $openingQty = $previousClosingBalances[$categoryName] ?? 0;

                // Get purchase and sales data for the current date
                $purchaseQty = $purchaseData[$currentDate][$categoryName] ?? 0;
                $salesQty = $salesData[$currentDate][$categoryName] ?? 0;

                // Calculate closing quantity
                $closingQty = $openingQty + $purchaseQty - $salesQty;

                // Store data in the report for this category
                $dateData['categories'][$categoryName] = [
                    'opening' => $openingQty,
                    'purchase' => $purchaseQty,
                    'sales' => $salesQty,
                    'closing' => $closingQty,
                ];

                // Update previous closing balances
                $previousClosingBalances[$categoryName] = $closingQty;
            }

            // Add the data for the current date to the report
            $dailyReport[] = $dateData;

            // Move to the next date
            $currentDate = date('Y-m-d', strtotime("$currentDate +1 day"));
        }

        return $dailyReport;
    }

    public function formatYearlyComparisonData($currentYearData, $previousYearData)
    {
        $finalResult = [];

        $dates = array_keys($currentYearData); // Assumes currentYearData and previousYearData have the same dates

        foreach ($dates as $date) {
            $currentYearDateData = $currentYearData[$date];
            $previousYearDateData = $previousYearData[$date];

            // Format for current year's date
            $currentYearFormattedData = ['Date' => date('d-M-Y', strtotime($currentYearDateData['date']))];

            foreach ($currentYearDateData['categories'] as $categoryName => $categoryData) {
                $currentYearFormattedData["{$categoryName} - Opening"] = $categoryData['opening'];
                $currentYearFormattedData["{$categoryName} - Purchase"] = $categoryData['purchase'];
                $currentYearFormattedData["{$categoryName} - Sales"] = $categoryData['sales'];
                $currentYearFormattedData["{$categoryName} - Closing"] = $categoryData['closing'];
            }
            $finalResult[] = $currentYearFormattedData;

            // Format for previous year's date
            $previousYearFormattedData = ['Date' => date('d-M-Y', strtotime($previousYearDateData['date']))];
            foreach ($previousYearDateData['categories'] as $categoryName => $categoryData) {
                $previousYearFormattedData["{$categoryName} - Opening"] = $categoryData['opening'];
                $previousYearFormattedData["{$categoryName} - Purchase"] = $categoryData['purchase'];
                $previousYearFormattedData["{$categoryName} - Sales"] = $categoryData['sales'];
                $previousYearFormattedData["{$categoryName} - Closing"] = $categoryData['closing'];
            }
            $finalResult[] = $previousYearFormattedData;
        }

        return $finalResult;
    }

}
