# EasyLobby, EasyCheckin/Checkout!
Allow Quick Checkin or Checkout using CustomID's from EasyLobby SVM

Project: HID - EasyLobby - Easy Check-In/Check-Out
Code Version: 1.0
Author: Benjamin Sommer
GitHub: https://github.com/remmosnimajneb
Theme Design by: HTML5 UP (HTML5UP.NET)
Licensing Information: CC BY-SA 4.0 (https://creativecommons.org/licenses/by-sa/4.0/)

## Disclamers!
1. This program has no relation to HID, EasyLobby SVM or any of it's affiliates. HID, EasyLobby SVM, and eAdvance may be trademarks of HID Global. Learn more about EasyLobby at https://www.hidglobal.com/products/software/easylobby
2. These programs (for the most part) integrate DIRECTLY with EasyLobby SQL Server - meaning, it's directly changing stuff in your database, in ways that were not necessarily perscribed by HID and can cause issues with the system. This program is given AS IS with ABSOLUTELY NO guarantees in any way. In no way shape or form is the author liable for any losses to revenue, data or anything due to using this program.
3. Lastly, these programs are built for the more technical crowd. If you're looking for a little EXE download - this ain't it. Sorry.

## Overview:
This is a quick little custom script to allow quick checkin and checkout of visitors on HID's EasyLobby SVM using CustomID's
When a visitor is registered on EasyLobby and assigned a CustomID just enter it into the input form and it will automatically find the visitor and based on their current status (PreRegistered or CheckedIn) it will check them in or out.
If the visitor is before the time it will allow override to change the time for checkin and then check them in.
If the visitor is within checkin time, it'll check them in or out
And if the Visitor is past their checkout time it'll throw and error.

## Installation:
Note: This assumes you've configured everything from the inital README.md (https://github.com/remmosnimajneb/easylobby-integrations). If you haven't.....now would be a good time to

### Configure EasyCheckin
1. Drop all your files into a directory within C:\wamp64\www (for example mydir)
2. Now open the Config.json with an editor of your choice
Insert your EasyLobby SVM SQL Username, Password and Database name.
Then you need to grab your Station ID from the SQL Server (Grab the [ID] column from dbo.Site on SSMS)
Then add an operator name into the EL_OPERATOR config (can be in words)
Then if you want a doorname, add that in, or leave as ""
3. That should be it! Create a registration and give it a shot!
Open the directory in a web browser (localhost/mydir) and type in a CustomID and see what happens!