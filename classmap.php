#!/usr/bin/php
<?php
 
/**
 * SNMP discovery script for Zabbix providing multiple SNMP values combined by index
 *
 * @author Robin Roevens <robin.roevens (at) gmail.com>
 */
 
$snmpwalk = '/usr/bin/snmpwalk';
$argv[0] = basename($argv[0]);
 
$usage = <<<EOM
{$argv[0]} is an alternative to the build-in SNMP low level discovery of Zabbix 2.0+
allowing to retreive multiple SNMP OID values by a common index.
It integrates seamless into Zabbix by using "External check" discovery rules.
 
Usage: {$argv[0]} <ip|host> <snmpwalk args> <OID1> [<OID2> ...]
Example: {$argv[0]} 192.1.1.1 "-v2c -cpublic" 1.3.6.1.4.1.19746.1.15.1.1.1.2 1.3.6.1.4.1.19746.1.15.1.1.1.8
This wil retreive all values for both given OIDs sorted under their common indexes in the form
{#MVSNMPINDEX}  = 0
{#MVSNMPVALUE1} = value of first OID.0
{#MVSNMPVALUE2} = value of second OID.0
{#MVSNMPINDEX}  = 1
{#MVSNMPVALUE1} = value of first OID.1
{#MVSNMPVALUE2} = value of second OID.1
and so on for all available indexes in both OIDs.
If an index is missing in one OID, then the value of that OID for that index will be empty.
 
Usage in Zabbx:
Define a discovery rule and select "External Check".
In the "Key"-field, you specify: {$argv[0]}[<ip|host>,<snmpwalk args>,<OID1>[,<OID2>,...]]
 
Example:
{$argv[0]}[192.1.1.1,"-v2c -c\{\$SNMP_COMMUNITY\}",1.3.6.1.4.1.19746.1.15.1.1.1.2,1.3.6.1.4.1.19746.1.15.1.1.1.8]
 
 
EOM;
array_shift($argv);
 
// Check input
if (!is_array($argv) || !array_key_exists(0, $argv)) {
    die($usage);
}
 
$host = $argv[0];
array_shift($argv);

if (!array_key_exists(0, $argv)) {
    die($usage);
}

$policy = $argv[0];
array_shift($argv);
 
if (!array_key_exists(0, $argv)) {
    die($usage);
}
 
$snmpArgs = $argv[0];
array_shift($argv);

$oid_intnames = '.1.3.6.1.2.1.2.2.1.2';
$oid_qosint = '.1.3.6.1.4.1.9.9.166.1.1.1.1.4' ;
$oid_classname = '.1.3.6.1.4.1.9.9.166.1.7.1.1.1' ; 
$oid_qosconfigindex = '.1.3.6.1.4.1.9.9.166.1.5.1.1.2'; //has to append qos intrface index and will get table with counter index per policy
 
// Get values
        $intnames = shell_exec("$snmpwalk $snmpArgs -On -Oq $host $oid_intnames");
        if (preg_match_all('/\.(\d+) "?([^"\r\n]*)/', $intnames, $matches_int)) {
        }
 



$qosint = shell_exec("$snmpwalk $snmpArgs -On -Oq $host $oid_qosint");
if (preg_match_all('/\.(\d+) "?([^"\r\n]*)/', $qosint, $matches_qosint)) {
}

$cmname = shell_exec("$snmpwalk $snmpArgs -On -Oq $host $oid_classname");
if (preg_match_all('/\.(\d+) "?([^"\r\n]*)/', $cmname, $matches_cmname)) {
}

//$policy="voice"; // can be parameter
//$policy="streaming"; // can be parameter

$as = array_search($policy,$matches_cmname[2]);


if ($as === false) {
	echo "no such policy try one of \n";
	print_r($cmname);
	die("try again");
}


$policy_snmp_id = $matches_cmname[1][$as];


$qosinterface_index = Array ();
$qosinterface_name = Array ();
$qosinterface_policy_index = Array ();
foreach ($matches_qosint[1] as $key => $value) {
$fkey = array_search ($matches_qosint[2][$key], $matches_int[1]);

$qosinterface_index[] = $value;
$qosinterface_name[] = $matches_int[2][$fkey];
}

foreach ($qosinterface_index as $value) {
 $tempor = shell_exec("$snmpwalk $snmpArgs -On -Oq $host $oid_qosconfigindex.$value");
 if (preg_match_all('/\.(\d+) "?([^"\r\n]*)/', $tempor, $matches_tempor)) {
}
 $qosinterface_policy_index[] = $matches_tempor[1][array_search ($policy_snmp_id,$matches_tempor[2])];
}

//echo "for each interface use this as append of the statistics snmp for policy $policy \n";
//foreach ($qosinterface_index as $key => $value){
//echo "for interface $qosinterface_name[$key] append $value.$qosinterface_policy_index[$key]";
//echo "\n";
//}




// Build data array

$data = array('data' => array());
$i = 0;
foreach ($qosinterface_index as $key => $value) {
        $data['data'][$i]['{#IFNAME_QOS}'] = $qosinterface_name[$key];
        $data['data'][$i]['{#QOSCN_OIDSUFFIX}'] = $value . "." . $qosinterface_policy_index[$key];
        $i++;
}

 
// Spit it out as JSON
print json_encode($data);

