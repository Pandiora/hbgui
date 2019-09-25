#! /bin/bash
##
# prints formatted SMART results for all drives
# tested and working on: Ubuntu 18.04.1 LTS (Bionic Beaver)
##
echo "================================================================================"
echo "DRIVE::Temp::Model::Serial::Health Status" | awk -F:: '{printf "%-7s%-6s%-22s%-20s%s\n", $1, $2, $3, $4, $5}'
echo "~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~"
for i in $(lsblk | grep -E "disk" | awk '{print $1}')
do
DevSupport=`smartctl -a /dev/$i | awk '/SMART support is:/{print $0}' | awk '{print $4}' | tail -1`
if [ "$DevSupport" == "Enabled" ]
then
DevTemp=`smartctl -a /dev/$i | awk '/Temperature/{print $0}' | awk '{print $10 "C"}'`
DevSerNum=`smartctl -a /dev/$i | awk '/Serial Number:/{print $0}' | awk '{print $3}'`
DevName=`smartctl -a /dev/$i | awk '/Device Model:/{print $0}' | awk '{print $4}'`
DevStatus=`smartctl -a /dev/$i | awk '/SMART overall-health/{print $0}' | awk '{print $1" "$5" "$6}'`
echo [$i]::$DevTemp::$DevName::$DevSerNum::$DevStatus | awk -F:: '{printf "%-7s%-6s%-22s%-20s%s\n", $1, $2, $3, $4, $5}'
fi
done
##
# now find drives that don't have SMART enabled and warn user about these drives
##
echo "--------------------------------------------------------------------------------"
for i in $(lsblk | grep -E "disk" | awk '{print $1}')
do
DevSupport=`smartctl -a /dev/$i | awk '/SMART support is:/{print $0}' | awk '{print $4}' | tail -1`
if [ "$DevSupport" != "Enabled" ]
then
echo [$i]::$DevSupport | awk -F:: '{printf "%-6s **ERROR!!! SMART Support Status: %s\n", $1, $2}'
fi
done
echo "================================================================================"