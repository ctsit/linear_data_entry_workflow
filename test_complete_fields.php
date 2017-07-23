<?php

return function ($project_id)
{

  //convert instruments names into complete status fields
  $instruments = array_keys(REDCap::getInstrumentNames());
  foreach($instruments as $key => $form) {
    $instruments[$key] = $form . '_complete';
  }

  $data = REDCap::getData($_GET['pid'], 'array', null, $instruments);

  echo '<pre>';
  print_r($instruments);
  print_r($data);
  echo '</pre>';


?>




<?php
}

?>
