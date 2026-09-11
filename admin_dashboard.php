<?php
	session_start();

	// Session Protection Gate (Siguraduha nga Admin ra ang makasulod)
	if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
		header("Location: index.php");
		exit();
	}

	// Database Connection Matrix
	include 'config.php';
	
	// 1. Definition sa imong criteria weights ug labels array
	$criteria_list = [
		'production_number'   => ['label' => 'Production Number', 'weight' => '20%'],
		'talent_portion'      => ['label' => 'Talent Portion', 'weight' => '20%'],
		'evening_gown'        => ['label' => 'Evening Gown', 'weight' => '15%'],
		'swimwear'            => ['label' => 'Swimwear', 'weight' => '10%'],
		'question_and_answer' => ['label' => 'Question & Answer', 'weight' => '25%'],
		'stage_presence'      => ['label' => 'Stage Presence', 'weight' => '10%']
	];

	// Kuhaon ang mga judges para mahimong dynamic headers sa kada criteria table
	$judges_res = $conn->query("SELECT id, fullname, judge_number FROM judges ORDER BY judge_number ASC");
	$judges_pool = [];
	$judges_list = []; // para sa signatures sa ubos sa matag printed page
	if ($judges_res) {
		while($j_row = $judges_res->fetch_assoc()) {
			$judges_pool[] = $j_row;
			$judges_list[] = $j_row['fullname'];
		}
	}

	// 2. Query para sa General / Over-all Tabulation Summary Calculations Leaderboard
	$sql_overall = "SELECT 
			c.id AS contestant_id,
			c.candidate_number,
			c.fullname,
			c.represented_location,
			AVG(CASE WHEN s.criteria_name = 'production_number' THEN s.score END) as avg_prod,
			AVG(CASE WHEN s.criteria_name = 'talent_portion' THEN s.score END) as avg_talent,
			AVG(CASE WHEN s.criteria_name = 'evening_gown' THEN s.score END) as avg_gown,
			AVG(CASE WHEN s.criteria_name = 'swimwear' THEN s.score END) as avg_swim,
			AVG(CASE WHEN s.criteria_name = 'question_and_answer' THEN s.score END) as avg_qa,
			AVG(CASE WHEN s.criteria_name = 'stage_presence' THEN s.score END) as avg_presence,
			(IFNULL(AVG(CASE WHEN s.criteria_name = 'production_number' THEN s.score END), 0) +
			 IFNULL(AVG(CASE WHEN s.criteria_name = 'talent_portion' THEN s.score END), 0) +
			 IFNULL(AVG(CASE WHEN s.criteria_name = 'evening_gown' THEN s.score END), 0) +
			 IFNULL(AVG(CASE WHEN s.criteria_name = 'swimwear' THEN s.score END), 0) +
			 IFNULL(AVG(CASE WHEN s.criteria_name = 'question_and_answer' THEN s.score END), 0) +
			 IFNULL(AVG(CASE WHEN s.criteria_name = 'stage_presence' THEN s.score END), 0)) as final_average_score
		FROM contestants c
		LEFT JOIN scores s ON c.id = s.contestant_id
		GROUP BY c.id
		ORDER BY final_average_score DESC, c.candidate_number ASC";

	$result_overall = $conn->query($sql_overall);
	
	include 'header.php';
	include 'navbar.php';
?>

<div class="container main-content py-4">
	<div class="dashboard-header py-3 px-4 mb-4 shadow no-print">
		<div class="d-flex justify-content-between align-items-center flex-wrap">
			<div>
				<h2 class="text-warning fw-bold mb-0"><i class="fa fa-chart-bar me-2"></i> TABULATION DASHBOARD</h2>
				<p class="mb-0 d-flex align-items-center gap-2"><span class="live-indicator"></span> Segmented Criteria Sheets & General Standings Leaderboard</p>
			</div>
			<div class="mt-2 mt-md-0 d-flex gap-2">
				<button onclick="window.print();" class="btn btn-warning fw-bold shadow px-4"><i class="fa fa-print me-1"></i> Print Current Tab</button>
				<a href="admin_controller.php" class="btn btn-outline-light btn-sm fw-bold align-self-center"><i class="fa fa-sliders-h me-1"></i> Controller</a>
			</div>
		</div>
	</div>
		<!-- Dynamic Tabs Navigation Menu -->
			<ul class="nav nav-tabs mb-4 border-warning no-print" id="tabulationTabs" role="tablist">
				<?php $isActive = true; foreach ($criteria_list as $key => $details): ?>
					<li class="nav-item">
						<button class="nav-link <?php echo $isActive ? 'active' : ''; ?>" id="tab-<?php echo $key; ?>" data-bs-toggle="tab" data-bs-target="#pane-<?php echo $key; ?>" type="button" role="tab">
							<?php echo $details['label']; ?> (<?php echo $details['weight']; ?>)
						</button>
					</li>
				<?php $isActive = false; endforeach; ?>
				<li class="nav-item">
					<button class="nav-link text-warning border border-warning-subtle rounded-top" id="tab-overall" data-bs-toggle="tab" data-bs-target="#pane-overall" type="button" role="tab">
						<i class="fa fa-trophy me-1"></i> OVER-ALL
					</button>
				</li>
			</ul>
		<!-- 👑 PREMIUM PRINT HEADER WITH LOGO SLOTS OVERLAYS -->
		<div class="print-only-header">
			<div class="print-header-layout">
				<!-- SLOT A: Left Side Logo (e.g. Pageant or Municipality Logo) -->
				<div class="print-logo-slot">
					<!-- Pwede nimo butangan og tinuod nga image path sa unahan: <img src="images/lgu_logo.png"> -->
					<span><img src="images/logo.png"></span>
				</div>
				<!-- CENTER: Official Text Contents Block -->
				<div class="print-title-text">
					<h2 style="margin: 0; font-weight: 900; text-transform: uppercase; letter-spacing: 1px; font-size: 22px;">Official Pageant Tabulation Report</h2>
					<p style="margin: 4px 0 0 0; font-style: italic; color: #444; font-size: 15px; font-weight: bold;" id="printSubTitle">Segment Score Sheet</p>
					<small style="color: #666; font-size: 11px; display: block; margin-top: 5px;">Generated on: <?php echo date('F d, Y h:i A'); ?></small>
				</div>
				<!-- SLOT B: Right Side Logo (e.g. SK or Tourism Logo) -->
				<div class="print-logo-slot">
					<!-- Pwede nimo butangan og tinuod nga image path sa unahan: <img src="images/sk_logo.png"> -->
					<span><img src="images/sk_logo.png"></span>
				</div>
			</div>
		</div>

		<div class="tab-content" id="tabulationTabsContent">        
			<!-- 📑 TAB SECTION 1: INDIVIDUAL CRITERIA SHEETS -->
			<?php $isActive = true; foreach ($criteria_list as $key => $details): ?>
				<div class="tab-pane fade <?php echo $isActive ? 'show active' : ''; ?>" id="pane-<?php echo $key; ?>" role="tabpanel">
					<div class="card bg-dark border-secondary p-4 shadow-lg">
						<h4 class="text-white mb-3 fw-bold no-print"><i class="fa fa-star text-warning me-2"></i> <?php echo $details['label']; ?> Detailed Scores Breakdown</h4>
						<div class="table-responsive">
							<table class="table table-custom table-hover align-middle text-center">
								<thead>
									<tr>
										<th style="width: 80px;">No.</th>
										<th class="text-start">Contestant Name</th>
										<?php foreach ($judges_pool as $jg): ?>
											<th>Judge <?php echo $jg['judge_number']; ?></th>
										<?php endforeach; ?>
										<th class="text-warning">Average Score</th>
									</tr>
								</thead>
								<tbody>
									<?php 
									$c_res = $conn->query("SELECT id, candidate_number, fullname FROM contestants ORDER BY candidate_number ASC");
									if ($c_res && $c_res->num_rows > 0):
										while ($cand = $c_res->fetch_assoc()):
									?>
                                    <tr>
                                        <td>
                                            <span class="badge bg-warning text-dark fw-bold fs-6">
                                                #<?php echo $cand['candidate_number']; ?>
                                            </span>
                                        </td>
                                        <td class="text-start fw-bold">
                                            <?php echo htmlspecialchars($cand['fullname']); ?>
                                        </td>
                                        
                                        <?php 
                                        $sum_scores = 0; 
                                        $voted_judges = 0;
                                        
                                        foreach ($judges_pool as $jg): 
                                            $s_query = $conn->query("SELECT score FROM scores WHERE contestant_id = {$cand['id']} AND judge_id = {$jg['id']} AND criteria_name = '{$key}' LIMIT 1");
                                            $score_row = $s_query->fetch_assoc();
                                            $current_score = $score_row['score'] ?? null;
                                            
                                            if ($current_score !== null) {
                                                $sum_scores += $current_score;
                                                $voted_judges++;
                                            }
                                        ?>
                                            <td>
                                                <?php echo $current_score !== null ? number_format($current_score, 2) : '-'; ?>
                                            </td>
                                        <?php endforeach; ?>
                                        
                                        <?php $segment_avg = ($voted_judges > 0) ? ($sum_scores / $voted_judges) : 0.00; ?>
                                        <td class="text-warning fw-bold fs-5">
                                            <?php echo number_format($segment_avg, 2); ?>
                                        </td>
                                    </tr>
                                    <?php 
                                        endwhile; 
                                    else: 
                                    ?>
                                        <tr>
                                            <td colspan="<?php echo count($judges_pool) + 3; ?>" class="text-muted py-4">
                                                No contestants configured in the system.
                                            </td>
                                        </tr>
                                    <?php endif; ?>
                                </tbody>
						    </table>
					    </div>

                        <!-- Dynamic Signature Lines para sa matag Criteria page panel inig print -->
                        <div class="signature-section mt-5" style="margin-top:50px">
                            <div class="row text-center">
                                <div class="col-12 mb-4">
                                    <h5 style="text-align: left; font-weight: bold; border-bottom: 1px solid #000; padding-bottom: 5px;">Panel of Judges Signatures</h5>
                                </div>
                                <?php foreach($judges_list as $j_name): ?>
                                    <div class="col-4" style="margin-bottom: 30px; display: inline-block; width: 33%;">
                                        <div class="sig-line"></div>
                                        <span><?php echo htmlspecialchars($j_name); ?></span><br>
                                        <small style="color: #666;">Official Pageant Judge</small>
                                    </div>
                                <?php endforeach; ?>
                            </div>
							<div class="row" style="margin:20px 0 20px 0"><h5>VERIFICATION</h5></div>
                            <div class="row text-center">
                                <div class="col-6" style="display: inline-block; width: 50%;">
                                    <div class="sig-line"></div>
                                    <span>Official Tabulator Staff</span><br>
                                    <small style="color: #666;">Data Entry Controller</small>
                                </div>
                                <div class="col-6" style="display: inline-block; width: 50%;">
                                    <div class="sig-line"></div>
                                    <span>Chairman, Board of Judges</span><br>
                                    <small style="color: #666;">Overall Verifier</small>
                                </div>
                            </div>
                        </div>

				    </div>
				</div>
			<?php $isActive = false; endforeach; ?>

			<!-- 🏆 TAB SECTION 2: GENERAL LEADERBOARD (OVER-ALL SUMMARY SHEET) -->
			<div class="tab-pane fade" id="pane-overall" role="tabpanel">
				<div class="card bg-dark border-warning p-4 shadow-lg">
					<h4 class="text-warning mb-3 fw-bold no-print">
						<i class="fa fa-crown me-2"></i> GENERAL TABULATION LEADERBOARD (SUMMARY SHEET)
					</h4>
					
					<div class="table-responsive">
						<table class="table table-custom table-hover align-middle">
							<thead>
								<tr class="text-center">
									<th style="width: 70px;">Rank</th>
									<th style="width: 90px;">No.</th>
									<th class="text-start">Contestant Name / Barangay</th>
									<th>Prod (20%)</th>
									<th>Talent (20%)</th>
									<th>Gown (15%)</th>
									<th>Swim (10%)</th>
									<th>Q&A (25%)</th>
									<th>Presence (10%)</th>
									<th class="text-warning">Final Avg</th>
								</tr>
							</thead>
							<tbody>
								<?php 
								if ($result_overall && $result_overall->num_rows > 0): 
									$rank = 1;
									while($row = $result_overall->fetch_assoc()): 
										$rank_class = 'rank-other';
										if ($rank == 1) $rank_class = 'rank-1';
										if ($rank == 2) $rank_class = 'rank-2';
										if ($rank == 3) $rank_class = 'rank-3';
										
										$final_score = $row['final_average_score'] ? number_format($row['final_average_score'], 2) : '0.00';
								?>
									<tr class="text-center">
										<td><div class="rank-badge <?php echo $rank_class; ?> mx-auto"><?php echo $rank; ?></div></td>
										<td>
											<span class="badge bg-warning text-dark fs-6 fw-bold px-3 py-2 rounded">
												#<?php echo $row['candidate_number']; ?>
											</span>
										</td>
										<td class="text-start">
											<div class="fw-bold fs-5"><?php echo htmlspecialchars($row['fullname']); ?></div>
											<small class="text-muted">
												<i class="fa fa-map-marker-alt text-danger me-1 no-print"></i> 
												<?php echo htmlspecialchars($row['represented_location']); ?>
											</small>
										</td>
										<td><?php echo number_format($row['avg_prod'] ?? 0, 2); ?></td>
										<td><?php echo number_format($row['avg_talent'] ?? 0, 2); ?></td>
										<td><?php echo number_format($row['avg_gown'] ?? 0, 2); ?></td>
										<td><?php echo number_format($row['avg_swim'] ?? 0, 2); ?></td>
										<td><?php echo number_format($row['avg_qa'] ?? 0, 2); ?></td>
										<td><?php echo number_format($row['avg_presence'] ?? 0, 2); ?></td>
										<td class="text-warning fw-bold fs-5"><?php echo $final_score; ?></td>
									</tr>
								<?php 
										$rank++;
									endwhile; 
								else: 
								?>
									<tr>
										<td colspan="10" class="text-center text-muted py-5">
											No tabulation records found.
										</td>
									</tr>
								<?php endif; ?>
							</tbody>
						</table>
					</div> <!-- End of table-responsive -->

                    <!-- 🔥 GI-REFACTOR NGA SIGNATURE LAYOUT MATRIX -->
                    <div class="signature-section mt-5">
                        <h5 style="text-align: left; font-weight: bold; border-bottom: 1px solid #000; padding-bottom: 5px; margin-bottom: 20px; color: #000 !important;">
                            Panel of Judges Signatures (Official Verification)
                        </h5>
                        <div class="sig-container">
                            <div class="sig-row">
                                <?php foreach($judges_list as $j_name): ?>
                                    <div class="sig-box">
                                        <div class="sig-line"></div>
                                        <span style="font-weight: bold; color: #000 !important;"><?php echo htmlspecialchars($j_name); ?></span><br>
                                        <small style="color: #444 !important;">Official Pageant Judge</small>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        </div>
                        <div class="sig-container" style="border-top: 1px dashed #ccc;">
                            <div class="sig-row" style="justify-content: space-around !important;">
                                <div class="sig-box" style="width: 45% !important;">
									<div class="sig-line"></div>
                                    <span style="font-weight: bold; color: #000 !important;">Official Tabulator Staff</span><br>
                                    <small style="color: #444 !important;">Data Entry Controller</small>
                                </div>
                                <div class="sig-box" style="width: 45% !important;">
									<div class="sig-line"></div>
                                    <span style="font-weight: bold; color: #000 !important;">Chairman, Board of Judges</span><br>
                                    <small style="color: #444 !important;">Overall Verifier</small>
                                </div>
                            </div>
                        </div>
                    </div> <!-- End of Signatories -->				
				</div> <!-- End of card -->
			</div> <!-- End of Over-all Tab Pane -->
		</div> <!-- End of tab-content -->
	</div> <!-- End of tab-content -->
</div> <!-- End of container main-content -->

<script>
	$(document).ready(function() {
        // A. Automatic tab navigation storage parameters memory controller
        let activeTab = sessionStorage.getItem('activeTabulationTab');
        if (activeTab) {
            let tabTrigger = new bootstrap.Tab(document.querySelector(activeTab));
            tabTrigger.show();
        }

        $('button[data-bs-toggle="tab"]').on('shown.bs.tab', function (e) {
            let targetTabId = '#' + $(e.target).attr('id');
            sessionStorage.setItem('activeTabulationTab', targetTabId);
        });

        // B. Auto refresh intervals (6 seconds frame loop)
        let reloadInterval = setInterval(function() {
            location.reload();
        }, 6000);
        
        // 🔥 KINI ANG GAHOM: Automatic ni nga mokuha sa Text sa ACTIVE TAB para i-inject sa papel!
        window.onbeforeprint = function() {
            clearInterval(reloadInterval); // block structural canvas interrupts
            
            // Kuhaon ang ngalan sa active segment gikan sa active navigation links text selection
            let currentTabLabel = $('.nav-tabs .nav-link.active').text().trim();
            
            // I-inject kini direkta sa imong printed sub-header profile fragment
            $('#printSubTitle').text(currentTabLabel + " Summary Score Sheet");
        };
	});
</script>

<?php include 'footer.php';?>