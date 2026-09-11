<?php
	session_start();
	// Database Connection
	include 'config.php';

	if ($conn->connect_error) {
		die("Database connection failed: " . $conn->connect_error);
	}

	// 1. DATA ENTRY FOR LEADERBOARD GRAPH & WINNERS CARDS (Gisiguro ang Top 5 para sa graph/cards)
	$sql = "SELECT 
				c.candidate_number,
				c.fullname,
				c.represented_location,
				(IFNULL(AVG(CASE WHEN s.criteria_name = 'production_number' THEN s.score END), 0) +
				 IFNULL(AVG(CASE WHEN s.criteria_name = 'talent_portion' THEN s.score END), 0) +
				 IFNULL(AVG(CASE WHEN s.criteria_name = 'evening_gown' THEN s.score END), 0) +
				 IFNULL(AVG(CASE WHEN s.criteria_name = 'swimwear' THEN s.score END), 0) +
				 IFNULL(AVG(CASE WHEN s.criteria_name = 'question_and_answer' THEN s.score END), 0) +
				 IFNULL(AVG(CASE WHEN s.criteria_name = 'stage_presence' THEN s.score END), 0)) as final_average_score
			FROM contestants c
			LEFT JOIN scores s ON c.id = s.contestant_id
			GROUP BY c.id
			ORDER BY final_average_score DESC, c.candidate_number ASC 
			LIMIT 5";

	$result = $conn->query($sql);

	$candidate_names = [];
	$candidate_scores = [];
	$winners_pool = [];

	if ($result && $result->num_rows > 0) {
		while ($row = $result->fetch_assoc()) {
			$candidate_names[]  = "No. " . $row['candidate_number'] . " - " . $row['fullname'];
			$candidate_scores[] = round($row['final_average_score'], 2);
			// Gi-save para sa Podium Winners Cards Component sa ubos
			$winners_pool[] = [
				'number' => $row['candidate_number'],
				'name'   => $row['fullname'],
				'score'  => round($row['final_average_score'], 2)
			];
		}
	}

	$criteria_weights = [20, 20, 15, 10, 25, 10];
	$criteria_labels  = ['Production Number', 'Talent Portion', 'Evening Gown', 'Swimwear', 'Question & Answer', 'Stage Presence'];

	// Define Titles setup mapping matrix
	$titles_map = [
		0 => ['title' => 'Beauty Queen',  'bg' => 'linear-gradient(135deg, #d4af37, #aa7c11)', 'icon' => 'fa-crown', 'text' => '#000'],
		1 => ['title' => '1st Runner-up', 'bg' => 'linear-gradient(135deg, #b0b7bd, #7f8c8d)', 'icon' => 'fa-medal', 'text' => '#000'],
		2 => ['title' => '2nd Runner-up', 'bg' => 'linear-gradient(135deg, #cd7f32, #965a38)', 'icon' => 'fa-award', 'text' => '#fff'],
		3 => ['title' => '3rd Runner-up', 'bg' => 'linear-gradient(135deg, #b0b7bd, #1a202c)', 'icon' => 'fa-star',  'text' => '#fff']
	];
	include 'header.php'; 
	include 'navbar.php'; 
?>
   
<div class="main-content container px-4 py-2">
    <!-- Header Block -->
    <div class="d-flex justify-content-between align-items-center flex-wrap mb-4">
        <div>
            <h2 class="text-white fw-bold tracking-wide"><i class="fa fa-chart-pie text-warning me-2"></i> OVER-ALL STATISTICS</h2>
            <p style="color:#bbb" class="mb-0 d-flex align-items-center gap-2">
                <span class="pulse-live"></span> Visualizing Standing Leaderboard Matrix & Category Weights
            </p>
        </div>
        <button onclick="location.reload();" class="btn btn-outline-warning fw-bold px-4 mt-2 mt-md-0">
            <i class="fa fa-sync-alt me-1"></i> Refresh Graph Data
        </button>
    </div>

    <!-- Charts Grid Row Layout -->
    <div class="row g-4 mb-4">
        <!-- 1. LEFT COLUMN: Top 5 Leading Candidates Bar Chart -->
        <div class="col-xl-7 col-12">
            <div class="analytics-card">
                <h4 class="text-white mb-3 fw-semibold"><i class="fa fa-trophy text-warning me-2"></i> Current Top 5 Leaderboard Standings</h4>
                <div style="position: relative; height: 350px;">
                    <canvas id="leaderboardBarChart"></canvas>
                </div>
            </div>
        </div>

        <!-- 2. RIGHT COLUMN: Criteria Distribution Doughnut Chart -->
        <div class="col-xl-5 col-12">
            <div class="analytics-card">
                <h4 class="text-white mb-3 fw-semibold"><i class="fa fa-percentage text-info me-2"></i> Criteria Structural Weight Allocation</h4>
                <div style="position: relative; height: 350px;" class="d-flex justify-content-center">
                    <canvas id="criteriaPieChart"></canvas>
                </div>
            </div>
        </div>
    </div>

    <!-- WINNERS PODIUM ROW COMPONENT -->
    <div class="analytics-card mb-5">
        <h4 class="text-white mb-3 fw-semibold"><i class="fa fa-crown text-warning me-2"></i> Live Projected Royal Court Leaders</h4>
        <div class="row g-3 row-cols-1 row-cols-sm-2 row-cols-xl-4">
            <?php 
            for ($i = 0; $i < 4; $i++): 
                if (isset($winners_pool[$i])): 
                    $data = $winners_pool[$i];
                    $meta = $titles_map[$i];
            ?>
			<div class="col">
				<div class="winner-podium-card" style="background: <?php echo $meta['bg']; ?>; color: <?php echo $meta['text']; ?>;">
					<div class="title-banner text-uppercase"><i class="fa <?php echo $meta['icon']; ?> me-1"></i> <?php echo $meta['title']; ?></div>
					<div class="cand-num">Contestant #<?php echo $data['number']; ?></div>
					<div class="cand-name"><?php echo htmlspecialchars($data['name']); ?></div>
					<div class="cand-score">Score: <?php echo number_format($data['score'], 2); ?> / 100.00</div>
					<i class="fa <?php echo $meta['icon']; ?> floating-icon"></i>
				</div>
			</div>
            <?php else: ?>
			<div class="col">
				<div class="winner-podium-card" style="background: #161925; color: #555;">
					<div class="title-banner text-uppercase text-muted">Rank <?php echo ($i + 1); ?> Leader</div>
					<div class="cand-num text-muted">#--</div>
					<div class="cand-name text-muted">Waiting for Scores...</div>
					<div class="cand-score text-muted">Score: 0.00</div>
				</div>
			</div>
            <?php 
                endif; 
            endfor; 
            ?>
        </div>
    </div>
</div>

<script>
const candidateLabels  = <?php echo json_encode($candidate_names); ?>;
const candidateData    = <?php echo json_encode($candidate_scores); ?>;
const criteriaLabels   = <?php echo json_encode($criteria_labels); ?>;
const criteriaWeights  = <?php echo json_encode($criteria_weights); ?>;

// INITIALIZE BAR CHART
const ctxBar = document.getElementById('leaderboardBarChart').getContext('2d');
new Chart(ctxBar, {
    type: 'bar',
    data: {
        labels: candidateLabels,
        datasets: [{
            label: 'Final Cumulative Average Score',
            data: candidateData,
            backgroundColor: ['rgba(255, 215, 0, 0.75)', 'rgba(0, 255, 204, 0.65)', 'rgba(157, 78, 221, 0.65)', 'rgba(58, 134, 255, 0.65)', 'rgba(255, 0, 110, 0.65)'],
            borderColor: ['#ffd700', '#00ffcc', '#9d4edd', '#3a86ff', '#ff006e'],
            borderWidth: 2,
            borderRadius: 8
        }]
    },
    options: {
        responsive: true,
        maintainAspectRatio: false,
		plugins: { legend: { display: false } },
		scales: {
			y: { 
				beginAtZero: true, 
				max: 100, 
				grid: { color: 'rgba(255, 255, 255, 0.07)' }, 
				ticks: { color: '#c5c6c7' } 
			},
			x: { 
				ticks: { 
					color: '#ffffff', 
					font: { size: 12, weight: '600' } 
				} 
			}
			}
	}
});

// INITIALIZE DOUGHNUT CHART
const ctxPie = document.getElementById('criteriaPieChart').getContext('2d');
new Chart(ctxPie, {
	type: 'doughnut',
	data: {
		labels: criteriaLabels,
		datasets: [{
			data: criteriaWeights,
			backgroundColor: ['#ffbe0b', '#fb5607', '#ff006e', '#8338ec', '#3a86ff', '#00f5d4'],
			borderWidth: 2,
			borderColor: '#1f2833'
		}]
	},
	options: {
		responsive: true,
		maintainAspectRatio: false,
		plugins: { 
			legend: { 
				position: 'bottom', 
				labels: { color: '#e4e6eb', font: { size: 11 } } 
			} 
		},
		cutout: '65%'
	}
});

// Auto Refresh Component Loop every 10 seconds for real-time smoothness
setInterval(() => { location.reload(); }, 10000);

</script>

<?php include 'footer.php';?>