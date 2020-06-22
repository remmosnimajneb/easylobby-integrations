<?php
/********************************
* Project: HID - EasyLobby - Easy Check-In/Check-Out
* Allow Quick Checkin or Checkout using CustomID's from EasyLobby SVM
* Code Version: 1.0
* Author: Benjamin Sommer
* GitHub: https://github.com/remmosnimajneb
* Theme Design by HTML5UP (HTML5UP.net)
***************************************************************************************/

/* Insert a CustomID from EasyLobby
* If they are before Check in time, prompt for ovverride and if pressed, check them in
* If their Check in time is now, check them in.
* If past Checkout time, throw an error
*/

/* Get Config File */
$Config = json_decode(file_get_contents("Config.json"), true);

/* Get SQL Login Info */
DEFINE('DB_NAME', $Config['SQL_DB_DATABASE']);
DEFINE('DB_USER', $Config['SQL_DB_USERNAME']);
DEFINE('DB_PASS', $Config['SQL_DB_PASSWORD']);

$DBConnection = new PDO( "sqlsrv:server=(local) ; Database = " . DB_NAME, DB_USER, DB_PASS);  
$DBConnection->setAttribute( PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION );  
$DBConnection->setAttribute( PDO::SQLSRV_ATTR_QUERY_TIMEOUT, 1 );  

// Check if CustomID is set
if(isset($_REQUEST['CustomID'])){

	/* 1. Get the ParentID */
	$Query = "SELECT FirstName, LastName, ParentID, Status, ValidFrom, ValidTo, GetDate() AS CurrentDate
				FROM 
				[EasyLobby10].[dbo].[RecordCustomId] AS RI
					INNER JOIN
				[EasyLobby10].[dbo].[Visitor] AS V
					ON V.[Id] = RI.[ParentId]
			WHERE
				GetDate() < ValidTo
					AND
				RI.[CustomId] = '" . $_REQUEST['CustomID'] . "'";

	$stm = $DBConnection->prepare($Query);
	$stm->execute();
	$Records = $stm->fetchAll()[0];
	
		if($Records["ParentID"] != NULL){

		/* Check Date */
			if($Records["CurrentDate"] < $Records["ValidFrom"] && (!isset($_REQUEST['Override']) ) ){
				// Ask if we should override
				$PromptForOverride = true;
			} else {
			/* PreRegistered */
				if($Records["Status"] == "Preregistered"){
					/* Add Record to [VisitorEntry] and then Update this Record */
						$SQL = "INSERT INTO 
									[VisitorEntry] 
								(Id, VisitorId, Type, StationId, Operator, DoorName, EntryTime) 
									VALUES 
								(NEWID(), '" . $Records['ParentID'] . "', 'CheckIn', '" . $Config['EL_STATION_ID'] . "', '" . $Config['EL_OPERATOR'] . "', '" . $Config['EL_DOORNAME'] . "', GetDate())";
						$stm = $DBConnection->prepare($SQL);
						$stm->execute();

						// Now, since ID isn't an Identity Key we need to Re-Query to get the ID
						$SQL = "SELECT Id
								FROM
									[VisitorEntry] 
								WHERE VisitorId = '" . $Records['ParentID'] . "' AND Type = 'CheckIn'";
						$stm = $DBConnection->prepare($SQL);
						$stm->execute();
						$Id = $stm->fetchAll();
						$Id = $Id[0]["Id"];

						/* Now if we are overriding */
						$SetValidTo = "";
						if(isset($_REQUEST['Override']) && $_REQUEST['Override']){
							$SetValidTo = "ValidFrom = GETDATE(),";
						}

					/* Now update the Record */
						$SQL = "UPDATE [Visitor] 
									SET 
								CheckInId = '" . $Id . "',
								" . $SetValidTo . "
								Status = 'CheckedIn',
								ModifiedDate = GETDATE()
									WHERE 
								Id = '" . $Records["ParentID"] . "'";
						$stm = $DBConnection->prepare($SQL);
						$stm->execute();
						$Message = "Visitor " . $Records["LastName"] . ", " . $Records["FirstName"] . " Checked In!";

			/* CheckedIn */
				} else if($Records["Status"] == "CheckedIn"){
					/* Add Record to [VisitorEntry] and then Update this Record */
					$SQL = "INSERT INTO 
								[VisitorEntry] 
							(Id, VisitorId, Type, StationId, Operator, DoorName, EntryTime) 
								VALUES 
							(NEWID(), '" . $Records['ParentID'] . "', 'CheckOut', '" . $Config['EL_STATION_ID'] . "', '" . $Config['EL_OPERATOR'] . "', '" . $Config['EL_DOORNAME'] . "', GetDate())";
					$stm = $DBConnection->prepare($SQL);
					$stm->execute();

					// Now, since ID isn't an Identity Key we need to Re-Query to get the ID
					$SQL = "SELECT Id
							FROM
								[VisitorEntry] 
							WHERE VisitorId = '" . $Records['ParentID'] . "' AND Type = 'CheckOut'";
					$stm = $DBConnection->prepare($SQL);
					$stm->execute();
					$Id = $stm->fetchAll();
					$Id = $Id[0]["Id"];

				/* Now update the Record */
					$SQL = "UPDATE [Visitor] 
								SET 
							CheckOutId = '" . $Id . "',
							Status = 'CheckOut',
							ModifiedDate = GETDATE()
								WHERE 
							Id = '" . $Records["ParentID"] . "'";
					$stm = $DBConnection->prepare($SQL);
					$stm->execute();
					$Message = "Visitor " . $Records["LastName"] . ", " . $Records["FirstName"] . " Checked Out!";
			
			/* CheckedOut */
				} else if($Records["Status"] == "CheckedOut"){
					$Message = "Error - Visitor has already Checked Out!";
			
			/* General Error */
				} else {
					$Message = "Oops, something went wrong with that!";
				}

			};
		} else {
			$Message = "Error cannot find ID!";
		}
}

?>


<!DOCTYPE HTML>
<html>
	<head>
		<title>EasyLobby Check In/Out</title>
		<meta charset="utf-8" />
		<meta name="viewport" content="width=device-width, initial-scale=1, user-scalable=no" />
		<link rel="stylesheet" href="assets/css/main.css" />
		<noscript><link rel="stylesheet" href="assets/css/noscript.css" /></noscript>
	</head>
	<body class="is-preload">

		<!-- Wrapper -->
			<div id="wrapper">

				<!-- Main -->
					<section id="main">
						<header>
							<h1>Check In/Out</h1>
							<p style="color:red;"><?php if(isset($Message)) echo $Message; ?></p>
						</header>
						<?php
							if(isset($PromptForOverride) && $PromptForOverride){
						?>
							<h2 style="color: orange;">Warning: Valid To/Valid From Time is not within Range! <br> Do you want override?</h2>
							<a href="index.php?CustomID=<?php echo $_REQUEST['CustomID']; ?>&Override=true"><button>Yes</button></a> | <a href="index.php"><button>No</button></a>
						<?php
							} else {
						?>
						<form action="index.php" method="POST">
							ID: <input type="text" name="CustomID"><br>
							<input type="submit" name="submit" value="CheckIn/Out">
						</form>
					<?php } ?>
					</section>

				<!-- Footer -->
					<footer id="footer">
						<ul class="copyright">
							<li><a href="https://bensommer.net" target="_blank">Built by Benjamin Sommer (@remmosnimajneb)</a> | <a href="https://html5up.net" target="_blank">Theme Design by HTML5UP</a></li>
						</ul>
					</footer>

			</div>

		<!-- Scripts -->
			<script>
				if ('addEventListener' in window) {
					window.addEventListener('load', function() { document.body.className = document.body.className.replace(/\bis-preload\b/, ''); });
					document.body.className += (navigator.userAgent.match(/(MSIE|rv:11\.0)/) ? ' is-ie' : '');
				}
			</script>

	</body>
</html>