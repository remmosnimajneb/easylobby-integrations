# JotForm to EasyLobby Integrator
Create Visitor Entries in HID's EasyLobby eAdvance SVM from JotForm.

Project: JotForm to EasyLobby Integrator
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
So this is another cool little script that allows using JotForm's to directly create Visitor Registrations in EasyLobby.
In short, you make a JotForm - use the WebHook integration to send to this script, it then makes the visitor registration, and if you want to integrate further with EasyCheckin, will send a customized email with a QR Code for quick and seamless Checkin using EasyLobby!
As with all these programs, it's a work in progress and may or may not get updates as we go.

## Installation:
Note: This assumes you've configured everything from the inital README.md (https://github.com/remmosnimajneb/easylobby-integrations). If you haven't.....now would be a good time to

### Configure the System
1. Drop all your files into a directory within C:\wamp64\www (for example mydir)
2. Now open the Config.json with an editor of your choice
SQLCONNECTION - The SQL Login you setup with EasyLobby - straightforward
SMTPCONNECTION - If you want to send auto-confirm messages - you'll need to set this up

	(Note this is running with PHPMailer - so if you want, consult there for more info)
	HOST - SMTP Host TLD (mail.example.tld)
	AUTH - true or false
	USERNAME, PASSWORD - no explanation needed here :)
	SECURITY - ssl or tls
	PORT - number
	FROM_ADDRESS - email format
	FROM_NAME - String

Further Config
	CUSTOM_ID_STARTING_OFFSET - Ofset when sending out Custom ID's - each ID is based off of the RecordID + an Offset (Look in the CustomID table for your offset)
	EASYCHECKIN_PUBLIC_URL - if you want to integrate with EasyCheckin - the URL for the QR Code to link to (if it's an internal system, that works also)
	ORG_NAME - Branding, Organization or Company Name
	FOOTER Stuff is simply branding for the email
	ADMIN_PANEL_USERNAME and ADMIN_PANEL_PASSWORD are used to login to the UI to make new forms

Ok! We're ready to configure a new form!
	Login on a browser to localhost/yourdir/admin
	Login with the username and password set above and add a new form

Now here is the "fun" part - you need to get the field names from JotForm.....buuuut JotForm doesn't give you an easy way to see them, so you need to use something like requestbin.com FIRST to get the fields (note if you edit a form, the names may change!)

So:
1. Make a new JotForm
2. Go to requestbin.com and get an endpoint URL
3. Add the URL to the form as a WebHook (https://www.jotform.com/blog/send-instant-submission-notifications-with-webhooks/)
4. Submit the form, you'll get the response on RequestBin, now you need the rawRequest

So if your rawRequest was this:
{"slug":"submit\/4327839213\/","q3_name":{"first":"firstname","last":"lastname"},"q4_email":"example@example.com","q7_typeA":"someotherfield","q8_appointment":{"date":"2020-06-21 10:00","duration":"15","timezone":"America\/New_York (GMT-04:00)"},"preview":"true"}

You'd need for the form fields below:
Name: q3_name
Email: q4_email
Title: q7_typeA
ValidFrom: q8_appointment
For example.

So here are all the form fields:
Slug is the /something/ in the URL you will use for JotForm
Name: Should be a JotForm "Name Field"
Title, Company - can be anything
Email - Should be JotForm email field
Cell Phone - Anything
ValidFrom - Build as a JotForm Appointment Field, can be any datetime though
ValidTo - time in MINUTES (60, 120, 30 ect) from the ValidFrom time to expire the Visitor
Employee, Category, Reason, Site and Registered by are all from EasyLobby - to make new records here - make it in EasyLobby first
SendEmail - should the system send an autoconfirm to the EMAIL field on a submission
Email Body - the email body to send, include the mail tags listed to customize the email!


Annnnnd now we're ready.
Save the config and now change the JotForm webhook to be yourpublicwebsite.tld/slug - So now, JotForm on each submission will send the data to the script, which will push to EasyLobby!

Ok It's alot, I know, but here it is.

Further updates to maybe come, but for now, enjoy!