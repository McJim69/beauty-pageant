<?php
	session_start();
	if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
		header("Location: index.php");
		exit();
	}

	// Database Connection
	include 'config.php';
	if ($conn->connect_error) { die("Connection failed: " . $conn->connect_error); }

	// I-define ang mga label para sa atong mga criteria
	$criteria_labels = [
		'production_number'   => 'Production Number (20%)',
		'talent_portion'      => 'Talent Portion (20%)',
		'evening_gown'        => 'Evening Gown (15%)',
		'swimwear'            => 'Swimwear (10%)',
		'question_and_answer' => 'Question and Answer (25%)',
		'stage_presence'      => 'Stage Presence (10%)'
	];

	// Kuhaon ang kasamtangang status sa tanang criteria gikan sa database
	$result = $conn->query("SELECT * FROM criteria_status");
	$status_list = [];
	while ($row = $result->fetch_assoc()) {
		$status_list[$row['criteria_name']] = $row['status'];
	}
	include 'header.php';
	include 'navbar.php';
?>

<div class="container py-5">
    <div class="card card-custom p-4 shadow-lg">
        <h4 class="mb-4 text-white"><i class="fa fa-lock text-warning me-2"></i> Criteria Access Management</h4>
        
        <div id="criteriaContainer">
            <?php foreach ($criteria_labels as $key => $label): 
                $current_status = $status_list[$key] ?? 'open';
                $row_class = ($current_status === 'open') ? 'status-open' : 'status-locked';
            ?>
                <div class="criteria-row <?php echo $row_class; ?> d-flex justify-content-between align-items-center flex-wrap gap-3" id="row_<?php echo $key; ?>">
                    <div>
                        <h5 class="mb-1 fw-bold text-white"><?php echo $label; ?></h5>
                        <small style="color:#bbb">Database Identifier: <code><?php echo $key; ?></code></small>
                    </div>
                    
                    <div class="d-flex align-items-center gap-3">
                        <!-- Status Badge Label -->
                        <span class="badge <?php echo ($current_status === 'open') ? 'bg-success' : 'bg-danger'; ?> fs-6 text-uppercase px-3 py-2" id="badge_<?php echo $key; ?>">
                            <i class="fa <?php echo ($current_status === 'open') ? 'fa-unlock-alt' : 'fa-lock'; ?> me-1"></i>
                            <?php echo $current_status; ?>
                        </span>

                        <!-- Action Button Toggle -->
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
					<div class="card bg-dark border-danger p-4 mt-5 shadow-lg">
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
				// I-disable ang button samtang nag-delete
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
								location.reload(); // I-refresh ang page para makita ang bag-ong status
							});
						} else {
							Swal.fire('Error', res.message, 'error');
							btn.prop('disabled', false).html('<i class="fa fa-trash-alt me-2"></i> Wipe & Reset Scores');
						}
					},
					error: function() {
						Swal.fire('System Error', 'Could not communicate with the reset script.', 'error');
						btn.prop('disabled', false).html('<i class="fa fa-trash-alt me-2"></i> Wipe & Reset Scores');
					}
				});
			}
		});
	});
		
    // Inig click sa Lock/Open button
    $('.toggle-btn').on('click', function() {
        let btn = $(this);
        let criteria = btn.data('criteria');
        let currentStatus = btn.data('status');
        let actionText = (currentStatus === 'open') ? 'LOCK' : 'OPEN';

        Swal.fire({
            title: actionText + ' this segment?',
            text: "Kini maka-apekto dayon sa scoring screens sa mga hurado.",
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

                // Ipadala ang hangyo sa toggle_criteria.php sa background
                $.ajax({
                    url: 'toggle_criteria.php',
                    type: 'POST',
                    data: { criteria_name: criteria, current_status: currentStatus },
                    dataType: 'json',
                    success: function(res) {
                        if (res.status === 'success') {
                            let newStatus = res.new_status;
                            
                            // 1. Update sa data attributes sa button
                            btn.data('status', newStatus);
                            btn.prop('disabled', false);

                            // 2. Usbon ang hitsura sa row, badge, ug buton base sa bag-ong status
                            if (newStatus === 'open') {
                                $('#row_' + criteria).removeClass('status-locked').addClass('status-open');
                                $('#badge_' + criteria).removeClass('bg-danger').addClass('bg-success').html('<i class="fa fa-unlock-alt me-1"></i> OPEN');
                                btn.removeClass('btn-success').addClass('btn-danger').html('<i class="fa fa-lock me-1"></i> Lock Segment');
                            } else {
                                $('#row_' + criteria).removeClass('status-open').addClass('status-locked');
                                $('#badge_' + criteria).removeClass('bg-success').addClass('bg-danger').html('<i class="fa fa-lock me-1"></i> LOCKED');
                                btn.removeClass('btn-danger').addClass('btn-success').html('<i class="fa fa-unlock-alt me-1"></i> Open Segment');
                            }

                            // Toast notification para limpyo tan-awon
                            const Toast = Swal.mixin({
                                toast: true,
                                position: 'top-end',
                                showConfirmButton: false,
                                timer: 3000,
                                timerProgressBar: true,
                                background: '#1f2335',
                                color: '#fff'
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

<?php include 'footer.php'; ?>

<?php $conn->close(); ?>
