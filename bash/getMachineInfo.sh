# using php to retrieve data
# add ur user to video and disk group like below AND REBOOT
usermod -G video www-data
usermod -G disk www-data


# Even more permissions needed
# edit the file to look like below to grant www-data user more permissions
sudo visudo
	#
	# This file MUST be edited with the 'visudo' command as root.
	#
	# Please consider adding local content in /etc/sudoers.d/ instead of
	# directly modifying this file.
	#
	# See the man page for details on how to write a sudoers file.
	#
	Defaults        env_reset
	Defaults        mail_badpass
	Defaults        secure_path="/usr/local/sbin:/usr/local/bin:/usr/sbin:/usr/bin:/sbin:/bin"

	# Host alias specification

	# User alias specification

	# Cmnd alias specification

	# User privilege specification
	root    ALL=(ALL:ALL) ALL

	# Allow members of group sudo to execute any command
	%sudo   ALL=(ALL:ALL) ALL

	# See sudoers(5) for more information on "#include" directives:
	www-data ALL = NOPASSWD: /usr/sbin/smartctl                 


	#includedir /etc/sudoers.d


# O B S O L E T E


# Additionally this script needs even more rights to access smartctl data
# adjust path to your folder
#cap_sys_rawio=ep /home/hbgui/php/functions.php


#!/bin/bash
# Getting Machine Info - Raspi specific
# please allow ur webserver-user write access to cpuinfo_cur_freq
# like ~#: chown www-data:root /sys/devices/system/cpu/cpu0/cpufreq/cpuinfo_cur_freq
# additionally add your webserver user to video group
# like ~#: usermod -G video www-data
# and for smartctl we need to add our user to disk group
# like ~#: usermod -G disk www-data
#################################################################################

#declare -A arr
#arr[0]=$(cat /sys/devices/system/cpu/cpu0/cpufreq/cpuinfo_cur_freq)	# 600000
#arr[1]=$(vcgencmd measure_temp | cut -d'=' -f 2 | cut -d'.' -f 1)	# temp=54.0'C
#arr[2]=$(smartctl -a /dev/sda | grep Temp | cut -d" " -f 31,37)		# 37
#arr[3]=$(smartctl -a /dev/sdb | grep Temp | cut -d" " -f 31,37)		# null
#
#echo ${arr[@]}