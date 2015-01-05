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

// no direct access
defined( '_JEXEC' ) or die( 'Restricted access' );

?>
<script type='text/javascript' src="https://www.google.com/jsapi"></script>
<div id="regions_div" style="width: 900px; height: 400px;"></div>
<script type="text/javascript">
google.load('visualization', '1', {'packages': ['geochart']});
 google.setOnLoadCallback(drawRegionsMap);

function drawRegionsMap() {
 var data = google.visualization.arrayToDataTable([
   ['Country', 'Queries']
   <?php foreach($this->data['country'] as $country): ?>
		<?php if(strlen($country->name) > 0): ?>
			,['<?php echo addslashes($country->name); ?>', <?php echo $country->nr; ?>]
		<?php endif; ?>
   <?php endforeach; ?>
 ]);

 var options = { displayMode: 'regions', minValue: 0,  colors: ['#8AC283', '#00FF00']};

 var chart = new google.visualization.GeoChart(document.getElementById('regions_div'));

 chart.draw(data, options);
}

</script>

<div id="chart_div" style="width: 900px; height: 400px;"></div>