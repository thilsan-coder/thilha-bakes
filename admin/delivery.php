<?php
require_once '../includes/db.php';
if (!isLoggedIn() || !isAdmin()) redirect('../login.php');

// update delivery status
if (isset($_POST['update_delivery'])) {
    $delivery_id = (int)$_POST['delivery_id'];
    $status      = mysqli_real_escape_string($conn, $_POST['status']);
    $delivered_at = $status === 'delivered' ? ", delivered_at=NOW()" : '';
    mysqli_query($conn,
        "UPDATE deliveries SET status='$status'
         $delivered_at
         WHERE delivery_id=$delivery_id");
    redirect('delivery.php?updated=1');
}

// assign delivery
if (isset($_POST['assign_delivery'])) {
    $order_id  = (int)$_POST['order_id'];
    $staff_id  = (int)$_POST['staff_id'];
    $address   = mysqli_real_escape_string($conn, $_POST['address']);
    $fee       = (float)$_POST['fee'];

    $exists = mysqli_fetch_assoc(mysqli_query($conn,
        "SELECT delivery_id FROM deliveries WHERE order_id=$order_id"));

    if (!$exists) {
        mysqli_query($conn,
            "INSERT INTO deliveries (order_id, staff_id, address, fee, status)
             VALUES ($order_id, $staff_id, '$address', $fee, 'pending')");
    } else {
        mysqli_query($conn,
            "UPDATE deliveries SET staff_id=$staff_id,
             address='$address', fee=$fee
             WHERE order_id=$order_id");
    }
    redirect('delivery.php?assigned=1');
}

// get all deliveries
$deliveries = mysqli_query($conn,
    "SELECT d.*, o.order_type, o.total_amount,
            o.notes as order_notes,
            u.name as customer, u.phone as cust_phone,
            s.name as staff_name
     FROM deliveries d
     JOIN orders o ON d.order_id = o.order_id
     LEFT JOIN users u ON o.user_id = u.user_id
     LEFT JOIN users s ON d.staff_id = s.user_id
     ORDER BY d.delivery_id DESC");

// orders not yet assigned for delivery
$unassigned = mysqli_query($conn,
    "SELECT o.*, u.name as customer, u.address, u.phone
     FROM orders o
     LEFT JOIN users u ON o.user_id=u.user_id
     LEFT JOIN deliveries d ON o.order_id=d.order_id
     WHERE d.delivery_id IS NULL
     AND o.status IN ('confirmed','baking','ready')
     ORDER BY o.created_at DESC");

// staff list
$staff_list = mysqli_query($conn,
    "SELECT user_id, name FROM users WHERE role='staff' ORDER BY name");

// stats
$total_del    = mysqli_fetch_assoc(mysqli_query($conn,
    "SELECT COUNT(*) as cnt FROM deliveries"))['cnt'];
$pending_del  = mysqli_fetch_assoc(mysqli_query($conn,
    "SELECT COUNT(*) as cnt FROM deliveries WHERE status='pending'"))['cnt'];
$dispatched   = mysqli_fetch_assoc(mysqli_query($conn,
    "SELECT COUNT(*) as cnt FROM deliveries WHERE status='dispatched'"))['cnt'];
$delivered    = mysqli_fetch_assoc(mysqli_query($conn,
    "SELECT COUNT(*) as cnt FROM deliveries WHERE status='delivered'"))['cnt'];
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>Delivery — Thilha Divine Bakes</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
<link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css" rel="stylesheet">
<style>
  *{box-sizing:border-box;}
  body{background:#f5f0eb;font-family:'Segoe UI',sans-serif;color:#2a1a10;margin:0;}

  
  
  
  
  
  
  
  
  

  .main{margin-left:240px;padding:30px;}
  .page-title{font-size:22px;font-weight:700;color:#1a0a0f;margin-bottom:2px;}
  .page-sub{font-size:13px;color:#8a6a4a;margin-bottom:24px;}

  .stat-row{display:grid;grid-template-columns:repeat(4,1fr);gap:14px;margin-bottom:24px;}
  .stat-card{background:#fff;border:1px solid #e8d8c0;border-top:4px solid #c9973a;border-radius:12px;padding:16px 20px;}
  .stat-num{font-size:24px;font-weight:700;color:#1a0a0f;}
  .stat-label{font-size:12px;color:#8a6a4a;margin-top:3px;}

  .card-box{background:#fff;border:1px solid #e8d8c0;border-radius:14px;padding:20px;margin-bottom:24px;}
  .card-title{font-size:16px;font-weight:600;color:#1a0a0f;margin-bottom:16px;padding-bottom:12px;border-bottom:1px solid #f0e4d0;display:flex;align-items:center;justify-content:space-between;}

  .table{color:#2a1a10;margin-bottom:0;}
  .table thead th{background:#fdf5ec;color:#8a5a20;font-size:12px;font-weight:600;letter-spacing:.5px;border-color:#e8d8c0;padding:10px 14px;}
  .table tbody td{border-color:#f5ede0;font-size:13px;padding:10px 14px;vertical-align:middle;}
  .table tbody tr:hover{background:#fdf5ec;}

  .del-pending{background:#fff3e0;color:#e65c00;font-size:11px;padding:4px 12px;border-radius:99px;font-weight:600;border:1px solid #ffcc80;}
  .del-dispatched{background:#e3f2fd;color:#1565c0;font-size:11px;padding:4px 12px;border-radius:99px;font-weight:600;border:1px solid #90caf9;}
  .del-delivered{background:#e8f5e9;color:#2e7d32;font-size:11px;padding:4px 12px;border-radius:99px;font-weight:600;border:1px solid #a5d6a7;}

  .btn-gold{background:#c9973a;border:none;border-radius:8px;color:#fff;font-weight:600;padding:7px 16px;font-size:13px;cursor:pointer;text-decoration:none;display:inline-flex;align-items:center;gap:6px;}
  .btn-gold:hover{background:#a07828;color:#fff;}

  .alert-box{border-radius:10px;padding:12px 16px;font-size:13px;margin-bottom:20px;display:flex;align-items:center;gap:8px;}
  .alert-success{background:#e8f5e9;border:1px solid #a5d6a7;border-left:4px solid #2e7d32;color:#1b5e20;}

  .form-label{font-size:13px;color:#8a6a4a;font-weight:600;}
  .form-control,.form-select{border:1px solid #e8d8c0;border-radius:8px;font-size:13px;color:#2a1a10;padding:9px 12px;}
  .form-control:focus,.form-select:focus{border-color:#c9973a;box-shadow:0 0 0 3px rgba(201,151,58,0.15);}
  .modal-content{border-radius:14px;border:1px solid #e8d8c0;}
  .modal-header{background:#fdf5ec;border-bottom:1px solid #e8d8c0;border-radius:14px 14px 0 0;}
</style>
</head>
<body>

<?php include '../includes/sidebar.php'; ?>

<!-- MAIN -->
<div class="main">
  <div class="page-title"><i class="bi bi-truck me-2" style="color:#c9973a;"></i>Delivery</div>
  <div class="page-sub">Manage all deliveries — <?= date('l, d F Y') ?></div>

  <?php if (isset($_GET['updated']) || isset($_GET['assigned'])): ?>
  <div class="alert-box alert-success">
    <i class="bi bi-check-circle-fill"></i>
    <?= isset($_GET['assigned']) ? 'Delivery assigned!' : 'Delivery status updated!' ?>
  </div>
  <?php endif; ?>

  <!-- STATS -->
  <div class="stat-row">
    <div class="stat-card">
      <div class="stat-num"><?= $total_del ?></div>
      <div class="stat-label">Total Deliveries</div>
    </div>
    <div class="stat-card" style="border-top-color:#e65c00;">
      <div class="stat-num" style="color:#e65c00;"><?= $pending_del ?></div>
      <div class="stat-label">Pending</div>
    </div>
    <div class="stat-card" style="border-top-color:#1565c0;">
      <div class="stat-num" style="color:#1565c0;"><?= $dispatched ?></div>
      <div class="stat-label">Dispatched</div>
    </div>
    <div class="stat-card" style="border-top-color:#2e7d32;">
      <div class="stat-num" style="color:#2e7d32;"><?= $delivered ?></div>
      <div class="stat-label">Delivered</div>
    </div>
  </div>

  <!-- UNASSIGNED ORDERS -->
  <div class="card-box">
    <div class="card-title">
      <span><i class="bi bi-exclamation-circle me-2" style="color:#e65c00;"></i>Orders Need Delivery Assignment</span>
    </div>
    <div class="table-responsive">
      <table class="table table-hover">
        <thead>
          <tr>
            <th>Order #</th>
            <th>Customer</th>
            <th>Phone</th>
            <th>Address</th>
            <th>Amount</th>
            <th>Status</th>
            <th>Action</th>
          </tr>
        </thead>
        <tbody>
          <?php
          $unassigned_count = mysqli_num_rows($unassigned);
          if ($unassigned_count === 0):
          ?>
          <tr>
            <td colspan="7" style="text-align:center;color:#8a6a4a;padding:20px;">
              All orders are assigned!
            </td>
          </tr>
          <?php else: ?>
          <?php while ($o = mysqli_fetch_assoc($unassigned)): ?>
          <tr>
            <td><strong>#<?= $o['order_id'] ?></strong></td>
            <td><?= htmlspecialchars($o['customer'] ?? 'Walk-in') ?></td>
            <td style="color:#8a6a4a;"><?= $o['phone'] ?? '—' ?></td>
            <td style="font-size:12px;color:#8a6a4a;">
              <?= htmlspecialchars($o['address'] ?? '—') ?>
            </td>
            <td><strong>Rs. <?= number_format($o['total_amount'],2) ?></strong></td>
            <td>
              <span style="font-size:11px;padding:3px 10px;border-radius:99px;background:#e8f5e9;color:#2e7d32;font-weight:600;">
                <?= ucfirst($o['status']) ?>
              </span>
            </td>
            <td>
              <button class="btn-gold"
                onclick="openAssignModal(
                  <?= $o['order_id'] ?>,
                  '<?= addslashes($o['customer'] ?? 'Walk-in') ?>',
                  '<?= addslashes($o['address'] ?? '') ?>'
                )">
                <i class="bi bi-person-check"></i> Assign
              </button>
            </td>
          </tr>
          <?php endwhile; ?>
          <?php endif; ?>
        </tbody>
      </table>
    </div>
  </div>

  <!-- ALL DELIVERIES -->
  <div class="card-box">
    <div class="card-title">
      <span><i class="bi bi-list-ul me-2" style="color:#c9973a;"></i>All Deliveries</span>
    </div>
    <div class="table-responsive">
      <table class="table table-hover">
        <thead>
          <tr>
            <th>Del #</th>
            <th>Order #</th>
            <th>Customer</th>
            <th>Address</th>
            <th>Staff</th>
            <th>Fee</th>
            <th>Status</th>
            <th>Delivered At</th>
            <th>Action</th>
          </tr>
        </thead>
        <tbody>
          <?php
          $del_count = mysqli_num_rows($deliveries);
          if ($del_count === 0):
          ?>
          <tr>
            <td colspan="9" style="text-align:center;color:#8a6a4a;padding:20px;">
              No deliveries yet!
            </td>
          </tr>
          <?php else: ?>
          <?php while ($d = mysqli_fetch_assoc($deliveries)): ?>
          <tr>
            <td><strong>#<?= $d['delivery_id'] ?></strong></td>
            <td>#<?= $d['order_id'] ?></td>
            <td><?= htmlspecialchars($d['customer'] ?? 'Walk-in') ?></td>
            <td style="font-size:12px;color:#8a6a4a;max-width:150px;">
              <?= htmlspecialchars($d['address']) ?>
            </td>
            <td>
              <span style="font-size:13px;font-weight:500;">
                <?= htmlspecialchars($d['staff_name'] ?? '—') ?>
              </span>
            </td>
            <td>Rs. <?= number_format($d['fee'],2) ?></td>
            <td>
              <span class="del-<?= $d['status'] ?>">
                <?= ucfirst($d['status']) ?>
              </span>
            </td>
            <td style="font-size:12px;color:#8a6a4a;">
              <?= $d['delivered_at']
                  ? date('d M, h:i A', strtotime($d['delivered_at']))
                  : '—' ?>
            </td>
            <td>
              <form method="POST" style="display:inline;">
                <input type="hidden" name="delivery_id" value="<?= $d['delivery_id'] ?>">
                <select name="status" onchange="this.form.submit()"
                  style="font-size:12px;border:1px solid #e8d8c0;border-radius:6px;padding:4px 8px;background:#fff;color:#2a1a10;cursor:pointer;">
                  <option value="pending"    <?= $d['status']==='pending'   ?'selected':'' ?>>Pending</option>
                  <option value="dispatched" <?= $d['status']==='dispatched'?'selected':'' ?>>Dispatched</option>
                  <option value="delivered"  <?= $d['status']==='delivered' ?'selected':'' ?>>Delivered</option>
                </select>
                <input type="hidden" name="update_delivery" value="1">
              </form>
            </td>
          </tr>
          <?php endwhile; ?>
          <?php endif; ?>
        </tbody>
      </table>
    </div>
  </div>
</div>

<!-- ASSIGN MODAL -->
<div class="modal fade" id="assignModal" tabindex="-1">
  <div class="modal-dialog modal-dialog-centered">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title">
          <i class="bi bi-truck me-2" style="color:#c9973a;"></i>Assign Delivery
        </h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
      </div>
      <div class="modal-body" style="padding:24px;">
        <form method="POST">
          <input type="hidden" name="order_id"       id="modal_order_id">
          <input type="hidden" name="assign_delivery" value="1">
          <div class="mb-3">
            <label class="form-label">Customer</label>
            <input type="text" id="modal_customer" class="form-control"
                   readonly style="background:#fdf5ec;">
          </div>
          <div class="mb-3">
            <label class="form-label">Delivery Address</label>
            <input type="text" name="address" id="modal_address"
                   class="form-control" placeholder="Enter delivery address" required>
          </div>
          <div class="mb-3">
            <label class="form-label">Assign Staff</label>
            <select name="staff_id" class="form-select" required>
              <option value="">Select Staff</option>
              <?php
              mysqli_data_seek($staff_list, 0);
              while ($s = mysqli_fetch_assoc($staff_list)):
              ?>
              <option value="<?= $s['user_id'] ?>"><?= htmlspecialchars($s['name']) ?></option>
              <?php endwhile; ?>
            </select>
          </div>
          <div class="mb-4">
            <label class="form-label">Delivery Fee (Rs.)</label>
            <input type="number" name="fee" class="form-control"
                   placeholder="0.00" step="0.01" min="0" value="0">
          </div>
          <button type="submit" class="btn-gold w-100" style="padding:12px;font-size:15px;">
            <i class="bi bi-check-lg me-2"></i>Assign Delivery
          </button>
        </form>
      </div>
    </div>
  </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script>
function openAssignModal(orderId, customer, address) {
  document.getElementById('modal_order_id').value  = orderId;
  document.getElementById('modal_customer').value  = customer;
  document.getElementById('modal_address').value   = address;
  new bootstrap.Modal(document.getElementById('assignModal')).show();
}
</script>
</body>
</html>