<?php
/**
*
* 	@version 	1.0.7  January 16, 2015
* 	@package 	Get Bible API
* 	@author  	Llewellyn van der Merwe <llewellyn@vdm.io>
* 	@adapted	class phpIp2Country to class Ipdata helper class for Joomla 3
*
**/

defined( '_JEXEC' ) or die( 'Restricted access' );

abstract class GetBibleActivityCron
{	

	private static $date;
	private static $setUpdate;
	
	/**
	*	Check if cron should run. 
	*/
	public static function canRun($time)
	{
		// update active state
		if(self::setActiveState($time)){
			if(self::unActive()){
				if (file_exists(JPATH_ADMINISTRATOR.'/components/com_ipdata/helpers/ipdata.php')) {
					// Import dependencies
					jimport('joomla.filesystem.file');
					JLoader::register('IpdataHelper', JPATH_ADMINISTRATOR.'/components/com_ipdata/helpers/ipdata.php');
					// Now we can run cron job all is ready
					return true;
				}
			}
		}
		return false;
	}
	
	 /**
	 * Do all updates
	 *
	 * @return  a bool
	 *
	 */
	public static function cronJob()
	{
		if(self::querySync()){
			if(self::cronActive()){
				return true;
			}
		}
		return false;
	}
	 
	 /**
	 * Turn active update off if older then ser time.
	 *
	 * @return  a bool
	 *
	 */
	protected static function setActiveState($time)
	{
		if ($time){
			// Get date in sql
			$date =& JFactory::getDate()->modify($time)->toSql();	
	
			// Get a db connection.
			$db = JFactory::getDbo();
			
			$query = $db->getQuery(true);
			 
			// Fields to update.
			$fields = array(
				$db->quoteName('active') . ' = 0'
			);
			 
			// Conditions for which records should be updated.
			$conditions = array(
				$db->quoteName('active') . ' != 0', 
				$db->quoteName('date') . ' < '.$db->quote($date)
			);
			
			// Check table
			$query->update($db->quoteName('#__getbible_activity_cron'))->set($fields)->where($conditions); 
				 
			$db->setQuery($query);
			 
			return $db->query();
		}
		return false;
	}
	
	 /**
	 * Check if all updates are un Active.
	 *
	 * @return  a bool
	 *
	 */
	protected static function unActive()
	{
		// Get a db connection.
		$db = JFactory::getDbo();
		
		$query = $db->getQuery(true);
		$query->select('id');
		$query->from($db->quoteName('#__getbible_activity_cron'));
		$query->where($db->quoteName('active')." = 1");
		$db->setQuery($query);
		$db->execute();
		if($db->getNumRows()){
			return false;
		}
		return true;
	}
	
	 /**
	 * Sync the latest getbible_query.php
	 *
	 * @return  a bool
	 *
	 */
	protected static function querySync()
	{
		$filename = JPATH_ROOT.'/logs/getbible_query.php';
		if (file_exists($filename)) {
			// set versions
			$versionArray = self::getVersions();
			// load the file
			$file 		= new SplFileObject($filename);
			$ipArray	= array();
			while (! $file->eof()) {
				$str 	= $file->fgets();
				$add 	= false;
				if (strpos($str,'#') === false) {
					if (!ctype_space($str) && trim($str) != "") {
						$parts  = preg_split('/\s+/', $str);
						foreach($parts as $piece) {
							if($piece == 'json_passage'){
								$add = true;
							} elseif (strpos($piece,'version->') !== false) {
								list($k,$v) = explode("->",trim($piece));
								// if no version is loged then the kjv was returned to user
								if(strlen($v) == 0){
									$v = 'kjv';
								}
								$result['VERSION'] = trim($v);
							} elseif (strpos($piece,'ip->') !== false) {
								list($k,$ip) = explode("->",trim($piece));
								$checker = filter_var($ip, FILTER_SANITIZE_NUMBER_INT);
								if($checker > 0){
									// check if this ip was aready set
									if(array_key_exists($checker, $ipArray)){
										$result['COUNTRY'] = $ipArray[$checker];
									} else {
										$ipdata = new IpdataHelper(trim($ip), false);
										if($ipdata){
											$result['COUNTRY']	= $ipdata->getInfo(10, 1);
											$ipArray[$checker]	= $result['COUNTRY'];
										}
									}
								}
							}
						}
						if($add){
							if((strlen(trim($result['COUNTRY'])) > 0) && (in_array($result['VERSION'], $versionArray))){
								// set update vlaues
								self::$setUpdate['country'][$result['COUNTRY']] = self::$setUpdate['country'][$result['COUNTRY']] + 1;
								// set update vlaues
								self::$setUpdate['version'][$result['VERSION']] = self::$setUpdate['version'][$result['VERSION']] + 1;
								// clear array
								unset($result['COUNTRY']);
								unset($result['VERSION']);
							}
						}
					}
				}
				unset($str);
			}
			unset($ipArray);
			// now save the data to the db
			self::addToDBActivity();
			
			// change file name so not to add these logs again
			$file = null;
			$new_filename = JPATH_ROOT.'/logs/getbible_query_'.JFactory::getDate()->toUnix().'.php';
			JFile::move($filename, $new_filename);
			
			return true;
		} 
		return false;
	}
	
	 /**
	 * Save Activity to their respective tables
	 *
	 * @return  void
	 *
	 */
	protected static function addToDBActivity()
	{
		if(count(self::$setUpdate) > 0){
			// Get a db connection.
			$db = JFactory::getDbo();
			foreach(self::$setUpdate as $table => $columns){
				if(count($columns) > 0){
					foreach($columns as $column => $number){
						// Create a new query object.
						$query = $db->getQuery(true);
						$query->select('id');
						$query->from($db->quoteName('#__getbible_activity_'.$table));
						$query->where($db->quoteName($table) . ' = ' . $db->quote($column));		 
						// Reset the query using our newly populated query object.
						$db->setQuery($query);
						$db->execute();
						if($db->getNumRows()){
							$id = $db->loadResult();
							// update value
							$query = $db->getQuery(true);
							// Fields to update.
							$fields = array(
								$db->quoteName('counter') . ' = ' . $db->quoteName('counter'). ' + '.$number
							);
							// Conditions for which records should be updated.
							$conditions = array(
								$db->quoteName('id') . ' = ' . $id
							);
							$query->update($db->quoteName('#__getbible_activity_'.$table))->set($fields)->where($conditions);
							$db->setQuery($query);
							$db->execute();
						} else {
							// add new value
							$query = $db->getQuery(true);
							// Insert columns.
							$columns = array($table, 'counter');
							// Insert values.
							$values = array($db->quote($column), $number);
							// Prepare the insert query.
							$query
								->insert($db->quoteName('#__getbible_activity_'.$table))
								->columns($db->quoteName($columns))
								->values(implode(',', $values));
							// Set the query using our newly populated query object and execute it.
							$db->setQuery($query);
							$db->execute();
						}
					}
					unset(self::$setUpdate[$table]);
				}
			}
		}
	}
	
	/**
	*	Set the cron to active
	*
	*	@returns void
	**/
	protected static function cronActive()
	{
		// Create and populate an object.
		$cron 			= new stdClass();
		$cron->date		= JFactory::getDate()->toSql();
		$cron->active	= 1;
		 
		// Insert the object into the activity cron table.
		$result = JFactory::getDbo()->insertObject('#__getbible_activity_cron', $cron);
	}
	
	/**
	*	Get the local installed versions
	*
	*	@returns a array
	**/
	protected static function getVersions()
	{
		// Get a db connection.
		$db = JFactory::getDbo();
		
		$query = $db->getQuery(true);
		$query->select('version');
		$query->from($db->quoteName('#__getbible_versions'));
		$db->setQuery($query);
		$db->execute();
		if($db->getNumRows()){
			return $db->loadColumn();
		}
		return array('kjv');
	}
	
}
