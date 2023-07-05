<?php

namespace App\Http\Controllers;

use App\Models\Brand;
use App\Models\Category;
use App\Models\LinkCompany;
use App\Models\physical_history;
use App\Models\Stock;
use App\Models\Subcategory;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

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

                    [$data_daily_opening] = DB::table('daily_openings')
                        ->select(DB::raw('SUM(qty) AS qty'))
                        ->whereIn('company_id', $comArray)
                        ->where('date', '=', date('Y-m-d', strtotime($request->from_date)))
                        ->where('brand_id', $brandListName['id'])
                        ->get();
                    $qty = !empty($data_daily_opening->qty) ? $data_daily_opening->qty : '0';
                    $openSum = $openSum + $qty;
                    [$balance] = DB::table('purchases')
                        ->select(DB::raw('SUM(qty) AS qty'))
                        ->where('brand_id', $brandListName['id'])
                        ->whereIn('company_id', $comArray)
                        ->whereBetween('invoice_date', [$request->from_date, $request->to_date])
                        ->get();
                    $balance = !empty($balance->qty) ? $balance->qty : 0;
                    $receiptSum = $receiptSum + $balance;

                    $total = $qty + $balance;
                    $totalSum = $totalSum + $total;

                    [$sales] = DB::table('sales')->select(DB::raw('SUM(qty) AS qty'))->whereIn('company_id', $comArray)->where(['brand_id' => $brandListName['id'], 'sales_type' => '1', 'is_cocktail' => '0'])->whereBetween('sale_date', [$request->from_date, $request->to_date])->get();
                    $sales = $sales->qty;

                    [$nc_sales] = DB::table('sales')->select(DB::raw('SUM(qty) AS qty'))->whereIn('company_id', $comArray)->where(['brand_id' => $brandListName['id'], 'is_cocktail' => '0', 'sales_type' => 2])->whereBetween('sale_date', [$request->from_date, $request->to_date])->get();
                    $nc_sales = $nc_sales->qty;

                    [$cocktail_sales] = DB::table('sales')->select(DB::raw('SUM(qty) AS qty'))->whereIn('company_id', $comArray)->where(['brand_id' => $brandListName['id'], 'is_cocktail' => '1'])->whereBetween('sale_date', [$request->from_date, $request->to_date])->get();
                    $cocktail_sales = $cocktail_sales->qty;

                    [$banquet_sales] = DB::table('sales')->select(DB::raw('SUM(qty) AS qty'))->whereIn('company_id', $comArray)->where(['brand_id' => $brandListName['id'], 'sales_type' => '3'])->whereBetween('sale_date', [$request->from_date, $request->to_date])->get();
                    $banquet_sales = $banquet_sales->qty;

                    [$spoilage_sales] = DB::table('sales')->select(DB::raw('SUM(qty) AS qty'))->whereIn('company_id', $comArray)->where(['brand_id' => $brandListName['id'], 'sales_type' => '4'])->whereBetween('sale_date', [$request->from_date, $request->to_date])->get();
                    $spoilage_sales = $spoilage_sales->qty;

                    // transfer In & Out

                    [$transferIn] = DB::table('transactions')->select(DB::raw('SUM(qty) AS qty'))->whereIn('company_to_id', $comArray)->where(['brand_id' => $brandListName['id']])->whereBetween('date', [$request->from_date, $request->to_date])->get(); // transfer in
                    $transferIn = $transferIn->qty;

                    //transfer in btl peg calculation start
                    $transfer = convertBtlPeg($transferIn, $brand_size, $brandListName['peg_size']);

                    //transfer in btl peg calculation ends

                    [$transferOut] = DB::table('transactions')->select(DB::raw('SUM(qty) AS qty'))->whereIn('company_id', $comArray)->where(['brand_id' => $brandListName['id']])->whereBetween('date', [$request->from_date, $request->to_date])->get(); // transfer out
                    $transferOut = $transferOut->qty;

                    //transfer out btl peg calculation start
                    $transferO = convertBtlPeg($transferOut, $brand_size, $brandListName['peg_size']);

                    //transfer in btl peg calculation ends


                    $banquetSum = $banquetSum + $banquet_sales; // sum of banquet
                    $spoilageSum = $spoilageSum + $spoilage_sales; // sum of spoilage
                    $ncSalesSum = $ncSalesSum + $nc_sales; // sum of non chargeable
                    $cocktailSalesSum = $cocktailSalesSum + $cocktail_sales; // sum of cocktails
                    $transferInSum = $transferInSum + $transferIn; // sum of cocktails
                    $transferOutSum = $transferOutSum + $transferOut; // sum of cocktails
                    $salesSum = $salesSum + $sales; // sum of sales
                    $closing = ($total + $transferOut) - ($sales + $nc_sales + $banquet_sales + $spoilage_sales + $transferIn); // closing formula
                    $closingSum = $closingSum + $closing;   // closing sum

                    [$PhyQty] = physical_history::select(DB::raw('SUM(qty) AS qty'))->whereIn('company_id', $comArray)->where(['brand_id' => $brandListName['id']])->whereDate('date', '=', $request->to_date)->get();

                    [$ItemCost] = Stock::select(DB::raw('AVG(cost_price) AS cost_price'), DB::raw('AVG(btl_selling_price) AS btl_selling_price'))->whereIn('company_id', $comArray)->where(['brand_id' => $brandListName['id']])->get();

                    $PhyClosing = !empty($PhyQty['qty']) ? $PhyQty['qty'] : 0;
                    $cost_price = !empty($ItemCost['cost_price']) ? $ItemCost['cost_price'] : 0;
                    $btl_selling_price = !empty($ItemCost['btl_selling_price']) ? $ItemCost['btl_selling_price'] : 0;

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
                    $arr['transfer_in'] = $transfer['btl'] . "." . $transfer['peg'];
                    $arr['transfer_out'] = $transferO['btl'] . "." . $transferO['peg'];
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

                    // total consumption 
                    $total_consumption = intval($sales + $nc_sales + $cocktail_sales + $banquet_sales + $spoilage_sales);
                    $totalConsumtion = $totalConsumtion + $total_consumption;
                    $totalConsumption = convertBtlPeg($total_consumption, $brand_size, $brandList->peg_size);
                    $arr['total_consumption'] = $totalConsumption['btl'] . "." . $totalConsumption['peg'];


                    // consumption 
                    $consumption = $total - $closing;
                    $btl_comsumption  = 0;
                    while ($consumption >= $brand_size) {
                        $consumption = $consumption - $brand_size;
                        $btl_comsumption++;
                    }
                    $peg_comsumption  = intval($consumption / $brandListName['peg_size']);
                    $arr['consumption'] = ($isMinus == true ? '-' : '') . $btl_comsumption . "." . $peg_comsumption;

                    // cost price


                    $peg_selling_price = $btl_selling_price / ($brandListName['btl_size'] / $brandListName['peg_size']); // calculate peg price from btl cost
                    $cost_peg_price = $cost_price / ($brandListName['btl_size'] / $brandListName['peg_size']); // calculate peg price from btl cost

                    $arr['selling_variance'] = $v_btl_closing * $btl_selling_price + $v_peg_closing * $peg_selling_price;
                    $selling_variance = $selling_variance + $arr['selling_variance'];

                    // cost price variance
                    $arr['cost_variance'] = $v_btl_closing * $cost_price + $v_peg_closing * $cost_peg_price;
                    $cost_variance = $cost_variance + $arr['cost_variance'];

                    // cost price variance
                    $arr['consumption_cost'] = $btl_comsumption * $cost_price + $peg_comsumption * $cost_peg_price;
                    $consumption_cost = $consumption_cost + $arr['consumption_cost'];

                    // cost price variance
                    $arr['physical_valuation'] = $p_btl_closing * $cost_price + $p_peg_closing * $cost_peg_price;
                    $physical_valuation = $physical_valuation + $arr['physical_valuation'];


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
                    // TOTAL CONSUMPTION 
                    $ConsumptionSUM = convertBtlPeg($totalConsumtion, $brand_size, $brandListName['peg_size']);
                    // comsumption 
                    $comsumptionSum = $totalSum - $closingSum;
                    $btl_comsumption_all  = 0;
                    while ($comsumptionSum >= $brand_size) {
                        $comsumptionSum = $comsumptionSum - $brand_size;
                        $btl_comsumption_all++;
                    }
                    $peg_comsumption_all  = intval($comsumptionSum / $brandListName['peg_size']);

                    // transfer in
                    $alltransferIn = convertBtlPeg($transferInSum, $brand_size, $brandListName['peg_size']);
                    $transfer_all_in = $alltransferIn['btl'] . "." . $alltransferIn['peg'];
                    // transfer out
                    $alltransferOut = convertBtlPeg($transferOutSum, $brand_size, $brandListName['peg_size']);
                    $transfer_all_out = $alltransferOut['btl'] . "." . $alltransferOut['peg'];

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
                        'transfer_in' => $transfer_all_in,
                        'transfer_out' => $transfer_all_out,
                        'closing' => $closing_all,
                        'physical' => $physical_all,
                        'variance' => floatval($physical_all) - floatval($closing_all),
                        'total_consumption' => $ConsumptionSUM['btl'] . "." . $ConsumptionSUM['peg'],
                        'consumption' => $btl_comsumption_all . "." . $peg_comsumption_all,
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

        // linked companies loop end here
        return json_encode($json);
    }
}
