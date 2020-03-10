<?php

#
# ss_netsnmp_memory.php
# Version 1.0 - For use with cacti 1.x
# January 1, 2018
#
# Copyright (C) 2006-2009, Eric A. Hall
# http://www.eric-a-hall.com/
#
# This software is licensed under the same terms as Cacti itself
#
# Modified by Mark Brugnoli-Vinten (netniV)
# For use against cacti 1.x (will not work with previous versions)
#
/* do NOT run this script through a web browser */
if (!isset($_SERVER['argv'][0]) || isset($_SERVER['REQUEST_METHOD'])  || isset($_SERVER['REMOTE_ADDR'])) {
	die('<br><strong>This script is only meant to run at the command line.</strong>');
}

#
# load the Cacti configuration settings if they aren't already present
#
global $config;

$no_http_headers = true;

/* display No errors */
error_reporting(0);

//cacti_log('calling ss_netsnmp_memory("'.implode('","',$_SERVER['argv']).'");');
if (!isset($called_by_script_server)) {
        include_once(dirname(__FILE__) . '/../include/global.php');
        include_once(dirname(__FILE__) . '/../lib/snmp.php');

        array_shift($_SERVER['argv']);
	$function_name = $_SERVER['argv'][0];
	array_shift($_SERVER['argv']);
        print call_user_func_array($function_name, $_SERVER['argv']);
}else{
        include_once($config['library_path'] . '/snmp.php');
}

#
# main function
#
function ss_netsnmp_memory($hostname, $snmp_version, $snmp_community,
	$snmp_port, $snmp_timeout, $snmp_auth_username = '', $snmp_auth_password = '',
	$snmp_auth_protocol = '', $snmp_priv_passphrase = '', $snmp_priv_protocol = '', $snmp_context = '') {

	$ping_retries = 0;
	$max_oids = 200;

	#
	# Cacti's SNMP timeout uses milliseconds, while PHP uses Net-SNMP format, which is
	# typically microseconds. Normalize by multiplying the timeout value by 1000.
	#
	$snmp_timeout = ($snmp_timeout * 1000);

	#
	# build a nested array of data elements for future use
	#
	$oids = array ("totalReal" => ".1.3.6.1.4.1.2021.4.5.0",
		"availReal" => ".1.3.6.1.4.1.2021.4.6.0",
		"totalSwap" => ".1.3.6.1.4.1.2021.4.3.0",
		"availSwap" => ".1.3.6.1.4.1.2021.4.4.0",
		"memBuffer" => ".1.3.6.1.4.1.2021.4.14.0",
		"memCached" => ".1.3.6.1.4.1.2021.4.15.0");

	#
	# query for each OID in $oid_array
	#
	foreach ($oids as $label => $oid) {

		#
		# create the memory array element from the snmp query results
		#
		$mem_array[$label] = trim(cacti_snmp_get($hostname, $snmp_community, $oid, $snmp_version, $snmp_auth_username, $snmp_auth_password, $snmp_auth_protocol, $snmp_priv_passphrase, $snmp_priv_protocol, $snmp_context, $snmp_port, $snmp_timeout, $ping_retries, $max_oids, SNMP_POLLER));

		#
		# verify snmp response data
		#
		if ((isset($mem_array[$label] ) == FALSE) || 
		    (substr($mem_array[$label] , 0, 16) == 'No Such Instance')) {
			print ('FATAL: Unable to read memory data from SNMP ('.$label.': '.$oid.")\n");
			return;
		}

		#
		# replace NULL with zero
		#
		if ($mem_array[$label] == '') {
			$mem_array[$label] = 0;
		}

		#
		# make sure the data only contains numbers
		#
		if (is_numeric($mem_array[$label] ) == FALSE) {
			if ((substr($mem_array[$label], -2) == "kB") ||
			    (substr($mem_array[$label], -6) == "KBytes")) {

				#
				# ignore "kB" or "KBytes" if it exists
				#

				$mem_array[$label] = intval($mem_array[$label]);
			} else {
				#
				# zero out any remaining non-numeric responses
				#
				$mem_array[$label] = 0;
			}
		}
	}

	#
	# determine extra memory values, so somebody else doesn't have to
	#
	$mem_array['usedReal'] = ($mem_array['totalReal'] - ($mem_array['availReal'] -
		$mem_array['memBuffer'] - $mem_array['memCached']));

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

	return($output);
}

#
# display the syntax
#
function ss_netsnmp_memory_syntax() {

	print "Syntax: ss_netsnmp_memory.php <hostname> <snmp_version> <snmp_community>\n" .
	"       <snmp_port> <snmp_timeout> [<snmp3_username> <snmp3_password> <snmp3_auth_protocol>" .
	"       [<snmp3_priv_password> <snmp3_priv_protocol> <snmp3_context>]]\n";
}
?>
