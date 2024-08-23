<?php

use App\Models\Brand;
use App\Models\Log;
use Illuminate\Support\Facades\DB;

function SaveLog($data)
{
	$Log = new Log($data);

	if ($Log->save()) {
		return true;
	} else {
		return false;
	}
}
function getBtlPeg($brand_id, $qty)
{
	$brandSize = Brand::select('btl_size', 'peg_size')->where('id', $brand_id)->get();
	if ($brandSize) {
		$brand_size = $brandSize[0]['btl_size'];
		// system stock
		$btl = 0; 
		$peg = 0;
		while ($qty >= $brand_size) {
			$qty = $qty - $brand_size;
			$btl++;
		}
		while ($qty >= $brandSize[0]['peg_size']) {
			$qty = $qty - $brandSize[0]['peg_size'];
			$peg++;
		}
		$formattedPeg = sprintf('%02d', $peg);
		return array('btl' => $btl, 'peg' => $formattedPeg, 'btl_size' => $brandSize[0]['btl_size'], 'peg_size' => $brandSize[0]['peg_size']);
	}
	return array('btl' => 0, 'peg' => 0, 'btl_size' => 0, 'peg_size' => 0);
}
function convertBtlPeg($qty, $brandSize, $peg_size)
{
	if ($brandSize > 0) {
		$brand_size = $brandSize;
		// system stock
		$btl = 0;
		$peg = 0;
		$total_q = intval($qty);
		while ($total_q >= $brand_size) {
			$total_q = $total_q - $brand_size;
			$btl++;
		}
		while ($total_q >= $peg_size) {
			$total_q = $total_q - $peg_size;
			$peg++;
		}
		return array('btl' => $btl, 'peg' => $peg, 'btl_size' => $brandSize, 'peg_size' => $peg_size);
	}
	return array('btl' => 0, 'peg' => 0, 'btl_size' => 0, 'peg_size' => 0);
}
function getrateamount($brand_id)
{
	$brandDetails = Brand::select('brands.btl_size', 'brands.peg_size', DB::raw('COALESCE(stocks.btl_selling_price,0) as btl_selling_price'), DB::raw('COALESCE(stocks.peg_selling_price,0) as peg_selling_price'))
		->join('stocks', 'stocks.brand_id', '=', 'brands.id')
		->where('brands.id', $brand_id)
		->get();
	if (count($brandDetails) > 0) {
		$btl_size = $brandDetails[0]['btl_size'];
		$peg_size = $brandDetails[0]['peg_size'];
		$btl_selling_price = $brandDetails[0]['btl_selling_price'];
		$peg_selling_price = $brandDetails[0]['peg_selling_price'];

		$peg_count = intval($btl_size / $peg_size);
		$pegprice = intval($btl_selling_price / $peg_count);
		$amount = $peg_count * $peg_selling_price;

		return array('pegprice' => $pegprice, 'amount' => $amount);
	}
	return array('pegprice' => 0, 'amount' => 0);
}
