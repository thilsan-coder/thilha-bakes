<?php
require_once '../includes/db.php';
if (!isLoggedIn() || !isAdmin()) redirect('../login.php');

if (isset($_POST['update_status'])) {
    $order_id = (int)$_POST['order_id'];
    $status   = mysqli_real_escape_string($conn, $_POST['status']);
    mysqli_query($conn, "UPDATE orders SET status='$status' WHERE order_id=$order_id");
}

$where         = "WHERE 1=1";
$status_filter = isset($_GET['status']) ? $_GET['status'] : '';
$type_filter   = isset($_GET['type'])   ? $_GET['type']   : '';
$date_filter   = isset($_GET['date'])   ? $_GET['date']   : '';
$search_filter = isset($_GET['search']) ? $_GET['search'] : '';

if ($status_filter) $where .= " AND o.status='$status_filter'";
if ($type_filter)   $where .= " AND o.order_type='$type_filter'";
if ($date_filter)   $where .= " AND DATE(o.created_at)='$date_filter'";
if ($search_filter) $where .= " AND (u.name LIKE '%$search_filter%' OR o.order_id LIKE '%$search_filter%')";

$orders      = mysqli_query($conn,
    "SELECT o.*, u.name as customer, u.phone
     FROM orders o
     LEFT JOIN users u ON o.user_id=u.user_id
     $where
     ORDER BY o.created_at DESC");
$total_count = mysqli_num_rows($orders);
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>Orders — Thilha Divine Bakes</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
<link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css" rel="stylesheet">
<style>
*{box-sizing:border-box;}
body{background:#f5f0eb;font-family:'Segoe UI',sans-serif;color:#2a1a10;margin:0;}
.page-title{font-size:22px;font-weight:700;color:#1a0a0f;margin-bottom:2px;}
.page-sub{font-size:13px;color:#8a6a4a;margin-bottom:24px;}
.filter-card{background:#fff;border:1px solid #e8d8c0;border-radius:12px;padding:16px 20px;margin-bottom:20px;}
.form-select,.form-control{border:1px solid #e8d8c0;border-radius:8px;font-size:13px;color:#2a1a10;padding:8px 12px;}
.form-select:focus,.form-control:focus{border-color:#c9973a;box-shadow:0 0 0 3px rgba(201,151,58,0.15);}
.btn-gold{background:#c9973a;border:none;border-radius:8px;color:#fff;font-weight:600;padding:8px 20px;font-size:13px;cursor:pointer;display:inline-flex;align-items:center;gap:6px;}
.btn-gold:hover{background:#a07828;color:#fff;}
.btn-outline-gold{background:#fff;border:1px solid #c9973a;border-radius:8px;color:#c9973a;font-weight:600;padding:8px 20px;font-size:13px;text-decoration:none;display:inline-flex;align-items:center;gap:6px;}
.btn-outline-gold:hover{background:#fdf5ec;}
.orders-card{background:#fff;border:1px solid #e8d8c0;border-radius:14px;padding:20px;}
.card-title{font-size:16px;font-weight:600;color:#1a0a0f;margin-bottom:16px;padding-bottom:12px;border-bottom:1px solid #f0e4d0;display:flex;align-items:center;justify-content:space-between;}
.table{color:#2a1a10;margin-bottom:0;}
.table thead th{background:#fdf5ec;color:#8a5a20;font-size:12px;font-weight:600;border-color:#e8d8c0;padding:10px 14px;}
.table tbody td{border-color:#f5ede0;font-size:13px;padding:11px 14px;vertical-align:middle;}
.table tbody tr:hover{background:#fdf5ec;}
.status-badge{font-size:11px;padding:4px 12px;border-radius:99px;font-weight:600;}
.badge-pending{background:#fff3e0;color:#e65c00;border:1px solid #ffcc80;}
.badge-confirmed{background:#e8f5e9;color:#2e7d32;border:1px solid #a5d6a7;}
.badge-baking{background:#f3e5f5;color:#6a1b9a;border:1px solid #ce93d8;}
.badge-ready{background:#e3f2fd;color:#1565c0;border:1px solid #90caf9;}
.badge-delivered{background:#e8f5e9;color:#1b5e20;border:1px solid #81c784;}
.type-badge{font-size:11px;padding:3px 9px;border-radius:99px;font-weight:500;}
.type-online{background:#e3f2fd;color:#1565c0;}
.type-walkin{background:#fff8e1;color:#f57f17;}
.type-phone{background:#f3e5f5;color:#6a1b9a;}
.type-whatsapp{background:#e8f5e9;color:#2e7d32;}

/* ✨ ENHANCED VIEW BUTTON */
.btn-view-action{
  display:inline-flex;align-items:center;gap:6px;
  padding:6px 16px;
  background:linear-gradient(135deg,#c9973a,#a07828);
  color:#fff;font-size:12px;font-weight:700;
  border-radius:99px;text-decoration:none;
  letter-spacing:.3px;transition:all .2s;
  box-shadow:0 2px 8px rgba(201,151,58,0.3);
  white-space:nowrap;
}
.btn-view-action:hover{
  background:linear-gradient(135deg,#a07828,#c9973a);
  color:#fff;transform:translateY(-1px);
  box-shadow:0 4px 14px rgba(201,151,58,0.45);
}
.btn-view-action i{font-size:11px;}
</style>
</head>
<body>
<?php include '../includes/sidebar.php'; ?>
<div class="main">
  <div class="page-title"><i class="bi bi-bag-fill me-2" style="color:#c9973a;"></i>Orders</div>
  <div class="page-sub">Manage all bakery orders — <?= date('l, d F Y') ?></div>

  <!-- FILTERS -->
  <div class="filter-card">
    <form method="GET" class="row g-2 align-items-end">
      <div class="col-md-3">
        <label style="font-size:12px;color:#8a6a4a;font-weight:600;">Search</label>
        <input type="text" name="search" class="form-control"
               placeholder="Order # or customer..."
               value="<?= htmlspecialchars($search_filter) ?>">
      </div>
      <div class="col-md-2">
        <label style="font-size:12px;color:#8a6a4a;font-weight:600;">Status</label>
        <select name="status" class="form-select">
          <option value="">All Status</option>
          <option value="pending"   <?= $status_filter=='pending'  ?'selected':'' ?>>Pending</option>
          <option value="confirmed" <?= $status_filter=='confirmed'?'selected':'' ?>>Confirmed</option>
          <option value="baking"    <?= $status_filter=='baking'   ?'selected':'' ?>>Baking</option>
          <option value="ready"     <?= $status_filter=='ready'    ?'selected':'' ?>>Ready</option>
          <option value="delivered" <?= $status_filter=='delivered'?'selected':'' ?>>Delivered</option>
        </select>
      </div>
      <div class="col-md-2">
        <label style="font-size:12px;color:#8a6a4a;font-weight:600;">Type</label>
        <select name="type" class="form-select">
          <option value="">All Types</option>
          <option value="online"   <?= $type_filter=='online'  ?'selected':'' ?>>Online</option>
          <option value="walkin"   <?= $type_filter=='walkin'  ?'selected':'' ?>>Walk-in</option>
          <option value="phone"    <?= $type_filter=='phone'   ?'selected':'' ?>>Phone</option>
          <option value="whatsapp" <?= $type_filter=='whatsapp'?'selected':'' ?>>WhatsApp</option>
        </select>
      </div>
      <div class="col-md-2">
        <label style="font-size:12px;color:#8a6a4a;font-weight:600;">Date</label>
        <input type="date" name="date" class="form-control" value="<?= $date_filter ?>">
      </div>
      <div class="col-md-3 d-flex gap-2">
        <button type="submit" class="btn-gold w-100">
          <i class="bi bi-search"></i> Search
        </button>
        <a href="orders.php" class="btn-outline-gold w-100 text-center">Clear</a>
      </div>
    </form>
  </div>

  <!-- ORDERS TABLE -->
  <div class="orders-card">
    <div class="card-title">
      <span><i class="bi bi-list-ul me-2" style="color:#c9973a;"></i>All Orders</span>
      <span style="font-size:13px;color:#8a6a4a;"><?= $total_count ?> orders found</span>
    </div>
    <div class="table-responsive">
      <table class="table table-hover">
        <thead>
          <tr>
            <th>Order #</th>
            <th>Customer</th>
            <th>Phone</th>
            <th>Type</th>
            <th>Amount</th>
            <th>Payment</th>
            <th>Status</th>
            <th>Date</th>
            <th>Action</th>
          </tr>
        </thead>
        <tbody>
          <?php
          mysqli_data_seek($orders, 0);
          while ($o = mysqli_fetch_assoc($orders)):
          ?>
          <tr>
            <td><strong>#<?= $o['order_id'] ?></strong></td>
            <td><?= htmlspecialchars($o['customer'] ?? 'Walk-in') ?></td>
            <td style="color:#8a6a4a;"><?= $o['phone'] ?? '—' ?></td>
            <td>
              <span class="type-badge type-<?= $o['order_type'] ?>">
                <?= ucfirst($o['order_type']) ?>
              </span>
            </td>
            <td><strong>Rs. <?= number_format($o['total_amount'],2) ?></strong></td>
            <td style="font-size:12px;color:#5a8a5a;font-weight:500;">
              <?= ucfirst($o['payment_method']) ?>
            </td>
            <td>
              <form method="POST" style="display:inline;">
                <input type="hidden" name="order_id" value="<?= $o['order_id'] ?>">
                <input type="hidden" name="update_status" value="1">
                <select name="status" onchange="this.form.submit()"
                  style="font-size:12px;border:1px solid #e8d8c0;border-radius:6px;padding:4px 8px;background:#fff;color:#2a1a10;cursor:pointer;">
                  <option value="pending"   <?= $o['status']=='pending'  ?'selected':'' ?>>Pending</option>
                  <option value="confirmed" <?= $o['status']=='confirmed'?'selected':'' ?>>Confirmed</option>
                  <option value="baking"    <?= $o['status']=='baking'   ?'selected':'' ?>>Baking</option>
                  <option value="ready"     <?= $o['status']=='ready'    ?'selected':'' ?>>Ready</option>
                  <option value="delivered" <?= $o['status']=='delivered'?'selected':'' ?>>Delivered</option>
                </select>
              </form>
            </td>
            <td style="font-size:12px;color:#8a6a4a;">
              <?= date('d M, h:i A', strtotime($o['created_at'])) ?>
            </td>
            <td>
              <a href="order_view.php?id=<?= $o['order_id'] ?>" class="btn-view-action">
                <i class="bi bi-eye"></i> View
              </a>
            </td>
          </tr>
          <?php endwhile; ?>
        </tbody>
      </table>
    </div>
  </div>
</div>
</body>
</html>
