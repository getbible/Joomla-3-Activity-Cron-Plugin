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

class GetbibleViewActivity extends JViewLegacy
{
	/**
	 * @var bool import success
	 */
	protected $data;

	/**
	 * Display the view
	 */
	public function display($tpl = null)
	{
		// Initialise variables.
		$this->data		= $this->get('Data');
		parent::display($tpl);
	}
	
	public function htmlEscape($val)
	{
		return htmlentities($val, ENT_COMPAT, 'UTF-8');
	}
	
}