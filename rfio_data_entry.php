<?php

  return function ($project_id)
  {
    /* Code provided by Tiago that assembles a list of $selectors. The 
    $selectors are gathered by starting from a list of all forms for the current
    record and event. Then each form that is complete is removed, as well as the
    first encountered non-complete (unverified or incomplete or new) form. The
    remaining list contains only form IDs that need to be removed.
    TL;DR: make $selectors, a string of form link IDs to be disabled*/
    global $Proj;

    # Create $field array made of completion status fields of all project forms
    /* Create $selectors array made of link IDs of all forms on left side
    navigation panel (formMenuList)*/
    $selectors = $fields = array();
    foreach(array_keys($Proj->forms) as $form_name) {
        $field_name = $form_name . '_complete';

        $fields[] = $field_name;
        $selectors[$field_name] = 'form[' . $form_name . ']';
    }   

    /* Use API to get array of completion status fields of all records
    for the current event*/
    $statuses = REDCap::getData($_GET['pid'], 'array', NULL,
                $fields, $_GET['event_id']);


    if (isset($statuses[$_GET['id']])) { # If current record is in $statuses
      /* Set statuses as an array of the completion status fields for the
      current record and event*/ 
      $statuses = $statuses[$_GET['id']][$_GET['event_id']]; 

      # Set flag for whether the current form should be included
      $prev_is_complete = TRUE;
      /* Loop through each form, exit loop if current form should not be
      included*/
      foreach ($statuses as $field_name => $status) {
          if (!$prev_is_complete) {
              break;
          }   

          # Remove current form from selectors if the previous form is complete
          unset($selectors[$field_name]);
          $prev_is_complete = ($status == 2);
      }
    }

    # Remove current page's form from list of selectors.
    unset($selectors[$_GET['page'] . '_complete']);
    
    # Our string of selectors, to be used/passed to Javascript.
    $selectors = implode(', ', $selectors);
    
    
    ?>
      <script>
        $(document).ready(function() {

          // Generate array of form link IDs
          var selector = '<?php echo $selectors; ?>'.split(", ");

          // Function to disable use of target link
          function disableLink(a) {
            a.style.pointerEvents = 'none';
            a.style.opacity = '.1';
          }

          function run() {
            
            // Loop through selector elements, find siblings
            for (i = 0; i < selector.length; i++) {
              var link = document.getElementById(selector[i]);
              $siblings = $(link).siblings();

              // If there is a sibling, disable its links
              if($siblings.length !== 0) {
                disableLink($siblings[0]);
              }

              // Disable link use of current selector element
              disableLink(link);
            }
          }

          run();
        });
      </script>
    <?php
  }

?>
