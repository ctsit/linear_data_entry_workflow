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
		$fieldsArr = json_decode($fieldsObj)->$form_name;
		$result = array();
		
		$custom_data = REDCap::getData('json');
		$encoded_data = json_decode($custom_data);
		$max_event_id = 0;
		foreach ($encoded_data as $item){
			$unique_event_name = $item->redcap_event_name;
			$event_id = REDCap::getEventIdFromUniqueEvent($unique_event_name);
			if($event_id > $max_event_id){
				$result = array();
				foreach ($fieldsArr as $field){
					if(isset($item->$field)){
						$latest_data_obj = new stdClass();
						$max_event_id = $event_id;
						$latest_data_obj->field_name = $field;
						$latest_data_obj->value = $item->$field;
						$latest_data_obj->event_id = $event_id;
						$result[] = $latest_data_obj;
					}
				}
			}
		}
		$encoded_result = json_encode($result);
		//print_r($result);
	?>
	<script type="text/javascript">
		var phpArray = '<?php echo $encoded_result; ?>';
		var resultArray = JSON.parse(phpArray);
		for(var i=0;i<resultArray.length;i++){
			$('input[name="'+resultArray[i].field_name+'"]').val(resultArray[i].value);
		}
	</script>
	<?php
	};
?>