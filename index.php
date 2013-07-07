<!DOCTYPE html>
<html>
<head>
	<meta charset="utf-8">
	<title>FacebookWall Test</title>
	<link rel='stylesheet' href='css/normalize.css'>
	<link rel='stylesheet' href='css/styles.css'>
	<script src="//ajax.googleapis.com/ajax/libs/jquery/1.10.1/jquery.min.js"></script>
	<script src="js/scripts.js"></script>
</head>
<body>
	<?php
		require_once('FacebookWall.php');
		$fb = new FacebookWall('mccranc', '326204564096805|TJBwx3q1wcOj62mPmN3K743K0us');
		echo $fb->render();
	?>
</body>
</html>