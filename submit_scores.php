<?php
	session_start();
	if (!isset($_SESSION['judge_id'])) {
		header("Location: index.php");
		exit();
	}
	header('Content-Type: application/json'); // Pwede nato gamiton para sa normal form submission o AJAX requests

	// 1. Susiha kung ang user naka-login ba isip usa ka opisyal nga hurado
	if (!isset($_SESSION['judge_id'])) {
		echo json_encode(['status' => 'error', 'message' => 'Unauthorized access. Please login first.']);
		exit();
	}

	// 2. Database Connection Parameters (I-adjust kini base sa imong local environment parameters)
	include 'config.php';

	if ($conn->connect_error) {
		echo json_encode(['status' => 'error', 'message' => 'Database connection failed: ' . $conn->connect_error]);
		exit();
	}

	// 3. Susiha kung gikan ba sa POST Request ang form components submission
	if ($_SERVER['REQUEST_METHOD'] === 'POST') {
		
		$judge_id      = (int)$_SESSION['judge_id'];
		$contestant_id = isset($_POST['contestant_id']) ? (int)$_POST['contestant_id'] : 0;

		// Gilimitahan ug gi-sanitize ang numerical components base sa max limits
		$production_number   = isset($_POST['production_number']) ? min(20.00, max(0.00, (float)$_POST['production_number'])) : 0.00;
		$talent_portion      = isset($_POST['talent_portion']) ? min(20.00, max(0.00, (float)$_POST['talent_portion'])) : 0.00;
		$evening_gown        = isset($_POST['evening_gown']) ? min(15.00, max(0.00, (float)$_POST['evening_gown'])) : 0.00;
		$swimwear            = isset($_POST['swimwear']) ? min(10.00, max(0.00, (float)$_POST['swimwear'])) : 0.00;
		$question_and_answer = isset($_POST['question_and_answer']) ? min(25.00, max(0.00, (float)$_POST['question_and_answer'])) : 0.00;
		$stage_presence      = isset($_POST['stage_presence']) ? min(10.00, max(0.00, (float)$_POST['stage_presence'])) : 0.00;

		if ($contestant_id <= 0) {
			echo json_encode(['status' => 'error', 'message' => 'Invalid contestant reference key identifier.']);
			exit();
		}

		// 4. I-verify kung buhi ug tinuod ba kini nga contestant ID
		$check_contestant = $conn->prepare("SELECT id FROM contestants WHERE id = ?");
		$check_contestant->bind_param("i", $contestant_id);
		$check_contestant->execute();
		$check_contestant->store_result();
		
		if ($check_contestant->num_rows === 0) {
			echo json_encode(['status' => 'error', 'message' => 'Contestant record data profile does not exist.']);
			$check_contestant->close();
			$conn->close();
			exit();
		}
		$check_contestant->close();

		/* 
		   5. UPSERT Strategy (Insert or Update if already exists)
		   Tungod kay duna tay UNIQUE KEY unique_judge_contestant (judge_id, contestant_id),
		   kini nga query mo-insert ug bag-ong record o mo-update sa score kung na-save na kini sa una.
		*/
		$sql = "INSERT INTO scores (
					judge_id, contestant_id, production_number, talent_portion, evening_gown, swimwear, question_and_answer, stage_presence
				) VALUES (?, ?, ?, ?, ?, ?, ?, ?)
				ON DUPLICATE KEY UPDATE 
					production_number = VALUES(production_number),
					talent_portion = VALUES(talent_portion),
					evening_gown = VALUES(evening_gown),
					swimwear = VALUES(swimwear),
					question_and_answer = VALUES(question_and_answer),
					stage_presence = VALUES(stage_presence)";

		$stmt = $conn->prepare($sql);
		
		if ($stmt) {
			$stmt->bind_param(
				"iidddddd", 
				$judge_id, 
				$contestant_id, 
				$production_number, 
				$talent_portion, 
				$evening_gown, 
				$swimwear, 
				$question_and_answer, 
				$stage_presence
			);

			if ($stmt->execute()) {
				// Success response pattern structure
				echo json_encode([
					'status' => 'success', 
					'message' => 'Official scores have been secured and successfully recorded!'
				]);
				
				/* Pwede nimo i-uncomment kung normal Form Post Redirect ang imong gusto imbis AJAX feedback:
				header("Location: judge_scoring.php?status=success");
				exit(); 
				*/
			} else {
				echo json_encode(['status' => 'error', 'message' => 'Transaction failure error: ' . $stmt->error]);
			}
			$stmt->close();
		} else {
			echo json_encode(['status' => 'error', 'message' => 'Statement optimization preparation error: ' . $conn->error]);
		}
	} else {
		echo json_encode(['status' => 'error', 'message' => 'Forbidden method call action protocol error.']);
	}

	$conn->close();
?>
