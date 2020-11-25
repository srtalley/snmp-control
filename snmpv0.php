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


//Error checking
if($apc_snmp_community == '') {
  $error_message = 'No community set.';
  echo $error_message;
  break;
} else if($apc_ip == '') {
  $error_message = 'No IP set.';
  echo $error_message;
  break;
} else if($apc_outlet == '') {
  $error_message = 'No outlet set.';
  echo $error_message;
  break;
} else if($apc_statusonly == 'false' && $apc_command == '') {
  $error_message = 'No command set.';
  echo $error_message;
  break;
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

  //set the command and get the result
  $apc_get_command_outlet_status = shell_exec('snmpget -v1 -c ' . $apc_snmp_community . ' ' . $apc_ip . ' ' . $apc_oid . '.1.1.4.4.2.1.3.' . $apc_outlet );

  //strip out the OID from the response and the INTEGER: portion and just return the number
  $apc_get_command_outlet_status_numeric = substr(strstr($apc_get_command_outlet_status, 'INTEGER: ' ), strlen('INTEGER: '));

  //make the respose be on or off
  if($apc_get_command_outlet_status_numeric == 1) {
    $apc_get_command_outlet_status_text = 'on';
  } else if($apc_get_command_outlet_status_numeric == 2) {
    $apc_get_command_outlet_status_text = 'off';
  } else {
    $apc_get_command_outlet_status_text = 'unknown';
  } //end if($apc_get_command_outlet_status_numeric == 1)

  $apc_get_command_outlet_name = shell_exec('snmpget -v1 -c ' . $apc_snmp_community . ' ' . $apc_ip . ' ' . $apc_oid . '.1.1.4.4.2.1.4.' . $apc_outlet );

  //remove the ending quote and get the name only
  $apc_get_command_outlet_name_temp = str_replace('"', '', substr(strstr($apc_get_command_outlet_name, 'STRING: "' ), strlen('STRING: "')));

  //remove the newline character at the end
  $apc_get_command_outlet_name_only = preg_replace("/[\n\r]/", "", $apc_get_command_outlet_name_temp);

  $response = array(
    'status' => 'success',
    'data' => array(
      'outlet_status' => $apc_get_command_outlet_status_text,
      'outlet_name' => $apc_get_command_outlet_name_only
    ),
  );

} else if($apc_statusonly == 'false') {

  //set the command and get the result
  $apc_set_command_string = shell_exec('snmpset -v1 -c ' . $apc_snmp_community . ' ' . $apc_ip . ' ' . $apc_oid . '.1.1.4.4.2.1.3.' . $apc_outlet . ' integer ' . $apc_command);

  //strip out the OID from the response and the INTEGER: portion and just return the number
  $apc_set_command_string_temp = substr(strstr($apc_set_command_string, 'INTEGER: ' ), strlen('INTEGER: '));

  //remove the newline character at the end
  $apc_set_command_string_numeric = preg_replace("/[\n\r]/", "", $apc_set_command_string_temp);

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

