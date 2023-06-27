<?php

use App\Models\Brand;
use App\Models\Log;

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
		while ($qty >= $brand_size) {
			$qty = $qty - $brand_size;
			$btl++;
		}
		$peg = $qty / $brandSize[0]['peg_size'];
		return array('btl' => $btl, 'peg' => $peg, 'btl_size' => $brandSize[0]['btl_size'], 'peg_size' => $brandSize[0]['peg_size']);
	}
	return array('btl' => 0, 'peg' => 0, 'btl_size' => 0, 'peg_size' => 0);
}
function convertBtlPeg($qty, $brandSize, $peg_size)
{
	if ($brandSize > 0) {
		$brand_size = $brandSize;
		// system stock
		$btl = 0;
		$total_q = intval($qty);
		while ($total_q >= $brand_size) {
			$total_q = $total_q - $brand_size;
			$btl++;
		}
		$peg = $total_q / $peg_size;
		return array('btl' => $btl, 'peg' => $peg, 'btl_size' => $brandSize, 'peg_size' => $peg_size);
	}
	return array('btl' => 0, 'peg' => 0, 'btl_size' => 0, 'peg_size' => 0);
}
