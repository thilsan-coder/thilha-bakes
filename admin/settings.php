<?php
require_once '../includes/db.php';
if (!isLoggedIn() || !isAdmin()) redirect('../login.php');

// create settings table if not exists
mysqli_query($conn, "CREATE TABLE IF NOT EXISTS settings (
    id INT AUTO_INCREMENT PRIMARY KEY,
    setting_key VARCHAR(100) UNIQUE NOT NULL,
    setting_value TEXT,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
)");

// insert defaults if empty
$defaults = [
    'bakery_name'    => 'Thilha Divine Bakes',
    'bakery_tagline' => 'Divine Bakes',
    'bakery_address' => 'Central Camp, Ampara, Sri Lanka',
    'bakery_phone'   => '+94 77 XXX XXXX',
    'bakery_email'   => 'info@thilhabakes.com',
    'bakery_hours'   => 'Mon-Sat: 7AM - 8PM',
    'currency'       => 'Rs.',
    'tax_percent'    => '0',
    'delivery_fee'   => '150',
    'min_order'      => '500',
    'loyalty_rate'   => '10',
    'whatsapp'       => '+94 77 XXX XXXX',
];

foreach ($defaults as $key => $val) {
    mysqli_query($conn,
        "INSERT IGNORE INTO settings (setting_key, setting_value)
         VALUES ('$key', '$val')");
}

// save settings
$success = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    foreach ($_POST as $key => $val) {
        $key = mysqli_real_escape_string($conn, $key);
        $val = mysqli_real_escape_string($conn, $val);
        mysqli_query($conn,
            "UPDATE settings SET setting_value='$val'
             WHERE setting_key='$key'");
    }
    $success = 'Settings saved successfully!';
}

// get all settings
$result = mysqli_query($conn, "SELECT * FROM settings");
$s = [];
while ($row = mysqli_fetch_assoc($result)) {
    $s[$row['setting_key']] = $row['setting_value'];
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>Settings — Thilha Divine Bakes</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
<link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css" rel="stylesheet">
<style>
  *{box-sizing:border-box;}
  body{background:#f5f0eb;font-family:'Segoe UI',sans-serif;color:#2a1a10;margin:0;}

  
  
  
  
  
  
  
  
  

  .main{margin-left:240px;padding:30px;}
  .page-title{font-size:22px;font-weight:700;color:#1a0a0f;margin-bottom:2px;}
  .page-sub{font-size:13px;color:#8a6a4a;margin-bottom:24px;}

  .card-box{background:#fff;border:1px solid #e8d8c0;border-radius:14px;padding:24px;margin-bottom:20px;}
  .card-title{font-size:16px;font-weight:600;color:#1a0a0f;margin-bottom:18px;padding-bottom:12px;border-bottom:1px solid #f0e4d0;display:flex;align-items:center;gap:8px;}

  .form-label{font-size:13px;color:#8a6a4a;font-weight:600;margin-bottom:5px;}
  .form-control,.form-select{border:1px solid #e8d8c0;border-radius:8px;font-size:14px;color:#2a1a10;padding:10px 14px;}
  .form-control:focus,.form-select:focus{border-color:#c9973a;box-shadow:0 0 0 3px rgba(201,151,58,0.15);outline:none;}

  .btn-save{background:#c9973a;border:none;border-radius:8px;color:#fff;font-weight:700;padding:12px 32px;font-size:14px;cursor:pointer;display:inline-flex;align-items:center;gap:8px;}
  .btn-save:hover{background:#a07828;}

  .alert-success{background:#e8f5e9;border:1px solid #a5d6a7;border-left:4px solid #2e7d32;border-radius:10px;padding:12px 16px;font-size:13px;color:#1b5e20;margin-bottom:20px;display:flex;align-items:center;gap:8px;}

  .preview-card{background:#1a0a0f;border-radius:12px;padding:20px;text-align:center;margin-bottom:20px;}
  .preview-name{font-family:Georgia,serif;font-size:24px;font-weight:bold;color:#e8c870;letter-spacing:3px;}
  .preview-sub{font-family:Georgia,serif;font-size:12px;color:#c9973a;letter-spacing:4px;margin-top:4px;}
  .preview-loc{font-size:11px;color:#7a5a28;margin-top:4px;}

  .setting-group{background:#fdf5ec;border-radius:10px;padding:16px;margin-bottom:16px;}
  .setting-group-title{font-size:12px;font-weight:700;color:#c9973a;letter-spacing:1px;text-transform:uppercase;margin-bottom:12px;display:flex;align-items:center;gap:6px;}
</style>
</head>
<body>

<?php include '../includes/sidebar.php'; ?>

<!-- MAIN -->
<div class="main">
  <div class="page-title"><i class="bi bi-gear-fill me-2" style="color:#c9973a;"></i>Settings</div>
  <div class="page-sub">Manage bakery system settings — <?= date('l, d F Y') ?></div>

  <?php if ($success): ?>
  <div class="alert-success">
    <i class="bi bi-check-circle-fill"></i><?= $success ?>
  </div>
  <?php endif; ?>

  <!-- PREVIEW -->
  <div class="preview-card">
    <div class="preview-name" id="prev-name"><?= $s['bakery_name'] ?></div>
    <div class="preview-sub" id="prev-tagline"><?= $s['bakery_tagline'] ?></div>
    <div class="preview-loc" id="prev-address"><?= $s['bakery_address'] ?></div>
  </div>

  <form method="POST">
  <div class="row g-4">
    <div class="col-md-6">

      <!-- BAKERY INFO -->
      <div class="card-box">
        <div class="card-title">
          <i class="bi bi-shop" style="color:#c9973a;"></i> Bakery Information
        </div>
        <div class="setting-group">
          <div class="setting-group-title">
            <i class="bi bi-info-circle"></i> Basic Info
          </div>
          <div class="mb-3">
            <label class="form-label">Bakery Name</label>
            <input type="text" name="bakery_name" class="form-control"
                   value="<?= htmlspecialchars($s['bakery_name']) ?>"
                   oninput="document.getElementById('prev-name').textContent=this.value">
          </div>
          <div class="mb-3">
            <label class="form-label">Tagline</label>
            <input type="text" name="bakery_tagline" class="form-control"
                   value="<?= htmlspecialchars($s['bakery_tagline']) ?>"
                   oninput="document.getElementById('prev-tagline').textContent=this.value">
          </div>
          <div class="mb-0">
            <label class="form-label">Address</label>
            <input type="text" name="bakery_address" class="form-control"
                   value="<?= htmlspecialchars($s['bakery_address']) ?>"
                   oninput="document.getElementById('prev-address').textContent=this.value">
          </div>
        </div>

        <div class="setting-group">
          <div class="setting-group-title">
            <i class="bi bi-telephone"></i> Contact
          </div>
          <div class="mb-3">
            <label class="form-label">Phone Number</label>
            <input type="text" name="bakery_phone" class="form-control"
                   value="<?= htmlspecialchars($s['bakery_phone']) ?>">
          </div>
          <div class="mb-3">
            <label class="form-label">WhatsApp Number</label>
            <input type="text" name="whatsapp" class="form-control"
                   value="<?= htmlspecialchars($s['whatsapp']) ?>">
          </div>
          <div class="mb-0">
            <label class="form-label">Email</label>
            <input type="email" name="bakery_email" class="form-control"
                   value="<?= htmlspecialchars($s['bakery_email']) ?>">
          </div>
        </div>

        <div class="setting-group" style="margin-bottom:0;">
          <div class="setting-group-title">
            <i class="bi bi-clock"></i> Business Hours
          </div>
          <div class="mb-0">
            <label class="form-label">Opening Hours</label>
            <input type="text" name="bakery_hours" class="form-control"
                   value="<?= htmlspecialchars($s['bakery_hours']) ?>"
                   placeholder="e.g. Mon-Sat: 7AM - 8PM">
          </div>
        </div>
      </div>

    </div>
    <div class="col-md-6">

      <!-- BUSINESS SETTINGS -->
      <div class="card-box">
        <div class="card-title">
          <i class="bi bi-sliders" style="color:#c9973a;"></i> Business Settings
        </div>

        <div class="setting-group">
          <div class="setting-group-title">
            <i class="bi bi-cash-coin"></i> Pricing & Tax
          </div>
          <div class="mb-3">
            <label class="form-label">Currency Symbol</label>
            <input type="text" name="currency" class="form-control"
                   value="<?= htmlspecialchars($s['currency']) ?>">
          </div>
          <div class="mb-3">
            <label class="form-label">Tax Percentage (%)</label>
            <input type="number" name="tax_percent" class="form-control"
                   value="<?= $s['tax_percent'] ?>" min="0" max="100" step="0.5">
          </div>
          <div class="mb-0">
            <label class="form-label">Minimum Order Amount (Rs.)</label>
            <input type="number" name="min_order" class="form-control"
                   value="<?= $s['min_order'] ?>" min="0">
          </div>
        </div>

        <div class="setting-group">
          <div class="setting-group-title">
            <i class="bi bi-truck"></i> Delivery
          </div>
          <div class="mb-0">
            <label class="form-label">Default Delivery Fee (Rs.)</label>
            <input type="number" name="delivery_fee" class="form-control"
                   value="<?= $s['delivery_fee'] ?>" min="0">
          </div>
        </div>

        <div class="setting-group" style="margin-bottom:0;">
          <div class="setting-group-title">
            <i class="bi bi-star"></i> Loyalty Program
          </div>
          <div class="mb-0">
            <label class="form-label">Points per Rs. Spent
              <small style="color:#8a6a4a;font-weight:400;">(e.g. 1 point per Rs.10)</small>
            </label>
            <input type="number" name="loyalty_rate" class="form-control"
                   value="<?= $s['loyalty_rate'] ?>" min="1">
          </div>
        </div>
      </div>

      <!-- QUICK STATS -->
      <div class="card-box">
        <div class="card-title">
          <i class="bi bi-bar-chart" style="color:#c9973a;"></i> System Info
        </div>
        <?php
        $total_products = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as c FROM products"))['c'];
        $total_users    = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as c FROM users"))['c'];
        $total_orders   = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as c FROM orders"))['c'];
        $total_bills    = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as c FROM bills"))['c'];
        ?>
        <div style="display:grid;grid-template-columns:1fr 1fr;gap:10px;">
          <div style="background:#fdf5ec;border-radius:8px;padding:12px;text-align:center;">
            <div style="font-size:22px;font-weight:700;color:#c9973a;"><?= $total_products ?></div>
            <div style="font-size:12px;color:#8a6a4a;">Products</div>
          </div>
          <div style="background:#fdf5ec;border-radius:8px;padding:12px;text-align:center;">
            <div style="font-size:22px;font-weight:700;color:#c9973a;"><?= $total_users ?></div>
            <div style="font-size:12px;color:#8a6a4a;">Users</div>
          </div>
          <div style="background:#fdf5ec;border-radius:8px;padding:12px;text-align:center;">
            <div style="font-size:22px;font-weight:700;color:#c9973a;"><?= $total_orders ?></div>
            <div style="font-size:12px;color:#8a6a4a;">Orders</div>
          </div>
          <div style="background:#fdf5ec;border-radius:8px;padding:12px;text-align:center;">
            <div style="font-size:22px;font-weight:700;color:#c9973a;"><?= $total_bills ?></div>
            <div style="font-size:12px;color:#8a6a4a;">Bills</div>
          </div>
        </div>
      </div>

    </div>
  </div>

  <button type="submit" class="btn-save">
    <i class="bi bi-check-lg"></i> Save All Settings
  </button>
  </form>
</div>

</body>
</html>