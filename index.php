<?php
	session_start();
	include 'config.php';

	$error_message = "";

	if ($_SERVER['REQUEST_METHOD'] === 'POST') {
		$username = trim($_POST['username']);
		$password = trim($_POST['password']);

		if (!empty($username) && !empty($password)) {
			
			// 1. HARDCODED ADMIN ACCOUNT CHECK
			// Mahimo nimo usbon ang password dinhi para sa imong Admin
			if ($username === 'admin' && $password === 'admin123') {
				$_SESSION['admin_logged_in'] = true;
				$_SESSION['admin_user'] = 'Administrator';
				header("Location: admin_management.php");
				exit();
			}

			// 2. JUDGES ACCOUNT CHECK (Pinaagi sa Database)
			$stmt = $conn->prepare("SELECT id, fullname, password, status FROM judges WHERE username = ? LIMIT 1");
			$stmt->bind_param("s", $username);
			$stmt->execute();
			$result = $stmt->get_result();

			if ($result && $result->num_rows > 0) {
				$judge = $result->fetch_assoc();
				
				if ($judge['status'] === 'inactive') {
					$error_message = "Kini nga judge account kay de-activated sa admin.";
				} else {
					// I-verify ang password gamit ang PHP password_verify (kay naka-hash kini sa database)
					if (password_verify($password, $judge['password'])) {
						$_SESSION['judge_id']   = $judge['id'];
						$_SESSION['judge_name'] = $judge['fullname'];
						
						header("Location: judge_scoring.php");
						exit();
					} else {
						$error_message = "Sayop nga password. Palihug sulayi pag-usab.";
					}
				}
			} else {
				$error_message = "Wala makit-an nga username sa sistema.";
			}
			$stmt->close();
		} else {
			$error_message = "Palihug sulati ang tanang fields.";
		}
	}
	$conn->close();
	include 'header.php';
?>

<style>body{background: #000 url(images/queen_logo.png)no-repeat; background-size:45%; background-position:center center;}</style>

<div class="container d-flex align-items-center justify-content-center main-content" style="">

<div class="login-card p-4 mx-3" style="background:rgba(0, 0, 0, 0.8);">
    <div class="login-header mb-4">
        <h3 class="text-warning fw-bold mb-1"><i class="fa fa-crown me-2"></i> PAGEANT SYSTEM</h3>
        <small class="text-muted">Official Tabulation Portal Login</small>
    </div>

    <!-- Error Notification Box -->
    <?php if (!empty($error_message)): ?>
        <div class="alert alert-danger bg-dark border-danger text-white d-flex align-items-center gap-2 py-2 fs-7" role="alert">
            <i class="fa fa-exclamation-circle text-danger"></i>
            <div><?php echo $error_message; ?></div>
        </div>
    <?php endif; ?>

    <form method="POST" action="">
        <!-- Username input -->
        <div class="mb-3">
            <label class="form-label text-white-50 small fw-bold">USERNAME</label>
            <div class="input-group">
                <span class="input-group-text bg-dark border-secondary text-muted"><i class="fa fa-user"></i></span>
                <input type="text" name="username" class="form-control form-control-custom" placeholder="Enter username" required autocomplete="off">
            </div>
        </div>

        <!-- Password input -->
        <div class="mb-4">
            <label class="form-label text-white-50 small fw-bold">PASSWORD</label>
            <div class="input-group">
                <span class="input-group-text bg-dark border-secondary text-muted"><i class="fa fa-lock"></i></span>
                <input type="password" name="password" class="form-control form-control-custom" placeholder="Enter password" required>
            </div>
        </div>

        <!-- Submit button -->
        <button type="submit" class="btn btn-warning w-100 fw-bold py-2 text-uppercase tracking-wider shadow">
            <i class="fa fa-sign-in-alt me-2"></i> Sign In Securely
        </button>
    </form>

    <div class="text-center mt-4">
        <small class="text-muted fs-7">Protected Tabulation Environment v2.0</small>
    </div>
</div>

</div>

<?php include 'footer.php';?>
