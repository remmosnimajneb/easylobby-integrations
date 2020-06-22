<?php
/********************************
* Project: JotForm to EasyLobby Integrator
* Create Visitor Entries in HID's EasyLobby eAdvance SVM from JotForm.
* Code Version: 1.0
* Author: Benjamin Sommer
* GitHub: https://github.com/remmosnimajneb
* Theme Design by HTML5UP (HTML5UP.net)
***************************************************************************************/

/* Add/Edit Forms */

// 0. Set Vars and other Imp Info
$SecretHash = "lnL2uSu1a0yl9438tDqRPdRG7P2l1fOjifbVjJnM8APRqe0wj65EnQo4aAmGDeoDZgT2nhrpS9BInHzb8XRWhFPAUSqKqYvDDcLn";

// Start Session
session_start();

/* Get Config File */
	$Config = json_decode(file_get_contents("../Config.json"), true);

	/* Get Forms File */
	$Forms = json_decode(file_get_contents("../Forms.json"), true);

error_reporting(0);

// Now check login
if(isset($_POST['Username']) && isset($_POST['Password'])){
	if($_POST['Username'] == $Config['ADMIN_PANEL_USERNAME'] && $_POST['Password'] == $Config['ADMIN_PANEL_PASSWORD']){
		$_SESSION['IsLoggedIn'] = true;
		$_SESSION['LoggedInUserAuth'] = $SecretHash;
	} else {
		$_SESSION['IsLoggedIn'] == false;
		$_SESSION['LoggedInUserAuth'] == null;
	}
}

	/* Get SQL Login Info */
	DEFINE('DB_NAME', $Config['SQLCONNECTION']['DB_NAME']);
	DEFINE('DB_USER', $Config['SQLCONNECTION']['DB_USERNAME']);
	DEFINE('DB_PASS', $Config['SQLCONNECTION']['DB_PASSWORD']);
	$DBConnection = new PDO( "sqlsrv:server=(local) ; Database = " . DB_NAME, DB_USER, DB_PASS);  
	$DBConnection->setAttribute( PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION );  
	$DBConnection->setAttribute( PDO::SQLSRV_ATTR_QUERY_TIMEOUT, 1 );

/* Add + Edit */
if(isset($_POST['Action'])){

	/* Edit */
	if($_POST['Action'] == "Edit"){

		$Forms[$_POST['Form']]["Name"] = $_POST['Name'];
		$Forms[$_POST['Form']]["Title"] = $_POST['Title'];
		$Forms[$_POST['Form']]["Company"] = $_POST['Company'];
		$Forms[$_POST['Form']]["Email"] = $_POST['Email'];
		$Forms[$_POST['Form']]["CellPhone"] = $_POST['CellPhone'];
		$Forms[$_POST['Form']]["ValidFrom"] = $_POST['ValidFrom'];
		$Forms[$_POST['Form']]["ValidTo"] = $_POST['ValidTo'];
		$Forms[$_POST['Form']]["Employee"] = $_POST['Employee'];
		$Forms[$_POST['Form']]["Category"] = $_POST['Category'];
		$Forms[$_POST['Form']]["Reason"] = $_POST['Reason'];
		$Forms[$_POST['Form']]["RegisteredBy"] = $_POST['RegisteredBy'];
		$Forms[$_POST['Form']]["SendEmail"] = $_POST['SendEmail'];
		$Forms[$_POST['Form']]["EmailBody"] = $_POST['EmailBody'];

		file_put_contents("../Forms.json", json_encode($Forms));

		$Message = "Form Saved!";

	}
}

?>
<!DOCTYPE HTML>
<html>
	<head>
		<title><?php echo $Config['ORG_NAME']; ?></title>
		<meta charset="utf-8" />
		<meta name="viewport" content="width=device-width, initial-scale=1, user-scalable=no" />
		<link rel="stylesheet" href="assets/css/main.css?ver=8">
	</head>
	<body class="is-preload">

		<!-- Wrapper -->
			<div id="wrapper">

				<!-- Header -->
					<header id="header">

						<!-- Logo -->
							<div class="logo">
								<a href="#"><strong><?php echo $Config['ORG_NAME']; ?></strong></span></a>
							</div>
					</header>
					<section id="one" class="main alt">
						
						<div class="inner alt">
							<div class="content">
									<?php 
										// If User didn't login, we force a login, otherwise show the add form
										if($_SESSION['IsLoggedIn'] != true && $_SESSION['LoggedInUserAuth'] != $SecretHash){
									?>
										<h2>Please Login!</h2>
										<form action="index.php" method="POST">
											Username: <input type="text" name="Username" required="required"><br>
											Password: <input type="password" name="Password" required="required"><br>
											<input type="submit" name="submit" value="Login">
										</form>
									<?php
										} else if(isset($_GET['Edit'])){
									?>
										<form action="index.php" method="POST">
											<input type="hidden" name="Action" value="Edit">
												<?php 
													if($_GET['Edit'] == ""){
														echo 'Form Slug: <input type="text" name="Form" value=""><br>';
													} else {
														echo '<input type="hidden" name="Form" value=' . $_GET["Edit"] . '>';
													}
												?>
											Name Field: <input type="text" name="Name" value="<?php echo $Forms[$_GET['Edit']]["Name"]; ?>"><br>
											Title Field: <input type="text" name="Title" value="<?php echo $Forms[$_GET['Edit']]["Title"]; ?>"><br>
											Company Field: <input type="text" name="Company" value="<?php echo $Forms[$_GET['Edit']]["Company"]; ?>"><br>
											Email Field: <input type="text" name="Email" value="<?php echo $Forms[$_GET['Edit']]["Email"]; ?>"><br>
											Cell Phone Field: <input type="text" name="CellPhone" value="<?php echo $Forms[$_GET['Edit']]["CellPhone"]; ?>"><br>
											Valid From Field: <input type="text" name="ValidFrom" value="<?php echo $Forms[$_GET['Edit']]["ValidFrom"]; ?>"><br>
											Valid To (In Minutes): <input type="text" name="ValidTo" value="<?php echo $Forms[$_GET['Edit']]["ValidTo"]; ?>"><br>

											Employee: 
												<select name="Employee">
													<?php 
														$SQL = "SELECT E.[Id], RCI.[CustomID], E.[FirstName], E.[LastName] FROM [RecordCustomId] AS RCI INNER JOIN [Employee] AS E ON E.[Id] = RCI.[ParentId]";
														$stm = $DBConnection->prepare($SQL);
														$stm->execute();
														$Employees = $stm->fetchAll();
														foreach($Employees as $Employee){
															echo "<option value='" . $Employee['CustomID'] . "'";
																if($Forms[$_GET['Edit']]["Employee"] ==  $Employee['CustomID']) echo " selected='selected'"; 
															echo ">" . $Employee['LastName'] . ", " . $Employee['FirstName'] . "</option>";
														}
													?>
												</select><br>

											Category: 
												<select name="Category">
													<?php 
														$SQL = "SELECT C.[Name] FROM [Category] AS C";
														$stm = $DBConnection->prepare($SQL);
														$stm->execute();
														$Categories = $stm->fetchAll();
														foreach($Categories as $Category){
															echo "<option value='" . $Category['Name'] . "'";
																if($Forms[$_GET['Edit']]["Category"] ==  $Category['Name']) echo " selected='selected'"; 
															echo ">" . $Category['Name'] . "</option>";
														}
													?>
												</select><br>

											Reason: 
												<select name="Reason">
													<?php 
														$SQL = "SELECT R.[ReasonForVisit] FROM [Reason] AS R";
														$stm = $DBConnection->prepare($SQL);
														$stm->execute();
														$Reasons = $stm->fetchAll();
														foreach($Reasons as $Reason){
															echo "<option value='" . $Reason['ReasonForVisit'] . "'";
																if($Forms[$_GET['Edit']]["Reason"] ==  $Reason['ReasonForVisit']) echo " selected='selected'"; 
															echo ">" . $Reason['ReasonForVisit'] . "</option>";
														}
													?>
												</select><br>

											Site: 
												<select name="Site">
													<?php 
														$SQL = "SELECT S.[SiteName] FROM [Site] AS S";
														$stm = $DBConnection->prepare($SQL);
														$stm->execute();
														$Sites = $stm->fetchAll();
														foreach($Sites as $Site){
															echo "<option value='" . $Site['SiteName'] . "'";
																if($Forms[$_GET['Edit']]["Site"] ==  $Site['SiteName']) echo " selected='selected'"; 
															echo ">" . $Site['SiteName'] . "</option>";
														}
													?>
												</select><br>

											Registered By: 
												<select name="RegisteredBy">
													<?php 
														$SQL = "SELECT U.[UserName] FROM [Users] AS U";
														$stm = $DBConnection->prepare($SQL);
														$stm->execute();
														$Users = $stm->fetchAll();
														foreach($Users as $User){
															echo "<option value='" . $User['UserName'] . "'";
																if($Forms[$_GET['Edit']]["RegisteredBy"] ==  $User['UserName']) echo " selected='selected'"; 
															echo ">" . $User['UserName'] . "</option>";
														}
													?>
												</select><br>

											Send Email: 
												<select name="SendEmail">
													<option value="true" <?php if($Forms[$_GET['Edit']]["SendEmail"] == true) echo " selected='selected'"; ?>>Yes</option>
													<option value="false" <?php if($Forms[$_GET['Edit']]["SendEmail"] == false) echo " selected='selected'"; ?>>No</option>
												</select><br>

											Email Body:
												<textarea name="EmailBody"><?php echo $Forms[$_GET['Edit']]["EmailBody"]; ?></textarea>
												<i>Available Markdown tags: {FIRST_NAME}, {LAST_NAME}, {TITLE}, {COMPANY}, {EMAIL}, {CELL_PHONE}, {VISIT_DATE}, {VISIT_TIME}, {CUSTOM_ID_NUMBER}, {QR_CODE_IMAGE}</i><br><br>

											<input type="Submit" name="Submit" value="Save">
										</form>
									<?php
									/* Forms */
										} else {
											?>
											<h1>Forms</h1>
											<p style="color:orange;"><?php if(isset($Message)) echo $Message; ?></p>
												<br>
											<a href="?Edit=" class="button">Add New Form</a>
												<br>
											<table>
												<thead>
													<tr>
														<td>Form Name</td>
														<td>Edit</td>
													</tr>
												</thead>
												<tbody>
											<?php
												foreach ($Forms as $Key => $Form) {
													echo "<tr><td>" . strtoupper($Key) . "</td>";
													echo "<td><a href='?Edit=" . $Key . "'>" . strtoupper($Key) . "</a></td></tr>";
												}
											?>
												</tbody>
											</table>
												<?php
										}
									?>
							</div>
					</section>
					
				<footer id="footer">
						
						<div class="copyright">
							<p><a href="https://bensommer.net" target="_blank">Built by Benjamin Sommer (@remmosnimajneb)</a> | <a href="https://html5up.net" target="_blank">Theme Design by HTML5UP</a></p>
						</div>
					</footer>

			</div>

		<!-- Scripts -->
			<script src="assets/js/jquery.min.js"></script>
			<script src="assets/js/jquery.dropotron.min.js"></script>
			<script src="assets/js/jquery.selectorr.min.js"></script>
			<script src="assets/js/jquery.scrollex.min.js"></script>
			<script src="assets/js/jquery.scrolly.min.js"></script>
			<script src="assets/js/browser.min.js"></script>
			<script src="assets/js/breakpoints.min.js"></script>
			<script src="assets/js/util.js"></script>
			<script src="assets/js/main.js"></script>

	</body>
</html>