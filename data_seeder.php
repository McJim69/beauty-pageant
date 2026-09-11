<?php
	header('Content-Type: text/html; charset=utf-8');

	include 'config.php';

	if ($conn->connect_error) {
		die("<div style='color:red; font-family:sans-serif;'>❌ Database connection failed: " . $conn->connect_error . "</div>");
	}

	echo "<div style='font-family:sans-serif; background:#111; color:#fff; padding:20px; border-radius:8px;'>";
	echo "<h2 style='color:#ffc107;'>🚀 Pageant System Data Seeder</h2>";

	// 2. Clear existing data safely to avoid duplicates during testing
	$conn->query("SET FOREIGN_KEY_CHECKS = 0;");
	$conn->query("TRUNCATE TABLE scores;");
	$conn->query("TRUNCATE TABLE judges;");
	$conn->query("TRUNCATE TABLE contestants;");
	$conn->query("SET FOREIGN_KEY_CHECKS = 1;");
	echo "• Old mock data tables cleared successfully.<br>";

	// 3. Seed Contestants (Mga Kandidata)
	$contestants = [
		[1, 'Maria Clara Santos', 'Barangay San Jose'],
		[2, 'Ana Patricia Reyes', 'Barangay Poblacion'],
		[3, 'Jasmine Dela Cruz', 'Barangay Santa Maria'],
		[4, 'Crystal Mae Flores', 'Barangay San Sebastian'],
		[5, 'Sofia Nicole Aquino', 'Barangay Santo Niño']
	];

	$stmt_contestant = $conn->prepare("INSERT INTO contestants (candidate_number, fullname, represented_location) VALUES (?, ?, ?)");
	foreach ($contestants as $c) {
		$stmt_contestant->bind_param("iss", $c[0], $c[1], $c[2]);
		$stmt_contestant->execute();
	}
	$stmt_contestant->close();
	echo "• <strong>5 Contestants</strong> added successfully.<br>";

	// 4. Seed Judges (Mga Hurado)
	// Ang mga password kay gi-hash gamit ang `password_hash` para luwas. Ang password sa tanan kay: judge123
	$default_password = password_hash('judge123', PASSWORD_DEFAULT);
	$judges = [
		[1, 'Judge Chairman - Ramos', 'judge1', $default_password],
		[2, 'Judge Member - Fernandez', 'judge2', $default_password],
		[3, 'Judge Member - Alcantara', 'judge3', $default_password]
	];

	$stmt_judge = $conn->prepare("INSERT INTO judges (judge_number, fullname, username, password) VALUES (?, ?, ?, ?)");
	foreach ($judges as $j) {
		$stmt_judge->bind_param("isss", $j[0], $j[1], $j[2], $j[3]);
		$stmt_judge->execute();
	}
	$stmt_judge->close();
	echo "• <strong>3 Judges</strong> added successfully. <small>(Default Password: <em>judge123</em>)</small><br>";

	// 5. Seed Mock Scores (Automatic Random Scores para sa testing)
	// Kini mag-generate og random scores base sa tagsa-tagsa ka maximum weight limitations sa criteria.
	$judge_ids = [];
	$res_judges = $conn->query("SELECT id FROM judges");
	while ($row = $res_judges->fetch_assoc()) {
		$judge_ids[] = $row['id'];
	}

	$contestant_ids = [];
	$res_contestants = $conn->query("SELECT id FROM contestants");
	while ($row = $res_contestants->fetch_assoc()) {
		$contestant_ids[] = $row['id'];
	}

	$stmt_score = $conn->prepare("INSERT INTO scores (judge_id, contestant_id, production_number, talent_portion, evening_gown, swimwear, question_and_answer, stage_presence) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");

	foreach ($judge_ids as $jid) {
		foreach ($contestant_ids as $cid) {
			// Mag-generate og random numbers nga naay decimals (.00, .25, .50, .75)
			$prod    = rand(15, 20) + (rand(0, 3) * 0.25); // Max 20
			$talent  = rand(14, 20) + (rand(0, 3) * 0.25); // Max 20
			$gown    = rand(11, 15) + (rand(0, 3) * 0.25); // Max 15
			$swim    = rand(7, 10)   + (rand(0, 3) * 0.25); // Max 10
			$qa      = rand(18, 25) + (rand(0, 3) * 0.25); // Max 25
			$press   = rand(7, 10)   + (rand(0, 3) * 0.25); // Max 10

			$stmt_score->bind_param("iidddddd", $jid, $cid, $prod, $talent, $gown, $swim, $qa, $press);
			$stmt_score->execute();
		}
	}
	$stmt_score->close();
	echo "• <strong>Randomized Mock Scores</strong> computed and successfully linked into the system leaderboard matrix.<br><br>";

	echo "<span style='color:#28a745; font-weight:bold;'>🎉 Database seeding completed! Ready for system testing.</span><br><br>";
	echo "<a href='admin_dashboard.php' style='background:#ffc107; color:#000; padding:10px 15px; border-radius:5px; text-decoration:none; font-weight:bold;'>Go to Admin Dashboard ➔</a>";
	echo "</div>";

	$conn->close();
?>
