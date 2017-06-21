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

  //get form names used internally by REDCap
  $forms = array_keys(REDCap::getInstrumentNames());

  //use form names to contruct complete_status field names
  foreach($forms as $index => $form_name) {
    $forms[$index] = $form_name . '_complete';
  }

  /*request data as an array to get corresponding record ids and events with
  complete forms */
  $completed_forms = REDCap::getData($_GET['pid'], 'array', null, $forms);

?>

  <script>

	//create rfio_dashboard object to avoid namespace collisions
	var rfio_dashboard = {};

	rfio_dashboard.json = <?php echo json_encode($project_json) ?>;
  rfio_dashboard.completedForms = <?php echo json_encode($completed_forms) ?>;

  /*converts a pageName on a link to the corresponding form's complete_status
  field name*/
  rfio_dashboard.pageToFormComplete = function(pageName) {
    return pageName + '_complete';
  }

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

  rfio_dashboard.getQueryParameters = function(url) {
    var parameters = {};
    var queryString = this.getQueryString(url);
    var reg = /([^?&=]+)=?([^&]*)/g;
    var keyValuePair;
    while(keyValuePair = reg.exec(queryString)) {
      parameters[keyValuePair[1]] = keyValuePair[2];
    }
    return parameters;
  }

  rfio_dashboard.run = function(){
    var $rows = $('#record_status_table tbody tr');

    for(var i = 0; i < $rows.length; i++) {
      var previousFormCompleted = true;

      //start at 1 to avoid disabling Record ID column
      for(var j = 1; j < $rows[i].cells.length; j++) {

        //if last form was incomplete disable every form after it
        if(!previousFormCompleted) {
          this.disableForm($rows[i].cells[j]);
          continue;
        }

        var link = $rows[i].cells[j].getElementsByTagName('a')[0].href;
        var param = this.getQueryParameters(link);

        if(this.completedForms[param.id][param.event_id] === undefined ||
           this.completedForms[param.id][param.event_id][this.pageToFormComplete(param.page)] === undefined ||
           this.completedForms[param.id][param.event_id][this.pageToFormComplete(param.page)] !== "2") {
             previousFormCompleted = false;
             continue;
        }
      }
    }
  }

	$('document').ready(function() {
		rfio_dashboard.run();
	});

  </script>

<?php
}
?>
