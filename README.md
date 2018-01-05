# cacti-netsnmp-memory

## Original source
This project is a source-code of the 0.7 version of cacti-netsnmp-memory produced by Eric A. Hall which is available on his website:
http://www.eric-a-hall.com/software/cacti-netsnmp-memory/

I have replicated the page as a readme here in its entirety.  The purpose of this repo is merely to control changes that I make to enable the netsnmp template to work with cacti 1.1.30.

Copyright for this template/code is held by Eric A Hall:
Copyright © 2010-2017 Eric A. Hall.

## Introduction
Although the Linux version of Cacti includes multiple script templates for monitoring memory utilization on various *NIX systems, the graphs that are produced by the default tools are not very informative. For example, the memory usage script template for the Cacti server's local Linux system (as measured through local operating system calls) only shows free memory and swap space usage, and does not display the total real memory or the amount that has been used. Meanwhile, the "ucd/net" script template that reads memory usage data from Net-SNMP MIBs also omits critical data, partly due to the fact that different operating systems implement the Net-SNMP MIBs differently.

> ![image](https://user-images.githubusercontent.com/9052188/34602586-39bd06a2-f1f8-11e7-9eab-117b1fae5901.png)

Figure 1


This script template is intended to overcome these shortcomings by fetching all of the available memory data from all known sources (including the standard HOST MIB), and then performing basic arithmetic to fill in any gaps in the data. For illustration purposes, the sample graph below shows the memory usage for a Linux host with 1 gigabyte of RAM. In that chart, the amount of real memory that is available to the system is displayed along with the amount of memory that has been reserved for processes ("used real"), the amount that has been allocated for buffers and disk caching, the amount that has not been allocated ("unused real"), and the amount of swap space that is currently in use. Note that this is the full and complete set of data, and some operating systems may provide much less data to the relevant SNMP MIBs.

## Installation
To use this script template, perform the following steps:

1. Download cacti-netsnmp-memory.0.7.tar.gz to a temporary directory on the Cacti server machine.
2. Expand the archive with the command tar -xvzf cacti-netsnmp-memory.0.7.tar.gz, and change to the cacti-netsnmp-memory directory that is created.
3. Copy scripts/ss_netsnmp_memory.php to the <cacti>/scripts/ directory.
4. Access the Cacti installation in a web browser, click on the "Import Templates" menu item on the left side of the Console screen, and import the template/Net-SNMP_memory_graph_template.xml file. Cacti should automatically create the required graph template, data input method, and data template objects.
5. Click on the Devices menu item on the left side of the Console screen, select a *NIX host that is running Net-SNMP, and scroll down to the "Associated Graph Templates " table. Select "Host Memory - ucd/net - Memory Usage" in the "Add Graph Template " drop-down box, and click the "Add" button.
6. After the Device screen reloads, verify that the "Host Memory - ucd/net - Memory Usage" graph template is now present, and then click the "Create Graphs for this Host" link at the top of the page.
7. Locate the "Host Memory - ucd/net - Memory Usage" graph template, enable the checkbox to its right, and then scroll to the bottom of the page and click the "Create" button.
  
_Note: these files are intended to be used with *Cacti 0.8.6 and 0.8.7 and PHP 5.2*, and may not operate as expected with other versions._

## Script Input and Output
In some cases you may want to execute the script file manually for debugging purposes. The parameters to the script use a fixed structure that is optimized for use with the Cacti poller, but also allows for human interaction.

In particular, the script uses an SNMP protocol "bundle" of the following values, separated by colon characters: In those cases where a value is unneeded (such as SNMP v3 authentication credentials for an SNMP v2 query), or where a default value is adequate (such as the SNMP port number), the value can be omitted.

- hostname: The domain name or IP address of the target device. This value is mandatory.
- version: The version of SNMP to use. The remaining parameter values will be verified against this value, and is mandatory.
- community: The SNMP community string to use. If version 1 or 2 was specified, the community string must be provided. If version 3 was specified, this value will be ignored.
- v3 username: Part of the credentials for SNMP v3 queries. If version 1 or 2 was specified, this value will be ignored.
- v3 password : Part of the credentials for SNMP v3 queries. If version 1 or 2 was specified, this value will be ignored.
- v3 authentication protocol : Part of the credentials for SNMP v3 queries. If version 1 or 2 was specified, this value will be ignored.
- v3 privilege password : Part of the credentials for SNMP v3 queries. If version 1 or 2 was specified, this value will be ignored.
- v3 privilige protocol : Part of the credentials for SNMP v3 queries. If version 1 or 2 was specified, this value will be ignored.
- v3 authentication context : Part of the credentials for SNMP v3 queries. If version 1 or 2 was specified, this value will be ignored.
- port number: The UDP port number for the SNMP daemon on the target device. If a value is not specified, the default value of "161" will be used.
- timeout: The number of seconds to wait for the SNMP query to be executed. If a value is not specified, the Cacti configuration will be read for a locally-defined default value.

Taken as a whole, a valid SNMP bundle for the localhost device using SNMP v2 with the community string of "public" on the default port number and a default timeout would be "localhost:2:public::::::::".

The output from the script contains the all of the data that is needed to populate the RRD file and associated graph template.

The full exchange for these requests, using example data from above, are shown below:
```
$ php ss_netsnmp_memory.php hostname:2:public::::::::
totalReal:2059672 availReal:386380 totalSwap:4241144 availSwap:4224844 memBuffer:900 memCached:1046992 usedReal:625400 usedSwap:16300
```
