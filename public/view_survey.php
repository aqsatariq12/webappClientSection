<?php
require_once '../includes/db.php';

// Public view should work with token (no admin login)
$survey_id = 0;
if (isset($_GET['token'])) {
	$stmt = $pdo->prepare("
        SELECT s.*, c.name AS client_name, c.cnic, c.address, c.contact_no_1, c.email, c.whatsapp,
		c.location_map,u.name AS surveyor_name
    	FROM surveys s
        JOIN clients c ON s.client_id = c.id
		JOIN users u ON s.user_id = u.id
        WHERE s.token = ?
    ");
	$stmt->execute([$_GET['token']]);
	$data = $stmt->fetch(PDO::FETCH_ASSOC);
} else {
	echo "Invalid or missing token.";
	exit;
}

if (!$data) {
	echo "Survey not found.";
	exit;
}

// Lookup maps for batteries
$manuMap = [];
$typeMap = [];
$modelMap = [];
$stmt = $pdo->query("SELECT id, name FROM manufacturer_battery");
foreach ($stmt as $row)
	$manuMap[$row['id']] = $row['name'];
$stmt = $pdo->query("SELECT id, name FROM type_battery");
foreach ($stmt as $row)
	$typeMap[$row['id']] = $row['name'];
$stmt = $pdo->query("SELECT id, name FROM model_battery");
foreach ($stmt as $row)
	$modelMap[$row['id']] = $row['name'];

// Cable name map
$cableNameMap = [];
$stmt = $pdo->query("SELECT id, value FROM cable_names");
foreach ($stmt as $row)
	$cableNameMap[$row['id']] = $row['value'];

// Attachment fetch
$attachmentStmt = $pdo->prepare("SELECT * FROM survey_images WHERE survey_id = ?");
$attachmentStmt->execute([$data['id']]);
$attachments = $attachmentStmt->fetchAll(PDO::FETCH_ASSOC);

// Inverter lookup maps
$invManuMap = [];
$stmt = $pdo->query("SELECT id, name FROM manufacturer_invertor");
foreach ($stmt as $row)
	$invManuMap[$row['id']] = $row['name'];

$invModelMap = [];
$stmt = $pdo->query("SELECT id, name FROM model_invertor");
foreach ($stmt as $row)
	$invModelMap[$row['id']] = $row['name'];

$invTypeMap = [];
$stmt = $pdo->query("SELECT id, name FROM type_invertor");
foreach ($stmt as $row)
	$invTypeMap[$row['id']] = $row['name'];

$invPhaseMap = [];
$stmt = $pdo->query("SELECT id, name FROM phase_type_inverter");
foreach ($stmt as $row)
	$invPhaseMap[$row['id']] = $row['name'];

// Decode JSON fields
$invData = json_decode($data['inverter_details'] ?? '[]', true);
$batteries = json_decode($data['battery_details'] ?? '[]', true);
?>
<!DOCTYPE html>
<html lang="en">

<head>
	<meta charset="UTF-8">
	<title>Survey Details</title>
	<meta name="viewport" content="width=device-width, initial-scale=1">
	<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
	<style>
		.form-section {
			border: 1px solid #ddd;
			padding: 15px;
			margin-bottom: 20px;
			border-radius: 5px;
		}

		.form-section h5 {
			margin-bottom: 15px;
			background: #f8f9fa;
			padding: 10px;
			border-radius: 4px;
		}

		label {
			font-weight: bold;
		}

		.nm-timeline {
			display: flex;
			align-items: flex-start;
			gap: 0;
			margin: 14px 0 22px;
			padding: 14px 16px;
			border: 1px solid #e9ecef;
			border-radius: 12px;
			background: #fff;
		}

		.nm-step {
			flex: 1;
			display: flex;
			flex-direction: column;
			align-items: center;
			position: relative;
			min-width: 0;
		}

		.nm-dot {
			width: 32px;
			height: 32px;
			border-radius: 50%;
			border: 3px solid #6c757d;
			background: #6c757d;
			display: flex;
			align-items: center;
			justify-content: center;
			font-weight: 700;
			font-size: 14px;
			color: #fff;
			z-index: 2;
		}

		.nm-label {
			margin-top: 6px;
			font-size: 12px;
			text-align: center;
			line-height: 1.2;
			color: #495057;
			padding: 0 6px;
		}

		.nm-bar {
			position: absolute;
			top: 16px;
			left: 50%;
			right: -50%;
			height: 4px;
			background: #6c757d;
			z-index: 1;
		}

		.nm-step:last-child .nm-bar {
			display: none;
		}

		/* states */
		.nm-step.completed .nm-dot {
			background: #28a745;
			border-color: #28a745;
		}

		.nm-step.current .nm-dot {
			background: #ffc107;
			border-color: #ffc107;
			color: #212529;
		}

		.nm-step.upcoming .nm-dot {
			background: #6c757d;
			border-color: #6c757d;
		}

		.nm-bar.completed {
			background: #28a745;
		}

		.nm-bar.upcoming {
			background: #6c757d;
		}

		@media print {

			#printBtn,
			.btn-outline-secondary {
				display: none !important;
			}

			/* @page {
				size: landscape;
			} */

		}
	</style>
</head>

<body>
	<div class="container mt-4">
		<div class="text-center mb-3">
			<img src="../assets/logo.png" alt="Company Logo" class="logo mb-2" style="max-height: 50px;">
		</div>
		<h4 class="mb-4">Survey Details</h4>

		<!-- ESA Details -->
		<div class="form-section">
			<h5>ESA Details</h5>
			<div class="row mb-2">
				<div class="col-md-4"><label>ESA Serial</label>
					<p><?= htmlspecialchars($data['esa_serial']) ?></p>
				</div>
				<div class="col-md-4"><label>Surveyor ID / Name</label>
					<p><?= htmlspecialchars($data['user_id']) ?> / <?= htmlspecialchars($data['surveyor_name']) ?></p>
				</div>
				<div class="col-md-4"><label>Date</label>
					<p><?= date('d/m/Y', strtotime($data['created_at'])) ?></p>
				</div>
			</div>
		</div>

		<!-- Client Info -->
		<div class="form-section">
			<h5>Client Details</h5>
			<div class="row mb-2">
				<div class="col-md-4"><label>Name</label>
					<p><?= htmlspecialchars($data['client_name']) ?></p>
				</div>
				<div class="col-md-4"><label>CNIC</label>
					<p><?= htmlspecialchars($data['cnic']) ?></p>
				</div>
				<div class="col-md-4"><label>Address</label>
					<p><?= htmlspecialchars($data['address']) ?></p>
				</div>
				<div class="col-md-4">
					<label>Email Address</label>
					<p><?= htmlspecialchars($data['email']) ?></p>
				</div>
				<div class="col-md-4"><label>Contact</label>
					<p><?= htmlspecialchars($data['contact_no_1']) ?></p>
				</div>
				<div class="col-md-4">
					<label>WhatsApp</label>
					<p><?= htmlspecialchars($data['whatsapp']) ?></p>
				</div>
				<!-- Location Map -->
				<div class="col-md-12 mt-1">
					<label>Location Map</label>
					<?php if (!empty($data['location_map'])): ?>
						<div class="p-2 border rounded bg-light mt-2 " style="width:160px;">
							<a href="https://www.google.com/maps/search/<?= urlencode($data['location_map']) ?>"
								target="_blank" class="btn btn-primary btn-sm">
								Open Location Map
							</a>
						</div>
					<?php else: ?>
						<p class="text-muted">No map link available</p>
					<?php endif; ?>
				</div>
			</div>
		</div>


		<!-- System & Bill Info -->
		<div class="form-section">
			<h5>System & Bill Info</h5>
			<div class="row mb-3">
				<div class="col-md-4"><label>Connection Type</label>
					<p><?= htmlspecialchars($data['connection_type']) ?></p>
				</div>
				<div class="col-md-4"><label>Service Type</label>
					<p><?= htmlspecialchars($data['service_type']) ?></p>
				</div>
			</div>
			<div class="row mb-3">
				<div class="col-md-4"><label>KW</label>
					<p><?= htmlspecialchars($data['system_kw']) ?></p>
				</div>
				<div class="col-md-4"><label>Bill No</label>
					<p><?= htmlspecialchars($data['bill_no']) ?></p>
				</div>
				<div class="col-md-4"><label>Sanction Load</label>
					<p><?= htmlspecialchars($data['sanction_load']) ?></p>
				</div>
			</div>
			<div class="mb-3">
				<label>Bill Picture</label><br>
				<?php if ($data['bill_pic']): ?>
					<img src="../uploads/<?= htmlspecialchars($data['bill_pic']) ?>" class="img-fluid rounded"
						style="max-height: 200px;">
				<?php else: ?>
					<p>No file uploaded</p><?php endif; ?>
			</div>
		</div>

		<!-- Panel Details -->
		<div class="form-section">
			<h5>Solar Panel Details</h5>
			<div class="row mb-3">
				<div class="col-md-4"><label>Type</label>
					<p><?= htmlspecialchars($data['panel_type']) ?></p>
				</div>
				<div class="col-md-4"><label>Model No</label>
					<p><?= htmlspecialchars($data['panel_model_no']) ?></p>
				</div>
				<div class="col-md-4"><label>Manufacturer</label>
					<p><?= htmlspecialchars($data['panel_manufacturer']) ?></p>
				</div>
			</div>
			<div class="row mb-3">
				<div class="col-md-4">
					<label>Power (KW)</label>
					<p><?= htmlspecialchars($data['panel_power']) ?></p>
				</div>
				<div class="col-md-4">
					<label>No. of Panels</label>
					<p><?= htmlspecialchars($data['panel_count']) ?></p>
				</div>
				<div class="col-md-4">
					<label>Total PV (KW)</label>
					<p>
						<?php
						$total_pv = (isset($data['panel_power'], $data['panel_count']) && $data['panel_power'] && $data['panel_count'])
							? $data['panel_power'] * $data['panel_count']
							: 0;
						echo htmlspecialchars(number_format($total_pv, 2));
						?>
					</p>
				</div>
			</div>
			<div class="row mb-3">
				<div class="col-md-4">
					<label>No. of Strings</label>
					<p><?= htmlspecialchars($data['panel_box_count']) ?></p>
				</div>
			</div>
			<div class="mb-3">
				<label>Strings Value</label><br>
				<?php $panel_boxes = json_decode($data['panel_boxes'], true) ?? [];
				foreach ($panel_boxes as $val) {
					echo '<span class="badge bg-secondary me-1 mb-1">' . htmlspecialchars($val) . '</span>';
				} ?>
			</div>
			<div class="mb-3">
				<label>Panel Picture</label><br>
				<?php if ($data['panel_pic']): ?>
					<img src="../uploads/<?= htmlspecialchars($data['panel_pic']) ?>" class="img-fluid rounded"
						style="max-height: 200px;">
				<?php else: ?>
					<p>No image uploaded.</p><?php endif; ?>
			</div>
		</div>

		<!-- Inverter Details -->
		<?php if (!empty($invData['inverters'])): ?>
			<div class="form-section">
				<h5>Inverter Details</h5>
				<div class="mb-2">
					<strong>Type:</strong>
					<?= htmlspecialchars($invTypeMap[$invData['type']] ?? $invData['type'] ?? '') ?><br>
					<strong>Phase:</strong>
					<?= htmlspecialchars($invPhaseMap[$invData['phase']] ?? $invData['phase'] ?? '') ?>
				</div>
				<?php foreach ($invData['inverters'] as $i => $inv): ?>
					<div class="card mb-3">
						<div class="card-header">Inverter <?= $i + 1 ?></div>
						<div class="card-body row g-2">
							<div class="col-md-2"><label>KW</label>
								<p><?= htmlspecialchars($inv['kw'] ?? '') ?></p>
							</div>
							<div class="col-md-3"><label>Manufacturer</label>
								<p><?= htmlspecialchars($invManuMap[$inv['manufacturer']] ?? $inv['manufacturer'] ?? '') ?></p>
							</div>
							<div class="col-md-3"><label>Model</label>
								<p><?= htmlspecialchars($invModelMap[$inv['model']] ?? $inv['model'] ?? '') ?></p>
							</div>
							<div class="col-md-2"><label>ID</label>
								<p><?= htmlspecialchars($inv['id'] ?? '') ?></p>
							</div>
							<div class="col-md-2"><label>Password</label>
								<p><?= htmlspecialchars($inv['password'] ?? '') ?></p>
							</div>
							<?php if (!empty($inv['pic'])): ?>
								<div class="col-md-3">
									<label>Picture</label><br>
									<img src="../uploads/<?= htmlspecialchars($inv['pic']) ?>"
										style="max-width:100px; max-height:100px;">
								</div>
							<?php endif; ?>
							<div class="col-md-2"><label>No. of Panels</label>
								<p><?= htmlspecialchars($inv['panel_count'] ?? '') ?></p>
							</div>
							<div class="col-md-2"><label>No. of Boxes</label>
								<p><?= htmlspecialchars($inv['box_count'] ?? '') ?></p>
							</div>
							<?php if (!empty($inv['boxes']) && is_array($inv['boxes'])): ?>
								<div class="col-12 mb-2">
									<label>Box Details</label><br>
									<?php foreach ($inv['boxes'] as $boxVal): ?>
										<span class="badge bg-secondary me-1 mb-1"><?= htmlspecialchars($boxVal) ?></span>
									<?php endforeach; ?>
								</div>
							<?php endif; ?>
						</div>
					</div>
				<?php endforeach; ?>
			</div>
		<?php endif; ?>

		<!-- Battery Details -->
		<?php if (!empty($batteries)): ?>
			<div class="form-section">
				<h5>Battery Details</h5>
				<?php foreach ($batteries as $i => $battery): ?>
					<div class="card mb-3">
						<div class="card-header">Battery <?= $i + 1 ?></div>
						<div class="card-body row g-2">
							<div class="col-md-3"><label>Manufacturer</label>
								<p><?= htmlspecialchars($manuMap[$battery['manufacturer']] ?? '') ?></p>
							</div>
							<div class="col-md-3"><label>Type</label>
								<p><?= htmlspecialchars($typeMap[$battery['type']] ?? '') ?></p>
							</div>
							<div class="col-md-3"><label>Model</label>
								<p><?= htmlspecialchars($modelMap[$battery['model']] ?? '') ?></p>
							</div>
							<div class="col-md-3"><label>Serial #</label>
								<p><?= htmlspecialchars($battery['serial'] ?? '') ?></p>
							</div>
							<div class="col-md-2"><label>Volt</label>
								<p><?= htmlspecialchars($battery['volt'] ?? '') ?></p>
							</div>
							<div class="col-md-2"><label>Amp</label>
								<p><?= htmlspecialchars($battery['amp'] ?? '') ?></p>
							</div>
							<div class="col-md-2"><label>Cell</label>
								<p><?= htmlspecialchars($battery['cell'] ?? '') ?></p>
							</div>
						</div>
					</div>
				<?php endforeach; ?>
			</div>
		<?php endif; ?>



		<!-- Cable Details -->
		<?php
		$hasCable = false;
		foreach (['ac', 'dc', 'battery'] as $key) {
			if (!empty(json_decode($data[$key . '_cables'] ?? '[]', true))) {
				$hasCable = true;
				break;
			}
		}
		if ($hasCable):
		?>
			<div class="form-section">
				<h5>Cable Details</h5>
				<?php foreach (['ac' => 'AC', 'dc' => 'DC', 'battery' => 'Battery'] as $key => $label):
					$cables = json_decode($data[$key . '_cables'] ?? '[]', true);
					if (empty($cables))
						continue;
				?>
					<h6><?= $label ?> Cables</h6>
					<?php foreach ($cables as $cable): ?>
						<div class="row mb-2">
							<div class="col-md-2"><label>Name</label>
								<p><?= htmlspecialchars($cableNameMap[$cable['name']] ?? '') ?></p>
							</div>
							<!-- Core -->
							<div class="col-md-2">
								<label>Core</label>
								<p>
									<?php if (($cable['core'] ?? '') === 'custom'): ?>
										<?= htmlspecialchars($cable['core_custom'] ?? '') ?>
									<?php else: ?>
										<?php
										if (($cable['core'] ?? '') === 'custom') {
											echo htmlspecialchars($cable['core_custom'] ?? '');
										} elseif (!empty($cable['core'])) {
											$stmt = $pdo->prepare("SELECT value FROM cable_cores WHERE id = ?");
											$stmt->execute([$cable['core']]);
											echo htmlspecialchars($stmt->fetchColumn() ?: '');
										}
										?>

									<?php endif; ?>
								</p>
							</div>
							<div class="col-md-2"><label>MM</label>
								<p><?= htmlspecialchars($cable['mm'] ?? '') ?></p>
							</div>
							<div class="col-md-2">
								<label>Feet</label>
								<p>
									<?php if (($cable['feet'] ?? '') === 'custom'): ?>
										<?= htmlspecialchars($cable['feet_custom'] ?? '') ?>
									<?php else: ?>
										<?php
										if (($cable['feet'] ?? '') === 'custom') {
											echo htmlspecialchars($cable['feet_custom'] ?? '');
										} elseif (!empty($cable['feet'])) {
											$stmt = $pdo->prepare("SELECT value FROM cable_feet WHERE id = ?");
											$stmt->execute([$cable['feet']]);
											echo htmlspecialchars($stmt->fetchColumn() ?: '');
										}
										?>

									<?php endif; ?>
								</p>
							</div>
							<div class="col-md-2"><label>Length</label>
								<p><?= htmlspecialchars($cable['length'] ?? '') ?></p>
							</div>
						</div>
				<?php endforeach;
				endforeach; ?>
			</div>
		<?php endif; ?>

		<!-- Other Equipment -->
		<div class="form-section">
			<h5>Other Equipment</h5>
			<div class="row">
				<?php
				// Same list as in survey_form.php
				$others = ['light_arrester', 'smart_controller', 'zero_export', 'light_earthing', 'delta_hub', 'ac_earthing', 'dc_earthing'];

				foreach ($others as $o):
					$isChecked = !empty($data[$o]); // Check if value was stored as 1 in DB
				?>
					<div class="col-md-4">
						<div class="form-check">
							<input type="checkbox" class="form-check-input" id="<?= $o ?>" <?= $isChecked ? 'checked' : '' ?>
								disabled>
							<label for="<?= $o ?>" class="form-check-label text-capitalize"
								style="color:black; font-weight:500;">
								<?= str_replace('_', ' ', $o) ?>
							</label>
						</div>
					</div>
				<?php endforeach; ?>
			</div>
		</div>


		<!-- Net Metering -->
		<div class="form-section">
			<h5>Net Metering Status</h5>
			<?php
			// Define the ordered steps
			$nm_steps = [
				'Application Submission',
				'Survey & Drawing',
				'Estimate Issuance',
				'Estimate & SD Payment Recieved',
				'Test Form Submission (Except Net Metering)',
				'Material & Execution',
				'Energisation',
				'Case Completed'
			];

			$currentStep = ($data['net_metering_progress'] !== null && $data['net_metering_progress'] !== '')
				? (int) $data['net_metering_progress']
				: -1;
			?>
			<div class="nm-timeline">
				<?php foreach ($nm_steps as $i => $label):
					$state = ($i < $currentStep) ? 'completed' : (($i === $currentStep) ? 'current' : 'upcoming');
					$barCls = ($i < $currentStep) ? 'completed' : 'upcoming';
				?>
					<div class="nm-step <?= $state ?>">
						<div class="nm-dot">✓</div>
						<div class="nm-label"><?= htmlspecialchars($label) ?></div>
						<?php if ($i < count($nm_steps) - 1): ?>
							<div class="nm-bar <?= $barCls ?>"></div>
						<?php endif; ?>
					</div>
				<?php endforeach; ?>
			</div>
		</div>


		<!-- Other Attachments -->
		<div class="form-section">
			<h5>Other Attachments</h5>
			<?php if ($attachments): ?>
				<ul class="list-group">
					<?php foreach ($attachments as $file): ?>
						<li class="list-group-item d-flex justify-content-between align-items-center">
							<?= htmlspecialchars($file['image_type']) ?>
							<a href="../uploads/<?= urlencode($file['image_path']) ?>" class="btn btn-sm btn-outline-primary"
								target="_blank">View</a>
						</li>
					<?php endforeach; ?>
				</ul>
			<?php else: ?>
				<p>No additional attachments found.</p><?php endif; ?>
		</div>

		<!-- Notes -->
		<div class="form-section">
			<h5>Additional Notes</h5>
			<p><?= nl2br(htmlspecialchars($data['notes'])) ?></p>
		</div>

		<button id="printBtn" onclick="window.print()"
			class="btn btn-primary mt-3" style="width: 150px;">
			🖨️ Print
		</button>
	</div>
</body>

</html>