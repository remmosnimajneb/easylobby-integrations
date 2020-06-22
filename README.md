# HID EasyLobby eAdvance SVM Integrations
Addons and Integrations for HID's EasyLobby SVM

Project: HID EasyLobby eAdvance SVM Integrations
Code Version: 1.0
Author: Benjamin Sommer
GitHub: https://github.com/remmosnimajneb
Theme Design by: HTML5 UP (HTML5UP.NET)
Licensing Information: CC BY-SA 4.0 (https://creativecommons.org/licenses/by-sa/4.0/)

## What is this?
So I've been playing around/helping out with a building testing out EasyLobby SVM for their registration system. We've run into a few issues/things we'd like to change, including easier Checkin, better UI, integrations with JotForm ect.
Here are a some scripts we've been working on to better use EasyLobby SVM, hopefully they'll help some of you guys also!

Programs so far built are:
1. EasyCheckin/Checkout - allow Quick Checkin and Checkout by CustomID
2. JotForm integrations - integrate your JotForms to directly insert Visitor records into Easy Lobby and send out a QR Code with connects with EasyCheckin for streamlined Checkin service!

## Disclamers!
1. This program has no relation to HID, EasyLobby SVM or any of it's affiliates. HID, EasyLobby SVM, and eAdvance may be trademarks of HID Global. Learn more about EasyLobby at https://www.hidglobal.com/products/software/easylobby
2. These programs (for the most part) integrate DIRECTLY with EasyLobby SQL Server - meaning, it's directly changing stuff in your database, in ways that were not necessarily perscribed by HID and can cause issues with the system. This program is given AS IS with ABSOLUTELY NO guarantees in any way. In no way shape or form is the author liable for any losses to revenue, data or anything due to using this program.
3. Lastly, these programs are built for the more technical crowd. If you're looking for a little EXE download - this ain't it. Sorry.

## Pre-Req's
Note: This assumes you have EasyLobby SVM installed properly and configured.

So the way this works is that it directly connects and alters the SQL Database for EasyLobby - so you need access to the local MSSQL Database on the machine it's installed on.
Meaning, if installed on another PC, you need to allow access to Port 3306 on the PC (LAN or WAN Port Forwarding works), or as we do, install this on a Local WAMP Stack, we're taking the second route.

### Setup WAMP Server
1. Install WAMP Server - https://www.wampserver.com/en/
2. Your going to want to change Apache port to another port - as 80 or 8080 may be used by EasyLobby eAdvance - so follow this to change the port to something arbitrary (let's say 4000) https://stackoverflow.com/questions/8574332/how-to-change-port-number-for-apache-in-wamp
3. Then comes the hard part, you need to instal SQL Drivers for PHP, you need drivers for "sqlsrv" for PHP (Using PDO). It's really annoying, so I'll try to give whatever points I can, but ultimately, it can be done, just have patience, alot of it.

### Installation and Configure SQLSRV Drivers for PHP on WAMPSERVER
0. Start here to grab the drivers (if unsure, grab all of them) https://docs.microsoft.com/en-us/sql/connect/php/microsoft-php-driver-for-sql-server?view=sql-server-2017
1. Then you need to drop the extracted DLL's into "C:\wamp64\bin\php\PHP_VERSION\ext\"
2. Finally (hopefully?) you need to configure them in PHP.ini, though, WAMP has TWO PHP.ini locations, so make sure to update both: "C:\wamp64\bin\php\PHP_VERSION\php.ini" AND "C:\wamp64\bin\php\PHP_VERSION\phpForApache.ini"
3. Now in the "Dynamic Extensions" section, add all the files you just stuck in as "extension=FILENAME" - keep in mind the filename should NOT include a ".dll"
4. Finally, hopefully if it went well, just start WAMP, you may get a bunch of warnings, that MAY be ok, just keep going.
5. If this doesn't work, look around online you'll find more help.

From here on in, your ready to install any of the individual apps as you wish!