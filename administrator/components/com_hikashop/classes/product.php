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
class hikashopProductClass extends hikashopClass{
	var $tables = array('price','variant','product_related','product_related','product_category','product');
	var $pkeys = array('price_product_id','variant_product_id','product_related_id','product_id','product_id','product_id');
	var $namekeys = array('','','','');
	var $parent = 'product_parent_id';
	var $toggle = array('product_published'=>'product_id');
	var $type = '';

	function get($id,$default=null){
		$element = parent::get($id);
		if($element){
			$this->addAlias($element);
		}
		return $element;
	}

	function saveForm(){
		$oldProduct = null;
		$product_id = hikashop_getCID('product_id');
		$categories = JRequest::getVar( 'category', array(), '', 'array' );
		JArrayHelper::toInteger($categories);
		$newCategories = array();
		if(count($categories)){
			foreach($categories as $category){
				$newCategory = new stdClass();
				$newCategory->category_id = $category;
				$newCategories[]=$newCategory;
			}
		}
		if($product_id){
			$oldProduct = $this->get($product_id);
			$oldProduct->categories = $newCategories;
		}else{
			$oldProduct = new stdClass;
			$oldProduct->categories = $newCategories;
		}
		$fieldsClass = hikashop_get('class.field');
		$element = $fieldsClass->getInput('product',$oldProduct);

		$status = true;
		if(empty($element)){
			$element = $_SESSION['hikashop_product_data'];
			$status = false;
		}
		if($product_id){
			$element->product_id = $product_id;
		}

		if(isset($element->product_price_percentage)){
			$element->product_price_percentage = hikashop_toFloat($element->product_price_percentage);
		}

		$element->categories = $categories;
		if(empty($element->product_id) && !count($element->categories) && (empty($element->product_type) || $element->product_type == 'main')) {
			$app = JFactory::getApplication();
			$id = $app->getUserState(HIKASHOP_COMPONENT.'.product.filter_id');
			if(empty($id) || !is_numeric($id)){
				$id='product';
				$class = hikashop_get('class.category');
				$class->getMainElement($id);
			}
			if(!empty($id)){
				$element->categories = array($id);
			}
		}
		$element->related = array();
		$related = JRequest::getVar( 'related', array(), '', 'array' );
		JArrayHelper::toInteger($related);
		if(!empty($related)){
			$related_ordering = JRequest::getVar( 'related_ordering', array(), '', 'array' );
			JArrayHelper::toInteger($related_ordering);
			foreach($related as $id){
				$obj = new stdClass();
				$obj->product_related_id = $id;
				$obj->product_related_ordering = $related_ordering[$id];
				$element->related[$id] = $obj;
			}
		}
		$options = JRequest::getVar( 'options', array(), '', 'array' );
		$element->options = array();
		JArrayHelper::toInteger($element->options);
		if(!empty($options)){
			$related_ordering = JRequest::getVar( 'options_ordering', array(), '', 'array' );
			JArrayHelper::toInteger($related_ordering);
			foreach($options as $id){
				$obj = new stdClass();
				$obj->product_related_id = $id;
				$obj->product_related_ordering = $related_ordering[$id];
				$element->options[$id] = $obj;
			}
		}
		$element->images = JRequest::getVar( 'image', array(), '', 'array' );
		JArrayHelper::toInteger($element->images);
		$element->files = JRequest::getVar( 'file', array(), '', 'array' );
		JArrayHelper::toInteger($element->files);

		$element->imagesorder = JRequest::getVar('imageorder', array(), '', 'array');
		JArrayHelper::toInteger($element->imagesorder);

		$element->tags = JRequest::getVar('tags', array(), '', 'array');

		$priceData = JRequest::getVar( 'price', array(), '', 'array' );
		$element->prices = array();
		foreach($priceData as $column => $value){
			hikashop_secureField($column);
			if($column=='price_access'){
				if(!empty($value)){
					foreach($value as $k => $v){
						$value[$k] = preg_replace('#[^a-z0-9,]#i','',$v);
					}
				}
			}elseif($column=='price_site_id'){
				jimport('joomla.filter.filterinput');
				$safeHtmlFilter = JFilterInput::getInstance(null, null, 1, 1);
				foreach($value as $k => $v){
					if(!is_null($safeHtmlFilter)) $value[$k] = $safeHtmlFilter->clean($v, 'string');
				}
			}elseif($column=='price_value'){
				$this->toFloatArray($value);
			}else{
				JArrayHelper::toInteger($value);
			}
			foreach($value as $k => $val){
				if($column=='price_min_quantity' && $val==1){
					$val=0;
				}
				if(!isset($element->prices[$k])) $element->prices[$k] = new stdClass();
				$element->prices[$k]->$column = $val;
			}
		}

		$element->oldCharacteristics = array();
		if(isset($element->product_type) && $element->product_type=='variant'){
			$characteristics = JRequest::getVar( 'characteristic', array(), '', 'array' );
			JArrayHelper::toInteger($characteristics);
			if(empty($characteristics)){
				$element->characteristics = array();
			}else{
				$this->database->setQuery('SELECT * FROM '.hikashop_table('characteristic').' WHERE characteristic_id IN ('.implode(',',$characteristics).')');
				$element->characteristics = $this->database->loadObjectList('characteristic_id');
			}
		}else{
			$characteristics = JRequest::getVar( 'characteristic', array(), '', 'array' );
			JArrayHelper::toInteger($characteristics);
			if(!empty($element->product_id)){
				$this->database->setQuery('SELECT b.characteristic_id FROM '.hikashop_table('variant').' AS a LEFT JOIN '.hikashop_table('characteristic').' AS b ON a.variant_characteristic_id=b.characteristic_id WHERE a.variant_product_id ='.$element->product_id.' AND b.characteristic_parent_id=0');
				if(!HIKASHOP_J25){
					$element->oldCharacteristics = $this->database->loadResultArray();
				} else {
					$element->oldCharacteristics = $this->database->loadColumn();
				}
			}
			if(empty($element->oldCharacteristics)){
				$element->oldCharacteristics = array();
			}
			if(!empty($characteristics)){
				$characteristics_ordering = JRequest::getVar( 'characteristic_ordering', array(), '', 'array' );
				JArrayHelper::toInteger($characteristics_ordering);
				$characteristics_default = JRequest::getVar( 'characteristic_default', array(), '', 'array' );
				JArrayHelper::toInteger($characteristics_default);
				$this->database->setQuery('SELECT * FROM '.hikashop_table('characteristic').' WHERE characteristic_parent_id IN ('.implode(',',$characteristics).')');
				$values = $this->database->loadObjectList();
				$element->characteristics = array();
				foreach($characteristics as $k => $id){
					$obj = new stdClass();
					$obj->characteristic_id = $id;
					$obj->ordering = $characteristics_ordering[$k];
					$obj->default_id = (int)@$characteristics_default[$k];
					$obj->values = array();
					foreach($values as $value){
						if($value->characteristic_parent_id==$id){
							$obj->values[$value->characteristic_id]=$value->characteristic_value;
						}
					}
					$element->characteristics[(int)$id] = $obj;
				}
			}
		}
		$class = hikashop_get('helper.translation');
		$class->getTranslations($element);
		if(!empty($element->product_sale_start)){
			$element->product_sale_start=hikashop_getTime($element->product_sale_start);
		}
		if(!empty($element->product_sale_end)){
			$element->product_sale_end=hikashop_getTime($element->product_sale_end);
		}

		$element->product_max_per_order=(int)$element->product_max_per_order;

		$element->product_description = JRequest::getVar('product_description','','','string',JREQUEST_ALLOWRAW);
		if(!empty($element->product_id) && !empty($element->product_code)){
			$query = 'SELECT product_id FROM '.hikashop_table('product').' WHERE product_code  = '.$this->database->Quote($element->product_code).' AND product_id!='.(int)$element->product_id.' LIMIT 1';
			$this->database->setQuery($query);
			if($this->database->loadResult()){
				$app = JFactory::getApplication();
				$app->enqueueMessage(JText::_( 'DUPLICATE_PRODUCT' ), 'error');
				JRequest::setVar( 'fail', $element  );
				return false;
			}
		}

		$config =& hikashop_config();
		if($config->get('alias_auto_fill',1) && empty($element->product_alias)){
			$this->addAlias($element);
			if($config->get('sef_remove_id',0)){
				$int_at_the_beginning = (int)$element->alias;
				if($int_at_the_beginning){
					$element->alias = $config->get('alias_prefix','p').$element->alias;
				}
			}
			$element->product_alias = $element->alias;
			unset($element->alias);
		}
		$autoKeyMeta = $config->get('auto_keywords_and_metadescription_filling',0);
		if($autoKeyMeta){
			$helper = hikashop_get('helper.seo');
			$helper->autoFillKeywordMeta($element, "product");
		}
		if($status){
			$status = $this->save($element);
		}else{
			JRequest::setVar( 'fail', $element  );
			return $status;
		}

		if($status){
			$this->updateCategories($element,$status);
			$this->updatePrices($element,$status);
			$this->updateFiles($element,$status,'files');
			$this->updateFiles($element,$status,'images',$element->imagesorder);
			$this->updateRelated($element,$status,'related');
			$this->updateRelated($element,$status,'options');
			$this->updateCharacteristics($element,$status);
			$class->handleTranslations('product',$status,$element);
		}else{
			JRequest::setVar( 'fail', $element  );
			if(empty($element->product_id) && empty($element->product_code) && empty($element->product_name)){
				$app = JFactory::getApplication();
				$app->enqueueMessage(JText::_( 'SPECIFY_NAME_AND_CODE' ), 'error');
			}else{
				$query = 'SELECT product_id FROM '.hikashop_table('product').' WHERE product_code  = '.$this->database->Quote($element->product_code).' LIMIT 1';
				$this->database->setQuery($query);
				if($this->database->loadResult()){
					$app = JFactory::getApplication();
					$app->enqueueMessage(JText::_( 'DUPLICATE_PRODUCT' ), 'error');
				}
			}
		}
		return $status;
	}

	function getCategories($product_id){
		if(empty($product_id)) return false;
		static $categoriesArray = array();
		if(!isset($categoriesArray[$product_id])){
			$query='SELECT category_id FROM '.hikashop_table('product_category').' WHERE product_id='.$product_id.' ORDER BY ordering ASC';
			$this->database->setQuery($query);
			if(!HIKASHOP_J25){
				$categoriesArray[$product_id]=$this->database->loadResultArray();
			} else {
				$categoriesArray[$product_id]=$this->database->loadColumn();
			}
		}
		return $categoriesArray[$product_id];
	}

	function getProducts($ids,$mode='id'){
		if(is_numeric($ids)){
			$ids = array($ids);
		}
		$where='';
		if(empty($ids)){
			$this->database->setQuery('SELECT product_id FROM '.hikashop_table('product').' ORDER BY product_id ASC');
			if(!HIKASHOP_J25){
				$ids = $this->database->loadResultArray();
			} else {
				$ids = $this->database->loadColumn();
			}
		}else{
			JArrayHelper::toInteger($ids,0);
		}

		if(count($ids)<1) return false;

		$query = 'SELECT * FROM '.hikashop_table('product_related').' AS a WHERE a.product_id IN ('.implode(',',$ids).')';
		$this->database->setQuery($query);
		$related = $this->database->loadObjectList();
		foreach($related as $rel){
			if($mode!='import' && $rel->product_related_type=='options' && !in_array($rel->product_related_id,$ids)) $ids[]=$rel->product_related_id;
		}

		$where=' WHERE product_id IN ('.implode(',',$ids).') OR product_parent_id IN ('.implode(',',$ids).')';
		$query = 'SELECT * FROM '.hikashop_table('product').$where.' ORDER BY product_parent_id ASC, product_id ASC';
		$this->database->setQuery($query);
		$all_products = $this->database->loadObjectList('product_id');
		if(empty($all_products)) return false;

		$all_ids = array_keys($all_products);

		$products = array();
		$variants = array();

		$ids = array();
		foreach($all_products as $key => $product){
			$all_products[$key]->prices=array();
			$all_products[$key]->files=array();
			$all_products[$key]->images=array();
			$all_products[$key]->variant_links=array();
			$all_products[$key]->translations=array();
			if($product->product_type=='main'){
				$all_products[$key]->categories=array();
				$all_products[$key]->categories_ordering=array();
				$all_products[$key]->related=array();
				$all_products[$key]->options=array();
				$all_products[$key]->variants=array();
				$products[$product->product_id]=&$all_products[$key];
				$ids[] = $product->product_id;
			}else{
				foreach($all_products as $key2 => $main){
					if($main->product_type != 'main') continue;
					if($main->product_id == $product->product_parent_id){
						$all_products[$key2]->variants[$product->product_id]=&$all_products[$key];
					}
				}
				$variants[$product->product_id]=&$all_products[$key];
			}
		}

		foreach($related as $rel){
			$type = $rel->product_related_type;
			$all_products[$rel->product_id]->{$type}[]=$rel->product_related_id;
		}

		$transHelper = hikashop_get('helper.translation');
		if($transHelper->isMulti(true)){
			$trans_table = 'jf_content';
			if($transHelper->falang){
				$trans_table = 'falang_content';
			}
			$query = 'SELECT * FROM '.hikashop_table($trans_table,false).' WHERE reference_id IN ('.implode(',',$all_ids).')  AND reference_table=\'hikashop_product\' ORDER BY reference_id ASC';
			$this->database->setQuery($query);
			$translations = $this->database->loadObjectList();
			if(!empty($translations)){
				foreach($translations as $translation){
					$all_products[$translation->reference_id]->translations[]=$translation;
				}
			}
		}
		if(!empty($ids)){
			$query = 'SELECT * FROM '.hikashop_table('product_category').' WHERE product_id IN ('.implode(',',$ids).') ORDER BY ordering ASC';
			$this->database->setQuery($query);
			$categories = $this->database->loadObjectList();
			if(!empty($categories)){
				foreach($categories as $category){
					$all_products[$category->product_id]->categories[]=$category->category_id;
					$all_products[$category->product_id]->categories_ordering[]=$category->ordering;
				}
			}
		}

		$query = 'SELECT * FROM '.hikashop_table('price').' WHERE price_product_id IN ('.implode(',',$all_ids).')';
		$this->database->setQuery($query);
		$prices = $this->database->loadObjectList();
		if(!empty($prices)){
			foreach($prices as $price){
				$all_products[$price->price_product_id]->prices[]=$price;
			}
		}
		$query = 'SELECT * FROM '.hikashop_table('file').' WHERE file_ref_id IN ('.implode(',',$all_ids).') AND file_type IN (\'product\',\'file\') ORDER BY file_ordering ASC, file_id ASC';
		$this->database->setQuery($query);
		$files = $this->database->loadObjectList();
		if(!empty($files)){
			foreach($files as $file){
				if($file->file_type=='file'){
					$type='files';
				}else{
					$type='images';
				}
				$all_products[$file->file_ref_id]->{$type}[]=$file;
			}
		}
		$query = 'SELECT * FROM '.hikashop_table('variant').' WHERE variant_product_id IN ('.implode(',',$all_ids).') ORDER BY ordering ASC';
		$this->database->setQuery($query);
		$variants = $this->database->loadObjectList();
		if(!empty($variants)){
			foreach($variants as $variant){
				$all_products[$variant->variant_product_id]->variant_links[]=$variant->variant_characteristic_id;
			}
		}
		$this->products =& $products;
		$this->all_products =& $all_products;
		$this->variants =& $variants;
		return true;
	}

	function toFloatArray(&$array, $default = null){
		if (is_array($array)) {
			foreach ($array as $i => $v) {
				$array[$i] = hikashop_toFloat($v);
			}
		} else {
			if ($default === null) {
				$array = array();
			} elseif (is_array($default)) {
				$this->toFloatArray($default, null);
				$array = $default;
			} else {
				$array = array( (float) $default );
			}
		}
	}

	function addAlias(&$element){
		if(empty($element->product_alias)){
			$element->alias = strip_tags(preg_replace('#<span class="hikashop_product_variant_subname">.*</span>#isU','',$element->product_name));
		}else{
			$element->alias = $element->product_alias;
		}
		$config = JFactory::getConfig();
		if(!$config->get('unicodeslugs')){
			$lang = JFactory::getLanguage();
			$element->alias = $lang->transliterate($element->alias);
		}
		$app = JFactory::getApplication();
		if(method_exists($app,'stringURLSafe')){
			$element->alias = $app->stringURLSafe($element->alias);
		}else{
			$element->alias = JFilterOutput::stringURLSafe($element->alias);
		}
	}

	function save(&$element,$stats=false){
		if(!$stats) $element->product_modified=time();
		if(empty($element->product_id)){
			if(strlen(@$element->product_quantity)==0){
				$element->product_quantity=-1;
			}
			$element->product_created=@$element->product_modified;
		}else{
			$element->old = $this->get($element->product_id);
		}

		if(empty($element->product_id)){
			if(empty($element->product_type)){
				if(!isset($element->product_parent_id) || empty($element->product_parent_id)){
					$element->product_type='main';
				}else{
					$element->product_type='variant';
				}
			}
		}
		if(isset($element->product_quantity) && !is_numeric($element->product_quantity)){
			$element->product_quantity=-1;
		}
		$new = false;
		if(empty($element->product_id)){
			if(empty($element->product_code) && !empty($element->product_name)){
				$search = explode(",","ç,æ,œ,á,é,í,ó,ú,à,è,ì,ò,ù,ä,ë,ï,ö,ü,ÿ,â,ê,î,ô,û,å,e,i,ø,u");
				$replace = explode(",","c,ae,oe,a,e,i,o,u,a,e,i,o,u,a,e,i,o,u,y,a,e,i,o,u,a,e,i,o,u");
				$test = str_replace($search, $replace, $element->product_name);
				$test=preg_replace('#[^a-z0-9_-]#i','',$test);
				if(empty($test)){
					$query = 'SELECT MAX(`product_id`) FROM '.hikashop_table('product');
					$this->database->setQuery($query);
					$last_pid = $this->database->loadResult();
					$last_pid++;
					$element->product_code = 'product_'.$last_pid;
				}else{
					$test = str_replace($search, $replace, $element->product_name);
					$element->product_code = preg_replace('#[^a-z0-9_-]#i','_',$test);
				}
			}elseif(empty($element->product_code)){
				return false;
			}
			$new=true;
		}
		JPluginHelper::importPlugin( 'hikashop' );
		$dispatcher = JDispatcher::getInstance();
		$do = true;
		if($new){
			$dispatcher->trigger( 'onBeforeProductCreate', array( & $element, & $do) );
		}else{
			$dispatcher->trigger( 'onBeforeProductUpdate', array( & $element, & $do) );
		}
		if(!$do){
			return false;
		}
		$tags = null;
		if(isset($element->tags)) {
			$tags = $element->tags;
			unset($element->tags);
		}

		$status = parent::save($element);
		if($status){
			$element->product_id = $status;
			if($new){
				$dispatcher->trigger( 'onAfterProductCreate', array( & $element ) );
			}else{
				$dispatcher->trigger( 'onAfterProductUpdate', array( & $element ) );
			}
			if($tags !== null && @$element->product_type!='variant') {
				$tagsHelper = hikashop_get('helper.tags');
				$fullElement = $element;
				if(!empty($element->old)) {
					foreach($element->old as $k => $v) {
						if(!isset($fullElement->$k))
							$fullElement->$k = $v;
					}
				}
				$tagsHelper->saveUCM('product', $fullElement, $tags);
			}
		}
		return $status;
	}

	function updatePrices($element,$status){
		$filters=array('price_product_id='.$status);
		if(count($element->prices)){
			$ids = array();
			foreach($element->prices as $price){
				if(!empty($price->price_id) && !empty($price->price_value)) $ids[] = $price->price_id;
			}
			if(!empty($ids)){
				$filters[]= 'price_id NOT IN ('.implode(',',$ids).')';
			}
		}
		$query = 'DELETE FROM '.hikashop_table('price').' WHERE '.implode(' AND ',$filters);
		$this->database->setQuery($query);
		$this->database->query();

		if(count($element->prices)){
			$insert = array();
			foreach($element->prices as $price){
				if(empty($price->price_value)) continue;
				if(empty($price->price_id))	$price->price_id = 'NULL';
				$line = '('.(int)$price->price_currency_id.','.$status.','.(int)$price->price_min_quantity.','.(float)$price->price_value.','.$price->price_id.','.$this->database->Quote(@$price->price_site_id);
				if(hikashop_level(2)){
					if(empty($price->price_access)){
						$price->price_access = 'all';
					}
					$line.=','.$this->database->Quote($price->price_access);
				}
				$insert[]=$line.')';
			}
			if(!empty($insert)){
				$select = 'price_currency_id,price_product_id,price_min_quantity,price_value,price_id,price_site_id';
				if(hikashop_level(2)){
					$select.=',price_access';
				}
				$query = 'REPLACE '.hikashop_table('price').' ('.$select.') VALUES '.implode(',',$insert).';';
				$this->database->setQuery($query);
				$this->database->query();
			}
		}
	}

	function updateCharacteristics($element, $product_id = 0, $auto_variants = null) {
		$product_id = (int)$product_id;
		if($product_id == 0) {
			$product_id = (int)@$element->product_id;
			if($product_id == 0)
				return false;
		}

		if($element->product_type == 'variant') {

			$query = 'DELETE FROM ' . hikashop_table('variant') . ' WHERE variant_product_id = ' . $product_id;
			if(!empty($element->characteristics))
				$query .= ' AND variant_characteristic_id NOT IN (' . implode(',', array_keys($element->characteristics)) . ')';

			$this->database->setQuery($query);
			$this->database->query();

			if(!empty($element->characteristics)) {
				$insert = array();
				foreach(array_keys($element->characteristics) as $c) {
					if(is_numeric($c) && (int)$c > 0)
						$insert[] = (int)$c . ',' . (int)$product_id . ',0';
				}
				if(empty($insert))
					return false;

				$query = 'INSERT IGNORE INTO ' . hikashop_table('variant').
						' (variant_characteristic_id, variant_product_id, ordering)'.
						' VALUES (' . implode('),(', $insert) . ');';
				$this->database->setQuery($query);
				$this->database->query();

				unset($insert);
			}
			return true;
		}

		if($element->product_type == 'main') {

			if(!empty($element->product_code) && !empty($element->old->product_code) && $element->product_code != $element->old->product_code) {
				if(HIKASHOP_J30)
					$product_code = "'" . $this->database->escape($element->old->product_code, true) . "%'";
				else
					$product_code = "'" . $this->database->getEscaped($element->old->product_code, true) . "%'";

				$query = 'UPDATE '.hikashop_table('product').
						' SET `product_code` = REPLACE(`product_code`,' . $this->database->Quote($element->old->product_code) . ',' . $this->database->Quote($element->product_code) . ')'.
						' WHERE `product_code` LIKE '.$product_code.' AND product_parent_id = '.(int)$element->product_id.' AND product_type = '.$this->database->Quote('variant');
				$this->database->setQuery($query);
				$this->database->query();
			}

			$config = hikashop_config();
			if($auto_variants === null)
				$auto_variants = $config->get('auto_variants', 1);

			$characteristic_ids = array();
			$default_ids = array();
			$characteristics = array();
			$ordering_max = 0;
			if(!empty($element->characteristics)) {
				foreach($element->characteristics as $c) {
					$characteristic_ids[] = (int)$c->characteristic_id;
					$default_ids[] = (int)$c->default_id;
					$characteristics[ (int)$c->characteristic_id ] = $c;
					$ordering_max = max($ordering_max, $c->ordering);
				}

				foreach($element->characteristics as $c) {
					if($c->ordering <= 0)
						$c->ordering = ++$ordering_max;
				}
			}

			if(!empty($element->oldCharacteristics)) {
				JArrayHelper::toInteger($element->oldCharacteristics);
			} else {
				if(!isset($element->oldCharacteristics)) {
					$query = 'SELECT b.characteristic_id FROM '.hikashop_table('variant').' AS v '.
						' LEFT JOIN '.hikashop_table('characteristic').' AS c ON v.variant_characteristic_id = c.characteristic_id '.
						' WHERE v.variant_product_id = '.(int)$product_id.' AND c.characteristic_parent_id = 0';
					$this->database->setQuery($query);
					if(!HIKASHOP_J25)
						$element->oldCharacteristics = $this->database->loadResultArray();
					else
						$element->oldCharacteristics = $this->database->loadColumn();
				}
				if(empty($element->oldCharacteristics))
					$element->oldCharacteristics = array();
			}

			$addition = array_diff($characteristic_ids, $element->oldCharacteristics);
			$deletion = array_diff($element->oldCharacteristics, $characteristic_ids);

			$query = 'SELECT * FROM '.hikashop_table('variant').' WHERE variant_product_id = ' . (int)$product_id;
			$this->database->setQuery($query);
			$current_data = $this->database->loadObjectList();

			$removed = array();
			$ordering = array();
			$defaults = array();
			if(!empty($default_ids))
				$defaults = array_combine($default_ids, $default_ids);
			if(!empty($current_data)) {
				foreach($current_data as $c) {
					$i = (int)$c->variant_characteristic_id;
					if(isset($characteristics[$i])) {
						if($c->ordering != $characteristics[$i]->ordering)
							$ordering[$i] = $c->ordering;
					} else if(isset($defaults[$i])) {
						unset($defaults[$i]);
					} else {
						$removed[] = $i;
					}
				}
			}

			if($auto_variants == 2) {
				$defaults = array(); $addition = array(); $deletion = array();
				$removed = array(); $ordering = array();
			}

			if(!empty($defaults)) {
				$query = 'INSERT IGNORE INTO ' . hikashop_table('variant') .
					' (variant_characteristic_id, variant_product_id, ordering) VALUES ('.implode(','.(int)$product_id.',0),(', $defaults).','.(int)$product_id.',0)';
				$this->database->setQuery($query);
				$this->database->query();
			}

			if(!empty($addition)) {
				$d = array();
				foreach($addition as $k) {
					$d[] = (int)$k . ',' . (int)$product_id . ',' . (int)$characteristics[(int)$k]->ordering;
				}
				$query = 'INSERT IGNORE INTO ' . hikashop_table('variant') .
					' (variant_characteristic_id, variant_product_id, ordering) VALUES ('.implode('),(', $d).')';
				$this->database->setQuery($query);
				$this->database->query();
			}

			if(!empty($removed) || !empty($ordering)) {
				$ids = array_merge($removed, array_keys($ordering));
				$query = 'DELETE FROM ' . hikashop_table('variant') . ' WHERE '.
					' variant_product_id = ' . (int)$product_id.
					' AND variant_characteristic_id IN ('.implode(',', $ids).')';
				$this->database->setQuery($query);
				$this->database->query();
			}

			if(!empty($ordering)) {
				$d = array();
				foreach($ordering as $k => $v) {
					$d[] = (int)$k . ',' . (int)$product_id . ',' . (int)$v;
				}
				$query = 'INSERT INTO ' . hikashop_table('variant') .
					' (variant_characteristic_id, variant_product_id, ordering) VALUES ('.implode('),(', $d).')';
				unset($d);
				$this->database->setQuery($query);
				$this->database->query();
			}

			if(empty($addition) && empty($deletion) && $auto_variants != 2)
				return true;

			if($auto_variants == 0) {
				if(!empty($addition)) {
					foreach($addition as $a) {
						$query = 'INSERT IGNORE INTO ' . hikashop_table('variant') . ' (variant_characteristic_id, variant_product_id, ordering) ' .
							' SELECT ' . (int)$characteristics[(int)$a]->default_id . ' AS variant_characteristic_id, p.product_id, 0 AS ordering FROM ' . hikashop_table('product') . ' AS p '.
							' WHERE p.product_parent_id = ' . (int)$product_id . ' AND p.product_type = ' . $this->database->Quote('variant');
						$this->database->setQuery($query);
						$this->database->query();

						if(HIKASHOP_J30)
							$product_code = "'" . $this->database->escape($element->product_code, true) . "%'";
						else
							$product_code = "'" . $this->database->getEscaped($element->product_code, true) . "%'";
						$query = 'UPDATE ' . hikashop_table('product') . ' AS p ' .
							' SET p.product_code = CONCAT(p.product_code, \'_'.(int)$characteristics[(int)$a]->default_id.'\') '.
							' WHERE p.product_parent_id = ' . (int)$product_id . ' AND p.product_type = ' . $this->database->Quote('variant') . ' AND p.product_code LIKE '.$product_code;
						$this->database->setQuery($query);
						$this->database->query();
					}
				}

				if(!empty($deletion)) {
					$query = 'DELETE v.* FROM ' . hikashop_table('variant') . ' AS v '.
						' INNER JOIN ' . hikashop_table('characteristic') . ' AS c ON v.variant_characteristic_id = c.characteristic_id '.
						' INNER JOIN ' . hikashop_table('product') . ' AS p ON v.variant_product_id = p.product_id '.
						' WHERE p.product_parent_id = ' . (int)$product_id .
							' AND p.product_type = ' . $this->database->Quote('variant').
							' AND c.characteristic_parent_id IN (' . implode(',', $deletion). ')';
					$this->database->setQuery($query);
					$this->database->query();
				}
			}
			else {
				JPluginHelper::importPlugin('hikashop');
				$dispatcher = JDispatcher::getInstance();

				$query = 'SELECT product_id FROM '.hikashop_table('product').' WHERE product_parent_id = ' . (int)$product_id . ' AND product_type = ' . $this->database->Quote('variant');
				$this->database->setQuery($query);
				if(!HIKASHOP_J25)
					$variant_ids = $this->database->loadResultArray();
				else
					$variant_ids = $this->database->loadColumn();

				$variants = array();
				if(!empty($variant_ids)) {
					JArrayHelper::toInteger($variant_ids);
					if(version_compare(PHP_VERSION, '5.2.0', '>='))
						$variants = array_fill_keys($variant_ids, array());
					else
						$variants = array_combine($variant_ids, array_fill(0, count($variant_ids), array()));

					$query = 'SELECT v.variant_characteristic_id as `characteristic_id`, v.variant_product_id as `product_id`, c.characteristic_parent_id as `characteristic_parent` '.
							' FROM '.hikashop_table('variant').' as v LEFT JOIN '.hikashop_table('characteristic').' AS c ON v.variant_characteristic_id = c.characteristic_id '.
							' WHERE variant_product_id IN ('.implode(',', $variant_ids).')';
					$this->database->setQuery($query);
					$variant_data = $this->database->loadObjectList();
					foreach($variant_data as $d) {
						$variants[ (int)$d->product_id ][ (int)$d->characteristic_parent ] = (int)$d->characteristic_id;
					}
				}

				if(!empty($deletion)) {
					$k_list = array();
					$d_list = array();

					if(!empty($element->characteristics)) {
						foreach($variants as $pid => $variant) {
							$key = array();
							foreach($variant as $k => $v) {
								if(!in_array($k, $deletion)) $key[] = $v;
							}
							sort($key);
							$key = implode(';', $key);
							if(!isset($k_list[$key]) && !isset($d_list[$key])) {
								$k_list[$key] = $pid;
							} else {
								if(isset($k_list[$key])) {
									$d_list[$key] = array($k_list[$key]);
									unset($k_list[$key]);
								}
								$d_list[$key][] = $pid;
							}
						}
					}

					if(!empty($d_list) || empty($element->characteristics)) {
						$old_default = array_diff($removed, $deletion);

						$delete = array();
						foreach($d_list as $k => $products) {
							$r = array();
							foreach($products as $p) {
								$product = $variants[$p];
								$r[$p] = count( array_intersect($product, $old_default) );
							}
							arsort($r);
							$r = array_keys($r);
							$keep = array_shift($r);
							$k_list[$k] = $keep;
							$delete = array_merge($delete, $r);
						}

						if(empty($element->characteristics))
							$delete = array_keys($variants);

						if(!empty($delete)) {
							$dispatcher->trigger('onBeforeVariantsDelete', array($product_id, $delete, $element));

							$query = 'DELETE p, v, pr, f '.
								' FROM '.hikashop_table('product').' AS p '.
									' INNER JOIN '.hikashop_table('variant').' AS v ON p.product_id = v.variant_product_id '.
									' LEFT JOIN '.hikashop_table('price').' AS pr ON p.product_id = pr.price_product_id '.
									' LEFT JOIN '.hikashop_table('file').' AS f ON (p.product_id = f.file_ref_id AND (f.file_type = '.$this->database->Quote('file').' OR f.file_type = '.$this->database->Quote('product').'))'.
								' WHERE p.product_id IN ('.implode(',', $delete).') AND p.product_type = ' . $this->database->Quote('variant');
							$this->database->setQuery($query);
							$this->database->query();

							$translationHelper = hikashop_get('helper.translation');
							$translationHelper->deleteTranslations('product', $delete);

							$dispatcher->trigger('onAfterVariantsDelete', array($product_id, $delete, $element));

							if(!empty($addition)) {
								foreach($delete as $d) {
									unset($variants[$d]);
								}
								foreach($variants as &$v) {
									foreach($deletion as $d) {
										unset($v[$d]);
									}
								}
								unset($v);
							}
						}
					}

					$query = 'DELETE v.* FROM ' . hikashop_table('variant') . ' AS v '.
						' INNER JOIN ' . hikashop_table('characteristic') . ' AS c ON v.variant_characteristic_id = c.characteristic_id '.
						' INNER JOIN ' . hikashop_table('product') . ' AS p ON v.variant_product_id = p.product_id '.
						' WHERE p.product_parent_id = ' . (int)$product_id .
							' AND p.product_type = ' . $this->database->Quote('variant').
							' AND c.characteristic_parent_id IN (' . implode(',', $deletion) . ')';
					$this->database->setQuery($query);
					$this->database->query();

					if(!empty($k_list)) {
						$old_default = array_diff($removed, $deletion);
						$data = 'CONCAT(`product_code`,\'_\')';
						foreach($old_default as $default) {
							$data = 'REPLACE('.$data.',\'_'.$default.'_\',\'_\')';
						}
						$query = 'UPDATE '.hikashop_table('product').
							' SET `product_code` = TRIM(TRAILING \'_\' FROM '.$data.')'.
							' WHERE `product_id` IN ('.implode(',', $k_list).')';
						$this->database->setQuery($query);
						$this->database->query();
					}
				}

				if(!empty($addition) || $auto_variants == 2) {
					$values = array();
					$values_defaults = array();
					foreach($addition as $a) {
						$values[$a] = $element->characteristics[$a]->values;
						$i = (int)$characteristics[(int)$a]->default_id;

						if(!empty($variants)) {
							$values_defaults[] = $i;
							unset($values[$a][ $i ]);
							if(empty($values[$a]))
								unset($values[$a]);
						}

						$query = 'INSERT IGNORE INTO ' . hikashop_table('variant') . ' (variant_characteristic_id, variant_product_id, ordering) ' .
							' SELECT ' . (int)$characteristics[(int)$a]->default_id . ' AS variant_characteristic_id, p.product_id, 0 AS ordering FROM ' . hikashop_table('product') . ' AS p '.
							' WHERE p.product_parent_id = ' . (int)$product_id . ' AND p.product_type = ' . $this->database->Quote('variant');
						$this->database->setQuery($query);
						$this->database->query();
					}
					ksort($values);

					if($auto_variants == 2) {
						if(!empty($element->duplicateVariants)) {

							$v_list = array();
							foreach($variants as $pid => $variant) {
								ksort($variant);
								$key = implode('_', $variant);
								$v_list[$key] = $pid;
							}

							$ids = $element->duplicateVariants;
							JArrayHelper::toInteger($ids);
							$ids = array_combine($ids, $ids);

							$c_id = array_keys($element->characteristics);
							$c_id = reset($c_id);
							$d_list = array();
							$r_list = array();
							foreach($variants as $pid => $variant) {
								if(!isset($ids[$pid])) {
									unset($variants[$pid]);
									continue;
								}

								$v = $variant[$c_id];
								$r_list[$v] = $v;

								ksort($variant);
								foreach($element->characteristics[$c_id]->values as $k => $v) {
									$variant[$c_id] = $v;
									$key = implode('_', $variant);
									$d_list[$key] = $pid;
								}
							}


							$having = null;
							$k_list = array_intersect($v_list, $d_list);
							if(!empty($k_list)) {
								$having = array();
								foreach($k_list as $k => $v) {
									$having[] = $this->database->Quote($element->product_code . '_' . $k);
								}
								$having = ' HAVING c_product_code NOT IN (' . implode(', ', $having) . ')';
							}
						}
						else {
							$having = array();
							sort($characteristic_ids);
							foreach($variants as $k => $v) {
								$f = true;
								foreach($characteristic_ids as $a) {
									if(!isset($v[$a]) || !in_array($v[$a], $element->characteristics[$a]->values)) {
										$f = false;
										break;
									}
								}
								if($f) {
									ksort($v);
									$p = $element->product_code . '_' . implode('_', $v);
									$having[] = $this->database->Quote($p);
								}
							}
							if(!empty($having)) {
								$having = ' HAVING c_product_code NOT IN (' . implode(', ', $having) . ')';
							} else {
								$having = null;
							}
							unset($variants);
							$variants = array();
						}

						foreach($characteristic_ids as $a) {
							$values[$a] = $element->characteristics[$a]->values;
						}
						ksort($values);
					}

					$p_code = $this->database->Quote($element->product_code . '_');
					$t = time();
					$concat = array();
					$tables = array();
					$filters = array();
					foreach($values as $k => $v) {
						$concat[] = 'c'.$k.'.characteristic_id';
						$tables[] = hikashop_table('characteristic') . ' AS c'.$k;
						$filters[] = 'c'.$k.'.characteristic_id IN ('.implode(',', array_keys($v)).')';
					}

					if(!empty($variants)) {
						$duplicate_ids = array_keys($variants);

						$fields = array();
						if(!HIKASHOP_J25) {
							$tmp = $this->database->getTableFields(hikashop_table('product'));
							$fields = reset($tmp);
							unset($tmp);
						} else {
							$fields = $this->database->getTableColumns(hikashop_table('product'));
						}
						unset($fields['product_id']);
						unset($fields['product_code']);
						unset($fields['product_parent_id']);
						unset($fields['product_created']);
						unset($fields['product_modified']);

						unset($fields['product_hit']);
						unset($fields['product_last_seen_date']);

						$fields = array_keys($fields);

						$p_code = 'p.product_code, \'_\'';
						if(!empty($r_list)) {
							$p_code = 'CONCAT(`product_code`,\'_\')';
							foreach($r_list as $r) {
								$p_code = 'REPLACE('.$p_code.',\'_'.$r.'_\',\'_\')';
							}
						}

						$query = 'INSERT IGNORE INTO ' . hikashop_table('product') . ' (product_code, product_parent_id, product_created, product_modified, product_hit, product_last_seen_date, '.implode(', ', $fields).') '.
							' SELECT CONCAT('.$p_code.', '.implode(',\'_\',', $concat).') AS c_product_code, p.product_id, '.$t.', '.$t.', 0, 0, '.implode(', ', $fields).
							' FROM ' . hikashop_table('product') . ' AS p, '. implode(', ', $tables).
							' WHERE p.product_id IN ('.implode(',', $duplicate_ids).') AND ('.implode(') AND (', $filters) . ')';
						if(!empty($having))
							$query .= $having;
						$this->database->setQuery($query);
						$this->database->query();

						if(!empty($values_defaults)) {
							$query = 'UPDATE ' . hikashop_table('product') . ' SET product_code = CONCAT(product_code, \'_'.implode('_', $values_defaults).'\') WHERE product_id IN ('.implode(',', $duplicate_ids).')';
							$this->database->setQuery($query);
							$this->database->query();
						}

						$query = 'INSERT IGNORE INTO ' . hikashop_table('price') . ' (price_product_id, price_currency_id, price_value, price_min_quantity, price_access, price_site_id) '.
							' SELECT p.product_id, pr.price_currency_id, pr.price_value, pr.price_min_quantity, pr.price_access, pr.price_site_id '.
							' FROM ' . hikashop_table('product') . ' AS p '.
							' INNER JOIN ' . hikashop_table('price') . ' AS pr ON p.product_parent_id = pr.price_product_id '.
							' WHERE p.product_parent_id IN ('.implode(',', $duplicate_ids).')';
						$this->database->setQuery($query);
						$this->database->query();

						$query = 'INSERT IGNORE INTO ' . hikashop_table('file') . ' (file_ref_id, file_name, file_description, file_path, file_type, file_free_download, file_ordering, file_limit) '.
							' SELECT p.product_id, f.file_name, f.file_description, f.file_path, f.file_type, f.file_free_download, f.file_ordering, f.file_limit '.
							' FROM ' . hikashop_table('product') . ' AS p '.
							' INNER JOIN ' . hikashop_table('file') . ' AS f ON (p.product_parent_id = f.file_ref_id AND f.file_type IN (\'file\',\'product\')) '.
							' WHERE p.product_parent_id IN ('.implode(',', $duplicate_ids).')';
						$this->database->setQuery($query);
						$this->database->query();

						$query = 'INSERT IGNORE INTO ' . hikashop_table('shipping_price') . ' (shipping_price_ref_id, shipping_id, shipping_price_ref_type, shipping_price_min_quantity, shipping_price_value, shipping_fee_value) '.
							' SELECT p.product_id, s.shipping_id, s.shipping_price_ref_type, s.shipping_price_min_quantity, s.shipping_price_value, s.shipping_fee_value '.
							' FROM ' . hikashop_table('product') . ' AS p '.
							' INNER JOIN ' . hikashop_table('shipping_price') . ' AS s ON (p.product_parent_id = s.shipping_price_ref_id AND shipping_price_ref_type = \'product\')'.
							' WHERE p.product_parent_id IN ('.implode(',', $duplicate_ids).')';
						$this->database->setQuery($query);
						$this->database->query();

						$dispatcher->trigger('onAfterVariantsDuplicate', array($product_id, $duplicate_ids, $element));

						$query = 'SELECT p.product_id, p.product_parent_id, p.product_code FROM ' . hikashop_table('product') . ' AS p WHERE p.product_parent_id IN ('.implode(',', $duplicate_ids).') AND p.product_created = ' . $t;
						$this->database->setQuery($query);
						$new_variants = $this->database->loadObjectList('product_id');
					}
					else {
						$query = 'INSERT IGNORE INTO '.hikashop_table('product').' (product_code, product_type, product_parent_id, product_published, product_modified, product_created, product_group_after_purchase) '.
							' SELECT CONCAT('.$p_code.', '.implode(',\'_\',', $concat).') as c_product_code, '. $this->database->Quote('variant') .','. (int)$product_id . ',1,' . $t . ',' . $t . ',' . $this->database->Quote(@$element->product_group_after_purchase) .
							' FROM ' . implode(', ', $tables) .
							' WHERE (' . implode(') AND (', $filters) . ')';
						if(!empty($having))
							$query .= $having;
						$this->database->setQuery($query);
						$this->database->query();

						$query = 'SELECT p.product_id, p.product_parent_id, p.product_code FROM ' . hikashop_table('product') . ' AS p WHERE p.product_parent_id = ' . (int)$product_id . ' AND p.product_created = ' . $t;
						$this->database->setQuery($query);
						$new_variants = $this->database->loadObjectList('product_id');
					}

					$data = array();
					$count_values = count($values);
					$value_characteristic = array();
					$new_variants_ids = array_keys($new_variants);

					foreach($addition as $a) {
						foreach($element->characteristics[$a]->values as $k => $v) {
							$value_characteristic[ (int)$k ] = (int)$a;
						}
					}

					if($auto_variants == 2) {
						foreach($characteristic_ids as $a) {
							foreach($element->characteristics[$a]->values as $k => $v) {
								$value_characteristic[ (int)$k ] = (int)$a;
							}
						}
					}

					foreach($new_variants as $v) {
						if((int)$v->product_parent_id != (int)$product_id) {
							foreach($variants[(int)$v->product_parent_id] as $k => $variant) {
								if($auto_variants == 2 && in_array($k, $characteristic_ids))
									continue;
								$data[] = (int)$variant . ',' . (int)$v->product_id;
							}
						}
						$codes = explode('_', $v->product_code);
						$codes = array_slice($codes, -$count_values);
						foreach($codes as $code) {
							if(isset($value_characteristic[ (int)$code ]))
								$data[] = (int)$code . ',' . (int)$v->product_id;
						}
					}
					unset($value_characteristic);
					unset($count_values);
					unset($new_variants);

					while(!empty($data)) {
						$sql_data = array_slice($data, 0, 250);
						$data = array_slice($data, 250);
						$query = 'INSERT IGNORE INTO ' . hikashop_table('variant') . ' (variant_characteristic_id, variant_product_id, ordering) '.
							' VALUES ('.implode(',0), (', $sql_data).',0)';
						$this->database->setQuery($query);
						$this->database->query();
						unset($sql_data);
					}

					if(!empty($variants)) {
						if(empty($duplicate_ids))
							$duplicate_ids = array_keys($variants);
						$query = 'UPDATE ' . hikashop_table('product') . ' SET product_parent_id = ' . (int)$product_id . ' WHERE product_parent_id IN ('.implode(',', $duplicate_ids).')';
						$this->database->setQuery($query);
						$this->database->query();
					}

					$dispatcher->trigger('onAfterVariantsCreation', array($product_id, $new_variants_ids, $element));
				}
			}
		}
		return true;
	}

	function updateRelated($element,$status,$type='related'){
		if($element->product_type=='variant') return true;
		$filter='';
		$config = hikashop_config();
		$both_ways = $config->get('product_association_in_both_ways',0);
		if($both_ways){
			$query = 'SELECT product_related_id FROM '.hikashop_table('product_related').' WHERE product_related_type=\''.$type.'\' AND product_id = '.$status.$filter;
			$this->database->setQuery($query);
			$this->database->query();
			$products = $products = $this->database->loadObjectList();
		}


		$query = 'DELETE FROM '.hikashop_table('product_related').' WHERE product_related_type=\''.$type.'\' AND product_id = '.$status.$filter;
		$this->database->setQuery($query);
		$this->database->query();
		if(count($element->$type)){
			$insert = array();
			foreach($element->$type as $new){
				$insert[]='('.$new->product_related_id.','.$status.',\''.$type.'\',\''.(int)$new->product_related_ordering.'\')';
			}
			if($both_ways && $type=='related'){
				foreach($element->$type as $new){
					$insert[]='('.$status.','.$new->product_related_id.',\''.$type.'\',\''.(int)$new->product_related_ordering.'\')';
					foreach($products as $product){
						if($product->product_related_id == $new->product_related_id){
							$product->still_related = true;
						}
					}
				}
			}
			$query = 'INSERT IGNORE INTO '.hikashop_table('product_related').' (product_related_id,product_id,product_related_type,product_related_ordering) VALUES '.implode(',',$insert).';';
			$this->database->setQuery($query);
			$this->database->query();
		}
			if($both_ways && $type=='related'){
				$ids=array();
				foreach($products as $product){
					if(!isset($product->still_related) || $product->still_related != true){
						$ids[]=$product->product_related_id;
					}
				}

				if(count($ids)){
					$query = 'DELETE FROM '.hikashop_table('product_related').' WHERE product_related_type=\''.$type.'\' AND product_id IN ('.implode(',',$ids).') AND product_related_id = '.$status;
					$this->database->setQuery($query);
					$this->database->query();
				}
			}
	}

	function updateCategories(&$element, $status) {
		if($element->product_type=='variant')
			return false;

		if(empty($element->categories) && $element->product_type == 'main') {
			$query = 'SELECT category_id FROM '.hikashop_table('category').' WHERE category_type=\'root\' AND category_parent_id=0 LIMIT 1';
			$this->database->setQuery($query);
			$root = $this->database->loadResult();
			$query = 'SELECT category_id FROM '.hikashop_table('category').' WHERE category_parent_id='.(int)$root.' AND category_type=\'product\' LIMIT 1';
			$this->database->setQuery($query);
			$root = $this->database->loadResult();
			$element->categories = array($root);
		}

		$this->database->setQuery('SELECT category_id FROM '.hikashop_table('product_category').' WHERE product_id='.$status);
		if(!HIKASHOP_J25) {
			$olds = $this->database->loadResultArray();
		} else {
			$olds = $this->database->loadColumn();
		}

		$do_nothing = array_intersect($element->categories, $olds);
		$delete = array_diff($olds, $do_nothing);
		$news = array_diff($element->categories, $do_nothing);
		if(!empty($delete)){
			$this->database->setQuery('DELETE FROM '.hikashop_table('product_category').' WHERE product_id='.$status.' AND category_id IN ('.implode(',',$delete).')');
			$this->database->query();
		}
		if(!empty($news)){
			$insert = array();
			foreach($news as $new){
				$insert[]='('.$new.','.$status.')';
			}
			$query = 'INSERT IGNORE INTO '.hikashop_table('product_category').' (category_id,product_id) VALUES '.implode(',',$insert).';';
			$this->database->setQuery($query);
			$this->database->query();
		}

		$reorders = array_merge($news, $delete);
		if(!empty($reorders)){
			$orderClass = hikashop_get('helper.order');
			$orderClass->pkey = 'product_category_id';
			$orderClass->table = 'product_category';
			$orderClass->groupMap = 'category_id';
			$orderClass->orderingMap = 'ordering';
			foreach($reorders as $reorder){
				$orderClass->groupVal = $reorder;
				$orderClass->reOrder();
			}
		}

		return (!empty($news) || !empty($delete));
	}

	function updateFiles(&$element,$status,$type='images',$orders=null){
		$filter='';
		if(count($element->$type)){
			$filter = 'AND file_id NOT IN ('.implode(',',$element->$type).')';
		}
		$file_type = 'product';
		if($type == 'files'){
			$file_type = 'file';
		}
		$main = ' FROM '.hikashop_table('file').' WHERE file_ref_id = '.$status.' AND file_type=\''.$file_type.'\' AND SUBSTRING(file_path,1,1) != \'@\' '.$filter;
		$this->database->setQuery('SELECT file_path '.$main);
		if(!HIKASHOP_J25){
			$toBeRemovedFiles = $this->database->loadResultArray();
		} else {
			$toBeRemovedFiles = $this->database->loadColumn();
		}
		if(!empty($toBeRemovedFiles)){
			$file = hikashop_get('class.file');
			$uploadPath = $file->getPath($file_type);
			$oldFiles = array();
			foreach($toBeRemovedFiles as $old){
				$oldFiles[] = $this->database->Quote($old);
			}
			$this->database->setQuery('SELECT file_path FROM '.hikashop_table('file').' WHERE file_path IN ('.implode(',',$oldFiles).') AND file_ref_id != '.$status);
			if(!HIKASHOP_J25){
				$keepFiles = $this->database->loadResultArray();
			} else {
				$keepFiles = $this->database->loadColumn();
			}
			foreach($toBeRemovedFiles as $old){
				if((empty($keepFiles) || !in_array($old,$keepFiles)) && JFile::exists( $uploadPath . $old)){
					JFile::delete( $uploadPath . $old );
					jimport('joomla.filesystem.folder');
					$thumbnail_folders = JFolder::folders($uploadPath);
					if(JFolder::exists($uploadPath.'thumbnails'.DS)) {
						$other_thumbnail_folders = JFolder::folders($uploadPath.'thumbnails');
						foreach($other_thumbnail_folders as $other_thumbnail_folder) {
							$thumbnail_folders[] = 'thumbnails'.DS.$other_thumbnail_folder;
						}
					}
					foreach($thumbnail_folders as $thumbnail_folder){
						if($thumbnail_folder != 'thumbnail' && substr($thumbnail_folder, 0, 9) != 'thumbnail' && substr($thumbnail_folder, 0, 11) != ('thumbnails'.DS))
							continue;
						if(!in_array($file_type,array('file','watermark')) && JFile::exists(  $uploadPath .$thumbnail_folder.DS. $old)){
							JFile::delete( $uploadPath .$thumbnail_folder.DS. $old );
						}
					}
				}
			}
			$this->database->setQuery('DELETE'.$main);
			$this->database->query();
		}
		if(!empty($orders) && is_array($element->$type) && count($element->$type)) {
			$this->database->setQuery('SELECT file_id, file_ordering FROM '.hikashop_table('file').' WHERE file_id IN ('.implode(',',$element->$type).')');
			$oldOrders = $this->database->loadObjectList();
			if(!empty($oldOrders)) {
				foreach($oldOrders as $oldOrder) {
					if(isset($orders[$oldOrder->file_id]) && $orders[$oldOrder->file_id] != $oldOrder->file_ordering) {
						$this->database->setQuery('UPDATE '.hikashop_table('file').' SET file_ordering = '.(int)$orders[$oldOrder->file_id].' WHERE file_id = '.$oldOrder->file_id);
						$this->database->query();
					}
				}
			}
		}
		if(count($element->$type)){
			$query = 'UPDATE '.hikashop_table('file').' SET file_ref_id='.$status.' WHERE file_id IN ('.implode(',',$element->$type).') AND file_ref_id=0';
			$this->database->setQuery($query);
			$this->database->query();
		}
	}

	function delete(&$elements, $ignoreFile=false){
		if(!is_array($elements))
			$elements = array($elements);

		if(!empty($elements)) {
			$query ='SELECT product_id FROM '.hikashop_table('product').' WHERE product_type=\'variant\' AND product_parent_id IN ('.implode(',',$elements).')';
			$this->database->setQuery($query);
			if(!HIKASHOP_J25)
				$elements = array_merge($elements, $this->database->loadResultArray());
			else
				$elements = array_merge($elements, $this->database->loadColumn());
		}

		JArrayHelper::toInteger($elements);
		JPluginHelper::importPlugin('hikashop');
		$dispatcher = JDispatcher::getInstance();
		$do = true;
		$dispatcher->trigger('onBeforeProductDelete', array(&$elements, &$do));
		if(!$do)
			return false;

		$status = parent::delete($elements);
		if($status) {
			$dispatcher->trigger('onAfterProductDelete', array(&$elements));

			$tagsHelper = hikashop_get('helper.tags');
			$tagsHelper->deleteUCM('product', $elements);

			$class = hikashop_get('class.file');
			$class->deleteFiles('product', $elements, $ignoreFile);
			$class->deleteFiles('file', $elements, $ignoreFile);
			$class = hikashop_get('helper.translation');
			$class->deleteTranslations('product', $elements);
			return count($elements);
		}
		return $status;
	}

	function addFiles(&$element,&$files){
		if(!empty($element->variants)){
			foreach($element->variants as $k => $variant){
				$this->addFiles($element->variants[$k],$files);
			}
		}
		if(!empty($element->options)){
			foreach($element->options as $k => $optionElement){
				$this->addFiles($element->options[$k],$files);
			}
		}
		foreach($files as $file){
			if($file->file_ref_id==$element->product_id){
				if($file->file_type=='file'){
					$element->files[]=$file;
				}else{
					$element->images[]=$file;
				}
			}
		}
	}

	function checkVariant(&$variant,&$element,$map=array(),$force=false){
		if(!empty($variant->variant_checked)) return true;
		$checkfields = array('product_name','product_description','prices','images','discount','product_url',
							'product_weight','product_weight_unit','product_keywords','product_meta_description',
							'product_dimension_unit','product_width','product_length','product_height','files',
							'product_contact','product_max_per_order','product_min_per_order','product_sale_start',
							'product_sale_end','product_manufacturer_id','file_path','file_name','file_description',
							'product_warehouse_id');
		$fieldsClass = hikashop_get('class.field');
		$fields = $fieldsClass->getFields('frontcomp',$element,'product','checkout&task=state');
		foreach($fields as $field){
			$checkfields[]=$field->field_namekey;
		}
		if(empty($variant->product_id)){
			$variant->product_id=$element->product_id;
			$variant->map=implode('_',$map);
			$variant->product_parent_id=$element->product_id;
			$variant->product_quantity = 0;
			$variant->product_code = '';
			$variant->product_published = -1;
			$variant->product_type = 'variant';
			$variant->product_sale_start = 0;
			$variant->product_sale_end = 0;
			$variant->characteristics=array();
			foreach($map as $k => $id){
				$variant->characteristics[$id]=$element->characteristics[$k]->values[$id];
			}
		}elseif(empty($variant->characteristics)){
			$variant->characteristics=array();
		}

		if(isset($variant->product_weight) && $variant->product_weight==0){
			$variant->product_weight_unit=$element->product_weight_unit;
		}
		if(isset($variant->product_length) && isset($variant->product_height) && isset($variant->product_width) && $variant->product_length==0 && $variant->product_height==0 && $variant->product_width==0){
			$variant->product_dimension_unit=$element->product_dimension_unit;
		}

		$variant->main_product_name = @$element->product_name;
		$variant->characteristics_text = '';
		$variant->variant_name = @$variant->product_name;
		$config =& hikashop_config();
		$perfs = $config->get('variant_increase_perf','1');
		$separator = JText::_('HIKA_VARIANTS_MIDDLE_SEPARATOR');
		if($separator == 'HIKA_VARIANTS_MIDDLE_SEPARATOR')
			$separator = ' ';
		$product_price_percentage = @$variant->product_price_percentage;
		foreach($checkfields as $field){
			if(!empty($variant->$field)){
				if($field != 'product_name' && (!is_numeric($variant->$field) || bccomp($variant->$field,0,5))){
					continue;
				}
			}

			if(isset($element->$field) && (is_array($element->$field) && count($element->$field) || is_object($element->$field))){
				$variant->$field=$this->_copy($element->$field);

				if($field=='prices'){
					if(!empty($variant->cart_product_total_variants_quantity)){
						$variant->cart_product_total_quantity = $variant->cart_product_total_variants_quantity;
					}
					if($product_price_percentage>0){
						foreach($variant->$field as $k => $v){
							foreach(get_object_vars($v) as $key => $value){
								if(in_array($key, array('taxes_without_discount','taxes','taxes_orig'))){
									foreach($value as $taxKey => $tax){
										$variant->prices[$k]->taxes[$taxKey]->tax_amount = $tax->tax_amount*$product_price_percentage/100;
									}
								}elseif(!in_array($key,array('price_currency_id','price_orig_currency_id','price_min_quantity','price_access'))){
									$variant->prices[$k]->$key = $value*$product_price_percentage/100;
								}
							}
						}
					}
				}
			}else{
				if($field=='product_name'){
					if(!empty($variant->characteristics)){
						foreach($variant->characteristics as $val){
							$variant->characteristics_text.=$separator.$val->characteristic_value;
						}
					}
				}elseif(!$perfs || $force){
					$variant->$field = @$element->$field;
				}
			}
		}
		if(empty($variant->product_name)){
			$variant->product_name=$variant->main_product_name;
		}
		$config =& hikashop_config();
		if(!empty($variant->main_product_name) && $config->get('append_characteristic_values_to_product_name',1)){
			$separator = JText::_('HIKA_VARIANT_SEPARATOR');
			if($separator == 'HIKA_VARIANT_SEPARATOR')
				$separator = ': ';
			$variant->product_name = $variant->main_product_name.'<span class="hikashop_product_variant_subname">'.$separator.$variant->characteristics_text.'</span>';
		}
		if(!$variant->product_published){
			$variant->product_quantity=0;
		}
		$variant->variant_checked = true;
	}
	function _copy(&$src){
		if(is_array($src)){
			$array = array();
			foreach($src as $k => $v){
				$array[$k]=$this->_copy($v);
			}
			return $array;
		}elseif(is_object($src)){
			$obj = new stdClass();
			foreach(get_object_vars($src) as $k => $v){
				$obj->$k=$this->_copy($v);
			}
			return $obj;
		}else{
			return $src;
		}
	}

	function generateVariantData(&$element){
		$config =& hikashop_config();
		$perfs = $config->get('variant_increase_perf',1);
		if($perfs && !empty($element->main)){
			$required_fields = array();

			foreach (get_object_vars($element->main) as $name=>$value) {
				if(!is_array($name)&&!is_object($name)){
					$required = false;

					foreach ($element->variants as $variant) {
						if(!empty($variant->$name) && (!is_numeric($variant->$name) || $variant->$name>0)){
							$required = true;
							break;
						}
					}
					if($required){
						foreach ($element->variants as $k=>$variant) {
							if(empty($variant->$name) || (is_numeric($variant->$name) && $variant->$name==0.0)){
								if($name=='product_quantity' && $variant->$name==0){
									continue;
								}
								$element->variants[$k]->$name=$element->main->$name;
							}
						}
					}
				}
			}
		}

		if(!isset($element->main->images)){
			if(!isset($element->main)) $element->main = new stdClass();
			$element->main->images=null;
		}
	}

	public function getTreeList($start = 0, $depth = 1, $serialized = false, $display = '', $limit = 200) {
		$app = JFactory::getApplication();
		$db = JFactory::getDBO();

		if(empty($display) || strpos($display, '%name%') === false)
			$display = '%name%';

		if($depth <= 0)
			$depth = 1;

		if($start > 0) {
			$query = 'SELECT a.*, b.category_depth as `base_depth`' .
				' FROM ' . hikashop_table('category') . ' AS a' .
				' INNER JOIN ' . hikashop_table('category') . ' AS b ON a.category_left >= b.category_left AND a.category_right <= b.category_right'.
				' WHERE b.category_id = ' . $start . ' AND a.category_type IN (\'product\',\'manufacturer\',\'vendor\',\'root\') AND a.category_depth >= b.category_depth AND a.category_depth <= (b.category_depth + ' . $depth . ')'.
				' ORDER BY a.category_left ASC, a.category_name ASC';
		} else {
			$query = 'SELECT a.*, 0 as `base_depth`' .
				' FROM ' . hikashop_table('category') . ' AS a' .
				' WHERE a.category_type IN (\'product\',\'manufacturer\',\'vendor\',\'root\') AND a.category_depth >= 0 AND a.category_depth <= ' . $depth .
				' ORDER BY a.category_left ASC, a.category_name ASC';
		}
		$db->setQuery($query);
		$category_elements = $db->loadObjectList();
		$categories = array();

		foreach($category_elements as &$element) {
			if(empty($element->value)){
				$val = str_replace(' ', '_', strtoupper($element->category_name));
				$element->value = JText::_($val);
				if($val == $element->value) {
					$element->value = $element->category_name;
				}
			}
			$element->category_name = $element->value;

			if($element->category_namekey == 'root') {
				$element->category_parent_id = -1;
			}

			if($element->category_depth < $element->base_depth + $depth) {
				$categories[] = (int)$element->category_id;
			}

			unset($element);
		}

		$product_elements = array();
		if(!empty($categories)) {
			$query = 'SELECT a.*, c.category_id FROM ' . hikashop_table('product') . ' AS a'.
				' INNER JOIN ' . hikashop_table('product_category') . ' AS b ON a.product_id = b.product_id'.
				' INNER JOIN ' . hikashop_table('category') . ' AS c ON c.category_id = b.category_id'.
				' WHERE b.category_id IN (' . implode(',', $categories) . ')'.
				' ORDER BY c.category_left ASC, c.category_name ASC, a.product_name ASC';
			$db->setQuery($query, 0, $limit);
			$product_elements = $db->loadObjectList();
		}

		if(!$serialized) {
			return array($category_elements, $product_elements);
		}

		$tree = array();
		$nodes = array();

		foreach($category_elements as $element) {
			$obj = new stdClass();
			$obj->status = 2;
			$obj->name = $element->category_name;
			$obj->value = $element->category_id;
			if($element->category_type == 'root') {
				$obj->status = 5;
				$obj->icon = 'world';
				$obj->noselection = 1;
			}
			if($element->category_depth == $element->base_depth + $depth) {
				$obj->status = 3;
			}
			$obj->data = array();

			if(!empty($element->category_parent_id) && $element->category_id != $element->category_parent_id && isset($nodes[$element->category_parent_id])) {
				$nodes[$element->category_parent_id]->data[] =& $obj;
			} else {
				$tree[] =& $obj;
			}
			if($element->category_type != 'root') {
				$nodes[$element->category_id] =& $obj;
			}
			unset($obj);
		}
		unset($category_elements);

		foreach($product_elements as $element) {
			$obj = new stdClass();
			$obj->status = 0;
			if(empty($display) || $display == '%name%') {
				$obj->name = $element->product_name;
			} else {
				if($element->product_quantity == -1)
					$element->product_quantity = JText::_('UNLIMITED');
				$obj->name = str_replace(
					array('%name%', '%code%', '%qty%'),
					array($element->product_name, $element->product_code, $element->product_quantity),
					$display
				);
			}
			$obj->value = $element->product_id;

			if(!empty($element->category_id) && isset($nodes[$element->category_id])) {
				$nodes[$element->category_id]->data[] =& $obj;
			} else {
				$tree[] =& $obj;
			}
			unset($obj);
		}
		unset($product_elements);

		foreach($nodes as &$node) {
			if(empty($node->data)) {
				unset($node->data);
				if($node->status == 2)
					$node->status = 4;
			}
			unset($node);
		}

		return $tree;
	}

	public function findTreeList($search = '', $start = 0, $serialized = false, $display = '', $limit = 200) {
		$app = JFactory::getApplication();
		$db = JFactory::getDBO();

		if(empty($display) || strpos($display, '%name%') === false)
			$display = '%name%';

		if($start > 0) {
			$query = 'SELECT a.*' .
					' FROM ' . hikashop_table('category') . ' AS a' .
					' INNER JOIN ' . hikashop_table('category') . ' AS b ON a.category_left >= b.category_left AND a.category_right <= b.category_right'.
					' WHERE b.category_id = ' . $start . ' AND a.category_type IN (\'product\',\'root\')'.
					' ORDER BY a.category_left ASC, a.category_name ASC';
		} else {
			$query = 'SELECT a.*' .
					' FROM ' . hikashop_table('category') . ' AS a' .
					' WHERE a.category_type IN (\'product\',\'root\')' .
					' ORDER BY a.category_left ASC, a.category_name ASC';
		}
		$db->setQuery($query);
		$category_elements = $db->loadObjectList();
		$categories = array();

		foreach($category_elements as &$element) {
			if(empty($element->value)){
				$val = str_replace(' ', '_', strtoupper($element->category_name));
				$element->value = JText::_($val);
				if($val == $element->value) {
					$element->value = $element->category_name;
				}
			}
			$element->category_name = $element->value;
			if($element->category_namekey == 'root') {
				$element->category_parent_id = -1;
			}
			$categories[] = $element->category_id;
			unset($element);
		}

		$product_elements = array();
		if(!empty($categories)) {
			if(HIKASHOP_J30)
				$searchStr = "'%" . $db->escape($search, true) . "%'";
			else
				$searchStr = "'%" . $db->getEscaped($search, true) . "%'";

			$query = 'SELECT a.*, c.category_id FROM ' . hikashop_table('product') . ' AS a'.
					' INNER JOIN ' . hikashop_table('product_category') . ' AS b ON a.product_id = b.product_id'.
					' INNER JOIN ' . hikashop_table('category') . ' AS c ON c.category_id = b.category_id'.
					' WHERE (a.product_name LIKE '.$searchStr.' OR a.product_code LIKE '.$searchStr.') AND b.category_id IN (' . implode(',', $categories) . ')'.
					' ORDER BY c.category_left ASC, c.category_name ASC, a.product_name ASC';
			$db->setQuery($query, 0, $limit);
			$product_elements = $db->loadObjectList();
		}

		if(!$serialized) {
			return array($category_elements, $product_elements);
		}

		$tree = array();
		$nodes = array();

		foreach($category_elements as $element) {
			$obj = new stdClass();
			$obj->status = 2;
			$obj->name = $element->category_name;
			$obj->value = $element->category_id;
			if($element->category_type == 'root') {
				$obj->status = 5;
				$obj->icon = 'world';
				$obj->noselection = 1;
			}
			$obj->data = array();

			if(!empty($element->category_parent_id) && $element->category_id != $element->category_parent_id && isset($nodes[$element->category_parent_id])) {
				$nodes[$element->category_parent_id]->data[] =& $obj;
			} else {
				$tree[] =& $obj;
			}
			if($element->category_type != 'root') {
				$nodes[$element->category_id] =& $obj;
			}
			unset($obj);
		}

		foreach($product_elements as $element) {
			$obj = new stdClass();
			$obj->status = 0;
			if(empty($display) || $display == '%name%') {
				$obj->name = $element->product_name;
			} else {
				if($element->product_quantity == -1)
					$element->product_quantity = JText::_('UNLIMITED');
				$obj->name = str_replace(
						array('%name%', '%code%', '%qty%'),
						array($element->product_name, $element->product_code, $element->product_quantity),
						$display
				);
			}
			$obj->value = $element->product_id;

			if(!empty($element->category_id) && isset($nodes[$element->category_id])) {
				$nodes[$element->category_id]->data[] =& $obj;
			} else {
				$tree[] =& $obj;
			}
			unset($obj);
		}
		unset($product_elements);

		$reverse_categories = array_reverse($category_elements);
		foreach($reverse_categories as $element) {
			$id = (int)$element->category_id;
			if(isset($nodes[$id]) && empty($nodes[$id]->data) && $nodes[$id]->status != 5) {
				if(isset($element->category_parent_id) && isset($nodes[$element->category_parent_id])) {
					foreach($nodes[$element->category_parent_id]->data as $k => $v) {
						if(isset($v->value) && $v->status != 0 && $v->value == $id)
							unset($nodes[$element->category_parent_id]->data[$k]);
					}
				}
				$nodes[$id] = null;
				unset($nodes[$id]);
			}
		}

		foreach($tree as $k => $t) {
			if($t === null)
				unset($tree[$k]);
		}

		foreach($nodes as &$node) {
			if(empty($node->data)) {
				unset($node->data);
			} else {
				$node->data = array_values($node->data);
			}
			unset($node);
		}

		return $tree;
	}

}
