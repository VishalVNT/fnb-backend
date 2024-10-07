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
        $result = DB::table('brands')
            ->select(
                'purchases.brand_id',
                'invoice_no',
                'invoice_date',
                'categories.name as category_group',
                'brands.name as brand_name',
                'btl_size',
                'purchases.no_btl',
                DB::raw('COALESCE(qty, 0) as qty'),
                DB::raw('COALESCE(mrp, 0) as mrp'),
                DB::raw('COALESCE(total_amount, 0) as total_amount'),
                'vendor_id',
                'suppliers.name as vendor_name'
            )
            ->join('purchases', 'purchases.brand_id', '=', 'brands.id')
            ->join('categories', 'categories.id', '=', 'brands.category_id')
            ->join('suppliers', 'suppliers.id', '=', 'purchases.vendor_id')
            ->where('purchases.status',1)
            ->whereDate('invoice_date', '>=', $from_date)
            ->whereDate('invoice_date', '<=', $to_date)
            ->where('purchases.company_id', $company_id)
            ->orderBy('invoice_date', 'asc')
            ->orderBy('invoice_no', 'asc')
            ->get();


        foreach ($result as $row) {
            if($row->qty > 0){

                $invoiceNo = $row->invoice_no;
                $invoiceDate = $row->invoice_date;
                $categoryGroup = $row->category_group;
                $brandName = $row->brand_name;
                $btlSize = $row->btl_size;
                $quantity = $row->qty;
                $rate = $row->mrp;

                if($rate == 0 || $rate == ''){
                    $rateDetails = DB::table('stocks')->where('company_id', $request->company_id)->where('brand_id', $row->brand_id)->select('btl_selling_price')->orderBy('id','desc')->first();
                    if(!empty($rateDetails)){
                        $rate = $rateDetails->btl_selling_price;
                    }else{
                        $rate = 0;
                    }
                }
                $amount = $rate * $row->no_btl;
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
                            ->select('id as salesId','brand_id', 'sale_price', 'category_id', 'no_btl','no_peg', 'sale_date', DB::raw('SUM(qty) as qty'))
                            ->where('category_id', $id)
                            ->whereDate('sale_date', '>=', $request->from_date)
                            ->whereDate('sale_date', '<=', $request->to_date)
                            ->where('company_id', $company_id)
                            ->where('sales.status', 1)
                            ->groupBy('brand_id', 'sale_date')
                            ->orderBy('sale_date', 'asc')
                            ->get();

            if(!empty($salesData)){
                foreach($salesData as $key => $value){
                    $allQty = DB::table('sales')
                                ->select('id as salesId','brand_id', 'sale_price', 'category_id', 'no_btl','no_peg', 'sale_date', 'qty')
                                ->where('category_id', $id)
                                ->whereDate('sale_date', '>=', $request->from_date)
                                ->whereDate('sale_date', '<=', $request->to_date)
                                ->where('company_id', $company_id)
                                ->where('sales.status', 1)
                                ->where('id', '!=', $value->salesId)
                                ->where('sale_date', $value->sale_date)
                                ->where('brand_id', $value->brand_id)
                                ->orderBy('sale_date', 'asc')
                                ->get();

                    if(!empty($allQty)){
                        $qty = (int)$value->qty;
                        $btlQty = (int)$value->no_btl;
                        $pegQty = (int)$value->no_peg;
                        foreach($allQty as $key2 => $value2){
                            $qty = $qty + (int)$value2->qty;
                            $btlQty = $btlQty + (int)$value2->no_btl;
                            $pegQty = $pegQty + (int)$value2->no_peg;
                        }
                        $salesData[$key]->qty = $qty;
                        $salesData[$key]->no_btl = $btlQty;
                        $salesData[$key]->no_peg = $pegQty;
                    }
                }
            }
            foreach ($salesData as $sale) {
                $cat = array(
                    'category_name' => $name,
                    'sales_date' => '',
                    'brand_name' => '',
                    'btl_size' => '',
                    //	'peg_size' => '',
                    'qty_inpeg' => '',
                    //'btl_selling_price' => '',
                    // 'peg_selling_price' => '',
                    'rate' => '',
                    'amount' => ''
                );

                $brandId = $sale->brand_id;
                $salesDate = $sale->sale_date;
                $sales_qty = $sale->qty;
                $sale_price = $sale->sale_price;

                $rate = 0;

                $rateDataFromStocks = DB::table('stocks')->where('company_id', $company_id)->where('brand_id', $brandId)->select('peg_selling_price')->orderBy('id', 'desc')->first();
                
                if(!empty($rateDataFromStocks)){
                    $rate = $rateDataFromStocks->peg_selling_price;
                }else{
                    $rate = 0;
                }
                
                //	$data  = getrateamount($brandId);
                
                
                
                
                /*	$brandDetails = DB::table('brands')
                ->where('id', $brandId)
                ->first(); */
                
                $brandDetails = DB::table('brands')
                ->join('stocks', 'stocks.brand_id', '=', 'brands.id')
                ->where('brands.id', $brandId)
                ->first();

                $no_btl = $sale->no_btl;
                $no_peg = (($brandDetails->btl_size/$brandDetails->peg_size) * $sale->no_btl) + $sale->no_peg;

                if($no_peg > 0){

                    if ($brandDetails) {
    
                        if (!in_array($name, $cat_array)) {
                            array_push($json, $cat);
                        }
                        array_push($cat_array, $name);

                        // old code to get rate and amount
                        // $price = getrateamount($brandId);
                        // $pegprice = $price['pegprice'];
                        // $amount = $price['amount'];

                        // new code to get rate and amount by Vishal as per client requirement on 12-09-2024
                        $amount = intval($no_peg) * $rate; 
    
    
                        $btl_size = $brandDetails->btl_size;
                        /*$peg_size = $brandDetails->peg_size;
                        $btl_selling_price = $brandDetails->btl_selling_price ?? '0'; 
                        $peg_selling_price = $brandDetails->peg_selling_price ?? '0';
                        
                        $peg_count = intval($btl_size / $peg_size);  
                        $pegprice = intval($btl_selling_price / $peg_count);  
                        $amount = $peg_count * $peg_selling_price; */
    
                        $brand = array(
                            'category_name' => '',
                            'sales_date' => $salesDate,
                            'brand_name' => $brandDetails->name,
                            'btl_size' => $btl_size,
                            //	'peg_size' => $peg_size,
                            'qty_inpeg' => intval($no_peg),
                            //'btl_selling_price' => $btl_selling_price,
                            //'peg_selling_price' =>$peg_selling_price,
                            // 'rate' => $pegprice,
                            // 'amount' => $amount
                            'rate' => $rate,
                            'amount' => $amount
                        );
    
                        array_push($json, $brand);
                    }
                }
            }
        }

        return json_encode($json);
    }
    public function StockRegisterReport(Request $request)
    {
        $json = [];
        $company_id = $request->company_id;
        $categories = Category::where('status', 1)->select('id', 'name')->get();
        $cat_array = array();

        foreach ($categories as $category) {
            $name = $category->name;
            $id = $category->id;
            $categoryData = [];

            $stockData = DB::table('daily_opening_closing_log')
                            ->where('category_id', $id)
                            ->where('company_id', $company_id)
                            // ->whereDate('log_date', '>=', $request->from_date)
                            ->whereDate('log_date', '<=', $request->to_date)
                            ->groupBy('brand_id')
                            ->join('brands','daily_opening_closing_log.brand_id', 'brands.id')
                            ->join('categories','brands.category_id', 'categories.id')
                            ->get();

            foreach ($stockData as $stock) {
                $brandId = $stock->brand_id;
                $brandDetails = DB::table('brands')->where('id', $brandId)->first();

                if ($brandDetails) {
                    $btl_size = $brandDetails->btl_size;

                    // Initialize or increment values for each bottle size
                    if (!isset($categoryData[$btl_size])) {
                        $categoryData[$btl_size] = [
                            'category_name' => $name,
                            'brands' => [],
                            'opening_balance' => 0,
                            'purchase' => 0,
                            'transfer' => 0,
                            'total' => 0,
                            'sales' => 0,
                            'closing_balance' => 0,
                        ];
                    }

                    $allCreditQty = 0;
                    $allDebitQty = 0;
                    $opening_qty = 0;
                    $allCreditQty = DB::table('daily_opening_closing_log')
                                    ->where('transaction_type', 'credit')
                                    ->where('company_id', $company_id)
                                    ->where('brand_id', $brandId)
                                    ->whereDate('log_date', '<', date('Y-m-d', strtotime($request->from_date)))
                                    ->sum('qty');

                    $allDebitQty = DB::table('daily_opening_closing_log')
                                    ->where('transaction_type', 'debit')
                                    ->where('company_id', $company_id)
                                    ->where('brand_id', $brandId)
                                    ->whereDate('log_date', '<', date('Y-m-d', strtotime($request->from_date)))
                                    ->sum('qty');

                                            
                    $opening_qty = (int)$allCreditQty - (int)$allDebitQty;
                    $purchase_qty = $this->getPurchaseQty($company_id, $brandId, $request->from_date, $request->to_date);
                    $sales_qty = $this->getSalesQty($company_id, $brandId, $request->from_date, $request->to_date);

                    // transfer received qty
                    $transfer_received_qty = DB::table('daily_opening_closing_log')
                                                ->where('transaction_type', 'credit')
                                                ->where('transaction_category', 'transfer')
                                                ->where('company_id', $company_id)
                                                ->where('brand_id', $brandId)
                                                ->whereDate('log_date', '>=', date('Y-m-d', strtotime($request->from_date)))
                                                ->whereDate('log_date', '<=', date('Y-m-d', strtotime($request->to_date)))
                                                ->where('status', 'active')
                                                ->sum('qty');

                    // transfer sent qty
                    $transfer_sent_qty = DB::table('daily_opening_closing_log')
                                                ->where('transaction_type', 'debit')
                                                ->where('transaction_category', 'transfer')
                                                ->where('company_id', $company_id)
                                                ->where('brand_id', $brandId)
                                                ->whereDate('log_date', '>=', date('Y-m-d', strtotime($request->from_date)))
                                                ->whereDate('log_date', '<=', date('Y-m-d', strtotime($request->to_date)))
                                                ->where('status', 'active')
                                                ->sum('qty');

                    if($transfer_received_qty >= $transfer_sent_qty){
                        $transaction_total_qty = $transfer_received_qty - $transfer_sent_qty;
                    }else{
                        $transaction_total_qty = abs($transfer_received_qty - $transfer_sent_qty);
                    }
                    $transfer_received_stock = convertBtlPeg($transfer_received_qty, $btl_size, $brandDetails->peg_size);
                    $transfer_sent_stock = convertBtlPeg($transfer_sent_qty, $btl_size, $brandDetails->peg_size);
                    $totalTransferStock = convertBtlPeg($transaction_total_qty, $btl_size, $brandDetails->peg_size);
                    $opening_stock = convertBtlPeg($opening_qty, $btl_size, $brandDetails->peg_size);
                    $purchase_stock = convertBtlPeg($purchase_qty, $btl_size, $brandDetails->peg_size);
                    $sales_stock = convertBtlPeg($sales_qty, $btl_size, $brandDetails->peg_size);

                    $totalInMl = $opening_qty + $purchase_qty + $transfer_received_qty;
                    $closing_balance = $totalInMl - $sales_qty - $transfer_sent_qty;
                    $closing_balance_in_btl_peg = convertBtlPeg($closing_balance, $btl_size, $brandDetails->peg_size);
                    
                    $transferReceived = $transfer_received_stock['btl'] . "." . $transfer_received_stock['peg'];
                    $transferSent = $transfer_sent_stock['btl'] . "." . $transfer_sent_stock['peg'];
                    $totalTransfer = $totalTransferStock['btl'] . "." . $totalTransferStock['peg'];
                    $opening_balance = $opening_stock['btl'] . "." . $opening_stock['peg'];
                    $purchase = $purchase_stock['btl'] . "." . $purchase_stock['peg'];
                    $sales = $sales_stock['btl'] . "." . $sales_stock['peg'];
                    $final_closing_balance = $closing_balance_in_btl_peg['btl'] . "." . $closing_balance_in_btl_peg['peg'];

                    $total = floatval($opening_balance) + floatval($purchase) + floatval($transferReceived) - floatval($transferSent);
                    


                    // Update the brand details
                    $brand = [
                        'category_name' => '',
                        'brand_name' => $brandDetails->name,
                        'btl_size' => $btl_size,
                        'opening_balance' => $opening_balance,
                        'purchase' => $purchase,
                        'transfer' => $transfer_received_qty >= $transfer_sent_qty ? $totalTransfer : -$totalTransfer,
                        'total' => $total,
                        'sales' => $sales,
                        'closing_balance' => $final_closing_balance
                    ];

                    // Push brand to the array
                    $categoryData[$btl_size]['brands'][] = $brand;

                    // Update subtotals
                    $categoryData[$btl_size]['opening_balance'] += floatval($opening_balance);
                    $categoryData[$btl_size]['purchase'] += floatval($purchase);
                    if($transfer_received_qty >= $transfer_sent_qty){
                        $categoryData[$btl_size]['transfer'] += floatval($totalTransfer);
                    }else{
                        $categoryData[$btl_size]['transfer'] -= floatval($totalTransfer);
                    }
                    $categoryData[$btl_size]['total'] += floatval($total);
                    $categoryData[$btl_size]['sales'] += floatval($sales);
                    $categoryData[$btl_size]['closing_balance'] += floatval($final_closing_balance);
                }
            }

            $currentIndex = 0;

            // Add the subtotal for each bottle size
            foreach ($categoryData as $btl_size => $data) {
                $currentIndex++;
                // Push category row if not added
                if (!in_array($data['category_name'], $cat_array)) {
                    array_push($json, [
                        'category_name' => $data['category_name'],
                        'brand_name' => '',
                        'btl_size' => '',
                        'opening_balance' => '',
                        'purchase' => '',
                        'transfer' => '',
                        'total' => '',
                        'sales' => '',
                        'closing_balance' => ''
                    ]);
                    array_push($cat_array, $data['category_name']);
                }

                // Push all brand data
                foreach ($data['brands'] as $brand) {
                    array_push($json, $brand);
                }

                $category_id = DB::table('categories')->where('name', $data['category_name'])->select('id')->first();

                $peg_size = DB::table('brands')->where('category_id',$category_id->id)->select('peg_size')->first();

                if(!empty($peg_size)){

                    $salesCalculationInPeg = $data['sales'];
                    $transferCalculationInPeg = $data['transfer'];
                    $closingBalanceCalculationInPeg = $data['closing_balance'];

                    $sales_btl_and_peg = explode('.', $salesCalculationInPeg);
                    $transfer_btl_and_peg = explode('.', $transferCalculationInPeg);
                    $closing_btl_and_peg = explode('.', $closingBalanceCalculationInPeg);

                    $salesBtl = $sales_btl_and_peg[0];

                    if(!empty($sales_btl_and_peg[1])){
                        $salesPeg = $sales_btl_and_peg[1];
                    }else{
                        $salesPeg = 0;
                    }

                    $transferBtl = $transfer_btl_and_peg[0];

                    if(!empty($transfer_btl_and_peg[1])){
                        $transferPeg = $transfer_btl_and_peg[1];
                    }else{
                        $transferPeg = 0;
                    }

                    $closingBtl = $closing_btl_and_peg[0];

                    if(!empty($closing_btl_and_peg[1])){
                        $closingPeg = $closing_btl_and_peg[1];
                    }else{
                        $closingPeg = 0;
                    }

                    $totalSalesQtyInMl = $btl_size * $salesBtl + $peg_size->peg_size * $salesPeg;
                    $totalTransferQtyInMl = $btl_size * $transferBtl + $peg_size->peg_size * $transferPeg;
                    $totalClosingQtyInMl = $btl_size * $closingBtl + $peg_size->peg_size * $closingPeg;

                    $salesQtyInBtlPeg = convertBtlPeg($totalSalesQtyInMl, $btl_size, $peg_size->peg_size);
                    $transferQtyInBtlPeg = convertBtlPeg($totalTransferQtyInMl, $btl_size, $peg_size->peg_size);
                    $closingQtyInBtlPeg = convertBtlPeg($totalClosingQtyInMl, $btl_size, $peg_size->peg_size);
                    
                    $salesCalculationInPeg = $salesQtyInBtlPeg['btl'] . "." . $salesQtyInBtlPeg['peg'];
                    $transferCalculationInPeg = $transferQtyInBtlPeg['btl'] . "." . $transferQtyInBtlPeg['peg'];
                    $closingBalanceCalculationInPeg = $closingQtyInBtlPeg['btl'] . "." . $closingQtyInBtlPeg['peg'];
                }else{
                    $salesCalculationInPeg = $data['sales'];
                    $transferCalculationInPeg = $data['transfer'];
                    $closingBalanceCalculationInPeg = $data['closing_balance'];
                }
                // Push subtotal for current bottle size
                array_push($json, [
                    'category_name' => '',
                    'brand_name' => 'SUBTOTAL (' . $btl_size . 'ml)',
                    'btl_size' => $btl_size,
                    'opening_balance' => $data['opening_balance'],
                    'purchase' => $data['purchase'],
                    'transfer' => $transferCalculationInPeg,
                    'total' => $data['total'],
                    'sales' => $salesCalculationInPeg,
                    'closing_balance' => $closingBalanceCalculationInPeg,
                ]);

                $totalItems = count($categoryData);

                if($currentIndex !== $totalItems){
                    array_push($json, [
                        'category_name' => $data['category_name'],
                        'brand_name' => '',
                        'btl_size' => '',
                        'opening_balance' => '',
                        'purchase' => '',
                        'transfer' => '',
                        'total' => '',
                        'sales' => '',
                        'closing_balance' => ''
                    ]);
                }

            }
        }
        return json_encode($json);
    }

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

        // Fetch all categories that are active
        $categories = Category::where(['status' => 1])->get();

        // Collect all unique category names
        $allCategories = $categories->pluck('name', 'id')->toArray();

        if (!empty($categories)) {
            foreach ($categories as $key => $value) {
                // Fetch sales data based on the category and date range
                $sales = DB::table('sales')
                            ->select(
                                DB::raw('COALESCE(SUM(qty), 0) as totalSaleQty'),
                                'sales.category_id',
                                'categories.name',
                                'sales.brand_id',
                                'sales.sale_date'
                            )
                            ->join('categories', 'categories.id', '=', 'sales.category_id')
                            ->where('sales.company_id', $company_id)
                            ->where('sales.status', 1)
                            ->where('categories.status', 1)
                            ->where('sales.category_id', $value->id)
                            ->whereDate('sales.sale_date', '>=', $fromDate)
                            ->whereDate('sales.sale_date', '<=', $toDate)
                            ->groupBy('sales.category_id', 'categories.name', 'sales.sale_date')
                            ->get();

                foreach ($sales as $sale) {
                    if (!empty($sale)) {
                        // Fetch the selling price for both bottle and peg
                        $peg_and_btl_selling_price = DB::table('stocks')
                                                        ->where('company_id', $company_id)
                                                        ->where('brand_id', $sale->brand_id)
                                                        ->where('category_id', $value->id)
                                                        ->select('btl_selling_price', 'peg_selling_price')
                                                        ->orderBy('id', 'desc')
                                                        ->first();

                        if (!empty($peg_and_btl_selling_price)) {
                            // Fetch bottle and peg size to calculate the total price
                            $btl_peg_size = DB::table('brands')->where('id', $sale->brand_id)->select('btl_size', 'peg_size')->first();

                            if (!empty($btl_peg_size)) {
                                $stockInBtlPeg = convertBtlPeg((int)$sale->totalSaleQty, $btl_peg_size->btl_size, $btl_peg_size->peg_size);
                                $sale_price = intval($peg_and_btl_selling_price->btl_selling_price) * intval($stockInBtlPeg['btl']) 
                                            + intval($peg_and_btl_selling_price->peg_selling_price) * intval($stockInBtlPeg['peg']);
                            }
                        }

                        // Assign data for each sale date and category
                        $category_name = $sale->name;
                        $sale_date = $sale->sale_date;

                        if (!isset($data[$sale_date])) {
                            $data[$sale_date] = [];
                        }

                        if (!isset($data[$sale_date][$category_name])) {
                            $data[$sale_date][$category_name] = 0;
                        }

                        $data[$sale_date][$category_name] += $sale_price;
                    }
                }
            }
        }

        // Ensure all dates have all categories and initialize with 0 if not present
        foreach ($data as $sale_date => $values) {
            foreach ($allCategories as $category_name) {
                if (!isset($data[$sale_date][$category_name])) {
                    $data[$sale_date][$category_name] = 0;
                }
            }
        }

        // Remove rows (dates) where all category sales prices are 0
        $data = array_filter($data, function ($values) {
            return array_sum($values) > 0;
        });

        // Calculate totals and remove categories with 0 sales across all dates
        $total = [];
        $categoryTotals = array_fill_keys(array_values($allCategories), 0);

        foreach ($data as $sale_date => $values) {
            // Sort values to maintain order of categories
            ksort($values);

            // Track categories with non-zero values
            foreach ($values as $category_name => $sale_price) {
                if ($sale_price > 0) {
                    $categoryTotals[$category_name] += (int)$sale_price;
                }
            }
        }

        // Remove categories with no sales data across all dates
        $categoryTotals = array_filter($categoryTotals, function ($total) {
            return $total > 0;
        });

        // Rebuild data with remaining categories only
        $finalData = [];
        foreach ($data as $sale_date => $values) {
            $filteredValues = array_intersect_key($values, $categoryTotals);

            if (!empty($filteredValues)) {
                // Replace 0 with an empty string and format sale prices to 2 decimal places
                $filteredValues = array_map(function ($value) {
                    return $value > 0 ? number_format($value, 2, '.', '') : '';
                }, $filteredValues);

                // Create the row for each date
                $entry = ['' => $sale_date] + $filteredValues;
                $entry['Total'] = number_format(array_sum(array_map('intval', $filteredValues)), 2, '.', '');  // Vertical total (total per date)
                $finalData[] = $entry;
            }
        }

        // Add the horizontal total row if there are remaining categories
        if (!empty($categoryTotals)) {
            // Sort totals to match the order of categories
            ksort($categoryTotals);

            $totalEntry = ['' => 'Total'] + array_map(function ($value) {
                return $value > 0 ? number_format($value, 2, '.', '') : '';
            }, $categoryTotals);
            $totalEntry['Total'] = number_format(array_sum($categoryTotals), 2, '.', '');  // Grand total across all categories and dates
            $finalData[] = $totalEntry;
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

            // Get opening logs once for the entire category
            $openingLogs = DB::table('daily_opening_closing_log')
                ->where('company_id', $company_id)
                ->whereIn('brand_id', $brandIds)
                ->whereDate('log_date', '<', $fromDate)
                ->where('transaction_category', '!=', 'transfer')
                ->select('brand_id', 'transaction_type', DB::raw('SUM(qty) as qty'))
                ->groupBy('brand_id', 'transaction_type')
                ->get();

            foreach ($btlSizes as $btl_size) {
                $openSum = $purchaseSum = $saleSum = $closingSum = 0;

                foreach ($categoryBrands->where('btl_size', $btl_size) as $brand) {
                    $brandId = $brand->id;

                    // Calculate opening stock from the log table
                    $brandCreditQty = $openingLogs->where('brand_id', $brandId)->where('transaction_type', 'credit')->sum('qty');
                    $brandDebitQty = $openingLogs->where('brand_id', $brandId)->where('transaction_type', 'debit')->sum('qty');
                    $open = (int)$brandCreditQty - (int)$brandDebitQty;
                    $openSum += $open;

                    // Get purchase data for the brand
                    $purchaseQty = isset($purchasesData[$brandId]) ? $purchasesData[$brandId]->qty : 0;
                    $purchaseSum += $purchaseQty;
                    $total = $open + $purchaseQty;

                    // Get sales data for the brand
                    $saleQty = isset($salesData[$brandId]) ? $salesData[$brandId]->qty : 0;
                    $saleSum += $saleQty;

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
                $openSumFinal = $openSum ? convertBtlPeg($openSum, $btl_size, $peg_size) : ['btl' => 0, 'peg' => 0];
                $purchaseSumFinal = $purchaseSum ? convertBtlPeg($purchaseSum, $btl_size, $peg_size) : ['btl' => 0, 'peg' => 0];
                $totalSumFinal = ($openSum + $purchaseSum) ? convertBtlPeg($openSum + $purchaseSum, $btl_size, $peg_size) : ['btl' => 0, 'peg' => 0];
                $saleSumFinal = $saleSum ? convertBtlPeg($saleSum, $btl_size, $peg_size) : ['btl' => 0, 'peg' => 0];
                $closingSumFinal = $closingSum ? convertBtlPeg($closingSum, $btl_size, $peg_size) : ['btl' => 0, 'peg' => 0];

                // Add the calculated data
                $data['opening'][$category->name . '-' . $btl_size] = $openSumFinal['btl'] . '.' . $openSumFinal['peg'];
                $data['purchase'][$category->name . '-' . $btl_size] = $purchaseSumFinal['btl'] . '.' . $purchaseSumFinal['peg'];
                $data['total'][$category->name . '-' . $btl_size] = $totalSumFinal['btl'] . '.' . $totalSumFinal['peg'];
                $data['sale'][$category->name . '-' . $btl_size] = $saleSumFinal['btl'] . '.' . $saleSumFinal['peg'];
                $data['closing'][$category->name . '-' . $btl_size] = $closingSumFinal['btl'] . '.' . $closingSumFinal['peg'];
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
            ->select('id', 'category_id', 'btl_size')
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

        $salesData = Sales::where('company_id', $company_id)
            ->whereBetween('sale_date', [$fromDate, $toDate])
            ->where('status', 1)
            ->select('brand_id', DB::raw('COALESCE(SUM(qty), 0) as qty'), 'sale_date')
            ->groupBy('sale_date', 'brand_id')
            ->get()
            ->groupBy('sale_date');

        $previousClosing = [];

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

                // Get opening logs once for the entire category
                $openingLogs = DB::table('daily_opening_closing_log')
                    ->where('company_id', $company_id)
                    ->whereIn('brand_id', $brandIds)
                    ->whereDate('log_date', '<', $currentDate)
                    ->select('brand_id', 'transaction_type', DB::raw('SUM(qty) as qty'))
                    ->groupBy('brand_id', 'transaction_type')
                    ->get();

                foreach ($btlSizes as $btl_size) {
                    $openSum = $purchaseSum = $saleSum = $closingSum = 0;

                    foreach ($categoryBrands->where('btl_size', $btl_size) as $brand) {
                        $brandId = $brand->id;

                        // Calculate opening stock
                        $brandCreditQty = $openingLogs->where('brand_id', $brandId)->where('transaction_type', 'credit')->sum('qty');
                        $brandDebitQty = $openingLogs->where('brand_id', $brandId)->where('transaction_type', 'debit')->sum('qty');
                        $open = (int)$brandCreditQty - (int)$brandDebitQty;

                        $openSum += $open;

                        // Get purchase and sale data for the brand
                        $purchaseQty = isset($purchasesData[$currentDate]) ? $purchasesData[$currentDate]->where('brand_id', $brandId)->sum('qty') : 0;
                        $purchaseSum += $purchaseQty;
                        $total = $open + $purchaseQty;

                        $saleQty = isset($salesData[$currentDate]) ? $salesData[$currentDate]->where('brand_id', $brandId)->sum('qty') : 0;

                        $saleSum += $saleQty;

                        // Calculate closing stock
                        $closing = $total - $saleQty;
                        $closingSum += $closing;
                    }

                    // Skip if all sums are zero
                    if ($openSum == 0 && $purchaseSum == 0 && $saleSum == 0 && $closingSum == 0) {
                        continue;
                    }

                    $peg_size = DB::table('brands')->where('category_id', $category->id)->where('btl_size', $btl_size)->select('peg_size')->first();

                    $openSumFinal = $openSum ? convertBtlPeg($openSum, $btl_size, $peg_size->peg_size) : ['btl' => 0, 'peg' => 0];
                    $purchaseSumFinal = $purchaseSum ? convertBtlPeg($purchaseSum, $btl_size, $peg_size->peg_size) : ['btl' => 0, 'peg' => 0];
                    $totalSumFinal = ($openSum + $purchaseSum) ? convertBtlPeg($openSum + $purchaseSum, $btl_size, $peg_size->peg_size) : ['btl' => 0, 'peg' => 0];
                    $saleSumFinal = $saleSum ? convertBtlPeg($saleSum, $btl_size, $peg_size->peg_size) : ['btl' => 0, 'peg' => 0];
                    $closingSumFinal = $closingSum ? convertBtlPeg($closingSum, $btl_size, $peg_size->peg_size) : ['btl' => 0, 'peg' => 0];

                    // Add the calculated data
                    $data['opening'][$category->short_name . '-' . $btl_size] = ($openSumFinal['btl'] || $openSumFinal['peg']) ? $openSumFinal['btl'] . '.' . $openSumFinal['peg'] : '0.00';
                    $data['purchase'][$category->short_name . '-' . $btl_size] = ($purchaseSumFinal['btl'] || $purchaseSumFinal['peg']) ? $purchaseSumFinal['btl'] . '.' . $purchaseSumFinal['peg'] : '0.00';
                    $data['total'][$category->short_name . '-' . $btl_size] = ($totalSumFinal['btl'] || $totalSumFinal['peg']) ? $totalSumFinal['btl'] . '.' . $totalSumFinal['peg'] : '0.00';
                    $data['sale'][$category->short_name . '-' . $btl_size] = ($saleSumFinal['btl'] || $saleSumFinal['peg']) ? $saleSumFinal['btl'] . '.' . $saleSumFinal['peg'] : '0.00';
                    $data['closing'][$category->short_name . '-' . $btl_size] = ($closingSumFinal['btl'] || $closingSumFinal['peg']) ? $closingSumFinal['btl'] . '.' . $closingSumFinal['peg'] : '0.00';

                    $previousClosing[$category->short_name][$btl_size] = $closingSumFinal;

                    if ($purchaseSum || $saleSum) {
                        $hasPurchaseOrSaleData = true;
                    }
                }
            }

            if ($hasPurchaseOrSaleData) {
                $json[] = $data['opening'];
                $json[] = $data['purchase'];
                
                // Add TP No information
                $tpNos = isset($purchasesData[$currentDate]) ? $purchasesData[$currentDate]->pluck('invoice_no')->unique()->implode(', ') : '';
                $json[] = [$currentDate => 'TP No', 'TP No' => $tpNos ?: '0.00'];
                
                $json[] = $data['total'];
                $json[] = $data['sale'];
                $json[] = $data['closing'];
            }

            // Move to the next date
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
            $categories = Category::where('status', 1)->get();
            foreach ($categories as $category) {
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
                        [$opening] = DailyOpening::where(['brand_id' => $brand['id'], 'company_id' => $company_id, ['date', 'like', '%-' . $newMonth[0] . '-' . $newMonth[1]]])
                            ->select(DB::raw('SUM(COALESCE(qty, 0)) as qty'))
                            ->get();

                        if ($opening)
                            $open = $opening['qty'];
                        else
                            $open = 0;
                        $openSum = $openSum + $open;
                        //purchase section
                        [$purchase] = purchase::where(['brand_id' => $brand['id'], 'company_id' => $company_id, ['invoice_date', 'like', '%-' . $newMonth[0] . '-' . $newMonth[1]]])
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
                        [$sales] = Sales::where(['brand_id' => $brand['id'], 'company_id' => $company_id, ['sale_date', 'like', '%-' . $newMonth[0] . '-' . $newMonth[1]]])
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
            $categories = Category::where('status', 1)->get();
            foreach ($categories as $category) {
                $btls = Brand::where(['category_id' => $category->id])->orderBy('btl_size', 'DESC')->groupBy(DB::raw("btl_size"))->get(); // get unique bottle size of that category
                foreach ($btls as $key2 => $btl_size) {
                    $brands = Brand::where(['category_id' => $category['id'], 'btl_size' => $btl_size['btl_size']])->get(); // get brand of that category
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
                        [$opening] = DailyOpening::where(['brand_id' => $brand['id'], 'company_id' => $company_id])
                            ->whereMonth('date', $newMonth[0])
                            ->whereYear('date', $newMonth[1])
                            ->select(DB::raw('SUM(COALESCE(qty, 0)) as qty'))
                            ->get();

                        if ($opening)
                            $open = $opening['qty'];
                        else
                            $open = 0;
                        $openSum = $openSum + $open;
                        // current year opening section end
                        //current year purchase section
                        [$purchase] = purchase::where(['brand_id' => $brand['id'], 'compa4ny_id' => $company_id])
                            ->whereMonth('invoice_date', $newMonth[0])
                            ->whereYear('invoice_date', $newMonth[1])
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
                        //current year purchase section end 

                        // current year sales start
                        [$sales] = Sales::where(['brand_id' => $brand['id'], 'company_id' => $company_id])
                            ->select(DB::raw('SUM(COALESCE(qty, 0)) as qty'))
                            ->whereMonth('sale_date', $newMonth[0])
                            ->whereYear('sale_date', $newMonth[1])
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
                        // current year sales end



                        // last year opening section
                        [$opening2] = DailyOpening::where(['brand_id' => $brand['id'], 'company_id' => $company_id])
                            ->whereMonth('date', $newMonth[0])
                            ->whereYear('date', $newMonth[1] - 1)
                            ->select(DB::raw('SUM(COALESCE(qty, 0)) as qty'))
                            ->get();

                        if ($opening2)
                            $open2 = $opening2['qty'];
                        else
                            $open2 = 0;
                        $openSum2 = $openSum2 + $open2;
                        // last year opening section end
                        //last year purchase section start 
                        [$purchase2] = purchase::where(['brand_id' => $brand['id'], 'company_id' => $company_id])
                            ->whereMonth('invoice_date', $newMonth[0])
                            ->whereYear('invoice_date', $newMonth[1] - 1)
                            ->select(DB::raw('SUM(COALESCE(qty, 0)) as qty'))
                            ->get();
                        if ($purchase2)
                            $purchaseQty2 = $purchase2['qty'];
                        else
                            $purchaseQty2 = 0;
                        $purchaseSum2 = $purchaseSum2 + $purchaseQty2;
                        //total section
                        $total2 = $purchaseQty2 + $open2;
                        if ($total2)
                            $totalSum2 = $totalSum2 + $total2;
                        //last year purchase section end
                        // last year sales start

                        [$sales2] = Sales::where(['brand_id' => $brand['id'], 'company_id' => $company_id])
                            ->select(DB::raw('SUM(COALESCE(qty, 0)) as qty'))
                            ->whereMonth('sale_date', $newMonth[0])
                            ->whereYear('sale_date', $newMonth[1] - 1)
                            ->get();
                        if ($sales2)
                            $saleQty2 = $sales2['qty'];
                        else
                            $saleQty2 = 0;
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
        $data = [];
        $subtotals = [];
        $categories = Category::where(['status' => 1])->get();
        $company_id = $request->company_id;
        $currentDate = $request->to_date;

        // Retrieve all unique btl_size values from the Brand table
        $btlSizes = Brand::distinct()->pluck('btl_size')->toArray();


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
                        $categoryData['opening-' . $size] = $open_btl['btl'] . '.' . $open_btl['peg'];
                    } else {
                        $categoryData['opening-' . $size] = '';
                    }
                }
                foreach ($btlSizes as $size) {
                    if ($size == $btl_size) {
                        $categoryData['purchase-' . $size] = $purchase_btl['btl'] . '.' . $purchase_btl['peg'];
                    } else {
                        $categoryData['purchase-' . $size] = '';
                    }
                }
                foreach ($btlSizes as $size) {
                    if ($size == $btl_size) {
                        $categoryData['sales-' . $size] = $sale_btl['btl'] . '.' . $sale_btl['peg'];
                    } else {
                        $categoryData['sales-' . $size] = '';
                    }
                }
                foreach ($btlSizes as $size) {
                    if ($size == $btl_size) {
                        $categoryData['closingstock-' . $size] = $closing_btl['btl'] . '.' . $closing_btl['peg'];
                    } else {
                        $categoryData['closingstock-' . $size] = '';
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
                    $categorySubtotal['opening-' . $size] = $c_open['btl'] . '.' . $c_open['peg'];
                } else {
                    $categorySubtotal['opening-' . $size] = '';
                }
            }
            foreach ($btlSizes as $size) {
                if ($size == $btl_size) {
                    $categorySubtotal['purchase-' . $size] = $c_purchase['btl'] . '.' . $c_purchase['peg'];
                } else {
                    $categorySubtotal['purchase-' . $size] = '';
                }
            }
            foreach ($btlSizes as $size) {
                if ($size == $btl_size) {
                    $categorySubtotal['sales-' . $size] = $c_sale['btl'] . '.' . $c_sale['peg'];
                } else {
                    $categorySubtotal['sales-' . $size] = '';
                }
            }
            foreach ($btlSizes as $size) {
                if ($size == $btl_size) {
                    $categorySubtotal['closingstock-' . $size] = $c_closing['btl'] . '.' . $c_closing['peg'];
                } else {
                    $categorySubtotal['closingstock-' . $size] = '';
                }
            }

            $data[] = $categorySubtotal;
        }

        $json = $data;

        return response()->json($json);
    }
}
