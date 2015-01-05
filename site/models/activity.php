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

defined( '_JEXEC' ) or die( 'Restricted access' );

jimport('joomla.application.component.helper');

class GetbibleModelActivity extends JModelList
{	
	public function getData()
	{		
		// Get a db connection.
		$db = JFactory::getDbo();
		// Create a new query object.
		$query = $db->getQuery(true);
		$query
			->select($db->quoteName(array('b.name', 'a.counter', 'a.country'),array('name', 'nr', 'code')))
			->from($db->quoteName('#__getbible_activity_country', 'a'))
			->join('INNER', $db->quoteName('#__ipdata_country', 'b') . ' ON (' . $db->quoteName('a.country') . ' = ' . $db->quoteName('b.codethree') . ')')
			->order($db->quoteName('a.counter') . ' DESC');
		 
		// Reset the query using our newly populated query object.
		$db->setQuery($query);
		$db->execute();
		if($db->getNumRows()){
			// Load the results as a list of stdClass objects (see later for more options on retrieving data).
			$results['country'] = $db->loadObjectList();
		} else {
			$results['country'] = false;
		}
		
		return $results;
		
	}
}
