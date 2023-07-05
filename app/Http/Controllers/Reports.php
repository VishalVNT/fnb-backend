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
                $totalConsumtion = 0;
                $transferInSum = 0;
                $transferOutSum = 0;
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
						->whereDate('invoice_date', '>=', $request->from_date)
                		->whereDate('invoice_date', '<=', $request->to_date)
                        ->get();
                    $balance = !empty($balance->qty) ? $balance->qty : 0;
                    $receiptSum = $receiptSum + $balance;

                    $total = $qty + $balance;
                    $totalSum = $totalSum + $total;

                    [$sales] = DB::table('sales')->select(DB::raw('SUM(qty) AS qty'))->whereIn('company_id', $comArray)->where(['brand_id' => $brandListName['id'], 'sales_type' => '1', 'is_cocktail' => '0'])->whereDate('sale_date', '>=', $request->from_date)->whereDate('sale_date', '<=', $request->to_date)->get();
                    $sales = $sales->qty;

                    [$nc_sales] = DB::table('sales')->select(DB::raw('SUM(qty) AS qty'))->whereIn('company_id', $comArray)->where(['brand_id' => $brandListName['id'], 'is_cocktail' => '0', 'sales_type' => 2])->whereDate('sale_date', '>=', $request->from_date)
                ->whereDate('sale_date', '<=', $request->to_date)
						->get();
                    $nc_sales = $nc_sales->qty;

                    [$cocktail_sales] = DB::table('sales')->select(DB::raw('SUM(qty) AS qty'))->whereIn('company_id', $comArray)->where(['brand_id' => $brandListName['id'], 'is_cocktail' => '1'])
					->whereDate('sale_date', '>=', $request->from_date)
                	->whereDate('sale_date', '<=', $request->to_date)
					->get();
                    $cocktail_sales = $cocktail_sales->qty;

                    [$banquet_sales] = DB::table('sales')->select(DB::raw('SUM(qty) AS qty'))->whereIn('company_id', $comArray)->where(['brand_id' => $brandListName['id'], 'sales_type' => '3'])
						->whereDate('sale_date', '>=', $request->from_date)
                		->whereDate('sale_date', '<=', $request->to_date)
						->get();
                    $banquet_sales = $banquet_sales->qty;

                    [$spoilage_sales] = DB::table('sales')->select(DB::raw('SUM(qty) AS qty'))->whereIn('company_id', $comArray)->where(['brand_id' => $brandListName['id'], 'sales_type' => '4'])
						->whereDate('sale_date', '>=', $request->from_date)
                		->whereDate('sale_date', '<=', $request->to_date)
						->get();
                    $spoilage_sales = $spoilage_sales->qty;

                    // transfer In & Out

                    [$transferIn] = DB::table('transactions')->select(DB::raw('SUM(qty) AS qty'))->whereIn('company_to_id', $comArray)->where(['brand_id' => $brandListName['id']])
						->whereDate('date', '>=', $request->from_date)
                		->whereDate('date', '<=', $request->to_date)
						->get(); // transfer in
                    $transferIn = $transferIn->qty;

                    //transfer in btl peg calculation start
                    $transfer = convertBtlPeg($transferIn, $brand_size, $brandListName['peg_size']);
                    $arr['transfer_in'] = $transfer['btl'] . "." . $transfer['peg'];
                    //transfer in btl peg calculation ends

                    [$transferOut] = DB::table('transactions')->select(DB::raw('SUM(qty) AS qty'))->whereIn('company_id', $comArray)->where(['brand_id' => $brandListName['id']])
						->whereDate('date', '>=', $request->from_date)
                		->whereDate('date', '<=', $request->to_date)
						->get(); // transfer out
                    $transferOut = $transferOut->qty;

                    //transfer out btl peg calculation start
                    $transferO = convertBtlPeg($transferOut, $brand_size, $brandListName['peg_size']);
                    $arr['transfer_out'] = $transferO['btl'] . "." . $transferO['peg'];
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

                    [$PhyQty] = physical_history::select(DB::raw('SUM(qty) AS qty'))->whereIn('company_id', $comArray)->where(['brand_id' => $brandListName['id'], 'date' => $request->to_date])->get();

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
                    $btl_selling_price = !empty($PhyQty['btl_selling_price']) ? $PhyQty['btl_selling_price'] : 0;
                    $peg_selling_price = !empty($PhyQty['peg_selling_price']) ? $PhyQty['peg_selling_price'] : 0;


                    $cost_btl_price = !empty($PhyQty['cost_price']) ? $PhyQty['cost_price'] : 0;
                    $cost_peg_price = $cost_btl_price / ($brandListName['btl_size'] / $brandListName['peg_size']); // calculate peg price from btl cost

                    // $arr['selling_price'] = $btl_selling_price;
                    // cost price variance
                    $arr['cost_variance'] = $v_btl_closing * $cost_btl_price + $v_peg_closing * $cost_peg_price;
                    $cost_variance = $cost_variance + $arr['cost_variance'];

                    // selling price variance

                    $arr['selling_variance'] = $v_btl_closing * $btl_selling_price + $v_peg_closing * $peg_selling_price;
                    $selling_variance = $selling_variance + $arr['selling_variance'];

                    // cost price variance
                    $arr['consumption_cost'] = $btl_comsumption * $cost_btl_price + $peg_comsumption * $cost_peg_price;
                    $consumption_cost = $consumption_cost + $arr['consumption_cost'];

                    // cost price variance
                    $arr['physical_valuation'] = $p_btl_closing * $cost_btl_price + $p_peg_closing * $cost_peg_price;
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
                        'variance' => intval($physical_all) - intval($closing_all),
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
				 
		
			 
			/* $arr['Test Data'] = [
				 'brand_id' => $brand_id,
				'opening_stock' => $opening_stock,
				'purchase_qty' => $purchase_qty,
				'total' => $total_qty,
				'physical_qty' => $physical_qty, 
				'consumption'=> $consumption,
				'purchase_price'=> $purchase_price,
				'cost_of_consumption' => $cost_of_consumption,
				 'sale_qty' =>  $sale_qty,
				 'closing' =>  $closing,
				 'variance' =>  $variance
				 
    		]; 
			array_push($json, $arr);*/
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
			
			$stock = getBtlPeg($brand_id,$quantity);
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
		$cat_array=array();
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
				$cat=array('category_name' => $name,
						'sales_date' => '',
						'brand_name' => '',
						'btl_size' => '',
						'qty' => '',
						'rate' => '',
						'amount' => '');
				
				$brandId = $sale->brand_id;
				$salesDate = $sale->sale_date;
				$sales_qty = $sale->qty;
				$no_peg = $sale->no_peg;
				$sale_price = $sale->sale_price;

				$brandDetails = DB::table('brands')
					->where('id', $brandId)
					->first();

				if ($brandDetails) {
					if(!in_array($name,$cat_array)){
						array_push($json,$cat);
					}
					array_push($cat_array,$name);
					$brand =array('category_name' => '',
						'sales_date' => $salesDate,
						'brand_name' => $brandDetails->name,
						'btl_size' => $brandDetails->btl_size,
						'qty' => $no_peg,
						'rate' => 0,
						'amount' => $sale_price);

					array_push($json,$brand);
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
			->select(DB::raw('COALESCE(SUM(sale_price), 0) as salePrice'), 'category_id','name')
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
	
}
