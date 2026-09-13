<?php
	session_start();
	header('Content-Type: application/json');

	// 1. Siguraduhing naka-login ang Judge
	if (!isset($_SESSION['judge_id'])) {
		echo json_encode(['status' => 'error', 'message' => 'Unauthorized access. Please login first.']);
		exit();
	}

	$judge_id = (int)$_SESSION['judge_id'];

	// 2. Database Connection
	include 'config.php';

	// 3. Susiha ang mga gipadalang data gikan sa Form
	if ($_SERVER['REQUEST_METHOD'] === 'POST') {
		
		$criteria_name = isset($_POST['criteria_name']) ? $conn->real_escape_string($_POST['criteria_name']) : '';
		$scores_array  = isset($_POST['scores']) ? $_POST['scores'] : [];

		if (empty($criteria_name) || empty($scores_array)) {
			echo json_encode(['status' => 'error', 'message' => 'No scores or criteria detected.']);
			exit();
		}

		// 4. 🔥 KORREKSYON SA SECURITY LOCK CHECK: Susiha ang status gikan sa bag-ong 'criteria' table
		$status_stmt = $conn->prepare("SELECT status FROM criteria WHERE criteria_key = ? LIMIT 1");
		$status_stmt->bind_param("s", $criteria_name);
		$status_stmt->execute();
		$status_res = $status_stmt->get_result();
		$status_result = $status_res ? $status_res->fetch_assoc() : null;
		$status_stmt->close();

		if (!$status_result || $status_result['status'] === 'locked') {
			echo json_encode(['status' => 'error', 'message' => 'This event segment has already been LOCKED by the admin. Modification denied.']);
			exit();
		}

		// 5. Pag-save sa mga scores gamit ang Transaction Architecture para luwas
		$conn->begin_transaction();

		try {
			$sql = "INSERT INTO scores (judge_id, contestant_id, criteria_name, score) 
					VALUES (?, ?, ?, ?)
					ON DUPLICATE KEY UPDATE score = VALUES(score)";
			
			$stmt = $conn->prepare($sql);
			if ($stmt === false) {
			    throw new Exception("Prepare failed: " . $conn->error);
			}

			foreach ($scores_array as $contestant_id => $score_value) {
				$c_id = (int)$contestant_id;
				$score = (float)$score_value;

				// I-bind ang values para sa matag contestant sa listahan
				$stmt->bind_param("iisd", $judge_id, $c_id, $criteria_name, $score);
				$stmt->execute();
			}

			$stmt->close();
			$conn->commit(); // I-save sa database kung malampuson ang tanan

			echo json_encode([
				'status' => 'success',
				'message' => 'All scores for this segment have been secured and updated successfully!'
			]);

		} catch (Exception $e) {
			$conn->rollback(); // I-cancel tanan kung naay error sa tunga-tunga aron dili maguba ang data
			echo json_encode(['status' => 'error', 'message' => 'Transaction crash: ' . $e->getMessage()]);
		}

	} else {
		echo json_encode(['status' => 'error', 'message' => 'Forbidden call standard protocol error.']);
	}

	$conn->close();
?>
