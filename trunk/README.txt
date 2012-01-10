YT-Cache Documentation
================================================================================

I Installation

Prerequisites:
* squid 2.7.STABLE9
* apache 2.2.17
* mysql 5.1.54
* php  5.3.5
* ruby 1.8.7

How to install prerequisites:
(recommended) Debian/Ubuntu: apt-get install squid squid-common squid-langpack apache2 mysql-server mysql-client php5 php5-mysql php5-gd ruby
CentOS/Redhat: yum install squid httpd mysql php php-mysql php-gd ruby

How to install script:
1. Login to FTP and upload all files to ROOT www folder (very important - do not put it to folder below top-level domain root, for example don't do http://my-cache.com/ytcache/)
2. Point web browser to server ip/domain name installer, example: http://my-cache.com/install
3. Follow the instructions
4. Delete whole install folder (for additional security)