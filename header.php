<?php 
	require_once __DIR__ . '/version.php';
	
	// Dynamic Title Settings based on active page
	$current_file = basename($_SERVER['SCRIPT_NAME']);
	$title = "Beauty Pageant System";
	
	if ($current_file === 'index.php') {
		$title = "Beauty Pageant | Login";
		
	} else if ($current_file === 'admin_management.php') {
		$title = "BPS | Management Panel";
		
	} else if ($current_file === 'admin_controller.php') {
		$title = "BPS | Event Controller";

	} else if ($current_file === 'admin_dashboard.php') {
		$title = "BPS | Tabulation Dashboard";

	} else if ($current_file === 'statistics.php') {
		$title = "BPS | TOP 5 Statistics";

	} else if ($current_file === 'judge_scoring.php') {
		$title = "BPS | Judge Board Scoring";
	}

?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title><?php echo htmlspecialchars($title); ?></title>
  <link rel="shortcut icon" href="images/logo.png" type="image/x-icon">

  <link href="assets/sweetalert2/dist/sweetalert2.all.min.css" rel="stylesheet">
  <link href="assets/bootstrap/css/bootstrap.min.css" rel="stylesheet">
  <link href="assets/fontawesome/css/all.min.css" rel="stylesheet">
  <link href="assets/customcss/style.css" rel="stylesheet">

  <script src="assets/sweetalert2/dist/sweetalert2.all.min.js"></script>
  <script src="assets/fontawesome/js/all.min.js"></script>
  <script src="assets/chartjs/chart.js"></script>
  <script src="assets/jquery/jquery.js"></script>
</head>

<body style="min-height:100vh">
