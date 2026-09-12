<?php
	session_start();
	if (!isset($_SESSION['judge_id'])) {
		header("Location: index.php");
		exit();
	}

	$judge_id = $_SESSION['judge_id'];

	// Dynamic Fetching Registry gikan sa Database Matrix
	$criteria_list = [];
	$criteria_query = $conn->query("SELECT criteria_key, label, weight, status FROM criteria ORDER BY sort_order ASC");
	while ($c_row = $criteria_query->fetch_assoc()) {
		$criteria_list[$c_row['criteria_key']] = [
			'label'  => $c_row['label'],
			'max'    => (float)$c_row['weight'], // ang 'weight' mao ang magsilbing 'max' score sa slider
			'weight' => number_format($c_row['weight'], 0) . '%',
			'status' => $c_row['status']
		];
	}

	include 'config.php';

	// 2. Kuhaon ang active criteria gikan sa URL dropdown
	$active_criteria = isset($_GET['criteria']) ? $_GET['criteria'] : 'production_number';
	$max_score = $criteria_list[$active_criteria]['max'];
	$criteria_label = $criteria_list[$active_criteria]['label'];

	// 3. Susiha kung 'open' ba o 'locked' kini nga criteria gikan sa database
	$status_query = $conn->prepare("SELECT status FROM criteria_status WHERE criteria_name = ?");
	$status_query->bind_param("s", $active_criteria);
	$status_query->execute();
	$status_result = $status_query->get_result()->fetch_assoc();
	$event_status = $status_result['status'] ?? 'open'; // default output
	$status_query->close();

	// 4. Kuhaon ang mga kandidata ug i-LEFT JOIN ang scores sa maong Judge para niini nga criteria
	$query = "SELECT c.*, IFNULL(s.score, 0.00) as current_score 
			  FROM contestants c 
			  LEFT JOIN scores s ON c.id = s.contestant_id AND s.judge_id = ? AND s.criteria_name = ?
			  ORDER BY c.candidate_number ASC";

	$stmt = $conn->prepare($query);
	$stmt->bind_param("is", $judge_id, $active_criteria);
	$stmt->execute();
	$contestants = $stmt->get_result();
	$stmt->close();

	include 'header.php';
	include 'navbar.php'; 
?>

<div class="main-content container">
    <!-- Criteria Selector Dropdown -->
    <div class="card card-custom p-3 mb-4 shadow">
        <label class="form-label fw-bold text-warning mb-2"><i class="fa fa-layer-group me-2"></i> SELECT CURRENT EVENT / CRITERIA:</label>
        <select class="form-select form-select-lg bg-dark text-white border-secondary" id="criteriaSelector" onchange="changeCriteria(this.value)">
            <?php foreach ($criteria_list as $key => $details): ?>
                <option value="<?php echo $key; ?>" <?php echo ($active_criteria == $key) ? 'selected' : ''; ?>>
                    <?php echo $details['label']; ?> (Max: <?php echo $details['max']; ?>%)
                </option>
            <?php endforeach; ?>
        </select>
    </div>

    <!-- Active Segment Banner & Lock Status -->
    <div class="alert <?php echo ($event_status === 'locked') ? 'alert-danger bg-dark border-danger' : 'alert-warning bg-dark border-warning'; ?> text-white d-flex justify-content-between align-items-center shadow-sm">
        <h5 class="mb-0 fw-bold text-uppercase">
            <i class="fa <?php echo ($event_status === 'locked') ? 'fa-lock text-danger' : 'fa-unlock-alt text-warning'; ?> me-2"></i> 
            Scoring Segment: <?php echo $criteria_label; ?>
        </h5>
        <div>
            <?php if($event_status === 'locked'): ?>
                <span class="badge bg-danger fs-6 text-uppercase px-3"><i class="fa fa-ban me-1"></i> Locked by Admin</span>
            <?php else: ?>
                <span class="badge bg-warning text-dark fs-6">Max Points: <?php echo $max_score; ?></span>
            <?php endif; ?>
        </div>
    </div>

    <!-- Scoring Form for all candidates -->
    <form id="bulkScoringForm" class="<?php echo ($event_status === 'locked') ? 'locked-overlay' : ''; ?>">
        <input type="hidden" name="criteria_name" value="<?php echo $active_criteria; ?>">
        
        <div class="candidate-list">
            <?php if ($contestants && $contestants->num_rows > 0): ?>
                <?php while($row = $contestants->fetch_assoc()): ?>
                    <div class="candidate-row shadow-sm d-flex align-items-center justify-content-between flex-wrap gap-3">
                        <div class="d-flex align-items-center gap-3">
                            <span class="num-badge fs-5">#<?php echo $row['candidate_number']; ?></span>
                            <div>
                                <h6 class="mb-0 fw-bold"><?php echo htmlspecialchars($row['fullname']); ?></h6>
                                <small style="color:#bbb"><?php echo htmlspecialchars($row['represented_location']); ?></small>
                            </div>
                        </div>
                        
                        <!-- Slider Controls populated with previously saved scores -->
                        <div class="d-flex align-items-center gap-3 flex-grow-1 justify-content-end" style="max-width: 500px;">
                            <input type="range" 
                                   class="form-range score-slider" 
                                   name="scores[<?php echo $row['id']; ?>]" 
                                   min="0" 
                                   max="<?php echo $max_score; ?>" 
                                   step="0.25" 
                                   value="<?php echo $row['current_score']; ?>"
                                   data-id="<?php echo $row['id']; ?>"
                                   <?php echo ($event_status === 'locked') ? 'disabled' : ''; ?>>
                            
                            <span class="score-display" id="display_<?php echo $row['id']; ?>">
                                <?php echo number_format($row['current_score'], 2); ?>
                            </span>
                        </div>
                    </div>
                <?php endwhile; ?>
            <?php else: ?>
                <p class="text-center text-muted my-4">No candidates found.</p>
            <?php endif; ?>
        </div>

        <!-- Submit Button (Mogawas ra kung dili locked ang event) -->
        <?php if($event_status !== 'locked'): ?>
            <div class="text-end mt-4 mb-5">
                <button type="submit" id="submitBtn" class="btn btn-warning btn-lg px-5 fw-bold text-uppercase shadow">
                    <i class="fa fa-save me-2"></i> Save Scores for this Event
                </button>
            </div>
        <?php endif; ?>
    </form>
</div>

<script>
	function changeCriteria(criteriaKey) {
		window.location.href = "judge_scoring.php?criteria=" + criteriaKey;
	}

	$(document).ready(function() {
		
		// 🔥 KINI ANG KORREKSYON: Loop sa tanang sliders sa pag-load sa page
		$('.score-slider').each(function() {
			let candidateId = $(this).data('id');
			let initialVal = parseFloat($(this).val()).toFixed(2);
			
			// I-display dayon ang saktong score gikan sa database imbis magpabilin nga 0.00
			$('#display_' + candidateId).text(initialVal);
		});

		// Monitor sa slider movements kung i-drag na sa judge
		$('.score-slider').on('input', function() {
			let candidateId = $(this).data('id');
			let val = parseFloat($(this).val()).toFixed(2);
			$('#display_' + candidateId).text(val);
		});

		// AJAX Bulk Scores Submit
		$('#bulkScoringForm').on('submit', function(e) {
			e.preventDefault();
			
			Swal.fire({
				title: 'Confirm Submission?',
				text: "Kini mo-save o mo-overwrite sa imong scores para sa <?php echo $criteria_label; ?>.",
				icon: 'question',
				showCancelButton: true,
				confirmButtonColor: '#ffc107',
				cancelButtonColor: '#d33',
				confirmButtonText: 'Yes, Save Scores!',
				background: '#1e1e1e',
				color: '#fff'
			}).then((result) => {
				if (result.isConfirmed) {
					$('#submitBtn').prop('disabled', true).html('<i class="fa fa-spinner fa-spin"></i> Saving...');
					
					$.ajax({
						url: 'submit_bulk_scores.php',
						type: 'POST',
						data: $('#bulkScoringForm').serialize(),
						dataType: 'json',
						success: function(res) {
							if(res.status === 'success') {
								Swal.fire({ title: 'Success!', text: res.message, icon: 'success', background: '#1e1e1e', color: '#fff' });
							} else {
								Swal.fire({ title: 'Error', text: res.message, icon: 'error', background: '#1e1e1e', color: '#fff' });
							}
							$('#submitBtn').prop('disabled', false).html('<i class="fa fa-save me-2"></i> Save Scores for this Event');
						},
						error: function() {
							Swal.fire('Error', 'Server connection failure.', 'error');
							$('#submitBtn').prop('disabled', false).html('<i class="fa fa-save me-2"></i> Save Scores for this Event');
						}
					});
				}
			});
		});
	});
</script>

<?php include 'footer.php';?>