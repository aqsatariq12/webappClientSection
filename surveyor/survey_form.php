<?php
require_once '../includes/auth.php';
require_once '../includes/db.php';

if ($_SESSION['role'] !== 'surveyor') {
  header("Location: ../index.php");
  exit;
}

$client_id = isset($_GET['client_id']) ? clean($_GET['client_id']) : null;

// Auto-generate ESA Serial
$stmt = $pdo->query("SELECT MAX(id) as last_id FROM surveys");
$last_id = $stmt->fetchColumn() + 1;
$esa_serial = "SURV-" . str_pad($last_id, 3, '0', STR_PAD_LEFT) . "/" . date('m') . "/" . date('Y');

// Surveyor info
$user_id = $_SESSION['user_id'];
$user_name_stmt = $pdo->prepare("SELECT name FROM users WHERE id = ?");
$user_name_stmt->execute([$user_id]);
$user_name = $user_name_stmt->fetchColumn();



$nm_steps = [
  'Application Submission',
  'Survey & Drawing',
  'Estimate Issuance',
  'Estimate & SD Payment Recieved',
  'Test Form Submission (Except Net Metering)',
  'Material & Execution',
  'Energisation',
  'Case Completed',
];
$currentStep = isset($data['net_metering_progress']) && $data['net_metering_progress'] !== ''
  ? (int) $data['net_metering_progress']
  : -1;


?>

<!DOCTYPE html>
<html lang="en">

<head>
  <meta charset="UTF-8">
  <title>New Survey</title>
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

    .dynamic-boxes {
      display: flex;
      flex-wrap: wrap;
      gap: 8px;
    }

    .dynamic-boxes input {
      width: 70px;
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
      color: #ffffffff;
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

    /* connector to the NEXT step */
    .nm-bar {
      position: absolute;
      top: 16px;
      /* vertically centers with the dot */
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
  </style>
</head>

<body>
  <div class="container mt-4">
    <div class="text-center mb-3">
      <img src="../assets/logo.png" alt="Company Logo" class="logo mb-2" style="max-height: 50px;">
    </div>
    <h4 class="mb-4">Survey Form</h4>
    <form action="save_survey.php?client_id=<?= $client_id ?>" method="POST" enctype="multipart/form-data">
      <input type="hidden" name="client_id" value="<?= $client_id ?>">
      <div class="form-section">
        <h5>ESA Details</h5>
        <div class="row mb-2">
          <div class="col-md-4">
            <label>ESA Serial No</label>
            <input type="text" name="esa_serial" value="<?= $esa_serial ?>" class="form-control" readonly>
          </div>
          <div class="col-md-4">
            <label>Surveyor</label>
            <input type="text" class="form-control" value="<?= htmlspecialchars($user_name) ?>" readonly>
            <input type="hidden" name="user_id" value="<?= $user_id ?>">
          </div>
          <div class="col-md-4">
            <label>Date</label>
            <input type="text" name="date" value="<?= date('d/m/Y') ?>" class="form-control" readonly>
          </div>
        </div>
      </div>

      <div class="form-section">
        <h5>System & Bill Info</h5>
        <div class="row mb-3">
          <div class="col-md-4">
            <label>System (KW)</label>
            <input type="number" step="0.01" name="system_kw" class="form-control" required>
          </div>
          <div class="col-md-4">
            <label>Connection Type</label>
            <select name="connection_type" class="form-select">
              <option>Residential</option>
              <option>Commercial</option>
              <option>Industry</option>
              <option>Agriculture</option>
            </select>
          </div>
        </div>

        <div class="row mb-3">
          <div class="col-md-4">
            <label>Service Type</label>
            <select name="service_type" class="form-select">
              <option>Net Metering</option>
              <option>Enhancement</option>
              <option>Others</option>
            </select>
          </div>
          <div class="col-md-4">
            <label>Bill No</label>
            <input type="text" name="bill_no" class="form-control">
          </div>
          <div class="col-md-4">
            <label>Bill Picture</label>
            <input type="file" name="bill_pic" class="form-control">
          </div>
        </div>

        <div class="mb-3">
          <label>Sanction Load</label>
          <input type="text" name="sanction_load" class="form-control">
        </div>
      </div>

      <div class="form-section">
        <h5>Solar Panel Details</h5>
        <div class="row mb-3">
          <div class="col-md-4">
            <label>Panel Model No</label>
            <input type="text" name="panel_model_no" class="form-control"
              value="<?= htmlspecialchars($data['panel_model_no'] ?? '') ?>">
          </div>
          <div class="col-md-4">
            <label>Panel Type</label>
            <select name="panel_type" class="form-select">
              <?php
              $types = ["Poly Cristalline", "Mono Cristalline", "Thin film", "Bifacial", "Others"];
              foreach ($types as $t) {
                $sel = ($data['panel_type'] ?? '') === $t ? 'selected' : '';
                echo "<option $sel>$t</option>";
              }
              ?>
            </select>
          </div>
          <div class="col-md-4">
            <label>Manufacturer</label>
            <select name="panel_manufacturer" class="form-select">
              <?php
              $manufacturers = ["Longi", "Jinko", "Canadian", "Phono", "JA"];
              foreach ($manufacturers as $m) {
                $sel = ($data['panel_manufacturer'] ?? '') === $m ? 'selected' : '';
                echo "<option $sel>$m</option>";
              }
              ?>
            </select>
          </div>
        </div>

        <div class="row mb-3">
          <div class="col-md-4">
            <label>Panel Power (KW)</label>
            <input type="text" name="panel_power" id="panelPower" class="form-control"
              value="<?= htmlspecialchars($data['panel_power'] ?? '') ?>">
          </div>
          <div class="col-md-4">
            <label>No. of Panels</label>
            <input type="number" name="panel_count" id="panelCount" class="form-control" min="1"
              value="<?= htmlspecialchars($data['panel_count'] ?? '') ?>">
          </div>
          <div class="col-md-4">
            <label>Total PV (KW)</label>
            <input type="text" id="totalPV" name="total_pv" class="form-control" readonly value="<?= (isset($data['panel_power'], $data['panel_count']) && $data['panel_power'] && $data['panel_count'])
              ? htmlspecialchars($data['panel_power'] * $data['panel_count'])
              : '' ?>">
          </div>
        </div>
        <div class="row mb-3">
          <div class="col-md-4">
            <label>No. of Boxes</label>
            <input type="number" name="panel_box_count" id="panelBoxCount" class="form-control" min="1"
              value="<?= htmlspecialchars($data['panel_box_count'] ?? '') ?>">
          </div>
        </div>

        <div class="mb-3">
          <label>Panel Box Details</label>
          <div id="panelBoxInputs" class="dynamic-boxes">
            <?php
            $panel_boxes = json_decode($data['panel_boxes'] ?? '[]', true);
            if ($panel_boxes) {
              foreach ($panel_boxes as $i => $val) {
                echo '<input type="text" name="panel_box_' . $i . '" 
                   class="form-control mb-2" 
                   value="' . htmlspecialchars($val) . '">';
              }
            }
            ?>
          </div>
        </div>

        <div class="mb-3">
          <label>Panel Picture</label>
          <input type="file" name="panel_pic" class="form-control">
          <?php if (!empty($data['panel_pic'])): ?>
            <img src="../uploads/<?= htmlspecialchars($data['panel_pic']) ?>" alt="Panel Pic"
              class="img-fluid rounded mt-2" style="max-height:200px;">
          <?php endif; ?>
        </div>
      </div>

      <div class="row g-2 mb-3 align-items-end">
        <div class="col-md-4">
          <label>Inverter Type</label>
          <select id="inverterTypeSelect" name="inverter_type" class="form-control">
            <option value="">-- Select Type --</option>
          </select>
        </div>
        <div class="col-md-4">
          <label>Inverter Phase</label>
          <select id="inverterPhaseSelect" name="inverter_phase" class="form-control">
            <option value="">-- Select Phase --</option>
          </select>
        </div>

        <div class="col-auto" style="min-width: 220px;">
          <label for="inverterCount" class="form-label">No. of Inverters</label>
          <input type="number" name="inverter_count" id="inverterCount" class="form-control" min="1" disabled>
        </div>
      </div>

      <div id="inverterInputs"></div>

      <div class="form-section">
        <h5>Battery Details</h5>
        <div class="form-check form-switch mb-3">
          <input class="form-check-input" type="checkbox" role="switch" id="batteryInstalled" name="battery_installed"
            value="1">
          <label class="form-check-label" for="batteryInstalled">Battery Installed</label>
        </div>

        <div id="batterySection" style="display: none;">
          <div class="mb-3">
            <label>No. of Batteries</label>
            <input type="number" id="batteryCount" class="form-control w-25" min="1" max="" value="">
            <input type="hidden" name="battery_count" id="battery_count" value="0">
          </div>
          <div id="batteryInputs"></div>
        </div>
      </div>

      <div class="form-section">
        <h5>Cable Details</h5>

        <!-- AC Cables -->
        <div class="mb-3">
          <h6>AC Cables</h6>
          <label>No. of AC Cables</label>
          <input type="number" id="acCableCount" class="form-control mb-2 w-25" min="1" max="" value="">
          <input type="hidden" name="ac_cable_count" id="ac_cable_count" value="0">
          <div id="acCableInputs"></div>
        </div>

        <!-- DC Cables -->
        <div class="mb-3">
          <h6>DC Cables</h6>
          <label>No. of DC Cables</label>
          <input type="number" id="dcCableCount" class="form-control mb-2 w-25" min="1" max="" value="">
          <input type="hidden" name="dc_cable_count" id="dc_cable_count" value="0">
          <div id="dcCableInputs"></div>
        </div>

        <!-- Battery Cables -->
        <div class="mb-3">
          <h6>Battery Cables</h6>
          <label>No. of Battery Cables</label>
          <input type="number" id="batteryCableCount" class="form-control mb-2 w-25" min="1" max="" value="">
          <input type="hidden" name="battery_cable_count" id="battery_cable_count" value="0">
          <div id="batteryCableInputs"></div>
        </div>
      </div>


      <div class="form-section">
        <h5>Other Equipment</h5>
        <div class="row">
          <?php
          $others = ['light_arrester', 'smart_controller', 'zero_export', 'light_earthing', 'delta_hub', 'ac_earthing', 'dc_earthing'];
          foreach ($others as $o):
            ?>
            <div class="col-md-4">
              <div class="form-check">
                <input type="checkbox" name="<?= $o ?>" value="1" class="form-check-input" id="<?= $o ?>">
                <label for="<?= $o ?>" class="form-check-label text-capitalize"><?= str_replace('_', ' ', $o) ?></label>
              </div>
            </div>
          <?php endforeach; ?>
        </div>
      </div>

      <div class="form-section">
        <h5>Net Metering Status</h5>

        <select name="net_metering_progress" class="form-select mb-3">
          <option value="">-- Select Status --</option>
          <?php foreach ($nm_steps as $i => $label): ?>
            <option value="<?= $i ?>" <?= ($currentStep === $i ? 'selected' : '') ?>>
              <?= htmlspecialchars($label) ?>
            </option>
          <?php endforeach; ?>
        </select>

        <div class="form-section">
          <h5>Case Tracking</h5>
          <div class="nm-timeline">
            <?php foreach ($nm_steps as $i => $label):
              $state = ($i < $currentStep) ? 'completed' : (($i === $currentStep) ? 'current' : 'upcoming');
              $barCls = ($i < $currentStep) ? 'completed' : 'upcoming'; // connector color to next
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



        <label>Additional Notes</label>
        <textarea name="notes" class="form-control" rows="3"><?= htmlspecialchars($data['notes'] ?? '') ?></textarea>
      </div>

      <div class="d-grid">
        <button class="btn btn-success">Submit Survey</button>
      </div>
    </form>
    <a href="dashboard.php" class="btn btn-outline-secondary mt-3">← Back to Dashboard</a>


    <script>
      function calculateTotalPV() {
        const power = parseFloat(document.getElementById('panelPower').value) || 0;
        const count = parseFloat(document.getElementById('panelCount').value) || 0;
        document.getElementById('totalPV').value = (power * count).toFixed(2);
      }

      document.getElementById('panelPower').addEventListener('input', calculateTotalPV);
      document.getElementById('panelCount').addEventListener('input', calculateTotalPV);

      // =============================
      // Panel Box Inputs
      // =============================
      document.getElementById('panelBoxCount').addEventListener('input', function () {
        const count = parseInt(this.value);
        const container = document.getElementById('panelBoxInputs');
        container.innerHTML = '';
        for (let i = 1; i <= count; i++) {
          const input = document.createElement('input');
          input.name = 'panel_box_' + i;
          input.placeholder = i;
          input.className = 'form-control';
          container.appendChild(input);
        }
      });

      // =============================
      // Inverter Inputs
      // =============================
      document.getElementById('inverterCount').addEventListener('input', function () {
        const count = parseInt(this.value);
        const container = document.getElementById('inverterInputs');
        container.innerHTML = '';

        for (let i = 1; i <= count; i++) {
          const inverterDiv = document.createElement('div');
          inverterDiv.className = 'inverter-group mb-4';
          inverterDiv.innerHTML = `
      <h6>Inverter ${i}</h6>
      <div class="row mb-2">
        <div class="col-md-3"><label>KW</label><input type="text" name="inverter_${i}_kw" class="form-control"></div>
        <div class="col-md-3">
          <label>Manufacturer</label>
          <select name="inverter_${i}_manufacturer" class="form-control form-select manufacturer-dropdown" data-inverter="${i}">
            <option value="">-- Select Manufacturer --</option>
          </select>
        </div>
        <div class="col-md-3">
          <label>Model</label>
          <select name="inverter_${i}_model" class="form-control form-select model-dropdown" id="modelDropdown_${i}">
            <option value="">-- Select Model --</option>
          </select>
        </div>
        <div class="col-md-3"><label>Inverter ID</label><input type="text" name="inverter_${i}_id" class="form-control"></div>
      </div>

      <div class="row mb-2">
        <div class="col-md-6"><label>Password</label><input type="text" name="inverter_${i}_password" class="form-control"></div>
        <div class="col-md-6"><label>Picture</label><input type="file" name="inverter_${i}_pic" class="form-control"></div>
      </div>

      <div class="row mb-2">
        <div class="col-md-4"><label>No. of Panels</label><input type="number" name="inverter_${i}_panel_count" class="form-control"></div>
        <div class="col-md-4"><label>No. of Boxes</label><input type="number" id="inv${i}BoxCount" name="inverter_${i}_box_count" class="form-control"></div>
      </div>

      </div>

      <div class="mb-3">
        <label>Box Details</label>
        <div id="inv${i}BoxInputs" class="dynamic-boxes"></div>
      </div>
    `;

          container.appendChild(inverterDiv);

          // Dropdowns
          const manuSelect = inverterDiv.querySelector('.manufacturer-dropdown');
          const modelSelect = inverterDiv.querySelector('.model-dropdown');
          const typeSelect = inverterDiv.querySelector('.type-dropdown');
          const phaseSelect = inverterDiv.querySelector('.phase-dropdown');

          // Load manufacturer + type + phase
          loadManufacturers(manuSelect);
          loadTypes(typeSelect);
          loadPhases(phaseSelect);

          manuSelect.addEventListener('change', function () {
            loadModels(this.value, modelSelect);
          });

          // Box inputs
          const boxCountInput = inverterDiv.querySelector(`#inv${i}BoxCount`);
          const boxInputs = inverterDiv.querySelector(`#inv${i}BoxInputs`);

          boxCountInput.addEventListener('input', function () {
            const boxCount = parseInt(this.value);
            boxInputs.innerHTML = '';
            for (let j = 1; j <= boxCount; j++) {
              const input = document.createElement('input');
              input.name = `inv${i}_box_${j}`;
              input.placeholder = `Box ${j}`;
              input.className = 'form-control mb-2';
              boxInputs.appendChild(input);
            }
          });
        }
      });

      function loadManufacturers(select) {
        fetch('get_inverter_data.php?type=manufacturers')
          .then(res => res.json())
          .then(data => {
            data.forEach(manu => {
              const opt = document.createElement('option');
              opt.value = manu.id;
              opt.textContent = manu.name;
              select.appendChild(opt);
            });
          });
      }

      function loadModels(manufacturerId, modelSelect) {
        modelSelect.innerHTML = '<option value="">-- Select Model --</option>';
        if (!manufacturerId) return;
        fetch(`get_inverter_data.php?type=models&manufacturer_id=${manufacturerId}`)
          .then(res => res.json())
          .then(data => {
            data.forEach(model => {
              const opt = document.createElement('option');
              opt.value = model.id;
              opt.textContent = model.name;
              modelSelect.appendChild(opt);
            });
          });
      }

      function loadTypes(select) {
        fetch('get_inverter_data.php?type=inv_types')
          .then(res => res.json())
          .then(data => {
            data.forEach(t => {
              const opt = document.createElement('option');
              opt.value = t.id;
              opt.textContent = t.name;
              select.appendChild(opt);
            });
          });
      }

      function loadPhases(select) {
        fetch('get_inverter_data.php?type=inv_phases')
          .then(res => res.json())
          .then(data => {
            data.forEach(p => {
              const opt = document.createElement('option');
              opt.value = p.id;
              opt.textContent = p.name;
              select.appendChild(opt);
            });
          });
      }

      // When user selects inverter phase
      const inverterTypeSelect = document.getElementById('inverterTypeSelect');
      const inverterPhaseSelect = document.getElementById('inverterPhaseSelect');
      const inverterCount = document.getElementById('inverterCount');

      function toggleInverterCount() {
        // Enable only if BOTH have a real value (not empty)
        if (inverterTypeSelect.value && inverterPhaseSelect.value) {
          inverterCount.disabled = false;
        } else {
          inverterCount.disabled = true;
          inverterCount.value = "";
        }
      }

      inverterTypeSelect.addEventListener('change', toggleInverterCount);
      inverterPhaseSelect.addEventListener('change', toggleInverterCount);

      // =============================
      // Battery Inputs
      // =============================
      document.getElementById('batteryInstalled').addEventListener('change', function () {
        document.getElementById('batterySection').style.display = this.checked ? 'block' : 'none';
      });

      document.getElementById('batteryCount').addEventListener('input', function () {
        const count = parseInt(this.value) || 0;
        document.getElementById('battery_count').value = count;

        const container = document.getElementById('batteryInputs');
        container.innerHTML = '';

        for (let i = 1; i <= count; i++) {
          const batteryDiv = document.createElement('div');
          batteryDiv.className = 'card mb-3';
          batteryDiv.innerHTML = `
      <div class="card-header bg-light">Battery ${i}</div>
      <div class="card-body row g-3">
        <div class="col-md-3">
          <select name="battery_${i}_manufacturer" class="form-select battery-manufacturer-dropdown">
            <option value="">-- Select Manufacturer --</option>
          </select>
        </div>
        <div class="col-md-3">
          <select name="battery_${i}_type" class="form-select battery-type-dropdown">
            <option value="">-- Select Type --</option>
          </select>
        </div>
        <div class="col-md-3">
          <select name="battery_${i}_model" class="form-select battery-model-dropdown">
            <option value="">-- Select Model --</option>
          </select>
        </div>
        <div class="col-md-2"><input name="battery_${i}_serial" class="form-control" placeholder="Serial #"></div>
        <div class="col-md-2"><input name="battery_${i}_volt" class="form-control" placeholder="Volt"></div>
        <div class="col-md-2"><input name="battery_${i}_amp" class="form-control" placeholder="Amp"></div>
        <div class="col-md-2"><input name="battery_${i}_cell" class="form-control" placeholder="Cell"></div>
      </div>
    `;
          container.appendChild(batteryDiv);

          // Get dropdown references
          const manufacturerSelect = batteryDiv.querySelector('.battery-manufacturer-dropdown');
          const typeSelect = batteryDiv.querySelector('.battery-type-dropdown');
          const modelSelect = batteryDiv.querySelector('.battery-model-dropdown');

          // Load manufacturers first
          loadBatteryManufacturers(manufacturerSelect);

          // When manufacturer changes -> load types
          manufacturerSelect.addEventListener('change', function () {
            typeSelect.innerHTML = '<option value="">-- Select Type --</option>';
            modelSelect.innerHTML = '<option value="">-- Select Model --</option>';
            if (this.value) {
              loadBatteryTypes(this.value, typeSelect);
            }
          });

          // When type changes -> load models
          typeSelect.addEventListener('change', function () {
            modelSelect.innerHTML = '<option value="">-- Select Model --</option>';
            if (manufacturerSelect.value && this.value) {
              loadBatteryModels(manufacturerSelect.value, this.value, modelSelect);
            }
          });
        }
      });

      // =============================
      // Helper functions
      // =============================
      function loadBatteryManufacturers(select) {
        fetch('get_battery_data.php?type=manufacturers')
          .then(res => res.json())
          .then(data => {
            data.forEach(mf => {
              const opt = document.createElement('option');
              opt.value = mf.id;
              opt.textContent = mf.name;
              select.appendChild(opt);
            });
          })
          .catch(err => console.error('Error loading manufacturers:', err));
      }

      function loadBatteryTypes(manufacturerId, select) {
        fetch(`get_battery_data.php?type=types&manufacturer_id=${manufacturerId}`)
          .then(res => res.json())
          .then(data => {
            data.forEach(bt => {
              const opt = document.createElement('option');
              opt.value = bt.id;
              opt.textContent = bt.name;
              select.appendChild(opt);
            });
          })
          .catch(err => console.error('Error loading types:', err));
      }

      function loadBatteryModels(manufacturerId, typeId, select) {
        fetch(`get_battery_data.php?type=models&manufacturer_id=${manufacturerId}&type_id=${typeId}`)
          .then(res => res.json())
          .then(data => {
            data.forEach(bm => {
              const opt = document.createElement('option');
              opt.value = bm.id;
              opt.textContent = bm.name;
              select.appendChild(opt);
            });
          })
          .catch(err => console.error('Error loading models:', err));
      }

      // =============================
      // Cable Inputs (AC, DC, Battery)
      // =============================
      function setupCableInput(prefix) {
        const countInput = document.getElementById(prefix + 'CableCount');
        const container = document.getElementById(prefix + 'CableInputs');

        // cache lists so we don't re-fetch per row
        let cachedLists = null;

        // fetch lists for this prefix (name/core/mm/feet)
        async function fetchLists() {
          try {
            const [namesRes, coresRes, mmsRes, feetRes] = await Promise.all([
              fetch(`get_cable_data.php?type=name&cable=${prefix}`),
              fetch(`get_cable_data.php?type=core&cable=${prefix}`),
              fetch(`get_cable_data.php?type=mm&cable=${prefix}`),
              fetch(`get_cable_data.php?type=feet&cable=${prefix}`)
            ]);
            cachedLists = {
              name: await namesRes.json(),
              core: await coresRes.json(),
              mm: await mmsRes.json(),
              feet: await feetRes.json()
            };
          } catch (err) {
            console.error('Error fetching cable lists for', prefix, err);
            cachedLists = {
              name: [],
              core: [],
              mm: [],
              feet: []
            };
          }
        }

        // helper that creates a select (or input fallback) inside containerEl
        function createSelectOrInput(containerEl, list, fieldName, placeholder) {
          if (Array.isArray(list) && list.length > 0) {
            const sel = document.createElement('select');
            sel.name = fieldName;
            sel.className = 'form-control mb-1';

            const d = document.createElement('option');
            d.value = '';
            d.textContent = `-- Select ${placeholder} --`;
            sel.appendChild(d);

            // normal DB options
            list.forEach(item => {
              // skip "Custom" from DB, we'll add our own
              if (item.value.toLowerCase() === 'custom') return;

              const o = document.createElement('option');
              o.value = item.id; // send id
              o.textContent = item.value; // display value
              sel.appendChild(o);
            });

            // only add "Custom" for Core & Feet
            if (placeholder === 'Core' || placeholder === 'Feet') {
              const customOpt = document.createElement('option');
              customOpt.value = 'custom';
              customOpt.textContent = 'Custom';
              sel.appendChild(customOpt);

              // hidden custom input
              const customInput = document.createElement('input');
              customInput.type = 'text';
              customInput.name = fieldName + '_custom';
              customInput.className = 'form-control mt-1 d-none';
              customInput.placeholder = `Enter custom ${placeholder}`;

              // toggle show/hide
              sel.addEventListener('change', function () {
                if (this.value === 'custom') {
                  customInput.classList.remove('d-none');
                } else {
                  customInput.classList.add('d-none');
                  customInput.value = '';
                }
              });

              containerEl.appendChild(sel);
              containerEl.appendChild(customInput);
            } else {
              // for Name & MM (normal only)
              containerEl.appendChild(sel);
            }

          } else {
            // fallback input if no options
            const inp = document.createElement('input');
            inp.name = fieldName;
            inp.className = 'form-control';
            inp.placeholder = placeholder;
            containerEl.appendChild(inp);
          }
        }

        // render rows
        async function renderCables(count) {
          container.innerHTML = '';
          if (!count || count <= 0) return;

          if (!cachedLists) {
            await fetchLists();
          }

          for (let i = 1; i <= count; i++) {
            const row = document.createElement('div');
            row.className = 'row g-2 mb-2';
            row.innerHTML = `
        <div class="col-md-2 name-col"></div>
        <div class="col-md-2 core-col"></div>
        <div class="col-md-2 mm-col"></div>
        <div class="col-md-2 feet-col"></div>
        <div class="col-md-2 length-col"></div>
      `;
            container.appendChild(row);

            const nameContainer = row.querySelector('.name-col');
            const coreContainer = row.querySelector('.core-col');
            const mmContainer = row.querySelector('.mm-col');
            const feetContainer = row.querySelector('.feet-col');
            const lengthContainer = row.querySelector('.length-col');

            // create selects or fallbacks
            createSelectOrInput(nameContainer, cachedLists.name, `${prefix}_cable_${i}_name`, 'Name');
            createSelectOrInput(coreContainer, cachedLists.core, `${prefix}_cable_${i}_core`, 'Core');
            createSelectOrInput(mmContainer, cachedLists.mm, `${prefix}_cable_${i}_mm`, 'MM');
            createSelectOrInput(feetContainer, cachedLists.feet, `${prefix}_cable_${i}_feet`, 'Feet');

            // length stays as text/number input (you can change type if you want)
            const lenInput = document.createElement('input');
            lenInput.name = `${prefix}_cable_${i}_length`;
            lenInput.className = 'form-control';
            lenInput.placeholder = 'Length';
            lengthContainer.appendChild(lenInput);
          }
        }

        // initial render (if value exists)
        renderCables(parseInt(countInput.value) || 0);

        // update on change
        countInput.addEventListener('input', function () {
          renderCables(parseInt(this.value) || 0);
          document.getElementById(prefix + '_cable_count').value = this.value || 0;
        });
      }

      // initialize for all three
      setupCableInput('ac');
      setupCableInput('dc');
      setupCableInput('battery');

      // Load global inverter type & phase dropdowns once page loads
      document.addEventListener("DOMContentLoaded", function () {
        const select = document.querySelector("select[name='net_metering_progress']");
        const steps = document.querySelectorAll(".nm-step");

        let currentStep = parseInt(select.value) || -1; // -1 = new user (not saved yet)

        function updateTimeline(index) {
          steps.forEach((step, i) => {
            step.classList.remove("completed", "current", "upcoming");
            if (i < index) {
              step.classList.add("completed");
            } else if (i === index) {
              step.classList.add("current");
            } else {
              step.classList.add("upcoming");
            }
            step.querySelector(".nm-dot").textContent = "✓";

            const bar = step.querySelector(".nm-bar");
            if (bar) {
              bar.className = "nm-bar " + (i < index ? "completed" : "upcoming");
            }
          });
        }

        // initialize
        updateTimeline(currentStep);

        select.addEventListener("change", function () {
          const newStep = parseInt(this.value);

          if (isNaN(newStep)) return;

          if (currentStep === -1) {
            // new user: only allow selecting 0 (first step)
            if (newStep === 0) {
              currentStep = 0;
              updateTimeline(currentStep);
            } else {
              alert("Start with the first step and save the survey to continue.");
              this.value = ""; // reset back
            }
          } else {
            // existing flow is handled in edit_survey.php
            this.value = currentStep;
          }
        });
      });
      // Load inverter types and phases when page is ready
      document.addEventListener('DOMContentLoaded', function () {
        loadTypes(inverterTypeSelect);
        loadPhases(inverterPhaseSelect);
      });

    </script>
</body>