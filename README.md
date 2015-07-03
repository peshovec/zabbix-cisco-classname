Monitor Cisco device QoS ( classname ) within Zabbix

Version:
Initial, which works (at least for me:)

Abstract (Overview):
Discover the relevant counters (SNMP oids suffixes) via external check (classmap.php)
Add the pairs QoS Interface / classname counters as items.

Cisco devices maintain index table, which maps QoS enabled Interface with the physical interface index.
 So there is a list of QoS enabled Interface with their indexes.

There is also table, which contain numeric index of the defined classnames.

There is another table, which provide "counter/classname" index For every QoS enabled interface and classname numeric index.

Finaly snmpget can be used against "SNMPCounter"."QoS Interface index"."counter/classname"


Here is an example:
----------
interface description (device name) <-> interface index 
    (e.g .1.3.6.1.2.1.2.2.1.2  
	IF-MIB::ifDescr.7 = STRING: GigabitEthernet0/1.711
    ) e.g. gi 0/1.711 is .7

qos index for the interface <-> interface index
    (e.g .1.3.6.1.4.1.9.9.166.1.1.1.1.4  
	CISCO-CLASS-BASED-QOS-MIB::cbQosIfIndex.114 = INTEGER: 7
    ) e.g .114 is the qos index for interface index .7 ... gi 0/1/.711

CMName index <-> name
    (e.g .1.3.6.1.4.1.9.9.166.1.7.1.1.1 
	CISCO-CLASS-BASED-QOS-MIB::cbQosCMName.3463889 = STRING: voice
    ) e.g policy/qos voice has index .3463889

almost finaly, the index for qos interface (.114) and voice (.3463889)
    (e.g. .1.3.6.1.4.1.9.9.166.1.5.1.1.2.114 
	CISCO-CLASS-BASED-QOS-MIB::cbQosConfigIndex.114.8257777 = Gauge32: 3463889
    ) e.g the counter/qos/policy index is 8257777

and finaly query specific qos.interface.index qos.counter.index...
    (e.g. cbQosCMPostPolicyByte64.114.8257777)
----------


Discovery (External check):
classmap.php takes classname in interest as argument.
and return the SNMP oid suffixes for that classname, per QoS enabled interface
{#IFNAME_QOS} contain the interface description (if:Descr) e.g. the device name of the interface
{#QOSCN_OIDSUFFIX} contain the coresponding (for the interface and classname) SNMP oid suffix to append at the counters base oid.

Template:
zbx_template_cisco-classname-qos.xml is template, which use classmap.php as "External script" disovery rule and add snmp itemprototypes.
This template is "hardcoded" for "voice" classname



Credits:
Hard to find summarized information:
https://sys4.de/en/blog/2013/02/07/monitoring-qos-cisco-routers/

http://zabbix.org/wiki/Advanced_SNMP_Discovery

Tim Koopman  
https://www.zabbix.com/forum/showthread.php?t=45689


https://github.com/pistonov/Zabbix-templates/blob/master/mvsnmp.discovery
