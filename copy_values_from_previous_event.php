<?php

	return function($project_id){
		require_once "../../plugins/custom_project_settings/cps_lib.php";

		parse_str($_SERVER['QUERY_STRING'], $qs_params);
		$pid = $qs_params['pid'];
		$form_name = $qs_params['page'];
		$event_id = $qs_params['event_id'];

		$cps_lib = new cps_lib();
		$fieldsObj = $cps_lib->getAttributeData($pid, 'copy_values_from_previous_event');
		$parsed_fieldsobj = str_replace("'", '"', $fieldsObj);
		$decoded_fieldsobj = json_decode($parsed_fieldsobj);
		$fields_array = array();
		$fieldsArr = json_encode($fields_array);
		/*
			Iterate over array of objects to get $form_name object and its value(fields).
		*/
		foreach ($decoded_fieldsobj as $key) {
			if($key->form_name == $form_name){
				$fieldsArr = $key->fields;
			}
		}

		$result = array();
		$custom_data = REDCap::getData('json');
		$encoded_data = json_decode($custom_data);
		$max_event_id = 0;

		foreach ($encoded_data as $item){
			$unique_event_name = $item->redcap_event_name;
			$unique_event_id = REDCap::getEventIdFromUniqueEvent($unique_event_name);
			if($unique_event_id > $max_event_id){
				$result = array();
				foreach ($fieldsArr as $field){
					/*
						Autofill fields with values in a form only if it is new form.
					*/
					if(isset($item->$field) && $event_id == $unique_event_id){
						$result = array();
						return;
					}
					/*
						Get names and values of the fields that need to be autofilled.
					*/
					if(isset($item->$field)){
						$latest_data_obj = new stdClass();
						$max_event_id = $unique_event_id;
						$latest_data_obj->field_name = $field;
						$latest_data_obj->value = $item->$field;
						$latest_data_obj->event_id = $unique_event_id;
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