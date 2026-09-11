<?php
	session_start();
	$_SESSION = array(); // Clear tanang session variables

	if (ini_get("session.use_cookies")) {
		$params = session_get_cookie_params();
		setcookie(session_name(), '', time() - 42000,
			$params["path"], $params["domain"],
			$params["secure"], $params["httponly"]
		);
	}

	session_destroy(); // Gub-on ang session architectural structure
	header("Location: index.php"); // I-balik sila sa login gateway
	exit();
?>
