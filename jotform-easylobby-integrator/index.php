<?php
/********************************
* Project: JotForm to EasyLobby Integrator
* Create Visitor Entries in HID's EasyLobby eAdvance SVM from JotForm.
* Code Version: 1.0
* Author: Benjamin Sommer
* GitHub: https://github.com/remmosnimajneb
* Theme Design by HTML5UP (HTML5UP.net)
***************************************************************************************/

/* Load Config Files */

	/* Get Config File */
	$Config = json_decode(file_get_contents("Config.json"), true);

	/* Get Forms File */
	$Forms = json_decode(file_get_contents("Forms.json"), true);

/* Run Checks */
	
	/* 1. Check if Form is defined */
    	$Form = basename($_SERVER['REQUEST_URI']);

    	/* Check Form is a real form */
    	if($Form == NULL || $Form == "" || !isset($Forms[$Form])){
    		echo "Oops, something went wrong with that request!";
    		die();
    	}

    /* 2. Check SQL Connection */
    	/* Get SQL Login Info */
		DEFINE('DB_NAME', $Config['SQLCONNECTION']['DB_NAME']);
		DEFINE('DB_USER', $Config['SQLCONNECTION']['DB_USERNAME']);
		DEFINE('DB_PASS', $Config['SQLCONNECTION']['DB_PASSWORD']);
	try {
		$DBConnection = new PDO( "sqlsrv:server=(local) ; Database = " . DB_NAME, DB_USER, DB_PASS);  
		$DBConnection->setAttribute( PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION );  
		$DBConnection->setAttribute( PDO::SQLSRV_ATTR_QUERY_TIMEOUT, 1 );  
	} catch(Exception $e) {
		echo "Uh oh, we couldn't connect to the SQL Server! Check your credentials and try again! <br>Error: " . $e;
		die();
	}

/* Otherwise let's move along already! */
	
	/* Setup DB Connection */
		$DBConnection = new PDO( "sqlsrv:server=(local) ; Database = " . DB_NAME, DB_USER, DB_PASS);  
		$DBConnection->setAttribute( PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION );  
		$DBConnection->setAttribute( PDO::SQLSRV_ATTR_QUERY_TIMEOUT, 1 );  

	/* Include Composer for the QR Code and BarCode Generator for CustomID */
		require __DIR__ . '/vendor/autoload.php';
		use Endroid\QrCode\QrCode;

	/* Include headers and classes for Mailer */
		use PHPMailer\PHPMailer\PHPMailer;
		use PHPMailer\PHPMailer\Exception;
  		require 'PHPMailer/Exception.php';
  		require 'PHPMailer/PHPMailer.php';
  		require 'PHPMailer/SMTP.php';


	/* Now if we have recieved a request, let's begin! */
	if(isset($_REQUEST['rawRequest'])){
	
	/* Submission Data to a JSON Decoded Array */
		$SubmissionData = json_decode($_REQUEST['rawRequest'], true);


	/* Configure the Visitor Fields */

		/* Data Fields */
			$Name = $SubmissionData[$Forms[$Form]['Name']];
			$Title = $SubmissionData[$Forms[$Form]['Title']];
			$Company = $SubmissionData[$Forms[$Form]['Company']];
			$Email = $SubmissionData[$Forms[$Form]['Email']];
			$CellPhone = $SubmissionData[$Forms[$Form]['CellPhone']];
			$RegisteredBy = $Forms[$Form]['RegisteredBy'];

		/* Dates */
			$ValidFrom = $SubmissionData[$Forms[$Form]['ValidFrom']];
			$ValidTo = $Forms[$Form]['ValidTo'];

		/* System Fields */
			/* Employee - Get the ID from the CustomID */
			$SQL = "SELECT E.[Id] FROM [RecordCustomId] AS RCI INNER JOIN [Employee] AS E ON E.[Id] = RCI.[ParentId] WHERE RCI.[CustomID] = '" . $Forms[$Form]['Employee'] . "'";
			$stm = $DBConnection->prepare($SQL);
			$stm->execute();
			$EmployeeID = $stm->fetchAll();
			$EmployeeID = $EmployeeID[0]["Id"];

			/* Category */
			$SQL = "SELECT [Id] FROM [Category] WHERE [Name] = '" . $Forms[$Form]['Category'] . "'";
			$stm = $DBConnection->prepare($SQL);
			$stm->execute();
			$CategoryID = $stm->fetchAll();
			$CategoryID = $CategoryID[0]["Id"];

			/* Reason */
			$SQL = "SELECT [Id] FROM [Reason] WHERE [ReasonForVisit] = '" . $Forms[$Form]['Reason'] . "'";
			$stm = $DBConnection->prepare($SQL);
			$stm->execute();
			$ReasonID = $stm->fetchAll();
			$ReasonID = $ReasonID[0]["Id"];

			/* Site ID */
			$SQL = "SELECT [Id] FROM [Site] WHERE [SiteName] = '" . $Forms[$Form]['Site'] . "'";
			$stm = $DBConnection->prepare($SQL);
			$stm->execute();
			$SiteID = $stm->fetchAll();
			$SiteID = $SiteID[0]["Id"];

	/* Create new Visitor Record */

	// Add a new Visitor Record
	$Query = "INSERT INTO Visitor (
							Id, 
							FirstName, 
							LastName, 
							Title, 
							Company, 
							Email, 
							CellPhone, 
							EmployeeId, 
							CategoryId, 
							ReasonId, 
							SiteId, 
							ValidFrom, 
							ValidTo, 
							RegisteredBy, 
							Status, 
							PrintCount, 
							ProxyPrint
						) VALUES (
							NEWID(), 
							'" . $Name["first"] . "', 
							'" . $Name["last"] . "', 
							'" . $Title . "', 
							'" . $Company . "', 
							'" . $Email . "', 
							'" . $CellPhone . "',
							'" . $EmployeeID . "', 
							'" . $CategoryID . "', 
							'" . $ReasonID . "', 
							'" . $SiteID . "', 
							'" . $ValidFrom['date'] . "', 
							DATEADD(MINUTE, " . $ValidTo . ", '" . $ValidFrom['date'] . "'), 
							'" . $RegisteredBy . "', 
							'Preregistered', 
							0,0)";
			$stm = $DBConnection->prepare($Query);
			$stm->execute();
			$RecordID = $DBConnection->lastInsertId();

	/* Now let's create the CustomID */
		/* First, get the UNIQUEID of the Record we just inserted */
			$SQL = "SELECT Id
					FROM
						[Visitor] 
					WHERE RecId = '" . $RecordID . "'";
			$stm = $DBConnection->prepare($SQL);
			$stm->execute();
			$UID = $stm->fetchAll();
			$UID = $UID[0]["Id"];

		/* Now insert new CustomID Record */
	$CustomID = $RecordID + $Config['CUSTOM_ID_STARTING_OFFSET'];
	$CustomID = "V" . $CustomID;

	$SQL = "INSERT INTO [RecordCustomId] (ParentId, CustomId, Type) VALUES ('" . $UID . "', '" . $CustomID . "', 0)";
	$stm = $DBConnection->prepare($SQL);
	$stm->execute();
	

	/* Now, if we need to send an email, let's do that */
	if($Forms[$Form]['SendEmail']){

		/* Generate QR Code */
		$qrCode = new QrCode($Config['EASYCHECKIN_PUBLIC_URL'] . '?CustomID=' . $CustomID);
		$qrCode->setWriterByName('png');
		$qrCode->setEncoding('ISO-8859-1');
		$qrCode->setSize(300);

		/* Send Email */
		try{
	        $mail = new PHPMailer();

	        $mail->SMTPDebug = 0;                       
	        $mail->isSMTP();                            
	        $mail->Host = $Config['SMTPCONNECTION']['HOST'];                       
	        $mail->SMTPAuth = $Config['SMTPCONNECTION']['AUTH'];                     
	        $mail->Username = $Config['SMTPCONNECTION']['USERNAME'];                      
	        $mail->Password = $Config['SMTPCONNECTION']['PASSWORD'];                   
	        $mail->SMTPSecure = $Config['SMTPCONNECTION']['SECURITY'];                     
	        $mail->Port = $Config['SMTPCONNECTION']['PORT'];                       

	        //Recipients
	        $mail->setFrom($Config['SMTPCONNECTION']['FROM_ADDRESS'], $Config['SMTPCONNECTION']['FROM_NAME']);
	        $mail->addAddress($Email);

	        $mail->isHTML(true);
	        
	        $mail->Subject   = $Config['ORG_NAME'] . ' - Reservation Confirmation';


	        /* Now parse out Mail Tags */
	        	$EmailBody = nl2br($Forms[$Form]['EmailBody']);

	        	/* Name */
	        	$EmailBody = str_replace("{FIRST_NAME}", ucfirst(strtolower($Name['first'])), $EmailBody);
	        	$EmailBody = str_replace("{LAST_NAME}", ucfirst(strtolower($Name['last'])), $EmailBody);

	        	/* Visitor Data */
	        	$EmailBody = str_replace("{TITLE}", $Title, $EmailBody);
	        	$EmailBody = str_replace("{COMPANY}", $Company, $EmailBody);
	        	$EmailBody = str_replace("{EMAIL}", $Email, $EmailBody);
	        	$EmailBody = str_replace("{CELL_PHONE}", $CellPhone, $EmailBody);

	        	/* Times */
	        	$EmailBody = str_replace("{VISIT_DATE}", date("l, F d, Y", strtotime($ValidFrom['date'])), $EmailBody);
	        	$EmailBody = str_replace("{VISIT_TIME}", date("h:i:sa", strtotime($ValidFrom['date'])), $EmailBody);

	        	/* CustomID and QR */
	        	$EmailBody = str_replace("{CUSTOM_ID_NUMBER}", $CustomID, $EmailBody);
	        	
	        	$QRImage = "<img src=\"" . $qrCode->writeDataUri() . "\" />";
	        	$EmailBody = str_replace("{QR_CODE_IMAGE}", $QRImage, $EmailBody);

		    /* Now Add to Email */
	        $Email = "<!DOCTYPE html PUBLIC \"-//W3C//DTD XHTML 1.0 Transitional//EN\" \"http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd\"><html style=\"width:100%;font-family:roboto, 'helvetica neue', helvetica, arial, sans-serif;-webkit-text-size-adjust:100%;-ms-text-size-adjust:100%;padding:0;Margin:0;\"> <head> <meta charset=\"UTF-8\"> <meta content=\"width=device-width, initial-scale=1\" name=\"viewport\"> <meta name=\"x-apple-disable-message-reformatting\"> <meta http-equiv=\"X-UA-Compatible\" content=\"IE=edge\"> <meta content=\"telephone=no\" name=\"format-detection\"> <title>New email</title><!--[if (mso 16)]> <style type=\"text/css\"> a{text-decoration: none;}</style><![endif]--> <link href=\"https://fonts.googleapis.com/css?family=Roboto:400,400i,700,700i\" rel=\"stylesheet\"> <style type=\"text/css\">@media only screen and (max-width:600px){.st-br{padding-left:10px!important; padding-right:10px!important}p, ul li, ol li, a{font-size:16px!important; line-height:150%!important}h1{font-size:30px!important; text-align:center; line-height:120%!important}h2{font-size:26px!important; text-align:center; line-height:120%!important}h3{font-size:20px!important; text-align:center; line-height:120%!important}h1 a{font-size:30px!important; text-align:center}h2 a{font-size:26px!important; text-align:center}h3 a{font-size:20px!important; text-align:center}.es-menu td a{font-size:14px!important}.es-header-body p, .es-header-body ul li, .es-header-body ol li, .es-header-body a{font-size:16px!important}.es-footer-body p, .es-footer-body ul li, .es-footer-body ol li, .es-footer-body a{font-size:14px!important}.es-infoblock p, .es-infoblock ul li, .es-infoblock ol li, .es-infoblock a{font-size:12px!important}*[class=\"gmail-fix\"]{display:none!important}.es-m-txt-c, .es-m-txt-c h1, .es-m-txt-c h2, .es-m-txt-c h3{text-align:center!important}.es-m-txt-r, .es-m-txt-r h1, .es-m-txt-r h2, .es-m-txt-r h3{text-align:right!important}.es-m-txt-l, .es-m-txt-l h1, .es-m-txt-l h2, .es-m-txt-l h3{text-align:left!important}.es-m-txt-r img, .es-m-txt-c img, .es-m-txt-l img{display:inline!important}.es-button-border{display:block!important}a.es-button{font-size:16px!important; display:block!important; border-left-width:0px!important; border-right-width:0px!important}.es-btn-fw{border-width:10px 0px!important; text-align:center!important}.es-adaptive table, .es-btn-fw, .es-btn-fw-brdr, .es-left, .es-right{width:100%!important}.es-content table, .es-header table, .es-footer table, .es-content, .es-footer, .es-header{width:100%!important; max-width:600px!important}.es-adapt-td{display:block!important; width:100%!important}.adapt-img{width:100%!important; height:auto!important}.es-m-p0{padding:0px!important}.es-m-p0r{padding-right:0px!important}.es-m-p0l{padding-left:0px!important}.es-m-p0t{padding-top:0px!important}.es-m-p0b{padding-bottom:0!important}.es-m-p20b{padding-bottom:20px!important}.es-mobile-hidden, .es-hidden{display:none!important}.es-desk-hidden{display:table-row!important; width:auto!important; overflow:visible!important; float:none!important; max-height:inherit!important; line-height:inherit!important}.es-desk-menu-hidden{display:table-cell!important}table.es-table-not-adapt, .esd-block-html table{width:auto!important}table.es-social{display:inline-block!important}table.es-social td{display:inline-block!important}}.rollover:hover .rollover-first{max-height:0px!important;}.rollover:hover .rollover-second{max-height:none!important;}#outlook a{padding:0;}.ExternalClass{width:100%;}.ExternalClass,.ExternalClass p,.ExternalClass span,.ExternalClass font,.ExternalClass td,.ExternalClass div{line-height:100%;}.es-button{mso-style-priority:100!important;text-decoration:none!important;}a[x-apple-data-detectors]{color:inherit!important;text-decoration:none!important;font-size:inherit!important;font-family:inherit!important;font-weight:inherit!important;line-height:inherit!important;}.es-desk-hidden{display:none;float:left;overflow:hidden;width:0;max-height:0;line-height:0;mso-hide:all;}.es-button-border:hover{border-style:solid solid solid solid!important;background:#d6a700!important;border-color:#42d159 #42d159 #42d159 #42d159!important;}.es-button-border:hover a.es-button{background:#d6a700!important;border-color:#d6a700!important;}</style> </head> <body style=\"width:100%;font-family:roboto, 'helvetica neue', helvetica, arial, sans-serif;-webkit-text-size-adjust:100%;-ms-text-size-adjust:100%;padding:0;Margin:0;\"> <div class=\"es-wrapper-color\" style=\"background-color:#F6F6F6;\"><!--[if gte mso 9]><v:background xmlns:v=\"urn:schemas-microsoft-com:vml\" fill=\"t\"><v:fill type=\"tile\" color=\"#f6f6f6\"></v:fill></v:background><![endif]--> <table class=\"es-wrapper\" width=\"100%\" cellspacing=\"0\" cellpadding=\"0\" style=\"mso-table-lspace:0pt;mso-table-rspace:0pt;border-collapse:collapse;border-spacing:0px;padding:0;Margin:0;width:100%;height:100%;background-repeat:repeat;background-position:center top;\"> <tr style=\"border-collapse:collapse;\"> <td class=\"st-br\" valign=\"top\" style=\"padding:0;Margin:0;\"> <table class=\"es-content\" cellspacing=\"0\" cellpadding=\"0\" align=\"center\" style=\"mso-table-lspace:0pt;mso-table-rspace:0pt;border-collapse:collapse;border-spacing:0px;table-layout:fixed !important;width:100%;\"> <tr style=\"border-collapse:collapse;\"> <td style=\"padding:0;Margin:0;background-color:transparent;\" bgcolor=\"transparent\" align=\"center\"> <table class=\"es-content-body\" style=\"mso-table-lspace:0pt;mso-table-rspace:0pt;border-collapse:collapse;border-spacing:0px;background-color:transparent;\" width=\"600\" cellspacing=\"0\" cellpadding=\"0\" bgcolor=\"transparent\" align=\"center\"> <tr style=\"border-collapse:collapse;\"> <td style=\"Margin:0;padding-bottom:15px;padding-top:30px;padding-left:30px;padding-right:30px;border-radius:10px 10px 0px 0px;background-color:#FFFFFF;background-position:left bottom;\" bgcolor=\"#ffffff\" align=\"left\"> <table width=\"100%\" cellspacing=\"0\" cellpadding=\"0\" style=\"mso-table-lspace:0pt;mso-table-rspace:0pt;border-collapse:collapse;border-spacing:0px;\"> <tr style=\"border-collapse:collapse;\"> <td width=\"540\" valign=\"top\" align=\"center\" style=\"padding:0;Margin:0;\"> <table style=\"mso-table-lspace:0pt;mso-table-rspace:0pt;border-collapse:collapse;border-spacing:0px;background-position:left bottom;\" width=\"100%\" cellspacing=\"0\" cellpadding=\"0\" role=\"presentation\"> <tr style=\"border-collapse:collapse;\"> <td align=\"center\" style=\"padding:0;Margin:0;\"><h1 style=\"Margin:0;line-height:36px;mso-line-height-rule:exactly;font-family:tahoma, verdana, segoe, sans-serif;font-size:30px;font-style:normal;font-weight:bold;color:#212121;\">Registration Confirmation<br></h1></td></tr></table></td></tr></table></td></tr><tr style=\"border-collapse:collapse;\"> <td align=\"left\" style=\"Margin:0;padding-top:30px;padding-bottom:30px;padding-left:30px;padding-right:30px;\"> <table width=\"100%\" cellspacing=\"0\" cellpadding=\"0\" style=\"mso-table-lspace:0pt;mso-table-rspace:0pt;border-collapse:collapse;border-spacing:0px;\"> <tr style=\"border-collapse:collapse;\"> <td width=\"540\" valign=\"top\" align=\"center\" style=\"padding:0;Margin:0;\"> <table width=\"100%\" cellspacing=\"0\" cellpadding=\"0\" role=\"presentation\" style=\"mso-table-lspace:0pt;mso-table-rspace:0pt;border-collapse:collapse;border-spacing:0px;\"> <tr style=\"border-collapse:collapse;\"> <td align=\"left\" style=\"padding:0;Margin:0;\"><p style=\"Margin:0;-webkit-text-size-adjust:none;-ms-text-size-adjust:none;mso-line-height-rule:exactly;font-size:16px;font-family:roboto, 'helvetica neue', helvetica, arial, sans-serif;line-height:24px;color:#131313;\">" . $EmailBody . "</p></td></tr></table></td></tr></table></td></tr></table></td></tr></table> <table class=\"es-footer\" cellspacing=\"0\" cellpadding=\"0\" align=\"center\" style=\"mso-table-lspace:0pt;mso-table-rspace:0pt;border-collapse:collapse;border-spacing:0px;table-layout:fixed !important;width:100%;background-color:#F6F6F6;background-repeat:repeat;background-position:center top;\"> <tr style=\"border-collapse:collapse;\"> <td style=\"padding:0;Margin:0;background-image:url(https://ehbjeu.stripocdn.email/content/guids/CABINET_1a7c91b699ecd64ce2bfe3d6319bcdab/images/31751560930679125.jpg);background-position:left bottom;background-repeat:no-repeat;\" background=\"https://ehbjeu.stripocdn.email/content/guids/CABINET_1a7c91b699ecd64ce2bfe3d6319bcdab/images/31751560930679125.jpg\" align=\"center\"> <table class=\"es-footer-body\" width=\"600\" cellspacing=\"0\" cellpadding=\"0\" bgcolor=\"#31cb4b\" align=\"center\" style=\"mso-table-lspace:0pt;mso-table-rspace:0pt;border-collapse:collapse;border-spacing:0px;background-color:transparent;\"> <tr style=\"border-collapse:collapse;\"> <td style=\"Margin:0;padding-top:30px;padding-bottom:30px;padding-left:30px;padding-right:30px;border-radius:0px 0px 10px 10px;background-color:#EFEFEF;\" bgcolor=\"#efefef\" align=\"left\"> <table width=\"100%\" cellspacing=\"0\" cellpadding=\"0\" style=\"mso-table-lspace:0pt;mso-table-rspace:0pt;border-collapse:collapse;border-spacing:0px;\"> <tr style=\"border-collapse:collapse;\"> <td class=\"es-m-p0r\" width=\"540\" align=\"center\" style=\"padding:0;Margin:0;\"> <table width=\"100%\" cellspacing=\"0\" cellpadding=\"0\" role=\"presentation\" style=\"mso-table-lspace:0pt;mso-table-rspace:0pt;border-collapse:collapse;border-spacing:0px;\"> <tr style=\"border-collapse:collapse;\"> <td align=\"center\" style=\"padding:0;Margin:0;\"><p style=\"Margin:0;-webkit-text-size-adjust:none;-ms-text-size-adjust:none;mso-line-height-rule:exactly;font-size:16px;font-family:roboto, 'helvetica neue', helvetica, arial, sans-serif;line-height:24px;color:#131313;\">" . $Config['ORG_NAME'] . "<br></p><p style=\"Margin:0;-webkit-text-size-adjust:none;-ms-text-size-adjust:none;mso-line-height-rule:exactly;font-size:16px;font-family:roboto, 'helvetica neue', helvetica, arial, sans-serif;line-height:24px;color:#131313;\">" . $Config['EMAIL_FOOTER_ADDRESS'] . "<br></p><p style=\"Margin:0;-webkit-text-size-adjust:none;-ms-text-size-adjust:none;mso-line-height-rule:exactly;font-size:16px;font-family:roboto, 'helvetica neue', helvetica, arial, sans-serif;line-height:24px;color:#131313;\">" . $Config['EMAIL_FOOTER_PHONE'] . "</p><p style=\"Margin:0;-webkit-text-size-adjust:none;-ms-text-size-adjust:none;mso-line-height-rule:exactly;font-size:16px;font-family:roboto, 'helvetica neue', helvetica, arial, sans-serif;line-height:24px;color:#131313;\">" . $Config['EMAIL_FOOTER_EMAIL'] . "<br></p></td></tr></table></td></tr></table></td></tr><tr style=\"border-collapse:collapse;\"> <td style=\"padding:0;Margin:0;background-position:left top;\" align=\"left\"> <table width=\"100%\" cellspacing=\"0\" cellpadding=\"0\" style=\"mso-table-lspace:0pt;mso-table-rspace:0pt;border-collapse:collapse;border-spacing:0px;\"> <tr style=\"border-collapse:collapse;\"> <td width=\"600\" valign=\"top\" align=\"center\" style=\"padding:0;Margin:0;\"> <table width=\"100%\" cellspacing=\"0\" cellpadding=\"0\" role=\"presentation\" style=\"mso-table-lspace:0pt;mso-table-rspace:0pt;border-collapse:collapse;border-spacing:0px;\"> <tr style=\"border-collapse:collapse;\"> <td height=\"40\" align=\"center\" style=\"padding:0;Margin:0;\"></td></tr></table></td></tr></table></td></tr></table></td></tr></table></td></tr></table> </div></body></html>";

				/* And Send Email! */
	        	$mail->Body      = $Email;
	       		$mail->Send();
		    } catch (Exception $e) {
		    	// Error E!
		  	};

		} // End if(SendEmail)
	
	} else {
		/* General Error */
		echo "Error";
		die();
	}        
?>