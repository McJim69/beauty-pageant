<?php
	session_start();
	header('Content-Type: application/json');

	// Database Connection
	include 'config.php';
	if ($conn->connect_error) {
		echo json_encode(['status' => 'error', 'message' => 'Connection failed']);
		exit();
	}

	if ($_SERVER['REQUEST_METHOD'] === 'POST') {
		$criteria_name = isset($_POST['criteria_name']) ? $conn->real_escape_string($_POST['criteria_name']) : '';
		
		// 🔥 KORREKSYON: Gi-ayo ang syntax error sa $_POST parsing dinhi
		$current_status = isset($_POST['current_status']) ? $_POST['current_status'] : '';

		// Pagbalhin sa status (Kung open, himoong locked. Kung locked, himoong open)
		$new_status = ($current_status === 'open') ? 'locked' : 'open';

		$stmt = $conn->prepare("UPDATE criteria_status SET status = ? WHERE criteria_name = ?");
		$stmt->bind_param("ss", $new_status, $criteria_name);

		if ($stmt->execute()) {
			echo json_encode([
				'status' => 'success',
				'new_status' => $new_status,
				'message' => 'Criteria status successfully updated to ' . strtoupper($new_status)
			]);
		} else {
			echo json_encode(['status' => 'error', 'message' => 'Database update failed.']);
		}
		$stmt->close();
	}
	$conn->close();
?>
