<?php

//Script to allow getting and setting SNMP for APC devices
//Edited: 2017-08-05
//Author: Steve Talley
//Outlet commands are: Outlet state: 1-On | 2-Off | 3-Immediate Reboot | 5 - Outlet on with Delay | 6 - Outlet off with Delay | 7 - Outlet Reboot with Delay
header("Content-Type: application/json");
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
header('Access-Control-Max-Age: 604800');
header('Access-Control-Allow-Headers: x-requested-with');
header('Access-Control-Allow-Origin: *');

//Retrieve GET Variables
$apc_snmp_community = !empty($_GET['community']) ? $_GET['community'] : '';
$apc_ip = !empty($_GET['ip']) ? $_GET['ip'] : '';
$apc_outlet = !empty($_GET['outlet']) ? $_GET['outlet'] : '';
$apc_statusonly = !empty($_GET['statusonly']) ? $_GET['statusonly'] : 'true';
$apc_command = !empty($_GET['command']) ? $_GET['command'] : '';

//see if $apc_outlet has multiple outlets separated by a comma
$apc_outlets = explode(',', $apc_outlet);
// print_r($apc_outlets);
// var_dump( $apc_outlet_array );
//Error checking
if($apc_snmp_community == '') {
  $error_message = 'No community set.';
  echo $error_message;
} else if($apc_ip == '') {
  $error_message = 'No IP set.';
  echo $error_message;
} else if($apc_outlet == '') {
  $error_message = 'No outlet set.';
  echo $error_message;
} else if($apc_statusonly == 'false' && $apc_command == '') {
  $error_message = 'No command set.';
  echo $error_message;
}

//Beginning of all APC APU OIDs

$apc_oid = '1.3.6.1.4.1.318';
// echo 'IP: ' . $apc_ip;
//
// echo '<br>';
//
// echo 'Outlet: ' . $apc_outlet;
//
// echo '<br>';
//
// echo 'Status Only: ' . $apc_statusonly;
//
// echo '<br>';
//
// echo 'Command: ' . $apc_command;
//
// echo '<br>';

//See if this is for getting stats or setting things
if($apc_statusonly == 'true') {
  $outlet_status_command = ' ';
  $outlet_name_command = ' ';
  foreach ($apc_outlets as $outlet) {
    // build command string
    $outlet_status_command .= $apc_oid . '.1.1.4.4.2.1.3.' . $outlet . ' ';
    $outlet_name_command .= $apc_oid . '.1.1.4.4.2.1.4.' . $outlet . ' ';
  }  // end foreach

  //set the command and get the result
  $apc_get_command_outlet_status_response = shell_exec( 'snmpget -v1 -c ' . $apc_snmp_community . ' ' . $apc_ip . ' ' . $outlet_status_command );

  $apc_get_command_outlet_name_response = shell_exec( 'snmpget -v1 -c ' . $apc_snmp_community . ' ' . $apc_ip . ' ' . $outlet_name_command );


  // sort the status responses
  // Remove trailing new line 
  $apc_get_command_outlet_status_response = preg_replace('/\n$/','',$apc_get_command_outlet_status_response);
  $apc_get_command_outlet_status_array = explode("\n", $apc_get_command_outlet_status_response);
  $apc_get_command_outlet_statuses = array();
  foreach( $apc_get_command_outlet_status_array as $apc_get_command_outlet_status ) {
    //strip out the OID from the response and the INTEGER: portion and just return the number
    $apc_get_command_outlet_statuses[] = substr(strstr($apc_get_command_outlet_status, 'INTEGER: ' ), strlen('INTEGER: '));
    // echo $apc_get_command_outlet_status_numeric;

  } // end foreach
  //remove any empty lines
  $apc_get_command_outlet_statuses = array_filter($apc_get_command_outlet_statuses, function($value) { return !is_null($value) && $value !== ''; });

  // see if all values are the same to set on, off or unknown
  if (count(array_unique($apc_get_command_outlet_statuses)) === 1 && end($apc_get_command_outlet_statuses) === '1') {
    $apc_get_command_outlet_status_text = 'on';
  } else if (count(array_unique($apc_get_command_outlet_statuses)) === 1 && end($apc_get_command_outlet_statuses) === '2') {
    $apc_get_command_outlet_status_text = 'off';
  } else {
    $apc_get_command_outlet_status_text = 'unknown';
  }

  // sort the status responses
  $apc_get_command_outlet_name_response = preg_replace('/\n$/','',$apc_get_command_outlet_name_response);

  $apc_get_command_outlet_name_array = explode("\n", $apc_get_command_outlet_name_response);
  $apc_get_command_outlet_names = array();
  foreach( $apc_get_command_outlet_name_array as $apc_get_command_outlet_name ) {
    //remove the ending quote and get the name only
    $apc_get_command_outlet_name_temp = str_replace('"', '', substr(strstr($apc_get_command_outlet_name, 'STRING: "' ), strlen('STRING: "')));

    //remove the newline character at the end
    $apc_get_command_outlet_names[] = preg_replace("/[\n\r]/", "", $apc_get_command_outlet_name_temp);
  } // end foreach

  // remove any empty lines

// var_dump($apc_get_command_outlet_names);
  $apc_get_command_outlet_name = implode(', ', $apc_get_command_outlet_names);

  $response = array(
    'status' => 'success',
    'data' => array(
      'outlet_status' => $apc_get_command_outlet_status_text,
      'outlet_name' => $apc_get_command_outlet_name
    ),
  );

} else if($apc_statusonly == 'false') {

  $outlet_command = ' ';
  foreach ($apc_outlets as $outlet) {
    // build command string
    $outlet_command .= $apc_oid . '.1.1.4.4.2.1.3.' . $outlet . ' integer ' . $apc_command . ' ';
  }  // end foreach
  //set the command and get the result
  $apc_set_command_string = shell_exec('snmpset -v1 -c ' . $apc_snmp_community . ' ' . $apc_ip . ' ' . $outlet_command);

  $apc_set_command_string_response = preg_replace('/\n$/','', $apc_set_command_string);
  $apc_set_command_outlet_status_array = explode("\n", $apc_set_command_string_response);

  $apc_set_command_outlet_statuses = array();
  foreach( $apc_set_command_outlet_status_array as $apc_set_command_outlet_status ) {
    //strip out the OID from the response and the INTEGER: portion and just return the number
    $apc_set_command_outlet_statuses[] = substr(strstr($apc_set_command_outlet_status, 'INTEGER: ' ), strlen('INTEGER: '));
    // echo $apc_get_command_outlet_status_numeric;

  } // end foreach


  // see if all values are the same to set on, off or unknown
  if (count(array_unique($apc_set_command_outlet_statuses)) === 1 && end($apc_set_command_outlet_statuses) === '1') {
    $apc_set_command_string_numeric = 1;
  } else if (count(array_unique($apc_set_command_outlet_statuses)) === 1 && end($apc_set_command_outlet_statuses) === '2') {
    $apc_set_command_string_numeric = 2;
  } else {
    $apc_set_command_outlet_status_text = 'failure';
  }

  $response = array(
    'status' => true,
    'data' => array(
      'outlet_status' => $apc_set_command_string_numeric,
    )
  );


}//end if($apc_statusonly == 'true')


//SEND JSON
echo json_encode($response);

?>

