<?php
	
	return function($project_id){
		include(dirname(__FILE__)."/../redcap_custom_project_settings/cps_lib.php");
		//require_once "prefill_data.php";

		if (!file_exists('../../redcap_connect.php')) {
		    $REDCAP_ROOT = "/var/www/redcap";
		    require_once $REDCAP_ROOT . '/redcap_connect.php';
		} else {
		    require_once '../../redcap_connect.php';
		}

		parse_str($_SERVER['QUERY_STRING'], $qs_params);
		$pid = $qs_params['pid'];
		$form_name = $qs_params['page'];
		$event_id = $qs_params['event_id'];
		/*
			TODO: Check if record exists for $pid, $event_id, $field_name.
		*/
		$cps_lib = new cps_lib();
		$fieldsObj = $cps_lib->getAttributeData($pid, 'copy_values_from_previous_event_hook');
		$fieldsArr = json_decode($fieldsObj)->current_medications;
		//$fieldsArr = array("medication_name","indication");
		$result = array();
		global $conn;
		$tableName = 'redcap_data';
		$implodedArr = implode('\',\'',$fieldsArr);

		/*
			Code if custom methods are moved to a different file, prefill_data.php
		*/
		//$prefill_data = new prefill_data();
		//$prefill_data->getFormData($project_id, $fieldsArr);
		// $sql = "SELECT value from $tableName where field_name = 'medication_name'";
		// if($stmt = $conn->prepare($sql)){
		// 	$stmt->execute();
		// 	$stmt->bind_result($col1);
		// 	while ($stmt->fetch()) {
		// 		echo '<script>alert("hi10");</script>';
		// 	}
		// }

		/*
			Use existing hook developer method to get redcap data.
			Get data for required fields from this data.
		*/
		$custom_data = REDCap::getData('json');

		/*
			Use custom method to get required data directly from DB.
			Directly get data for required fields from the method.
		*/
		$sql = "SELECT field_name,value from $tableName where project_id = $project_id AND field_name IN ('$implodedArr') ORDER BY event_id DESC LIMIT ".count($fieldsArr);
		echo $sql;
		if($stmt = $conn->prepare($sql)){
			$stmt->execute();
			$stmt->bind_result($col1, $col2);
			
			while($stmt->fetch()){
				$obj = new stdClass();
				$obj->field_name = $col1;
				$obj->value = $col2;
				$result[] = $obj;
			}
		}
		$encoded_result = json_encode($result);
	?>

	<script type="text/javascript">
		/*
			Render auto fill data in fields.
		*/
		var phpArray = '<?php echo $encoded_result; ?>';
		var resultArray = JSON.parse(phpArray);
		for(var i=0;i<resultArray.length;i++){
			$('input[name="'+resultArray[i].field_name+'"]').val(resultArray[i].value);
		}
	</script>
	<?php
	};
?>