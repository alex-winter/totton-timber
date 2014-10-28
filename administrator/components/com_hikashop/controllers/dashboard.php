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
class DashboardController extends hikashopController{
	var $type = 'widget';

	function __construct($config = array()) {
		$this->display = array('listing','csv','cpanel');
		$this->modify_views = array('edit');
		$this->add = array('add');
		$this->modify = array('save');
		$this->delete = array('delete','remove');
		parent::__construct($config);
	}

	function cpanel() {
		JRequest::setVar('layout', 'cpanel');
		return $this->display();
	}

	function save() {
		if($this->store()) {
			echo '<html><head><script type="text/javascript">parent.window.location.href=\''.hikashop_completeLink('dashboard',false,true).'\';</script></head><body></body></html>';
			exit;
		}
	}

}
