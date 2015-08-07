## Installation ##

Prerequisites:

```
- squid 2.7.STABLE9
- apache 2.2.17
- mysql 5.1.54
- php  5.3.5
- ruby 1.8.7
```

## How to install prerequisites ##

(recommended) Debian/Ubuntu: apt-get install squid squid-common squid-langpack apache2 mysql-server mysql-client php5 php5-mysql php5-gd ruby
CentOS/Redhat: yum install squid httpd mysql php php-mysql php-gd ruby
Others:
There must be installed:
  * Apache
  * PHP
  * Mysql
  * PHP GD package (for creating graphs)
  * squid
  * Ruby

## Setup squid ##
Squid configuration is probalby on one of the next locations:
  * /etc/squid/squid.conf
  * /usr/local/etc/squid/squid.conf
  * /usr/local/squid/etc/squid/squid.conf
If you still can't find it, try **updatedb** + **locate squid.conf** commands.

In the end of the config file add following:
```
url_rewrite_program /var/www/cache.rb
url_rewrite_host_header off
cache deny to_localhost
header_access Server deny to_localhost
```

replace **/var/www/cache.rb** with your path if different.

## Setup Apache2 ##
(Ubuntu/Debian): Edit /etc/apache2/apache2.conf and add on the end:
  * ServerToken Prod

## Setup PHP ##
(Ubuntu/Debian): Edit /etc/php5/apache2/php.ini and add on the end:
  * expose\_php = Off

# How to install script #

```
1. Login to FTP and upload all files to ROOT www folder (very important - do not put it to folder below top-level domain root, example http://my-cache.com/ytcache/ is incorrect!)
2. Go to shell and set owner www-data on www: chown www-data:www-data /var/www -R
3. Point web browser to server ip/domain name installer, example: http://my-cache.com/install
4. Follow the instructions
5. Delete whole install folder (for additional security)
```

Now go to web root (http://yout-domain.you/index.php) and login with admin/admin.

In the settings there is a storage control which is 4 paths (this will be changed to more flexible solution) of idenpendent directories where cache files is kept.
After you set 4 paths you need to format them, this will create 16 directories 1 level deep each (16x16 folders).
This is done this way to prevent having too many files in only one directory which would lag whole machine.
One path with 16x16 levels deep should hold 7 million files with no significant decrease in performance.

Next thing is a debug.
Set this to 0 ONLY if you have less than 5 users simoultaniously accessing the cache otherwise this table will be flooded with requests and debug info.
In the productivity mode keep this to 2 as it will show in the debug table only criticals and errors.

## Forwarding ports to cache (only when in bridge mode) ##

This cannot hold detailed manual for all cases becouse there's a very different routers on the market, and i will try to populate this list as much as i can including "generic" solution.


---


Generic solution

Next subnets should be forwarded to local cache ip, port 8080 (transparent):

;;; youtube videos preffered - etc o-o.preferred.bud01s03.v19.lscache6.c.youtube.com
74.125.108.0/24

;;; youtube videos standard
208.65.154.0/24

;;; youtube www
74.125.232.192/28

;;; v1.lscache1.c.youtube.com
74.125.15.0/24

;;; tc.v1.cache1.c.youtube.com
208.65.155.0/24

;;; tc.v1.cache2.c.youtube.com
208.117.226.0/24

;;;  v1.cache1.c.youtube.com
74.125.218.0/24

;;; cache.telekom.rs
79.101.110.0/23


---


Mikrotik (bridge configuration-this router don't NAT anything, just bridges)

```
/ip firewall address-list

add address=74.125.108.0/24 comment="youtube videos preffered - etc o-o.preferred.bud01s03.v19.lscache6.c.youtube.com" disabled=no list=youtube

add address=208.65.154.0/24 comment="youtube videos standard" disabled=no list=youtube

add address=74.125.232.192/28 comment="youtube www" disabled=no list=youtube

add address=74.125.15.0/24 comment=v1.lscache1.c.youtube.com disabled=no list=youtube

add address=208.65.155.0/24 comment=tc.v1.cache1.c.youtube.com disabled=no list=youtube

add address=208.117.226.0/24 comment=tc.v1.cache2.c.youtube.com disabled=no list=youtube

add address=74.125.218.0/24 comment=" v1.cache1.c.youtube.com" disabled=no list=youtube

add address=79.101.110.0/23 comment=cache.telekom.rs disabled=no list=youtube

add address=[LOCAL SUBNET] comment=cache.telekom.rs disabled=no list=youtube_source
```
```
/ip firewall nat
add action=dst-nat chain=dstnat comment="youtube redirect" disabled=yes dst-address-list=youtube dst-port=\
    80 protocol=tcp src-address-list=youtube_source to-addresses=[LOCALIP] to-ports=8080
```
replace **LOCALIP** and **LOCAL SUBNET** with appropriate addresses