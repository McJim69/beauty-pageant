<?php
	session_start();

	// 1. Session Protection Gate
	if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
		header("Location: index.php");
		exit();
	}

	// 2. Database Connection
	include 'config.php';

	// 🔥 FIX & AUTO-MIGRATION GATES: If the new table doesn't exist, create it automatically!
	$conn->query("CREATE TABLE IF NOT EXISTS criteria (
		id INT AUTO_INCREMENT PRIMARY KEY,
		criteria_key VARCHAR(50) NOT NULL UNIQUE,
		label VARCHAR(100) NOT NULL,
		weight DECIMAL(5,2) NOT NULL,
		sort_order INT DEFAULT 0,
		status ENUM('open', 'locked') DEFAULT 'open',
		created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
	);");

	// Check if it's empty, if so, seed the default criteria configurations
	$check_empty = $conn->query("SELECT id FROM criteria LIMIT 1");
	if ($check_empty && $check_empty->num_rows === 0) {
		$conn->query("INSERT INTO criteria (criteria_key, label, weight, sort_order, status) VALUES
		('production_number', 'Production Number', 20.00, 1, 'open'),
		('talent_portion', 'Talent Portion', 20.00, 2, 'open'),
		('evening_gown', 'Evening Gown', 15.00, 3, 'open'),
		('swimwear', 'Swimwear', 10.00, 4, 'open'),
		('question_and_answer', 'Question and Answer', 25.00, 5, 'open'),
		('stage_presence', 'Stage Presence', 10.00, 6, 'open');");
	}

	// 3. Dynamic Fetching Registry from Database
	$criteria_list = [];
	$criteria_query = $conn->query("SELECT criteria_key, label, weight, status FROM criteria ORDER BY sort_order ASC");

	if ($criteria_query) {
		while ($c_row = $criteria_query->fetch_assoc()) {
			$criteria_list[$c_row['criteria_key']] = [
				'label'  => $c_row['label'],
				'max'    => (float)$c_row['weight'],
				'weight' => number_format($c_row['weight'], 0) . '%',
				'status' => $c_row['status']
			];
		}
	} else {
		die("Database Query System Failure: " . $conn->error);
	}
?>

<?php include 'header.php'; ?>
<?php include 'navbar.php'; ?>

<div class="container main-content">
    <div class="d-flex justify-content-between align-items-center mb-4 pb-3 border-bottom border-secondary">
        <div>
            <h2 class="text-warning fw-bold"><i class="fa fa-sliders-h me-2"></i> EVENT CONTROLLER PANEL</h2>
            <p class="text-muted mb-0">Control which segments are currently active or disabled for the judges.</p>
        </div>
        <div>
            <a href="admin_dashboard.php" class="btn btn-outline-info fw-bold"><i class="fa fa-chart-bar me-1"></i> View Live Results</a>
        </div>
    </div>

    <div class="card card-custom p-4 shadow-lg">
        <h4 class="mb-4 text-white"><i class="fa fa-lock text-muted me-2"></i> Criteria Access Management</h4>
        
        <div id="criteriaContainer">
            <?php foreach ($criteria_list as $key => $details): 
                $current_status = $details['status'];
                $row_class = ($current_status === 'open') ? 'status-open' : 'status-locked';
            ?>
                <div class="criteria-row <?php echo $row_class; ?> d-flex justify-content-between align-items-center flex-wrap gap-3" id="row_<?php echo $key; ?>">
                    <div>
                        <h5 class="mb-1 fw-bold text-white"><?php echo $details['label']; ?> (<?php echo $details['weight']; ?>)</h5>
                        <small class="text-muted">Database Identifier: <code><?php echo $key; ?></code></small>
                    </div>
                    
                    <div class="d-flex align-items-center gap-3">
                        <span class="badge <?php echo ($current_status === 'open') ? 'bg-success' : 'bg-danger'; ?> fs-6 text-uppercase px-3 py-2" id="badge_<?php echo $key; ?>">
                            <i class="fa <?php echo ($current_status === 'open') ? 'fa-unlock-alt' : 'fa-lock'; ?> me-1"></i>
                            <?php echo $current_status; ?>
                        </span>

                        <button class="btn <?php echo ($current_status === 'open') ? 'btn-danger' : 'btn-success'; ?> fw-bold toggle-btn px-4" 
                                data-criteria="<?php echo $key; ?>" 
                                data-status="<?php echo $current_status; ?>"
                                id="btn_<?php echo $key; ?>">
                            <?php echo ($current_status === 'open') ? '<i class="fa fa-lock me-1"></i> Lock Segment' : '<i class="fa fa-unlock-alt me-1"></i> Open Segment'; ?>
                        </button>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
		<!-- Danger Zone Reset Button Component -->
		<div class="card bg-dark border-danger p-3 mt-2 shadow-lg">
			<div class="d-flex justify-content-between align-items-center flex-wrap gap-3">
				<div>
					<h4 class="text-danger fw-bold mb-1"><i class="fa fa-exclamation-triangle"></i> DANGER ZONE</h4>
					<p class="text-white mb-0 small">Kini nga button mopapas sa TANANG scores sa mga judges ug mo-open sa tanang criteria para sa tinuod nga event.</p>
				</div>
				<button id="btnResetDatabase" class="btn btn-danger btn-lg fw-bold px-4 text-uppercase">
					<i class="fa fa-trash-alt me-2"></i> Wipe & Reset Scores
				</button>
			</div>
		</div>		
    </div>
</div>

<script>
$(document).ready(function() {
    // 🔥 EVENT TRIGGER: Activation handle para sa Wipe & Reset scores database protocol
    $('#criteriaContainer').parent().append(`
        <script>
        $('#btnResetDatabase').on('click', function() {
            Swal.fire({
                title: 'CRITICAL WARNING!',
                text: "Sigurado ka ba gyud nga papason ang TANANG scores sa database? Dili na kini mabalik ug ma-zero ang tibuok standing!",
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#dc3545',
                cancelButtonColor: '#3085d6',
                confirmButtonText: 'Yes, RESET EVERYTHING!',
                cancelButtonText: 'Cancel',
                background: '#161925',
                color: '#fff'
            }).then((result) => {
                if (result.isConfirmed) {
                    let btn = $('#btnResetDatabase');
                    btn.prop('disabled', true).html('<i class="fa fa-spinner fa-spin"></i> Wiping Data...');

                    $.ajax({
                        url: 'reset_scores.php',
                        type: 'POST',
                        dataType: 'json',
                        success: function(res) {
                            if (res.status === 'success') {
                                Swal.fire({
                                    title: 'System Wiped!',
                                    text: res.message,
                                    icon: 'success',
                                    background: '#161925',
                                    color: '#fff'
                                }).then(() => {
                                    location.reload(); 
                                });
                            } else {
                                Swal.fire('Error', res.message, 'error');
                                btn.prop('disabled', false).html('<i class="fa fa-trash-alt me-2"></i> Wipe & Reset Scores');
                            }
                        },
                        error: function() {
                            Swal.fire('System Error', 'Could not communicate with the reset script framework.', 'error');
                            btn.prop('disabled', false).html('<i class="fa fa-trash-alt me-2"></i> Wipe & Reset Scores');
                        }
                    });
                }
            });
        });
        <\/script>
    `);

    // Kung ang buton mo kay naay explicit selector ID target binding, gamita diretso kini:
    $('.btn-danger.btn-lg').attr('id', 'btnResetDatabase');
	
    $('.toggle-btn').on('click', function() {
        let btn = $(this);
        let criteria = btn.data('criteria');
        let currentStatus = btn.data('status');
        let actionText = (currentStatus === 'open') ? 'LOCK' : 'OPEN';

        Swal.fire({
            title: actionText + ' this segment?',
            text: "This immediately updates judges screens access protocols.",
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: (currentStatus === 'open') ? '#dc3545' : '#28a745',
            cancelButtonColor: '#3085d6',
            confirmButtonText: 'Yes, ' + actionText + ' it!',
            background: '#161925',
            color: '#fff'
        }).then((result) => {
            if (result.isConfirmed) {
                btn.prop('disabled', true).html('<i class="fa fa-spinner fa-spin"></i> Processing...');

                $.ajax({
                    url: 'toggle_criteria.php',
                    type: 'POST',
                    data: { criteria_name: criteria, current_status: currentStatus },
                    dataType: 'json',
                    success: function(res) {
                        if (res.status === 'success') {
                            let newStatus = res.new_status;
                            btn.data('status', newStatus).prop('disabled', false);

                            if (newStatus === 'open') {
                                $('#row_' + criteria).removeClass('status-locked').addClass('status-open');
                                $('#badge_' + criteria).removeClass('bg-danger').addClass('bg-success').html('<i class="fa fa-unlock-alt me-1"></i> OPEN');
                                btn.removeClass('btn-success').addClass('btn-danger').html('<i class="fa fa-lock me-1"></i> Lock Segment');
                            } else {
                                $('#row_' + criteria).removeClass('status-open').addClass('status-locked');
                                $('#badge_' + criteria).removeClass('bg-success').addClass('bg-danger').html('<i class="fa fa-lock me-1"></i> LOCKED');
                                btn.removeClass('btn-danger').addClass('btn-success').html('<i class="fa fa-unlock-alt me-1"></i> Open Segment');
                            }

                            const Toast = Swal.mixin({
                                toast: true, position: 'top-end', showConfirmButton: false, timer: 3000, timerProgressBar: true, background: '#1f2335', color: '#fff'
                            });
                            Toast.fire({ icon: 'success', title: res.message });
                        } else {
                            Swal.fire('Error', 'Failed to toggle status.', 'error');
                            btn.prop('disabled', false);
                        }
                    },
                    error: function() {
                        Swal.fire('System Error', 'Cannot connect to controller script.', 'error');
                        btn.prop('disabled', false);
                    }
                });
            }
        });
    });
});
</script>

<?php include 'footer.php';?>