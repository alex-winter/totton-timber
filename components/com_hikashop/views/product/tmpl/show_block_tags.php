<?php
/**
 * @package	HikaShop for Joomla!
 * @version	2.3.4
 * @author	hikashop.com
 * @copyright	(C) 2010-2014 HIKARI SOFTWARE. All rights reserved.
 * @license	GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */
defined('_JEXEC') or die('Restricted access');
?><?php
if(!$this->params->get('show_tags', 1))
	return;

if(!empty($this->variant_name))
	return;

$tagHelper = hikashop_get('helper.tags');
if(!$tagHelper->isCompatible())
	return;

?><div id="hikashop_product_tags_main" class="hikashop_product_tags"><?php
if(!empty($this->element->product_id)) {
	$this->element->tags = new JHelperTags;
	$this->element->tags->getItemTags('com_hikashop.product', $this->element->product_id);
	if(!empty($this->element->tags)) {
		$this->element->tagLayout = new JLayoutFile('joomla.content.tags');
		echo $this->element->tagLayout->render($this->element->tags->itemTags);
	}
}
?></div>
