<?php
return function($project_id) {

  // Read configuration data from the custom_project_settings data store
  $my_extension_name = 'rfio_hook';
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
  $completed_forms = REDCap::getData($_GET['pid'], 'array', $_GET['id'], $forms);

  ?>

    <script>

    $('document').ready(function() {

      var completedForms = <?php echo json_encode($completed_forms) ?>;
      var exceptions = <?php echo $my_settings ?>;
      console.log(completedForms)

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
          var $links = $('.formMenuList');
          var previousFormCompleted = true;

          for(var i = 0; i < $links.length; i++) {
            var childLinks = $links[i].querySelectorAll('a');
            for(var j = 0; j < childLinks.length; j++) {
              var url = childLinks[j].href;
              var param = getQueryParameters(url);

              //if last form was incomplete disable every form after it
              if(exceptions.indexOf(param.page) != -1){
                continue;
              }

              //if last form was incomplete disable every form after it
              if(!previousFormCompleted) {
                disableForm(childLinks[j]);
                continue;
              }

              /*Need to check if completedForms value's are undefined because
              REDcap does not enter data for incomplete/new forms.*/
              if(completedForms[param.id] === undefined ||
                 completedForms[param.id][param.event_id] === undefined ||
                 completedForms[param.id][param.event_id][pageToFormComplete(param.page)] === undefined ||
                 completedForms[param.id][param.event_id][pageToFormComplete(param.page)] !== "2") {
                   previousFormCompleted = false;
                   //skip text link next to button if its there
                   j++;
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
