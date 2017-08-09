<?php
return function($project_id) {

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
  $completed_forms = REDCap::getData($_GET['pid'], 'array', $_GET['id'], $forms);

  //Proj is a REDCap var used to pass information about the current project
  global $Proj;
  ?>

    <script>

    $('document').ready(function() {

      var completedForms = <?php echo json_encode($completed_forms) ?>;
      var exceptions = <?php echo $my_settings ?>;

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

      //returns the arm number of the given event
      function getArm(eventId) {
        var eventInfo = <?php echo json_encode($Proj->eventInfo) ?>;
        return eventInfo[eventId]['arm_num'];
      }

      //returns an array of instrument names for the given event
      function getEventForms(eventId) {
        var eventForms = <?php echo json_encode($Proj->eventsForms) ?>;
        return eventForms[eventId];
      }

      //returns an array of event ids for a given arm number
      function getEventsInArm(armNum) {
        var events = <?php echo json_encode($Proj->events) ?>;
        return Object.keys(events[armNum]['events']);
      }

      //checks if all of the forms before the current event have been completed
      function previousFormsComplete(record_id, event_id) {
        var forms = completedForms[record_id];
        var arm = getArm(event_id);
        var eventsInArm = getEventsInArm(arm);
        var previousFormCompleted = true;

        for(var index in eventsInArm) {
          var currentEvent = eventsInArm[index];
          if(currentEvent >= event_id) {
            break;
          }

          if(!instrumentsComplete(currentEvent, forms[currentEvent])) {
            previousFormCompleted = false;
            break;
          }
        }

        return previousFormCompleted;
      }

      //checks if every instrument for the given event has been completed
      function instrumentsComplete(eventId, completionData) {
        var complete = true;
        var instruments = getEventForms(eventId)

        for(index in instruments) {
          var instrument = instruments[index];

          //check if current form is an exception
          if(exceptions.indexOf(instrument) !== -1) {
            continue;
          }

          //check completion status
          if(completionData[pageToFormComplete(instrument)] !== "2") {
            complete = false;
            break;
          }
        }

        return complete;
      }


      function run(){
          var $links = $('.formMenuList');
          var previousFormCompleted = previousFormsComplete(<?php echo $_GET['id'], ',', $_GET['event_id']?>);

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
