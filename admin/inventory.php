<?php
require_once '../includes/db.php';
if (!isLoggedIn() || !isAdmin()) redirect('../login.php');

// delete
if (isset($_GET['delete'])) {
    $id = (int)$_GET['delete'];
    mysqli_query($conn, "DELETE FROM inventory WHERE item_id=$id");
    redirect('inventory.php?deleted=1');
}

// add / edit
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name     = mysqli_real_escape_string($conn, $_POST['ingredient_name']);
    $qty      = (float)$_POST['quantity'];
    $unit     = mysqli_real_escape_string($conn, $_POST['unit']);
    $min      = (float)$_POST['min_level'];
    $expiry   = $_POST['expiry_date'] ? "'".$_POST['expiry_date']."'" : 'NULL';

    if (isset($_POST['item_id']) && $_POST['item_id']) {
        $id = (int)$_POST['item_id'];
        mysqli_query($conn,
            "UPDATE inventory SET
             ingredient_name='$name', quantity=$qty, unit='$unit',
             min_level=$min, expiry_date=$expiry
             WHERE item_id=$id");
        redirect('inventory.php?updated=1');
    } else {
        mysqli_query($conn,
            "INSERT INTO inventory
             (ingredient_name, quantity, unit, min_level, expiry_date)
             VALUES ('$name', $qty, '$unit', $min, $expiry)");
        redirect('inventory.php?added=1');
    }
}

$items    = mysqli_query($conn, "SELECT * FROM inventory ORDER BY ingredient_name");
$low      = mysqli_fetch_assoc(mysqli_query($conn,
            "SELECT COUNT(*) as cnt FROM inventory WHERE quantity <= min_level"))['cnt'];
$total    = mysqli_fetch_assoc(mysqli_query($conn,
            "SELECT COUNT(*) as cnt FROM inventory"))['cnt'];
$expiring = mysqli_fetch_assoc(mysqli_query($conn,
            "SELECT COUNT(*) as cnt FROM inventory
             WHERE expiry_date IS NOT NULL
             AND expiry_date <= DATE_ADD(CURDATE(), INTERVAL 7 DAY)
             AND expiry_date >= CURDATE()"))['cnt'];
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>Inventory — Thilha Divine Bakes</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
<link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css" rel="stylesheet">
<style>
  *{box-sizing:border-box;}
  body{background:#f5f0eb;font-family:'Poppins', sans-serif;color:#2a1a10;margin:0;}

  .main{margin-left:240px;padding:30px;}
  .page-title{font-size:22px;font-weight:700;color:#1a0a0f;margin-bottom:2px;}
  .page-sub{font-size:13px;color:#8a6a4a;margin-bottom:24px;}

  .stat-row{display:grid;grid-template-columns:repeat(3,1fr);gap:14px;margin-bottom:24px;}
  .stat-card{background:#fff;border:1px solid #e8d8c0;border-radius:12px;padding:16px 20px;display:flex;align-items:center;gap:14px;}
  .stat-icon{width:44px;height:44px;border-radius:10px;display:flex;align-items:center;justify-content:center;font-size:20px;flex-shrink:0;}
  .stat-num{font-size:22px;font-weight:700;color:#1a0a0f;}
  .stat-label{font-size:12px;color:#8a6a4a;margin-top:2px;}

  .card-box{background:#fff;border:1px solid #e8d8c0;border-radius:14px;padding:20px;margin-bottom:24px;}
  .card-title{font-size:16px;font-weight:600;color:#1a0a0f;margin-bottom:16px;padding-bottom:12px;border-bottom:1px solid #f0e4d0;display:flex;align-items:center;justify-content:space-between;}

  .btn-gold{background:#c9973a;border:none;border-radius:8px;color:#fff;font-weight:600;padding:8px 20px;font-size:13px;cursor:pointer;text-decoration:none;display:inline-flex;align-items:center;gap:6px;}
  .btn-gold:hover{background:#a07828;color:#fff;}
  
  /* FIXED ALIGNMENT FOR TABLE BUTTONS */
  .btn-edit{background:#e3f2fd;border:none;border-radius:6px;color:#1565c0;font-weight:600;padding:6px 12px;font-size:12px;cursor:pointer;display:inline-flex;align-items:center;gap:4px;}
  .btn-edit:hover{background:#bbdefb;}
  .btn-del{background:#ffebee;border:none;border-radius:6px;color:#c62828;font-weight:600;padding:6px 12px;font-size:12px;cursor:pointer;text-decoration:none;display:inline-flex;align-items:center;}
  .btn-del:hover{background:#ffcdd2;}

  .table{color:#2a1a10;margin-bottom:0;}
  .table thead th{background:#fdf5ec;color:#8a5a20;font-size:12px;font-weight:600;letter-spacing:.5px;border-color:#e8d8c0;padding:12px 14px;}
  
  /* VERTICAL ALIGNMENT FIX */
  .table tbody td{border-color:#f5ede0;font-size:13px;padding:14px 14px;vertical-align:middle;}
  .table tbody tr:hover{background:#fdf5ec;}

  .stock-ok{background:#e8f5e9;color:#2e7d32;font-size:11px;padding:4px 10px;border-radius:99px;font-weight:600;}
  .stock-low{background:#fff3e0;color:#e65c00;font-size:11px;padding:4px 10px;border-radius:99px;font-weight:600;}
  .stock-out{background:#ffebee;color:#c62828;font-size:11px;padding:4px 10px;border-radius:99px;font-weight:600;}
  .expiry-warn{background:#fff3e0;color:#e65c00;font-size:11px;padding:4px 10px;border-radius:99px;font-weight:600;}
  .expiry-ok{font-size:13px;color:#8a6a4a;}

  .progress-wrap{width:100px;height:8px;background:#f0e4d0;border-radius:4px;overflow:hidden;}
  .progress-fill{height:100%;border-radius:4px;}

  .alert-box{border-radius:10px;padding:12px 16px;font-size:13px;margin-bottom:20px;display:flex;align-items:center;gap:8px;}
  .alert-success{background:#e8f5e9;border:1px solid #a5d6a7;border-left:4px solid #2e7d32;color:#1b5e20;}
  .alert-deleted{background:#ffebee;border:1px solid #ffcdd2;border-left:4px solid #c62828;color:#b71c1c;}

  .form-label{font-size:13px;color:#8a6a4a;font-weight:600;}
  .form-control,.form-select{border:1px solid #e8d8c0;border-radius:8px;font-size:13px;color:#2a1a10;padding:9px 12px;}
  .form-control:focus,.form-select:focus{border-color:#c9973a;box-shadow:0 0 0 3px rgba(201,151,58,0.15);}
  .modal-content{border-radius:14px;border:1px solid #e8d8c0;}
  .modal-header{background:#fdf5ec;border-bottom:1px solid #e8d8c0;border-radius:14px 14px 0 0;}
</style>
</head>
<body>

<?php include '../includes/sidebar.php'; ?>

<div class="main">
  <div class="page-title"><i class="bi bi-layers-fill me-2" style="color:#c9973a;"></i>Inventory</div>
  <div class="page-sub">Track ingredients and stock levels — <?= date('l, d F Y') ?></div>

  <!-- Messages -->
  <?php if (isset($_GET['added'])): ?>
  <div class="alert-box alert-success"><i class="bi bi-check-circle-fill"></i> Ingredient added!</div>
  <?php elseif (isset($_GET['updated'])): ?>
  <div class="alert-box alert-success"><i class="bi bi-check-circle-fill"></i> Ingredient updated!</div>
  <?php elseif (isset($_GET['deleted'])): ?>
  <div class="alert-box alert-deleted"><i class="bi bi-trash-fill"></i> Ingredient deleted!</div>
  <?php endif; ?>

  <!-- Notifications -->
  <div style="display: flex; flex-direction: column; gap: 10px; margin-bottom: 24px;">
    <?php if ($low > 0): ?>
    <div style="background:#fff8ee;border-radius:10px;padding:14px 20px;display:flex;align-items:center;border:1px solid #f0d080; gap:12px;">
      <div style="font-size:20px;">📦</div>
      <div>
        <div style="font-size:14px;font-weight:600;color:#7a5000;"><?= $low ?> item(s) running low</div>
        <div style="font-size:12px;color:#b08020;">Please reorder soon</div>
      </div>
    </div>
    <?php endif; ?>

    <?php if ($expiring > 0): ?>
    <div style="background:#fff0f5;border-radius:10px;padding:14px 20px;display:flex;align-items:center;border:1px solid #f0b8c8; gap:12px;">
      <div style="font-size:20px;">📅</div>
      <div>
        <div style="font-size:14px;font-weight:600;color:#7a1535;"><?= $expiring ?> item(s) expiring within 7 days</div>
        <div style="font-size:12px;color:#b03060;">Replace these quickly</div>
      </div>
    </div>
    <?php endif; ?>
  </div>

  <!-- Stats -->
  <div class="stat-row">
    <div class="stat-card">
      <div class="stat-icon" style="background:#e3f2fd;">📦</div>
      <div>
        <div class="stat-num"><?= $total ?></div>
        <div class="stat-label">Total Ingredients</div>
      </div>
    </div>
    <div class="stat-card" style="border-top:3px solid #e65c00;">
      <div class="stat-icon" style="background:#fff3e0;">⚠️</div>
      <div>
        <div class="stat-num" style="color:#e65c00;"><?= $low ?></div>
        <div class="stat-label">Low Stock</div>
      </div>
    </div>
    <div class="stat-card" style="border-top:3px solid #c62828;">
      <div class="stat-icon" style="background:#ffebee;">📅</div>
      <div>
        <div class="stat-num" style="color:#c62828;"><?= $expiring ?></div>
        <div class="stat-label">Expiring Soon</div>
      </div>
    </div>
  </div>

  <!-- Inventory Table -->
  <div class="card-box">
    <div class="card-title">
      <span><i class="bi bi-list-ul me-2" style="color:#c9973a;"></i>All Ingredients</span>
      <button class="btn-gold" onclick="openAddModal()">
        <i class="bi bi-plus-lg"></i> Add Ingredient
      </button>
    </div>
    <div class="table-responsive">
      <table class="table">
        <thead>
          <tr>
            <th>Ingredient</th>
            <th>Quantity</th>
            <th>Min Level</th>
            <th>Stock Level</th>
            <th>Expiry Date</th>
            <th>Status</th>
            <th>Actions</th>
          </tr>
        </thead>
        <tbody>
          <?php
          mysqli_data_seek($items, 0);
          while ($item = mysqli_fetch_assoc($items)):
            $pct = $item['min_level'] > 0 ? min(100, round(($item['quantity'] / $item['min_level']) * 50)) : 100;
            $bar_color = $item['quantity'] <= 0 ? '#c62828' : ($item['quantity'] <= $item['min_level'] ? '#e65c00' : '#2e7d32');

            $exp_warn = false;
            if ($item['expiry_date']) {
                $exp_date = new DateTime($item['expiry_date']);
                $diff = (new DateTime())->diff($exp_date)->days;
                $exp_warn = $diff <= 7 && $exp_date >= (new DateTime());
            }
          ?>
          <tr>
            <td><strong><?= htmlspecialchars($item['ingredient_name']) ?></strong></td>
            <td><strong><?= number_format($item['quantity'], 1) ?></strong> <small class="text-muted"><?= $item['unit'] ?></small></td>
            <td><?= number_format($item['min_level'], 1) ?> <small class="text-muted"><?= $item['unit'] ?></small></td>
            <td>
              <div class="progress-wrap"><div class="progress-fill" style="width:<?= $pct ?>%;background:<?= $bar_color ?>;"></div></div>
            </td>
            <td>
              <?php if ($item['expiry_date']): ?>
                <span class="<?= $exp_warn ? 'expiry-warn' : 'expiry-ok' ?>">
                  <?= $exp_warn ? '⚠️ ' : '' ?><?= date('d M Y', strtotime($item['expiry_date'])) ?>
                </span>
              <?php else: ?>
                <span class="text-muted">—</span>
              <?php endif; ?>
            </td>
            <td>
              <?php if ($item['quantity'] <= 0): ?> <span class="stock-out">Out of Stock</span>
              <?php elseif ($item['quantity'] <= $item['min_level']): ?> <span class="stock-low">Low Stock</span>
              <?php else: ?> <span class="stock-ok">Good</span> <?php endif; ?>
            </td>
            <td>
              <div style="display:flex;gap:6px; align-items: center;">
                <button class="btn-edit" onclick="openEditModal(<?= $item['item_id'] ?>, '<?= addslashes($item['ingredient_name']) ?>', <?= $item['quantity'] ?>, '<?= $item['unit'] ?>', <?= $item['min_level'] ?>, '<?= $item['expiry_date'] ?>')">
                  <i class="bi bi-pencil"></i>
                </button>
                <a href="inventory.php?delete=<?= $item['item_id'] ?>" class="btn-del" onclick="return confirm('Delete?')"><i class="bi bi-trash"></i></a>
              </div>
            </td>
          </tr>
          <?php endwhile; ?>
        </tbody>
      </table>
    </div>
  </div>
</div>

<!-- Modal Same as before -->
<div class="modal fade" id="invModal" tabindex="-1">
  <div class="modal-dialog modal-dialog-centered">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title" id="invModalTitle">Add Ingredient</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
      </div>
      <div class="modal-body" style="padding:24px;">
        <form method="POST">
          <input type="hidden" name="item_id" id="item_id">
          <div class="mb-3">
            <label class="form-label">Ingredient Name</label>
            <input type="text" name="ingredient_name" id="f_name" class="form-control" required>
          </div>
          <div class="row g-3 mb-3">
            <div class="col-6">
              <label class="form-label">Quantity</label>
              <input type="number" name="quantity" id="f_qty" class="form-control" step="0.1" required>
            </div>
            <div class="col-6">
              <label class="form-label">Unit</label>
              <select name="unit" id="f_unit" class="form-select">
                <option value="kg">kg</option><option value="g">g</option><option value="L">L</option><option value="ml">ml</option><option value="pcs">pcs</option>
              </select>
            </div>
          </div>
          <div class="mb-3">
            <label class="form-label">Min Stock Level</label>
            <input type="number" name="min_level" id="f_min" class="form-control" step="0.1" required>
          </div>
          <div class="mb-4">
            <label class="form-label">Expiry Date</label>
            <input type="date" name="expiry_date" id="f_expiry" class="form-control">
          </div>
          <button type="submit" class="btn-gold w-100" style="padding:12px;">Save Ingredient</button>
        </form>
      </div>
    </div>
  </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script>
function openAddModal() {
  document.getElementById('invModalTitle').innerText = 'Add Ingredient';
  document.getElementById('item_id').value = '';
  document.getElementById('f_name').value = '';
  document.getElementById('f_qty').value = '';
  document.getElementById('f_min').value = '';
  document.getElementById('f_expiry').value = '';
  new bootstrap.Modal(document.getElementById('invModal')).show();
}
function openEditModal(id, name, qty, unit, min, expiry) {
  document.getElementById('invModalTitle').innerText = 'Edit Ingredient';
  document.getElementById('item_id').value = id;
  document.getElementById('f_name').value = name;
  document.getElementById('f_qty').value = qty;
  document.getElementById('f_unit').value = unit;
  document.getElementById('f_min').value = min;
  document.getElementById('f_expiry').value = expiry;
  new bootstrap.Modal(document.getElementById('invModal')).show();
}
</script>
</body>
</html>