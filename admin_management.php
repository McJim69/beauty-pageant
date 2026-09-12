<?php
	session_start();

	// 1. Session Protection Gate (Siguraduha nga Admin ra ang makasulod)
	if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
		header("Location: index.php");
		exit();
	}

	// 2. Database Connection
	include 'config.php';

	$message = "";
	$msg_type = "";

	// 3. BACKEND ACTIONS PROCESSED HERE
	if ($_SERVER['REQUEST_METHOD'] === 'POST') {
		
		// ACTION A: ADD CANDIDATE SUBMISSION
		if (isset($_POST['action']) && $_POST['action'] === 'add_candidate') {
			$num  = (int)$_POST['candidate_number'];
			$name = trim($_POST['fullname']);
			$loc  = trim($_POST['represented_location']);

			if (!empty($num) && !empty($name)) {
				$stmt = $conn->prepare("INSERT INTO contestants (candidate_number, fullname, represented_location) VALUES (?, ?, ?)");
				$stmt->bind_param("iss", $num, $name, $loc);
				if ($stmt->execute()) {
					$message = "🎉 Candidate #{$num} successfully added to the roster!";
					$msg_type = "success";
				} else {
					$message = "❌ Error adding candidate. (Possible Duplicate Candidate Number)";
					$msg_type = "danger";
				}
				$stmt->close();
			}
		}

		// ACTION B: ADD JUDGE SUBMISSION
		if (isset($_POST['action']) && $_POST['action'] === 'add_judge') {
			$num  = (int)$_POST['judge_number'];
			$name = trim($_POST['fullname']);
			$user = trim($_POST['username']);
			$pass = trim($_POST['password']);

			if (!empty($num) && !empty($name) && !empty($user) && !empty($pass)) {
				$hashed_pass = password_hash($pass, PASSWORD_DEFAULT);

				$stmt = $conn->prepare("INSERT INTO judges (judge_number, fullname, username, password) VALUES (?, ?, ?, ?)");
				$stmt->bind_param("isss", $num, $name, $user, $hashed_pass);
				if ($stmt->execute()) {
					$message = "🎉 Judge #{$num} ({$name}) successfully registered!";
					$msg_type = "success";
				} else {
					$message = "❌ Error adding judge. (Possible Duplicate Judge Number or Username)";
					$msg_type = "danger";
				}
				$stmt->close();
			}
		}

		// ACTION C: ADD CRITERIA SUBMISSION
		if (isset($_POST['action']) && $_POST['action'] === 'add_criteria') {
			$label  = trim($_POST['label']);
			$weight = (float)$_POST['weight'];
			$order  = (int)$_POST['sort_order'];
			
			// Mag-generate og database key gikan sa label (e.g., 'Evening Gown' -> 'evening_gown')
			$key = strtolower(str_replace(' ', '_', preg_replace('/[^A-Za-z0-9 ]/', '', $label)));

			if (!empty($label) && $weight > 0) {
				$stmt = $conn->prepare("INSERT INTO criteria (criteria_key, label, weight, sort_order) VALUES (?, ?, ?, ?)");
				$stmt->bind_param("ssdi", $key, $label, $weight, $order);
				if ($stmt->execute()) {
					$message = "🎉 New criteria segment '{$label}' deployed successfully!";
					$msg_type = "success";
				} else {
					$message = "❌ Error deploying criteria. (Possible Duplicate Name or Key)";
					$msg_type = "danger";
				}
				$stmt->close();
			} else {
				$message = "❌ Please fill out all required criteria input parameters.";
				$msg_type = "danger";
			}
		}

		// ACTION D: GLOBAL DELETE PROCESSOR
		if (isset($_POST['action']) && $_POST['action'] === 'delete_item') {
			$target_table = $_POST['target_table'];
			$target_id    = (int)$_POST['target_id'];

			// 🔥 KORREKSYON: Gi-apil na nato ang 'criteria' sa luwas nga listahan para ma-delete na nimo og husto!
			if ($target_table === 'contestants' || $target_table === 'judges' || $target_table === 'criteria') {
				$stmt = $conn->prepare("DELETE FROM {$target_table} WHERE id = ?");
				$stmt->bind_param("i", $target_id);
				if ($stmt->execute()) {
					$message = "🗑️ Selected profile record permanently removed from the database system matrix.";
					$msg_type = "warning";
				} else {
					$message = "❌ Failed to delete record resource handler profile details.";
					$msg_type = "danger";
				}
				$stmt->close();
			}
		}
	}

	// 4. DYNAMIC VALIDATION PROTOCOL: Compute cumulative values
	$weight_check = $conn->query("SELECT SUM(weight) as total_weight FROM criteria");
	$weight_row = $weight_check->fetch_assoc();
	$total_system_weight = (float)($weight_row['total_weight'] ?? 0.00);

	$validation_warning = "";
	if ($total_system_weight !== 100.00) {
		$validation_warning = "⚠️ <strong>Tabulation Allocation Mismatch:</strong> Your current criteria total weights sum up to <strong>" . number_format($total_system_weight, 2) . "%</strong> instead of 100.00%. Please adjust them to perfect the 100% calculation balance index loop.";
	}

	// 5. FETCH REFRESHED LISTS
	$candidates_list = $conn->query("SELECT * FROM contestants ORDER BY candidate_number ASC");
	$judges_list     = $conn->query("SELECT * FROM judges ORDER BY judge_number ASC");
	$criteria_list   = $conn->query("SELECT * FROM criteria ORDER BY sort_order ASC");

	include 'header.php';
	include 'navbar.php'; 
?>

<div class="main-content container py-4">
    <div class="mb-4">
        <h2 class="text-warning fw-bold"><i class="fa fa-users-cog me-2"></i> MANAGEMENT PANEL</h2>
        <p style="color:#bbb">Register, Create Criteria, or manage your official judges and candidates profile.</p>
    </div>

    <!-- Alert Messages Display Area -->
    <?php if (!empty($message)): ?>
        <div class="alert alert-<?php echo $msg_type; ?> bg-dark border-<?php echo $msg_type; ?> text-white" role="alert">
            <?php echo $message; ?>
        </div>
    <?php endif; ?>

    <!-- Navigation Tabs -->
    <ul class="nav nav-tabs mb-4 border-secondary" id="managementTabs" role="tablist">
        <li class="nav-item">
            <button class="nav-link active fs-5" id="candidates-tab" data-bs-toggle="tab" data-bs-target="#candidates" type="button" role="tab"><i class="fa fa-female me-2"></i> Candidates</button>
        </li>
        <li class="nav-item">
            <button class="nav-link fs-5" id="judges-tab" data-bs-toggle="tab" data-bs-target="#judges" type="button" role="tab"><i class="fa fa-gavel me-2"></i> Pageant Judges</button>
        </li>
		<li class="nav-item">
			<button class="nav-link fs-5" id="criteria-tab" data-bs-toggle="tab" data-bs-target="#criteriaTabPane" type="button" role="tab"><i class="fa fa-list-ol me-2"></i> Pageant Criteria</button>
		</li>
    </ul>

    <div class="tab-content" id="managementTabsContent">
        
        <!-- 💡 TAB 1: CANDIDATES MANAGEMENT SECTION -->
        <div class="tab-pane fade show active" id="candidates" role="tabpanel">
            <div class="row g-4">
                <!-- Left: Entry Form -->
                <div class="col-lg-4">
                    <div class="card card-custom p-4 shadow">
                        <h5 class="text-white border-bottom border-secondary pb-2 mb-3"><i class="fa fa-plus text-warning me-2"></i> Add Candidate</h5>
                        <form method="POST" action="">
                            <input type="hidden" name="action" value="add_candidate">
                            <div class="mb-3">
                                <label class="form-label small">CANDIDATE NUMBER</label>
                                <input type="number" name="candidate_number" class="form-control bg-dark border-secondary text-white" required min="1">
                            </div>
                            <div class="mb-3">
                                <label class="form-label small">FULL NAME</label>
                                <input type="text" name="fullname" class="form-control bg-dark border-secondary text-white" required placeholder="e.g. Jane Doe">
                            </div>
                            <div class="mb-3">
                                <label class="form-label small">REPRESENTED LOCATION</label>
                                <input type="text" name="represented_location" class="form-control bg-dark border-secondary text-white" placeholder="e.g. Barangay San Jose">
                            </div>
                            <button type="submit" class="btn btn-warning w-100 fw-bold mt-2"><i class="fa fa-save me-1"></i> Save Candidate</button>
                        </form>
                    </div>
                </div>
                
                <!-- Right: Directory Table -->
                <div class="col-lg-8">
                    <div class="card card-custom p-4 shadow">
                        <h5 class="text-white mb-3"><i class="fa fa-list text-muted me-2"></i> Candidate Directory</h5>
                        <div class="table-responsive">
                            <table class="table table-custom align-middle text-center">
                                <thead>
                                    <tr>
                                        <th>No.</th>
                                        <th class="text-start">Full Name</th>
                                        <th class="text-start">Location Profile</th>
                                        <th>Action</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php if ($candidates_list && $candidates_list->num_rows > 0): ?>
                                        <?php while ($c = $candidates_list->fetch_assoc()): ?>
                                        <tr>
                                            <td><span class="badge bg-warning text-dark fw-bold fs-6">#<?php echo $c['candidate_number']; ?></span></td>
                                            <td class="text-start fw-bold"><?php echo htmlspecialchars($c['fullname']); ?></td>
                                            <td class="text-start"><?php echo htmlspecialchars($c['represented_location'] ?? 'N/A'); ?></td>
                                            <td>
                                                <form method="POST" action="" onsubmit="return confirm('Sigurado ka nga papason kini nga kandidata?');">
                                                    <input type="hidden" name="action" value="delete_item">
                                                    <input type="hidden" name="target_table" value="contestants">
                                                    <input type="hidden" name="target_id" value="<?php echo $c['id']; ?>">
                                                    <button type="submit" class="btn btn-outline-danger btn-sm"><i class="fa fa-trash"></i></button>
                                                </form>
                                            </td>
                                        </tr>
                                        <?php endwhile; ?>
                                    <?php else: ?>
                                        <tr>
                                            <td colspan="4" class="text-muted py-4">No candidates registered yet.</td>
                                        </tr>
                                    <?php endif; ?>
                                </tbody>
                            </table>
                        </div>
                    </div> <!-- 🔥 GI-ADD: Sirado sa card-custom -->
                </div> <!-- 🔥 GI-ADD: Sirado sa col-lg-8 -->				
            </div> <!-- 🔥 GI-ADD: Sirado sa row g-4 -->
        </div> <!-- 🔥 GI-ADD: Sirado sa tab-pane -->

        <!-- 💡 TAB 2: JUDGES MANAGEMENT SECTION -->
        <div class="tab-pane fade" id="judges" role="tabpanel">
            <div class="row g-4">
                <!-- Left: Entry Form -->
                <div class="col-lg-4">
                    <div class="card card-custom p-4 shadow">
                        <h5 class="text-white border-bottom border-secondary pb-2 mb-3">
                            <i class="fa fa-user-plus text-warning me-2"></i> Register New Judge
                        </h5>
                        <form method="POST" action="">
                            <input type="hidden" name="action" value="add_judge">
                            
                            <div class="mb-3">
                                <label class="form-label small fw-bold">JUDGE NUMBER / ORDER</label>
                                <input type="number" name="judge_number" class="form-control bg-dark border-secondary text-white" required min="1" placeholder="e.g. 1">
                            </div>
                            
                            <div class="mb-3">
                                <label class="form-label small fw-bold">JUDGE FULL NAME</label>
                                <input type="text" name="fullname" class="form-control bg-dark border-secondary text-white" required placeholder="e.g. Hon. Juan Dela Cruz">
                            </div>
                            
                            <div class="mb-3">
                                <label class="form-label small fw-bold">SYSTEM ACCESS USERNAME</label>
                                <input type="text" name="username" class="form-control bg-dark border-secondary text-white" required placeholder="e.g. judge1" autocomplete="off">
                            </div>
                            
                            <div class="mb-4">
                                <label class="form-label small fw-bold">TEMPORARY PASSWORD</label>
                                <input type="password" name="password" class="form-control bg-dark border-secondary text-white" required placeholder="Enter system security pass">
                            </div>
                            
                            <button type="submit" class="btn btn-success w-100 fw-bold text-uppercase">
                                <i class="fa fa-user-check me-1"></i> Deploy Judge Account
                            </button>
                        </form>
                    </div>
                </div>

                <!-- Right: Directory Table -->
                <div class="col-lg-8">
                    <div class="card card-custom p-4 shadow">
                        <h5 class="text-white mb-3">
                            <i class="fa fa-list text-muted me-2"></i> Official Judges Pool
                        </h5>
                        <div class="table-responsive">
                            <table class="table table-custom align-middle text-center">
                                <thead>
                                    <tr>
                                        <th>Judge ID</th>
                                        <th class="text-start">Full Name / Title</th>
                                        <th class="text-start">Login Username</th>
                                        <th>Action</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php if ($judges_list && $judges_list->num_rows > 0): ?>
                                        <?php while ($j = $judges_list->fetch_assoc()): ?>
                                        <tr>
                                            <td>
                                                <span class="badge bg-secondary px-3 py-2 fw-bold">
                                                    ID #<?php echo $j['judge_number']; ?>
                                                </span>
                                            </td>
                                            <td class="text-start fw-bold">
                                                <?php echo htmlspecialchars($j['fullname']); ?>
                                            </td>
                                            <td class="text-start text-info">
                                                <code><?php echo htmlspecialchars($j['username']); ?></code>
                                            </td>
                                            <td>
                                                <form method="POST" action="" onsubmit="return confirm('Sigurado ka nga papason kini nga hurado? Ma-papas pod ang tanang scores nga iyang gi-input.');">
                                                    <input type="hidden" name="action" value="delete_item">
                                                    <input type="hidden" name="target_table" value="judges">
                                                    <input type="hidden" name="target_id" value="<?php echo $j['id']; ?>">
                                                    <button type="submit" class="btn btn-outline-danger btn-sm">
                                                        <i class="fa fa-trash"></i>
                                                    </button>
                                                </form>
                                            </td>
                                        </tr>
                                        <?php endwhile; ?>
                                    <?php else: ?>
                                        <tr>
                                            <td colspan="4" class="text-muted py-4">No judges registered in the system matrix yet.</td>
                                        </tr>
                                    <?php endif; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div> <!-- 💡 End of Judges Tab Pane -->	
		<!-- 💡 TAB 3: CRITERIA CRUD OPERATIONS PANEL -->
		<div class="tab-pane fade" id="criteriaTabPane" role="tabpanel">
			<!-- 🔥 LIVE TOTAL WEIGHT VALIDATION REGISTRY DISPLAY CARD -->
			<div class="card p-3 mb-4 shadow border-0" style="background: #1f2335;">
				<div class="d-flex justify-content-between align-items-center flex-wrap gap-2">
					<div>
						<h6 class="mb-1 text-white-50 small fw-bold tracking-wider">CRITERIA ALLOCATION VALIDATION ENGINE</h6>
						<h5 class="mb-0 text-white fw-bold">
							Current Total System Weight: 
							<span class="<?php echo ($total_system_weight == 100.00) ? 'text-success' : 'text-danger'; ?>" id="liveWeightText">
								<?php echo number_format($total_system_weight, 2); ?>%
							</span>
						</h5>
					</div>
					<div>
						<?php if ($total_system_weight == 100.00): ?>
							<span class="badge bg-success px-4 py-2 fs-7 text-uppercase"><i class="fa fa-check-circle me-1"></i> System Balanced (100%)</span>
						<?php else: ?>
							<span class="badge bg-danger px-4 py-2 fs-7 text-uppercase animate-pulse"><i class="fa fa-exclamation-triangle me-1"></i> Balance Error</span>
						<?php endif; ?>
					</div>
				</div>
				<?php if (!empty($validation_warning)): ?>
					<div class="alert alert-danger bg-dark border-danger text-white mt-3 mb-0 py-2 fs-7" role="alert">
						<i class="fa fa-info-circle text-danger me-2"></i> <?php echo $validation_warning; ?>
					</div>
				<?php endif; ?>
			</div>
			<div class="row g-4">
				<!-- Left: Form Input component logic -->
				<div class="col-lg-4">
					<div class="card card-custom p-4 shadow">
						<h5 class="text-white border-bottom border-secondary pb-2 mb-3"><i class="fa fa-plus text-warning me-2"></i> Create New Criteria</h5>
						<form method="POST" action="">
							<input type="hidden" name="action" value="add_criteria">
							<div class="mb-3">
								<label class="form-label small text-white-50">CRITERIA LABEL / NAME</label>
								<input type="text" name="label" class="form-control bg-dark border-secondary text-white" required placeholder="e.g., Evening Gown">
							</div>
							<div class="mb-3">
								<label class="form-label small text-white-50">PERCENTAGE WEIGHT (%)</label>
								<input type="number" name="weight" class="form-control bg-dark border-secondary text-white" required step="0.01" min="1" max="100" placeholder="e.g., 15">
							</div>
							<div class="mb-3">
								<label class="form-label small text-white-50">SORT ORDER / POSITION</label>
								<input type="number" name="sort_order" class="form-control bg-dark border-secondary text-white" required min="1" value="1">
							</div>
							<button type="submit" class="btn btn-warning w-100 fw-bold mt-2"><i class="fa fa-save me-1"></i> Deploy Criteria</button>
						</form>
					</div>
				</div>
				
				<!-- Right: View Directory Table components -->
				<div class="col-lg-8">
					<div class="card card-custom p-4 shadow">
						<h5 class="text-white mb-3"><i class="fa fa-list text-muted me-2"></i> Criteria System Registry</h5>
						<div class="table-responsive">
							<table class="table table-custom align-middle text-center">
								<thead>
									<tr>
										<th>Order</th>
										<th class="text-start">Criteria Name</th>
										<th>Max Weight</th>
										<th>Database Key</th>
										<th>Action</th>
									</tr>
								</thead>
								<tbody>
									<?php 
									$c_list = $conn->query("SELECT * FROM criteria ORDER BY sort_order ASC");
									if ($c_list && $c_list->num_rows > 0):
										while ($crit = $c_list->fetch_assoc()):
									?>
										<tr>
											<td><span class="badge bg-secondary">Pos: <?php echo $crit['sort_order']; ?></span></td>
											<td class="text-start fw-bold"><?php echo htmlspecialchars($crit['label']); ?></td>
											<td><span class="badge bg-info fw-bold"><?php echo number_format($crit['weight'], 0); ?>%</span></td>
											<td><code><?php echo $crit['criteria_key']; ?></code></td>
											<td>
												<form method="POST" action="" onsubmit="return confirm('WARNING: Deleting this criteria will erase all scores submitted under this category! Proceed?');">
													<input type="hidden" name="action" value="delete_item">
													<input type="hidden" name="target_table" value="criteria">
													<input type="hidden" name="target_id" value="<?php echo $crit['id']; ?>">
													<button type="submit" class="btn btn-outline-danger btn-sm"><i class="fa fa-trash"></i></button>
												</form>
											</td>
										</tr>
									<?php endwhile; else: ?>
										<tr><td colspan="5" class="text-muted py-4">No criteria segments registered yet.</td></tr>
									<?php endif; ?>
								</tbody>
							</table>
						</div>
					</div>
				</div>
			</div>
		</div> <!-- 💡 End of Criteria Tab Pane -->	
    </div> <!-- Sirado sa tab-content -->
</div> <!-- Sirado sa main-content container -->

<script>
$(document).ready(function() {
    // 1. Susiha kung duna ba tay gi-save nga active tab sa memory sa browser (sessionStorage)
    let activeTab = sessionStorage.getItem('activeManagementTab');

    if (activeTab) {
        // Kung duna, i-trigger ang Bootstrap para mo-switch automatic niana nga tab sa pag-load sa page
        let tabTrigger = new bootstrap.Tab(document.querySelector(activeTab));
        tabTrigger.show();
    }

    // 2. Monitoron nato matag higayon nga ang admin mag-click sa laing tab menu buttons
    $('button[data-bs-toggle="tab"]').on('shown.bs.tab', function (e) {
        // Kuhaon ang ID selector sa tab trigger button (e.g., '#candidates-tab' o '#judges-tab')
        let targetTabId = '#' + $(e.target).attr('id');
        
        // I-save kini sa temporary browser storage para dili malimtan inig reload sa page
        sessionStorage.setItem('activeManagementTab', targetTabId);
    });
});
</script>

<?php include 'footer.php';?>