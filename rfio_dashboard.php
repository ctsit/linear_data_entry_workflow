<?php
return function($project_id) {

	$URL = $_SERVER['REQUEST_URI'];

	//check if we are on the right page
	if(preg_match('/DataEntry\/record_status_dashboard/', $URL) !== 1) {
    return;
  }

	// Read configuration data from the custom_project_settings data store
	$my_extension_name = 'rfio_hooks';
	require_once "../../plugins/custom_project_settings/cps_lib.php";
	$cps = new cps_lib();
	$my_settings = $cps->getAttributeData($project_id, $my_extension_name);

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
	$('document').ready(function() {

    var completedForms = <?php echo json_encode($completed_forms) ?>;
		var exceptions = <?php echo ($my_settings ? $my_settings : "[]"); ?>;

    /*converts a pageName on a link to the corresponding form's complete_status
    field name*/
    function pageToFormComplete(pageName) {
      return pageName + '_complete';
    }

    function disableForm(cell) {
        cell.style.pointerEvents = 'none';
        cell.style.opacity = '.1';
    }

    function getQueryString(url) {
      url = decodeURI(url);
      return url.match(/\?.+/)[0];
    }

    function getQueryParameters(url) {
      var parameters = {};
      var queryString = getQueryString(url);
      var reg = /([^?&=]+)=?([^&]*)/g;
      var keyValuePair;
      while(keyValuePair = reg.exec(queryString)) {
        parameters[keyValuePair[1]] = keyValuePair[2];
      }
      return parameters;
    }

    function run(){
      var $rows = $('#record_status_table tbody tr');

      for(var i = 0; i < $rows.length; i++) {
        var previousFormCompleted = true;

        //start at 1 to avoid disabling Record ID column
        for(var j = 1; j < $rows[i].cells.length; j++) {

					var link = $rows[i].cells[j].getElementsByTagName('a')[0].href;
					var param = getQueryParameters(link);

					//check if form is an exception
					if(exceptions.indexOf(param.page) != -1) {
						continue;
					}

          //if last form was incomplete disable every form after it
          if(!previousFormCompleted) {
            disableForm($rows[i].cells[j]);
            continue;
          }

          /*Need to check if event_id is defined in completedForms first because
          js crashes if it has to check the property of an undefined variable*/
          if(completedForms[param.id][param.event_id] === undefined ||
             completedForms[param.id][param.event_id][pageToFormComplete(param.page)] === undefined ||
             completedForms[param.id][param.event_id][pageToFormComplete(param.page)] !== "2") {
               previousFormCompleted = false;
               continue;
          }
        }
      }
    }

    //run the hook
		run();

	});

  </script>

<?php
}
?>
