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
class CurrencyController extends hikashopController{
	var $modify = array();
	var $delete = array();
	var $modify_views = array();
	function __construct($config = array(),$skip=false){
		$this->display[]='update';
		if(!$skip){
			parent::__construct($config,$skip);
			$this->registerDefaultTask('update');
		}
		JRequest::setVar('tmpl','component');
	}
	function update(){
		$currency=JRequest::getInt('hikashopcurrency',0);
		if(!empty($currency)){
			$app = JFactory::getApplication();
			$app->setUserState( HIKASHOP_COMPONENT.'.currency_id', $currency );
			$app->setUserState(HIKASHOP_COMPONENT.'.shipping_method', null);
			$app->setUserState(HIKASHOP_COMPONENT.'.shipping_id', null);
			$app->setUserState(HIKASHOP_COMPONENT.'.shipping_data', null);
			$app->setUserState(HIKASHOP_COMPONENT.'.payment_method', null);
			$app->setUserState(HIKASHOP_COMPONENT.'.payment_id', null);
			$app->setUserState(HIKASHOP_COMPONENT.'.payment_data', null);
			$url = JRequest::getString('return_url','');
			if(!empty($url)){
				if(hikashop_disallowUrlRedirect($url)) return false;
				$app->redirect(urldecode($url));
			}
		}
		return true;
	}
}
