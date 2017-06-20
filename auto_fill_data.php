<?php

if (!file_exists('../../redcap_connect.php')) {
    $REDCAP_ROOT = "/var/www/redcap";
    require_once $REDCAP_ROOT . '/redcap_connect.php';
} else {
    require_once '../../redcap_connect.php';
}

class auto_fill_data {

  var $conn;

  // Initializes the connection object from the global connection object.
  function auto_fill_data () {
    global $conn;
    $this->conn = $conn;
  }

  /* It takes project_id, record, arm_id, event_id, form_name, instance, field_name as params
   * and searches for the latest saved field_value across the events for the record. 
   * Returns String value if it is not present returns empty string.
   */
  function getPreviousFieldValue($project_id, $record, $arm_id, $event_id, $form_name, $instance, $field_name) {
  	$currFormRepeatable = isCurrFormRepeatable($event_id, $form_name);
  	$return_value = "";
  	if ($currFormRepeatable && isset($instance) && $instance >= 2) {
  		//get the value from previous instances.
  		
  	}
  	// fetch all the event ids;
  	$event_arr = getEventIds($arm_id);

  	// filter event ids greater than the curr event_id;

  	foreach ($ev_id as $event_arr) {
  		$value = getValueFromEventId($project_id, $event_id, $record, $field_name);
  		if (isset($value)) {
  			return $value;
  		}
  	}
  	return "";
  }

  /*
   * It takes two parameters event_id and form_name
   * and return boolean value
   */
  function isCurrFormRepeatable($event_id, $form_name) {
  	return null;
  }

  /*
   * It takes project_id, event_id, record, field_name as params
   * It tries to get the value for a particular event_id.
   * returns String if present else returns null;
   */
  function getValueFromEventId($project_id, $event_id, $record, $field_name) {
  	return null;
  }

  /*
   * It takes project_id, event_id, record and field_name as parameters
   * and returns an array of objects. Each object contains value and instance fields.
   * if no object exists returns null;
   */
  function getDataWithQueryParams($project_id, $event_id, $record, $field_name) {
  	$sql = "SELECT value, instance from redcap_data where project_id = ? and record = ? and event_id = ? and field_name =?";
	  $res = array();

    if ($stmt=$this->conn->prepare($sql)) {
      $stmt->bind_param("isis", $project_id, $record, $event_id, $field_name);
      $stmt->execute();

      /* bind variables to prepared statement */
      $stmt->bind_result($col1, $col2);
      while ($stmt->fetch()) {
      	// $res[] = $col1;
      }
	}
	return $res;
  }


  /*
   * It takes arm_id as parameter
   * and returns an array object contianing event_ids
   */
  function getEventIds($arm_id) {
    //select event_id from redcap_events_metadata where arm_id = 14;
    $sql = "SELECT event_id from redcap_events_metadata where arm_id = ?";
	$res = array();

    if ($stmt=$this->conn->prepare($sql)) {
      $stmt->bind_param("i", $arm_id);
      $stmt->execute();

      /* bind variables to prepared statement */
      $stmt->bind_result($col1);
      while ($stmt->fetch()) {
      	$res[] = $col1;
      }
	}
	return $res;
  }

}

?>