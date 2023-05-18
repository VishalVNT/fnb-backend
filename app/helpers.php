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
		return array('btl' => $btl, 'peg' => $peg,'btl_size' => $brandSize[0]['btl_size']);
	}
	return array('btl' => 0, 'peg' => 0, 'btl_size' => 0);
}
