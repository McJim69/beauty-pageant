<?php
	// 🔥 KORREKSYON: Siguraduha nga walay bisan unsa nga HTML space sa ibabaw niini nga tag
	session_start();

	// I-set daan ang header nga JSON para dili mag-error ang jQuery sa pikas bahin
	header('Content-Type: application/json');

	// SECURITY CHECK: Susiha kung tinuod ba nga admin ang naka-session
	if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
		echo json_encode(['status' => 'error', 'message' => 'Unauthorized access. Access denied.']);
		exit();
	}

	include 'config.php';

	if ($conn->connect_error) {
		echo json_encode(['status' => 'error', 'message' => 'Database connection failed: ' . $conn->connect_error]);
		exit();
	}

	// Sugdan ang transactional architecture para hapsay ang pagpapas
	$conn->begin_transaction();

	try {
		// 1. Papason ang tanang entries sa scores table
		$conn->query("SET FOREIGN_KEY_CHECKS = 0;");
		$conn->query("TRUNCATE TABLE scores;");
		$conn->query("SET FOREIGN_KEY_CHECKS = 1;");

		// 2. I-reset ang tanang criteria status ngadto sa 'locked'
		$conn->query("UPDATE criteria SET status = 'locked';");

		// I-commit ang transaction kung walay nahitabong error
		$conn->commit();

		echo json_encode([
			'status' => 'success', 
			'message' => 'The database has been cleanly wiped! All scores are reset to 0.00 and all segments are now OPEN!'
		]);

	} catch (Exception $e) {
		// I-cancel ang operation kung naay crash sa database engine
		$conn->rollback();
		echo json_encode(['status' => 'error', 'message' => 'Reset crash failure: ' . $e->getMessage()]);
	}

	$conn->close();
	exit();
?>
