<?php
return function($project_id) {

	$URL = $_SERVER['REQUEST_URI'];
  parse_str($_SERVER['QUERY_STRING'], $query);
  $page_name = $query['page'];

	// Check for correct page via the redcap_data_entry_form hook function

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
	$('document').ready(function() {

    var json = <?php echo json_encode($project_json); ?>;
    var completedForms = <?php echo json_encode($completed_forms); ?>;
    var pageName = '<?php echo $page_name; ?>';

    /*converts a pageName on a link to the corresponding form's complete_status
    field name*/
    function pageToFormComplete(pageName) {
      return pageName + '_complete';
    }

    function disableForm(cell) {
        cell.style.pointerEvents = 'none';
        cell.style.opacity = '.1';
    }

    function disableLink(a) {
      a.style.pointerEvents = 'none';
      a.style.opacity = '.1';
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
      var $links = $('.formMenuList a');
      var previousFormCompleted = false;

      // Disable links for forms that are not complete
      for(var i = 0; i < $links.length; i++) {
        /* Links come in pairs. The same action is performed on each pair with
        the following i, j, and k configuration.*/
        if(i%2 == 1){
          var j = i-1;
          var k = i-3;
        } else {
          j = i;
          k = i-2;
        }

        if(k >= 0) {
          if($links[k].title == 'Complete') {
            previousFormCompleted = true;
          } else {
            previousFormCompleted = false;
          }
        }

        if($links[j].title !== 'Complete' && !previousFormCompleted) {
          disableLink($links[i]);
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
