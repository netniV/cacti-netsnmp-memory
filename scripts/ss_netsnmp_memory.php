<?php

#
# ss_netsnmp_memory.php
# version 0.7
# September 11, 2009
#
# Copyright (C) 2006-2009, Eric A. Hall
# http://www.eric-a-hall.com/
#
# This software is licensed under the same terms as Cacti itself
#

#
# load the Cacti configuration settings if they aren't already present
#
if (isset($config) == FALSE) {

	if (file_exists(dirname(__FILE__) . "/../include/config.php")) {
		include_once(dirname(__FILE__) . "/../include/config.php");
	}

	if (file_exists(dirname(__FILE__) . "/../include/global.php")) {
		include_once(dirname(__FILE__) . "/../include/global.php");
	}

	if (isset($config) == FALSE) {
		echo ("FATAL: Unable to load Cacti configuration files \n");
		return;
	}
}

#
# load the Cacti SNMP libraries if they aren't already present
#
if (defined('SNMP_METHOD_PHP') == FALSE) {

	if (file_exists(dirname(__FILE__) . "/../lib/snmp.php")) {
		include_once(dirname(__FILE__) . "/../lib/snmp.php");
	}

	if (defined('SNMP_METHOD_PHP') == FALSE) {
		echo ("FATAL: Unable to load SNMP libraries \n");
		return;
	}
}

#
# call the main function manually if executed outside the Cacti script server
#
if (isset($GLOBALS['called_by_script_server']) == FALSE) {

	array_shift($_SERVER["argv"]);
	print call_user_func_array("ss_netsnmp_memory", $_SERVER["argv"]);
}

#
# main function
#
function ss_netsnmp_memory($protocol_bundle="") {

	#
	# 1st function argument contains the protocol-specific bundle
	#
	# use '====' matching for strpos in case colon is 1st character
	#
	if ((trim($protocol_bundle) == "") || (strpos($protocol_bundle, ":") === FALSE)) {

		echo ("FATAL: No SNMP parameter bundle provided\n");
		ss_netsnmp_memory_syntax();
		return;
	}

	$protocol_array = explode(":", $protocol_bundle);

	if (count($protocol_array) < 11) {

		echo ("FATAL: Not enough elements in SNMP parameter bundle\n");
		ss_netsnmp_memory_syntax();
		return;
	}

	if (count($protocol_array) > 11) {

		echo ("FATAL: Too many elements in SNMP parameter bundle\n");
		ss_netsnmp_memory_syntax();
		return;
	}

	#
	# 1st bundle element is $snmp_hostname
	#
	$snmp_hostname = trim($protocol_array[0]);

	if ($snmp_hostname == "") {

		echo ("FATAL: Hostname not specified in SNMP parameter bundle\n");
		ss_netsnmp_memory_syntax();
		return;
	}

	#
	# 2nd bundle element is $snmp_version
	#
	$snmp_version = trim($protocol_array[1]);

	if ($snmp_version == "") {

		echo ("FATAL: SNMP version not specified in SNMP parameter bundle\n");
		ss_netsnmp_memory_syntax();
		return;
	}

	if (($snmp_version != 1) and ($snmp_version != 2) and ($snmp_version != 3)) {

		echo ("FATAL: \"$snmp_version\" is not a valid SNMP version\n");
		ss_netsnmp_memory_syntax();
		return;
	}

	#
	# 3rd bundle element is $snmp_community
	#
	$snmp_community = trim($protocol_array[2]);

	if (($snmp_version != 3) and ($snmp_community == "")) {

		echo ("FATAL: SNMP v$snmp_version community not specified in SNMP parameter bundle\n");
		ss_netsnmp_memory_syntax();
		return;
	}

	#
	# 4th bundle element is $snmp_v3_username
	#
	$snmp_v3_username = trim($protocol_array[3]);

	#
	# 5th bundle element is $snmp_v3_password
	#
	$snmp_v3_password = trim($protocol_array[4]);

	#
	# 6th bundle element is $snmp_v3_authproto
	#
	$snmp_v3_authproto = trim($protocol_array[5]);

	#
	# 7th bundle element is $snmp_v3_privpass
	#
	$snmp_v3_privpass = trim($protocol_array[6]);

	#
	# 8th bundle element is $snmp_v3_privproto
	#
	$snmp_v3_privproto = trim($protocol_array[7]);

	#
	# 9th bundle element is $snmp_v3_context
	#
	$snmp_v3_context = trim($protocol_array[8]);

	#
	# 10th bundle element is $snmp_port
	#
	$snmp_port = trim($protocol_array[9]);

	if ($snmp_port == "") {

		#
		# if the value was omitted use the default port number
		#
		$snmp_port = 161;
	}

	if (is_numeric($snmp_port) == FALSE) {

		echo ("FATAL: Non-numeric SNMP port \"$snmp_port\" specified in SNMP parameter bundle\n");
		ss_netsnmp_memory_syntax();
		return;
	}

	#
	# 11th bundle element is $snmp_timeout
	#
	$snmp_timeout = trim($protocol_array[10]);

	if ($snmp_timeout == "") {

		#
		# if the value was omitted use the Cacti SNMP timeout
		#
		if (isset($GLOBALS['config']['config_options_array']['snmp_timeout'])) {

			$snmp_timeout = trim($GLOBALS['config']['config_options_array']['snmp_timeout']);
		}

		elseif (isset($GLOBALS['_SESSION']['sess_config_array']['snmp_timeout'])) {

			$snmp_timeout = trim($GLOBALS['_SESSION']['sess_config_array']['snmp_timeout']);
		}

		else {
			echo ("FATAL: Timeout not specified in SNMP parameter bundle and " .
				"unable to determine Cacti default\n");
			ss_netsnmp_memory_syntax();
			return;
		}
	}

	if (is_numeric($snmp_timeout) == FALSE) {

		echo ("FATAL: Non-numeric SNMP timeout \"$snmp_timeout\" specified in SNMP parameter bundle\n");
		ss_netsnmp_memory_syntax();
		return;
	}

	#
	# Cacti's SNMP timeout uses milliseconds, while PHP uses Net-SNMP foormat, which is
	# typically microseconds. Normalize by multiplying the timeout value by 1000.
	#
	$snmp_timeout = ($snmp_timeout * 1000);

	#
	# build a nested array of data elements for future use
	#
	$oid_array = array ("totalReal" => ".1.3.6.1.2.1.25.2.2.0",
		"availReal" => ".1.3.6.1.4.1.2021.4.6.0",
		"totalSwap" => ".1.3.6.1.4.1.2021.4.3.0",
		"availSwap" => ".1.3.6.1.4.1.2021.4.4.0",
		"memBuffer" => ".1.3.6.1.4.1.2021.4.14.0",
		"memCached" => ".1.3.6.1.4.1.2021.4.15.0");

	#
	# build the snmp_arguments array for future use
	#
	# note that the array structure varies according to the version of Cacti in use
	#
	if (isset($GLOBALS['config']['cacti_version']) == FALSE) {

		echo ("FATAL: Unable to determine Cacti version\n");
		return;
	}

	elseif (substr($GLOBALS['config']['cacti_version'],0,5) == "0.8.6") {
		$snmp_arguments = array(
			$snmp_hostname,
			$snmp_community,
			"",
			$snmp_version,
			$snmp_v3_username,
			$snmp_v3_password,
			$snmp_port,
			$snmp_timeout,
			"",
			"");
		}

	elseif (substr($GLOBALS['config']['cacti_version'],0,5) >= "0.8.7") {
		$snmp_arguments = array(
			$snmp_hostname,
			$snmp_community,
			"",
			$snmp_version,
			$snmp_v3_username,
			$snmp_v3_password,
			$snmp_v3_authproto,
			$snmp_v3_privpass,
			$snmp_v3_privproto,
			$snmp_v3_context,
			$snmp_port,
			$snmp_timeout,
			"",
			"");
		}

	else {
		echo ("FATAL: \"" . $GLOBALS['config']['cacti_version'] .
			"\" is not a supported Cacti version\n");
		return;
	}

	#
	# query for each OID in $oid_array
	#
	foreach ($oid_array as $label => $oid) {

		#
		# use next OID for $snmp_arguments array and query
		#
		$snmp_arguments[2] = $oid;

		#
		# create the memory array element from the snmp query results
		#
		$mem_array[$label] = trim(call_user_func_array("cacti_snmp_get", $snmp_arguments));

		#
		# verify snmp response data
		#
		if ((isset($mem_array[$label] ) == FALSE) ||
			(substr($mem_array[$label] , 0, 16) == "No Such Instance")) {

			echo ("FATAL: Unable to read memory data from SNMP\n");
			return;
		}

		#
		# replace NULL with zero
		#
		if ($mem_array[$label] == "") {

			$mem_array[$label] = 0;
		}

		#
		# make sure the data only contains numbers
		#
		if (is_numeric($mem_array[$label] ) == FALSE) {

			#
			# ignore "kB" or "KBytes" if it exists
			#
			if ((substr($mem_array[$label], -2) == "kB") ||
				(substr($mem_array[$label], -6) == "KBytes")) {

				$mem_array[$label] = intval($mem_array[$label] );
			}

			#
			# zero out any remaining non-numeric responses
			#
			else {
				$mem_array[$label] = 0;
			}
		}
	}

	#
	# determine extra memory values, so somebody else doesn't have to
	#
	$mem_array['usedReal'] = ($mem_array['totalReal'] - ($mem_array['availReal'] +
		$mem_array['memBuffer'] + $mem_array['memCached']));

	$mem_array['usedSwap'] = ($mem_array['totalSwap'] - $mem_array['availSwap']);

	#
	# generate output
	#
	$output = "";

	#
	# concatenate the elements of $mem_array into a single string
	#
	foreach ($mem_array as $key => $value) {

		$output = $output . $key . ":" . $value . " ";
	}

	$output = trim($output);

	#
	# return the string
	#
	if (isset($GLOBALS['called_by_script_server']) == TRUE) {

		return($output);
	}

	else {
		echo ($output . "\n");
	}
}

#
# display the syntax
#
function ss_netsnmp_memory_syntax() {

	echo ("Syntax: ss_netsnmp_memory.php <hostname>:<snmp_version>:[<snmp_community>]:\ \n" .
	"      [<snmp3_username>]:[<snmp3_password>]:[<snmp3_auth_protocol>]:[<snmp3_priv_password>]:\ \n" .
	"      [<snmp3_priv_protocol>]:[<snmp3_context>]:[<snmp_port>}:[<snmp_timeout>] \n");
}

?>
