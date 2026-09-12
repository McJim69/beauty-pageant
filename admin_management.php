<?php
	session_start();
	if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
		header("Location: index.php");
		exit();
	}

	// 1. Database Connection
	include 'config.php';
	if ($conn->connect_error) {
		die("Database connection failed: " . $conn->connect_error);
	}

	$message = "";
	$msg_type = "";

	// 2. BACKEND ACTIONS PROCESSED HERE
	if ($_SERVER['REQUEST_METHOD'] === 'POST') {
		
		// A. ADD CANDIDATE SUBMISSION
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

		// B. ADD JUDGE SUBMISSION
		if (isset($_POST['action']) && $_POST['action'] === 'add_judge') {
			$num  = (int)$_POST['judge_number'];
			$name = trim($_POST['fullname']);
			$user = trim($_POST['username']);
			$pass = trim($_POST['password']);

			if (!empty($num) && !empty($name) && !empty($user) && !empty($pass)) {
				// Gi-hash ang password para luwas base sa standard protocol security layers
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

		// C. DELETE ACTIONS
		if (isset($_POST['action']) && $_POST['action'] === 'delete_item') {
			$target_table = $_POST['target_table'];
			$target_id    = (int)$_POST['target_id'];

			if ($target_table === 'contestants' || $target_table === 'judges') {
				$stmt = $conn->prepare("DELETE FROM {$target_table} WHERE id = ?");
				$stmt->bind_param("i", $target_id);
				$stmt->execute();
				$stmt->close();
				$message = "🗑️ Selected profile record deleted from the system matrix.";
				$msg_type = "warning";
			}
		}
	}

	// 3. FETCH REFRESHED LISTS
	$candidates_list = $conn->query("SELECT * FROM contestants ORDER BY candidate_number ASC");
	$judges_list     = $conn->query("SELECT * FROM judges ORDER BY judge_number ASC");

	include 'header.php';
	include 'navbar.php'; 
?>

<div class="main-content container py-4">
    <div class="mb-4">
        <h2 class="text-warning fw-bold"><i class="fa fa-users-cog me-2"></i> ROSTER MANAGEMENT PANEL</h2>
        <p style="color:#bbb">Register, view, or manage your official judges and contest candidates profile setups.</p>
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
                                <label class="form-label small">REPRESENTED LOCATION / BARANGAY</label>
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