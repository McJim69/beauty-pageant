<?php
// Susiha kung kinsa ang naka-login para haom ang mga links nga mogawas
$is_admin = isset($_SESSION['admin_logged_in']) && $_SESSION['admin_logged_in'] === true;
$is_judge = isset($_SESSION['judge_id']);
$user_display_name = "";

if ($is_admin) {
    $user_display_name = $_SESSION['admin_user'] ?? 'Administrator';
} elseif ($is_judge) {
    $user_display_name = $_SESSION['judge_name'] ?? 'Official Judge';
}

// Pagkuha sa ngalan sa current file para sa active state highlight
$current_page = basename($_SERVER['PHP_SELF']);
?>
<nav class="navbar navbar-expand-lg navbar-dark shadow mb-4" style="background-color: #161925; border-bottom: 2px solid #ffc107;">
    <div class="container px-4">
        <!-- Logo / Brand Section -->
        <a class="navbar-brand fw-bold text-warning d-flex align-items-center gap-2" href="#">
            <i class="fa fa-crown"></i> PAGEANT LEADERBOARD
        </a>
        <!-- Mobile Toggle Button (Hamburger Menu) -->
        <button class="navbar-toggler border-secondary text-warning" type="button" data-bs-toggle="collapse" data-bs-target="#mainNavbar" aria-controls="mainNavbar" aria-expanded="false" aria-label="Toggle navigation">
            <span class="navbar-toggler-icon"></span>
        </button>

        <!-- Navbar Links and Navigation Elements -->
        <div class="collapse navbar-collapse" id="mainNavbar">
            <ul class="navbar-nav me-auto mb-2 mb-lg-0">
                <?php if ($is_admin): ?>
                    <!-- Admin Specific Navigation Links -->
					<li class="nav-item">
						<a class="nav-link fw-semibold <?php echo ($current_page == 'admin_management.php') ? 'active text-warning fw-bold' : ''; ?>" href="admin_management.php">
							<i class="fa fa-users-cog me-1"></i> Management
						</a>
					</li>
                    <li class="nav-item">
                        <a class="nav-link fw-semibold <?php echo ($current_page == 'admin_controller.php') ? 'active text-warning fw-bold' : ''; ?>" href="admin_controller.php">
                            <i class="fa fa-sliders-h me-1"></i> Controller
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link fw-semibold <?php echo ($current_page == 'admin_dashboard.php') ? 'active text-warning fw-bold' : ''; ?>" href="admin_dashboard.php">
                            <i class="fa fa-chart-bar me-1"></i> Leaderboard
                        </a>
                    </li>
					<li class="nav-item">
						<a class="nav-link fw-semibold <?php echo ($current_page == 'statistics.php') ? 'active text-warning fw-bold' : ''; ?>" href="statistics.php">
							<i class="fa fa-chart-pie me-1"></i> Statistics
						</a>
					</li>					
                <?php endif; ?>

                <?php if ($is_judge): ?>
                    <!-- Judge Specific Navigation Links -->
                    <li class="nav-item">
                        <a class="nav-link fw-semibold <?php echo ($current_page == 'judge_scoring.php') ? 'active text-warning fw-bold' : ''; ?>" href="judge_scoring.php">
                            <i class="fa fa-star me-1"></i> Scoring Board
                        </a>
                    </li>
                <?php endif; ?>
            </ul>

            <!-- Right Align Section: Profile Name & Logout Logic -->
            <?php if ($is_admin || $is_judge): ?>
                <div class="d-flex align-items-center gap-3 mt-2 mt-lg-0 pt-2 pt-lg-0">
                    <span class="text-light fs-7">
                        <i class="fa fa-user-circle text-muted me-1"></i> 
                        Active: <strong class="text-white"><?php echo htmlspecialchars($user_display_name); ?></strong>
                    </span>
                    <a href="logout.php" class="btn btn-outline-danger btn-sm px-3 fw-bold">
                        <i class="fa fa-sign-out-alt me-1"></i> Logout
                    </a>
                </div>
            <?php endif; ?>
        </div>
    </div>
</nav>
