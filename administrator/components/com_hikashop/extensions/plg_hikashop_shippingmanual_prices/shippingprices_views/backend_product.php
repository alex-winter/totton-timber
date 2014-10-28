<?php
/**
 * @package	HikaShop for Joomla!
 * @version	2.3.4
 * @author	hikashop.com
 * @copyright	(C) 2010-2014 HIKARI SOFTWARE. All rights reserved.
 * @license	GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */
defined('_JEXEC') or die('Restricted access');
?><fieldset class="adminform"><legend><?php echo JText::_('SHIPPING_PRICES'); ?></legend>
	<table class="adminlist table table-striped hikashop_product_prices_table" width="100%">
		<thead>
			<tr>
				<th class="title"><?php echo JText::_('HIKA_NAME'); ?></th>
				<th class="title" width="10px"><?php echo JText::_('MINIMUM_QUANTITY'); ?></th>
				<th class="title"><?php echo JText::_('PRICE'); ?></th>
				<th class="title"><?php echo JText::_('FEE'); ?></th>
				<th class="title"><?php echo JText::_('SHIPPING_BLOCKED'); ?></th>
				<th class="title"><?php echo JText::_('ACTIONS'); ?></th>
			</tr>
		</thead>
	<tbody>
<?php
$i = 0;
$previous_shipping_id = -1;
foreach($shippings as &$shipping) {
	$shipping->shipping_params = unserialize($shipping->shipping_params);

	$shipping_data = $shipping->shipping_name . ' - ' . $currencyHelper->displayPrices(array($shipping), 'shipping_price', 'shipping_currency_id');
	if(isset($shipping->shipping_params->shipping_percentage) && bccomp($shipping->shipping_params->shipping_percentage,0,3)) {
		$shipping_data .= ' +'.$shipping->shipping_params->shipping_percentage.'%';
	}

	$rest = array();
	if(!empty($shipping->shipping_params->shipping_min_volume)){ $rest[] = JText::_('SHIPPING_MIN_VOLUME').':'.$shipping->shipping_params->shipping_min_volume.$shipping->shipping_params->shipping_size_unit; }
	if(!empty($shipping->shipping_params->shipping_max_volume)){ $rest[] = JText::_('SHIPPING_MAX_VOLUME').':'.$shipping->shipping_params->shipping_max_volume.$shipping->shipping_params->shipping_size_unit; }
	if(!empty($shipping->shipping_params->shipping_min_weight)){ $rest[] = JText::_('SHIPPING_MIN_WEIGHT').':'.$shipping->shipping_params->shipping_min_weight.$shipping->shipping_params->shipping_weight_unit; }
	if(!empty($shipping->shipping_params->shipping_max_weight)){ $rest[] = JText::_('SHIPPING_MAX_WEIGHT').':'.$shipping->shipping_params->shipping_max_weight.$shipping->shipping_params->shipping_weight_unit; }

	if(isset($shipping->shipping_params->shipping_min_price) && bccomp($shipping->shipping_params->shipping_min_price,0,5)){
		$shipping->shipping_min_price=$shipping->shipping_params->shipping_min_price;
		$rest[] = JText::_('SHIPPING_MIN_PRICE').':'.$currencyHelper->displayPrices(array($shipping),'shipping_min_price','shipping_currency_id');
	}
	if(isset($shipping->shipping_params->shipping_max_price) && bccomp($shipping->shipping_params->shipping_max_price,0,5)){
		$shipping->shipping_max_price=$shipping->shipping_params->shipping_max_price;
		$rest[] = JText::_('SHIPPING_MAX_PRICE').':'.$currencyHelper->displayPrices(array($shipping),'shipping_max_price','shipping_currency_id');
	}
	if(!empty($shipping->shipping_params->shipping_zip_prefix)){ $rest[]=JText::_('SHIPPING_PREFIX').':'.$shipping->shipping_params->shipping_zip_prefix; }
	if(!empty($shipping->shipping_params->shipping_min_zip)){ $rest[]=JText::_('SHIPPING_MIN_ZIP').':'.$shipping->shipping_params->shipping_min_zip; }
	if(!empty($shipping->shipping_params->shipping_max_zip)){ $rest[]=JText::_('SHIPPING_MAX_ZIP').':'.$shipping->shipping_params->shipping_max_zip; }
	if(!empty($shipping->shipping_params->shipping_zip_suffix)){ $rest[]=JText::_('SHIPPING_SUFFIX').':'.$shipping->shipping_params->shipping_zip_suffix; }
	if(!empty($shipping->zone_name_english)){ $rest[]=JText::_('ZONE').':'.$shipping->zone_name_english; }
	if(!empty($rest)) {
		$shipping_data .= '<div style="margin-left:10px">'.implode('<br/>', $rest).'</div>';
	}

	if($previous_shipping_id != $shipping->shipping_id) {
		echo "\r\n".'<tr class="hikashop_shipping_price_category"><td colspan="5">'.$shipping_data.'</td><td align="center">'.
			'<a href="#" onclick="return hikashop_addline_shippingprice(this,'.$shipping->shipping_id.',\''.str_replace(array('"',"'"),array('&quot;','\\\''),$shipping->shipping_name).'\',\''.$shipping->currency_symbol.'\');"><img src="'.HIKASHOP_IMAGES.'add.png" alt="+"/></a>'.
			'</td></tr>';
	}
	$previous_shipping_id = $shipping->shipping_id;

	if(!empty($shipping->shipping_price_value) || !empty($shipping->shipping_fee_value)) {
		if($shipping->shipping_price_min_quantity < 1)
			$shipping->shipping_price_min_quantity = 1;
		if($shipping->shipping_price_value < 0 || $shipping->shipping_fee_value < 0) {
			$blocked_checked = 'checked="checked"';
			$attribute = 'readonly="readonly"';
			$shipping->shipping_price_value = -1;
			$shipping->shipping_fee_value = -1;
		}else{
			$blocked_checked = '';
			$attribute = '';
		}
		echo '<tr><td>'.
			'<input type="hidden" name="shipping_prices['.$i.'][id]" value="'.$shipping->shipping_price_id.'"/>'.
			'<input type="hidden" name="shipping_prices['.$i.'][shipping_id]" value="'.$shipping->shipping_id.'"/>'.
			'</td><td><input type="text" name="shipping_prices['.$i.'][qty]" value="'.$shipping->shipping_price_min_quantity.'" size="3"/></td>'.
			'<td style="text-align:center"><input type="text" id="shipping_prices_value_'.$i.'" '.$attribute.' name="shipping_prices['.$i.'][value]" value="'.$shipping->shipping_price_value.'" size="7"/> '.$shipping->currency_symbol.'</td>'.
			'<td style="text-align:center"><input type="text" id="shipping_prices_fee_'.$i.'" '.$attribute.' name="shipping_prices['.$i.'][fee]" value="'.$shipping->shipping_fee_value.'" size="7"/> '.$shipping->currency_symbol.'</td>'.
			'<td><input type="checkbox" onchange="hikashop_shippingprice_blocked_change('.$i.', this)" '.$blocked_checked.'/></td>'.
			'<td align="center">'.
			'<a href="#" onclick="return hikashop_remline_shippingprice(this);"><img src="'.HIKASHOP_IMAGES.'delete.png" alt="-"/></a>'.
			'</td></tr>';
	}

	$i++;
	unset($shipping);
}
?>
		<tr id="hikashop_shipping_price_tpl_line" style="display:none">
			<td><input type="hidden" name="{field_id}" value="{shipping_id}"/></td>
			<td><input type="text" name="{field_qty}" value="" size="3"/></td>
			<td style="text-align:center"><input id="shipping_prices_value_{cpt}" type="text" name="{field_value}" value="" size="7"/> {currency}</td>
			<td style="text-align:center"><input id="shipping_prices_fee_{cpt}" type="text" name="{field_fee}" value="" size="7"/> {currency}</td>
			<td><input type="checkbox" onchange="hikashop_shippingprice_blocked_change({cpt}, this)" /></td>
			<td align="center"><a href="#" onclick="return hikashop_remline_shippingprice(this);"><img src="<?php echo HIKASHOP_IMAGES; ?>delete.png" alt="-"/></a></td>
		</tr>
	</tbody>
</table>
<input type="hidden" name="shipping_prices[init]" value=""/>
<script type="text/javascript">
var hikashop_shippingprice_cpt = <?php echo $i; ?>;
function hikashop_addline_shippingprice(el,id,name,currency) {
	var d = document, tplLine = d.getElementById("hikashop_shipping_price_tpl_line"),
		tableUser = tplLine.parentNode,
		htmlblocks = {
			cpt: hikashop_shippingprice_cpt,
			field_id: "shipping_prices["+hikashop_shippingprice_cpt+"][shipping_id]",
			field_qty: "shipping_prices["+hikashop_shippingprice_cpt+"][qty]",
			field_fee: "shipping_prices["+hikashop_shippingprice_cpt+"][fee]",
			field_value: "shipping_prices["+hikashop_shippingprice_cpt+"][value]",
			shipping_id: id,
			name: name,
			currency: currency
		};
	if(!tplLine) return;
	var trLine = tplLine.cloneNode(true);
	trLine.id = "";
	while(el != null && el.tagName.toLowerCase() != "tr") { el = el.parentNode; }
	if(el == null || !el.nextSibling) {
		tableUser.appendChild(trLine);
	} else {
		while(el.nextSibling && el.nextSibling.tagName && el.nextSibling.tagName.toLowerCase() == "tr" && el.nextSibling.class != "hikashop_shipping_price_category") { el = el.nextSibling; }
		tableUser.insertBefore(trLine, el.nextSibling);
	}
	trLine.style.display = "";
	for (var i = tplLine.cells.length - 1; i >= 0; i--) {
		for(var k in htmlblocks) {
			if(trLine.cells[i])
				trLine.cells[i].innerHTML = trLine.cells[i].innerHTML.replace(new RegExp("{"+k+"}","g"), htmlblocks[k]);
		}
	}
	hikashop_shippingprice_cpt++;
	return false;
}
function hikashop_remline_shippingprice(el) {
	while(el != null && el.tagName.toLowerCase() != "tr") { el = el.parentNode; }
	if(!el) return;
	var table = el.parentNode;
	table.removeChild(el);
	return false;
}
function hikashop_shippingprice_blocked_change(id, el) {
	var d = document,
		elValue = d.getElementById("shipping_prices_value_"+id),
		elFee = d.getElementById("shipping_prices_fee_"+id);
	if(!elValue || !elFee)
		return false;
	if(el.checked) {
		elValue.setAttribute("readonly", "readonly");
		elValue.value= "-1";
		elFee.setAttribute("readonly", "readonly");
		elFee.value= "-1";
	} else {
		elValue.removeAttribute("readonly", "readonly");
		elValue.value= "";
		elFee.removeAttribute("readonly", "readonly");
		elFee.value= "";
	}
}
</script>
</fieldset>
