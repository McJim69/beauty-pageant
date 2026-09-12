<?php
	session_start();
	header('Content-Type: application/json');

	// 1. Session Protection Gate (Siguraduha nga Admin ra ang maka-toggle)
	if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
		echo json_encode(['status' => 'error', 'message' => 'Unauthorized access.']);
		exit();
	}

	// 2. Database Connection
	include 'config.php';

	if ($conn->connect_error) {
		echo json_encode(['status' => 'error', 'message' => 'Database connection failed']);
		exit();
	}

	// 3. Process the Toggle Status Request
	if ($_SERVER['REQUEST_METHOD'] === 'POST') {
		$criteria_name  = isset($_POST['criteria_name']) ? $conn->real_escape_string($_POST['criteria_name']) : '';
		$current_status = isset($_POST['current_status']) ? $_POST['current_status'] : '';

		// Pagbalhin sa status (Kung open, himoong locked. Kung locked, himoong open)
		$new_status = ($current_status === 'open') ? 'locked' : 'open';

		// 🔥 KORREKSYON: Gi-update ang table name gikan sa 'criteria_status' ngadto sa bag-ong 'criteria' table
		$stmt = $conn->prepare("UPDATE criteria SET status = ? WHERE criteria_key = ?");
		$stmt->bind_param("ss", $new_status, $criteria_name);

		if ($stmt->execute()) {
			echo json_encode([
				'status' => 'success',
				'new_status' => $new_status,
				'message' => 'Criteria status successfully updated to ' . strtoupper($new_status)
			]);
		} else {
			echo json_encode(['status' => 'error', 'message' => 'Database execution failed: ' . $stmt->error]);
		}
		$stmt->close();
	} else {
		echo json_encode(['status' => 'error', 'message' => 'Invalid request method.']);
	}

	$conn->close();
	exit();
?>
