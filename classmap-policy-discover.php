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

./classmap-policy-discover.php IPADDRESS "-v2c -c COMMUNITY" 

IPADDRESS is the adress of the cisco device
"-v2c -c COMMUNITY" are snmpwalk parameters

will discover definded class policies
 (e.g. 
.1.3.6.1.4.1.9.9.166.1.7.1.1.1.1593 "name1"
.1.3.6.1.4.1.9.9.166.1.7.1.1.1.3463889 "name2"
.1.3.6.1.4.1.9.9.166.1.7.1.1.1.15715633 "data1"
 )

and return SNMP OID suffixes for the QoS counter, according QoS enabled interface and policy name ..
 (e.g.
#IFNAME_QOS}":"GigabitEthernet0\/0.8","
#QOSCN_OIDSUFFIX}":"45.38766593","
#QOS_POLICY}":"name2"},

 
#IFNAME_QOS}":"GigabitEthernet0\/0.4","
#QOSCN_OIDSUFFIX}":"41.46766573","
#QOS_POLICY}":"data1"},
 )


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




$qosinterface_index = Array ();
$qosinterface_name = Array ();
$qosinterface_policy_index = Array ();
$qosinterface_policy_string = Array ();

foreach ($matches_qosint[1] as $key => $value) {
$fkey = array_search ($matches_qosint[2][$key], $matches_int[1]);

$qosinterface_index[] = $value;
$qosinterface_name[] = $matches_int[2][$fkey];
}


foreach ($matches_cmname[1] as $pind => $pstr) {
$i = 0;
	$policy_snmp_id = $pstr;
	$policy = $matches_cmname[2][$pind];
foreach ($qosinterface_index as $value) {
 $tempor = shell_exec("$snmpwalk $snmpArgs -On -Oq $host $oid_qosconfigindex.$value");
 if (preg_match_all('/\.(\d+) "?([^"\r\n]*)/', $tempor, $matches_tempor)) {
}
 $qosinterface_policy_index[$i][] = $matches_tempor[1][array_search ($policy_snmp_id,$matches_tempor[2])];
 $qosinterface_policy_string[$i][] = $policy;
 $i++;
}
}
// Build data array


$data = array('data' => array());
$i = 0;
$qi = 0;

foreach ($qosinterface_index as $key => $value) {
	$pi = 0;
	foreach ($qosinterface_policy_index[$qi] as $qkey){
        $data['data'][$i]['{#IFNAME_QOS}'] = $qosinterface_name[$key];
        $data['data'][$i]['{#QOSCN_OIDSUFFIX}'] = $value . "." . $qosinterface_policy_index[$qi][$pi];
        $data['data'][$i]['{#QOS_POLICY}'] = $qosinterface_policy_string[$qi][$pi];
        $i++;
	$pi++;
	}
	$qi++;
}

 
// Spit it out as JSON
print json_encode($data);

