<?php
/*
 * Takes a json file and disables/enables certain forms for certain patients on
 * record_status_dashboard based on the given json file.
 *
 */
return function($project_id) {

	$URL = $_SERVER['REQUEST_URI'];

	//check if we are on the right page
	if(preg_match('/DataEntry\/record_status_dashboard/', $URL) !== 1) {
    return;
  }

	//Read configuration data from redcap_custom_project_settings data store
	$my_extension_name = 'reveal_forms_in_order';
	require_once "../../plugins/custom_project_settings/cps_lib.php";
	$cps = new cps_lib();
	$my_settings = $cps->getAttributeData($project_id, $my_extension_name);
	$project_json = json_decode($my_settings, true);

?>

  <script>

	//create rfio_dashboard object to avoid namespace collisions
	var rfio_dashboard = {};

	rfio_dashboard.json = <?php echo json_encode($project_json) ?>;

	rfio_dashboard.disableForm = function(cell) {
	    cell.style.pointerEvents = 'none';
	    cell.style.opacity = '.1';
	}

	rfio_dashboard.enableForm = function(cell) {
	    cell.style.pointerEvents = 'auto';
	    cell.style.opacity = '1';
	}

  rfio_dashboard.getQueryString = function(url) {
    url = decodeURI(url);
    return url.match(/\?.+/)[0];
  }

  rfio_dashboard.run = function(){

  }

	$('document').ready(function() {
		rfio_dashboard.run();
	});

  </script>

<?php
}
?>
