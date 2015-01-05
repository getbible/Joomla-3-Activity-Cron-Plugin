<?php
/**
* 
* 	@version 	1.0.5  December 08, 2014
* 	@package 	Get Bible API
* 	@author  	Llewellyn van der Merwe <llewellyn@vdm.io>
* 	@copyright	Copyright (C) 2013 Vast Development Method <http://www.vdm.io>
* 	@license	GNU General Public License <http://www.gnu.org/copyleft/gpl.html>
*
**/

defined('_JEXEC') or die;

class plgSystemGetBibleActivityCronInstallerScript
{
	public function preflight($type, $parent) {
		if ($type == 'uninstall') {
			return true;
		}
		
		$app = JFactory::getApplication();
		
		$jversion = new JVersion();
		if (!$jversion->isCompatible('3.0.0')) {
			$app->enqueueMessage('Please upgrade to at least Joomla! 3.0.0 before continuing!', 'error');
			return false;
		}
		
		if (!file_exists(JPATH_ADMINISTRATOR.'/components/com_getbible/helpers/get.php') && !file_exists(JPATH_ADMINISTRATOR.'/components/com_ipdata/helpers/ipdata.php')) {
			$app->enqueueMessage('Please install the <a href="https://getbible.net/downloads" target="_blank">GetBible component</a> and please also install the <a href="https://www.vdm.io/joomla/item/ip-data" target="_blank">Ipdata component</a> before continuing.', 'error');
			return false;
		}
		
		if (!file_exists(JPATH_ADMINISTRATOR.'/components/com_getbible/helpers/get.php')) {
			$app->enqueueMessage('Please install the <a href="https://getbible.net/downloads" target="_blank">GetBible component</a> before continuing.', 'error');
			return false;
		}
		
		if (!file_exists(JPATH_ADMINISTRATOR.'/components/com_getbible/models/get.php') && !file_exists(JPATH_ADMINISTRATOR.'/components/com_ipdata/helpers/ipdata.php')) {
			$app->enqueueMessage('You must first upgrade the GetBible component to <a href="https://getbible.net/downloads" target="_blank">v1.0.6</a> and please also install the <a href="https://www.vdm.io/joomla/item/ip-data" target="_blank">Ipdata component</a> before continuing.', 'error');
			return false;
		}
		
		if (!file_exists(JPATH_ADMINISTRATOR.'/components/com_getbible/models/get.php')) {
			$app->enqueueMessage('You must first upgrade the GetBible component to <a href="https://getbible.net/downloads" target="_blank">v1.0.6</a> before continuing.', 'error');
			return false;
		}
		
		if (!file_exists(JPATH_ADMINISTRATOR.'/components/com_ipdata/helpers/ipdata.php')) {
			$app->enqueueMessage('Please install the <a href="https://www.vdm.io/joomla/item/ip-data" target="_blank">Ipdata component</a> before continuing.', 'error');
			return false;
		}
		
		return true;
	}
	
	/**
	 * method to run after an install/update/uninstall method
	 *
	 * @return void
	 */
	function postflight($type, $parent)
	{
		if ($type == 'install') {
			// Set Global Settings
			$db = JFactory::getDBO();
			$query = $db->getQuery(true);
			
			// Fields to update.
			$fields = array(
				$db->quoteName('params') . ' = ' . $db->quote('{"timer":"-1 day"}'),
				$db->quoteName('enabled') . ' = ' . $db->quote('1')
			);
			$conditions = array(
				$db->quoteName('name').' = '.$db->quote('plg_system_getbibleactivitycron'), 
				$db->quoteName('type').' = '.$db->quote('plugin'), 
				$db->quoteName('element').' = '.$db->quote('getbibleactivitycron'),
				$db->quoteName('folder').' = '.$db->quote('system')
			);
			$query->update($db->quoteName('#__extensions'))->set($fields)->where($conditions);
 			$db->setQuery($query);
			
			$app 	= JFactory::getApplication();
			if($db->query()){
				$app->enqueueMessage('Plugin was published, and should run the cron once a day! To change the timer open the plugin in the plugin manager and adjust it manually.', 'message');
				return false;
			} else {
				$app->enqueueMessage('There was an error setting the plugin status, please do it manually in the plugin manager!', 'error');
				return false;
			}
		}
	}
	
	public function update($parent) {
		$this->copyFiles($parent);
	}
	
	public function install($parent) {
		$this->copyFiles($parent);
	}
	
	public function uninstall($parent){
		// remove activityCron helper file
		$activityCron	= JPATH_ADMINISTRATOR.'/components/com_getbible/helpers/activityCron.php';
		if (!JFile::delete($activityCron)) {
			$app->enqueueMessage('Could not delete '.str_replace(JPATH_ADMINISTRATOR, '', $activityCron).', please remove manually.', 'error');
		}
		// setup front-end path
		$site = JPATH_SITE.'/components/com_getbible';
		// remove front module
		$model = $site.'/models/activity.php';
		if (!JFile::delete($model)) {
			$app->enqueueMessage('Could not delete '.str_replace(JPATH_SITE, '', $model).', please remove manually.', 'error');
		}
		// remove fron  controller
		$controller = $site.'/controllers/activity.php';
		if (!JFile::delete($controller)) {
			$app->enqueueMessage('Could not delete '.str_replace(JPATH_SITE, '', $controller).', please remove manually.', 'error');
		}
		// remove fron view
		$view = $site.'/views/activity';
		if (!JFolder::delete($view)) {
			$app->enqueueMessage('Could not delete '.str_replace(JPATH_SITE, '', $view).', please remove manually.', 'error');
		}
	}
	
	protected function copyFiles($parent) {
		$app = JFactory::getApplication();
		$installer = $parent->getParent();
		
		// move the admin folder
		$admin = JPATH_ADMINISTRATOR.'/components/com_getbible';
		$src = $installer->getPath('source').'/admin';
		if (!JFolder::copy($src, $admin, '', true)) {
			$app->enqueueMessage('Could not copy to '.str_replace(JPATH_ADMINISTRATOR, '', $admin).', please make sure destination is writable!', 'error');
		}
		
		// move the site folder
		$site = JPATH_SITE.'/components/com_getbible';
		$src = $installer->getPath('source').'/site';
		if (!JFolder::copy($src, $site, '', true)) {
			$app->enqueueMessage('Could not copy to '.str_replace(JPATH_SITE, '', $site).', please make sure destination is writable!', 'error');
		}
	}
	
}