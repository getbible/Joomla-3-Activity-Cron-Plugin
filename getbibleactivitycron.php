<?php
/**
* 
* 	@version 	1.0.7  January 16, 2015
* 	@package 	Get Bible API
* 	@author  	Llewellyn van der Merwe <llewellyn@vdm.io>
* 	@copyright	Copyright (C) 2013 Vast Development Method <http://www.vdm.io>
* 	@license	GNU General Public License <http://www.gnu.org/copyleft/gpl.html>
*
**/

defined( '_JEXEC' ) or die( 'Restricted access' );

jimport( 'joomla.plugin.plugin' );

jimport('joomla.application.component.helper');

// Added for Joomla 3.0
if(!defined('DS')){
	define('DS',DIRECTORY_SEPARATOR);
};

/**
 * Get Bible Activity Cron Plugin
 */
class plgSystemGetBibleActivityCron extends JPlugin
{
	protected $document;
	protected $com_params;
	
	protected function canRun() {
		if (file_exists(JPATH_ADMINISTRATOR.DS.'components'.DS.'com_getbible'.DS.'helpers'.DS.'activityCron.php')) {
			require_once JPATH_ADMINISTRATOR.DS.'components'.DS.'com_getbible'.DS.'helpers'.DS.'activityCron.php';
			// get timer
			$timer = $this->params->get('timer', '-1 day');
			if(GetBibleActivityCron::canRun($timer)){
				return true;
			}
		}
		return false;
	}
	
	public function onAfterDispatch() {
		if (!$this->canRun()) {
			return;
		}
		// run the cron job
		GetBibleActivityCron::cronJob();
	}
	
	/** 
	 * Event Triggered in Back-end [on Before cPanel display]
	 */
	public function getbible_bk_onBefore_cPanel_display($array)
	{
		// set the return url
		$uri = (string) JUri::getInstance();
		$return = urlencode(base64_encode($uri));
		// load language
		JFactory::getLanguage()->load('plg_system_getbibleactivitycron', JPATH_ADMINISTRATOR);
		// get Bible Parameters
		$this->com_params = &JComponentHelper::getParams('com_getbible');
		// get plugin id
		$pluginId = $this->pluginId('plg_system_getbibleactivitycron','plugin','getbibleactivitycron','system');
		if($pluginId){
			$pluginUrl = JURI::base().'index.php?option=com_plugins&amp;task=plugin.edit&amp;extension_id='.(int) $pluginId;
		} else {
			$pluginUrl = JURI::base().'index.php?option=com_plugins&view=plugins&filter_search=System - getBible Activity Cron';
		}
		
		// set the ip update tab
		$div = '<div class="span12"><h2>Activity Cron Job!</h2><div class="well well-small">';
		$div .= '<h2 class="nav-header">'.$this->lastActivityUpdate().'</h2>';
		$div .= '<p>The cron job is set to update your activity table <code>'.JText::_($this->updaterText('timer')).'</code>
					To change the timer <a href="'.$pluginUrl.'" >click here</a>.</p>';
		// First check user access
		$canDo = JHelperContent::getActions('com_getbible', 'getbible');
		if($this->com_params->get('log') == 0 && $canDo->get('core.admin')){
			$div .= '<h3>You must turn logging on in the "Global Settings" tab on the Component <a href="index.php?option=com_config&amp;view=component&amp;component=com_getbible&amp;path=&amp;return='.$return.'">Options</a> page.</h3>';
			$div .= '<p>Failure to doing so will prevent these activity charts from being accurate and up to date.</p>';
		}
		$div .= '</div>';
		$script = '(function($) {
					
					var $event = $.event,
						$special,
						resizeTimeout;
					
					$special = $event.special.debouncedresize = {
						setup: function() {
							$( this ).on( "resize", $special.handler );
						},
						teardown: function() {
							$( this ).off( "resize", $special.handler );
						},
						handler: function( event, execAsap ) {
							// Save the context
							var context = this,
								args = arguments,
								dispatch = function() {
									// set correct event type
									event.type = "debouncedresize";
									$event.dispatch.apply( context, args );
								};
					
							if ( resizeTimeout ) {
								clearTimeout( resizeTimeout );
							}
					
							execAsap ?
								dispatch() :
								resizeTimeout = setTimeout( dispatch, $special.threshold );
						},
						threshold: 150
					};
					
					})(jQuery);';
		$data = $this->getData();
		if($data['country']){
			$div .= '<h2>Country Chart</h2>';
			$div .= '<div class="well well-small">';
			$div .= '<p>The total API queries made per country.</p>';
			$div .= '<div class="well" style="background-color:#fff;">';
			$div .= '<div id="regions_div" style="height: 500px;"></div>';
			$div .= '</div></div>';
			// setup the needed script
			$script .= "google.load('visualization', '1', {'packages': ['geochart']});";			
			$script .= 'google.setOnLoadCallback(drawRegionsMap);';
			$script .= "jQuery(document).ready(function() {
							jQuery('a[data-toggle=\"tab\"]').on('shown.bs.tab', function (e) {
								drawRegionsMap();
							});
							jQuery(window).bind('debouncedresize', function() {
								drawRegionsMap();
							});
						});";
			$script .= 'function drawRegionsMap() {';
			$script .= 'var data = google.visualization.arrayToDataTable([';
			$script .= "['Country', 'Queries']";
			foreach($data['country'] as $country){
				if(strlen($country->name) > 0){
				$script.= ",['".addslashes($country->name)."', ".$country->nr."]";
				}
			}
			$script .= ']);';
			$script .= "var options = { displayMode: 'regions', minValue: 0, width: '100%', height: '100%', colors: ['#44C479', '#047232']};";
			$script .= "var chart = new google.visualization.GeoChart(document.getElementById('regions_div'));";
			$script .= "chart.draw(data, options);}";
		}
		if($data['version']){
			$div .= '<h2>Version Chart</h2>';
			$div .= '<div class="well well-small">';
			$div .= '<p>The total API queries made per version.</p>';
			$div .= '<div class="well" style="background-color:#fff;">';
			$div .= '<div id="versions_div" style="height: 500px;"></div>';
			$div .= '</div></div>';
			// setup the needed script
			$script .= 'google.load("visualization", "1", {packages:["corechart"]});';
			$script .= 'google.setOnLoadCallback(drawVersions);';
			$script .= "jQuery(document).ready(function() {
							jQuery('a[data-toggle=\"tab\"]').on('shown.bs.tab', function (e) {
								drawVersions();
							});
							jQuery(window).bind('debouncedresize', function() {
								drawVersions();
							});
						});";
			$script .= 'function drawVersions() {';
			$script .= 'var data = google.visualization.arrayToDataTable([';
			$script .= "['Version', 'Query']";
			foreach($data['version'] as $version){
				if(strlen($version->name) > 0){
				$script .= ",['".addslashes($version->name)."', ".$version->nr."]";
				}
			}
			$script .= ']);';
			$script .= "var options = { is3D: true };";
			$script .= "var chart = new google.visualization.PieChart(document.getElementById('versions_div'));";
			$script .= "chart.draw(data, options);}";
		}
		$div .= '</div>';
		
		// Set Activity Tab
		$array[4]->div	= $div;
		// load the script into the document
		if(count($data['country']) || count($data['version'])){
			// get the document
			$this->document	= &JFactory::getDocument();
			// add the Javascript to page
			if (!$this->js_loaded('jquery')) {	
				JHtml::_('jquery.framework');
			}
			$this->document->addScript('https://www.google.com/jsapi');
			$this->document->addScriptDeclaration($script);
		}
	}
	
	protected function getData()
	{	
		if (file_exists(JPATH_ADMINISTRATOR.DS.'components'.DS.'com_getbible'.DS.'helpers'.DS.'activityCron.php')) {	
			// Get a db connection.
			$db = JFactory::getDbo();
			// Create a new query object.
			$query = $db->getQuery(true);
			$query
				->select($db->quoteName(array('b.name', 'a.counter', 'a.country'),array('name', 'nr', 'code')))
				->from($db->quoteName('#__getbible_activity_country', 'a'))
				->join('INNER', $db->quoteName('#__ipdata_country', 'b') . ' ON (' . $db->quoteName('a.country') . ' = ' . $db->quoteName('b.codethree') . ')')
				->order($db->quoteName('a.counter') . ' DESC');
			$db->setQuery($query);
			$db->execute();
			if($db->getNumRows()){
				$results['country'] = $db->loadObjectList();
			} else {
				$results['country'] = false;
			}
			
			// Create a new query object.
			$query = $db->getQuery(true);
			$query
				->select($db->quoteName(array('b.name', 'a.counter'),array('name', 'nr')))
				->from($db->quoteName('#__getbible_activity_version', 'a'))
				->join('INNER', $db->quoteName('#__getbible_versions', 'b') . ' ON (' . $db->quoteName('a.version') . ' = ' . $db->quoteName('b.version') . ')')
				->order($db->quoteName('a.counter') . ' DESC');
			$db->setQuery($query);
			$db->execute();
			if($db->getNumRows()){
				$results['version'] = $db->loadObjectList();
			} else {
				$results['version'] = false;
			}
			return $results;
		}
		return false;
	}
	
	protected function updaterText($type)
	{
		switch($this->params->get($type.'timer', '-1 day')){
			case "-1 hour":
			return 'PLG_SYSTEM_GETBIBLEACTIVITYCRON_CONFIG_ONCE_A_HOUR';
			break;
			case "-5 hours":
			return 'PLG_SYSTEM_GETBIBLEACTIVITYCRON_CONFIG_EVERY_FIVE_HOURS';
			break;
			case "-12 hours":
			return 'PLG_SYSTEM_GETBIBLEACTIVITYCRON_CONFIG_EVERY_TWELVE_HOURS';
			break;
			case "-1 day":
			return 'PLG_SYSTEM_GETBIBLEACTIVITYCRON_CONFIG_ONCE_A_DAY';
			break;
			case "-2 day":
			return 'PLG_SYSTEM_GETBIBLEACTIVITYCRON_CONFIG_SECOND_DAY';
			break;
			case "-5 day":
			return 'PLG_SYSTEM_GETBIBLEACTIVITYCRON_CONFIG_FIFTH_DAY';
			break;
			case "-7 day":
			return 'PLG_SYSTEM_GETBIBLEACTIVITYCRON_CONFIG_ONCE_A_WEEK';
			break;
			case "0":
			return 'PLG_SYSTEM_GETBIBLEACTIVITYCRON_CONFIG_NEVER';
			break;
		}
	}
	
	protected function lastActivityUpdate()
	{
		$db = JFactory::getDBO();
		$query = $db->getQuery(true);
		$query->select('#__getbible_activity_cron.date');
		$query->from('#__getbible_activity_cron');
		$query->where('#__getbible_activity_cron.active = 1');
		$db->setQuery($query);
		$db->execute();
		if($db->getNumRows()){
			return 'The last update was: '.$db->loadResult().' <i>(no update pending)</i>';
		} 
		
		$query = $db->getQuery(true);
		$query->select('#__getbible_activity_cron.date');
		$query->from('#__getbible_activity_cron');
		$query->where('#__getbible_activity_cron.active = 8');
		$db->setQuery($query);
		$db->execute();
		if($db->getNumRows()){
			return 'An update has started at: '.$db->loadResult();
		}
		
		$query = $db->getQuery(true);
		$query->select('#__getbible_activity_cron.date');
		$query->from('#__getbible_activity_cron');
		$query->where('#__getbible_activity_cron.active = 0');
		$query->order('#__getbible_activity_cron.date DESC');
		$db->setQuery($query);
		$db->execute();
		if($db->getNumRows()){
			return 'The last update was: '.$db->loadResult().' <i>(update pending)</i>';
		}
		return 'No Update Yet!';
	}
	
	protected function js_loaded($script_name)
	{
		$head_data 	= $this->document->getHeadData();
		foreach (array_keys($head_data['scripts']) as $script) {
			if (stristr($script, $script_name)) {
				return true;
			}
		}
		return false;
	}
	
	protected function pluginId($name,$type,$element,$folder)
	{
		$db = JFactory::getDBO();
		$query = $db->getQuery(true);
		$query
			->select($db->quoteName('a.extension_id'))
			->from($db->quoteName('#__extensions', 'a'))
			->where($db->quoteName('a.name').' = '.$db->quote($name))
			->where($db->quoteName('a.type').' = '.$db->quote($type))
			->where($db->quoteName('a.element').' = '.$db->quote($element))
			->where($db->quoteName('a.folder').' = '.$db->quote($folder));
		$db->setQuery($query);
		$db->execute();
		if($db->getNumRows()){
			return $db->loadResult();
		}
		return false;
	}
}