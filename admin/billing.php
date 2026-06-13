<?php
require_once '../includes/db.php';
if (!isLoggedIn() || !isAdmin()) redirect('../login.php');

// generate bill
if (isset($_POST['generate_bill'])) {
    $order_id = (int)$_POST['order_id'];
    $discount = (float)$_POST['discount'];
    $order    = mysqli_fetch_assoc(mysqli_query($conn,
        "SELECT * FROM orders WHERE order_id=$order_id"));
    $subtotal = (float)$order['total_amount'];
    $total    = $subtotal - $discount;
    $last     = mysqli_fetch_assoc(mysqli_query($conn,
        "SELECT bill_id FROM bills ORDER BY bill_id DESC LIMIT 1"));
    $next_id     = $last ? $last['bill_id'] + 1 : 1;
    $bill_number = 'TDB-' . date('Y') . '-' . str_pad($next_id, 4, '0', STR_PAD_LEFT);
    $exists = mysqli_fetch_assoc(mysqli_query($conn,
        "SELECT bill_id FROM bills WHERE order_id=$order_id"));
    if (!$exists) {
        mysqli_query($conn,
            "INSERT INTO bills (order_id, bill_number, subtotal, discount, total)
             VALUES ($order_id, '$bill_number', $subtotal, $discount, $total)");
        mysqli_query($conn,
            "UPDATE orders SET payment_status='paid' WHERE order_id=$order_id");
    }
    redirect('billing.php?success=1');
}

// revenue summary stats
$today_revenue = mysqli_fetch_assoc(mysqli_query($conn,
    "SELECT COALESCE(SUM(b.total),0) as amt FROM bills b
     JOIN orders o ON b.order_id=o.order_id
     WHERE DATE(o.created_at)=CURDATE()"))['amt'];
$week_revenue  = mysqli_fetch_assoc(mysqli_query($conn,
    "SELECT COALESCE(SUM(b.total),0) as amt FROM bills b
     JOIN orders o ON b.order_id=o.order_id
     WHERE o.created_at >= DATE_SUB(NOW(), INTERVAL 7 DAY)"))['amt'];
$month_revenue = mysqli_fetch_assoc(mysqli_query($conn,
    "SELECT COALESCE(SUM(b.total),0) as amt FROM bills b
     JOIN orders o ON b.order_id=o.order_id
     WHERE MONTH(o.created_at)=MONTH(NOW())
     AND YEAR(o.created_at)=YEAR(NOW())"))['amt'];
$total_revenue = mysqli_fetch_assoc(mysqli_query($conn,
    "SELECT COALESCE(SUM(total),0) as amt FROM bills"))['amt'];
$total_bills   = mysqli_fetch_assoc(mysqli_query($conn,
    "SELECT COUNT(*) as cnt FROM bills"))['cnt'];
$unpaid_count  = mysqli_fetch_assoc(mysqli_query($conn,
    "SELECT COUNT(*) as cnt FROM orders
     WHERE payment_status='unpaid' AND status != 'pending'"))['cnt'];

// all bills
$bills = mysqli_query($conn,
    "SELECT b.*, o.order_type, o.payment_method, o.status as order_status,
            u.name as customer, u.phone
     FROM bills b
     JOIN orders o ON b.order_id=o.order_id
     LEFT JOIN users u ON o.user_id=u.user_id
     ORDER BY b.issued_at DESC");

// unbilled orders
$unbilled = mysqli_query($conn,
    "SELECT o.*, u.name as customer
     FROM orders o
     LEFT JOIN users u ON o.user_id=u.user_id
     LEFT JOIN bills b ON o.order_id=b.order_id
     WHERE b.bill_id IS NULL AND o.status != 'pending'
     ORDER BY o.created_at DESC");
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>Billing — Thilha Divine Bakes</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
<link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css" rel="stylesheet">
<style>
*{box-sizing:border-box;}
body{background:#f5f0eb;font-family:'Segoe UI',sans-serif;color:#2a1a10;margin:0;}
.page-title{font-size:22px;font-weight:700;color:#1a0a0f;margin-bottom:2px;}
.page-sub{font-size:13px;color:#8a6a4a;margin-bottom:24px;}
.rev-grid{display:grid;grid-template-columns:repeat(auto-fit,minmax(150px,1fr));gap:14px;margin-bottom:24px;}
.rev-card{background:#fff;border:1px solid #e8d8c0;border-top:4px solid #c9973a;border-radius:12px;padding:16px 18px;}
.rev-label{font-size:12px;color:#8a6a4a;margin-bottom:6px;font-weight:500;}
.rev-num{font-size:20px;font-weight:700;color:#1a0a0f;}
.card-box{background:#fff;border:1px solid #e8d8c0;border-radius:14px;padding:20px;margin-bottom:20px;}
.card-title{font-size:16px;font-weight:600;color:#1a0a0f;margin-bottom:16px;padding-bottom:12px;border-bottom:1px solid #f0e4d0;}
.table{color:#2a1a10;margin-bottom:0;}
.table thead th{background:#fdf5ec;color:#8a5a20;font-size:12px;font-weight:600;border-color:#e8d8c0;padding:10px 14px;}
.table tbody td{border-color:#f5ede0;font-size:13px;padding:10px 14px;vertical-align:middle;}
.table tbody tr:hover{background:#fdf5ec;}
.btn-gold{background:#c9973a;border:none;border-radius:8px;color:#fff;font-weight:600;padding:7px 16px;font-size:13px;cursor:pointer;display:inline-flex;align-items:center;gap:6px;text-decoration:none;}
.btn-gold:hover{background:#a07828;color:#fff;}
.btn-view{background:#e3f2fd;border:none;border-radius:8px;color:#1565c0;font-weight:600;padding:7px 12px;font-size:13px;cursor:pointer;text-decoration:none;display:inline-block;}
.btn-print{background:#e8f5e9;border:none;border-radius:8px;color:#2e7d32;font-weight:600;padding:7px 12px;font-size:13px;cursor:pointer;text-decoration:none;display:inline-block;}
.alert-success-box{background:#e8f5e9;border:1px solid #a5d6a7;border-left:4px solid #2e7d32;border-radius:10px;padding:12px 16px;font-size:13px;color:#1b5e20;margin-bottom:20px;display:flex;align-items:center;gap:8px;}
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
  <div class="page-title"><i class="bi bi-receipt-cutoff me-2" style="color:#c9973a;"></i>Billing</div>
  <div class="page-sub">Generate and manage customer bills — <?= date('l, d F Y') ?></div>

  <?php if (isset($_GET['success'])): ?>
  <div class="alert-success-box"><i class="bi bi-check-circle-fill"></i> Bill generated successfully!</div>
  <?php endif; ?>

  <!-- REVENUE SUMMARY -->
  <div class="rev-grid">
    <div class="rev-card" style="background:#1a0a0f;border:none;">
      <div class="rev-label" style="color:#7a5a38;">Today's Revenue</div>
      <div class="rev-num" style="color:#e8c870;">Rs. <?= number_format($today_revenue,0) ?></div>
    </div>
    <div class="rev-card">
      <div class="rev-label">This Week</div>
      <div class="rev-num">Rs. <?= number_format($week_revenue,0) ?></div>
    </div>
    <div class="rev-card" style="border-top-color:#e8789a;">
      <div class="rev-label">This Month</div>
      <div class="rev-num">Rs. <?= number_format($month_revenue,0) ?></div>
    </div>
    <div class="rev-card" style="border-top-color:#66bb6a;">
      <div class="rev-label">Total Revenue</div>
      <div class="rev-num">Rs. <?= number_format($total_revenue,0) ?></div>
    </div>
    <div class="rev-card" style="border-top-color:#42a5f5;">
      <div class="rev-label">Total Bills</div>
      <div class="rev-num"><?= $total_bills ?></div>
    </div>
    <div class="rev-card" style="border-top-color:#ef5350;">
      <div class="rev-label">Unpaid Orders</div>
      <div class="rev-num" style="color:#ef5350;"><?= $unpaid_count ?></div>
    </div>
  </div>

  <!-- UNBILLED ORDERS -->
  <div class="card-box">
    <div class="card-title"><i class="bi bi-exclamation-circle me-2" style="color:#e65c00;"></i>Orders Waiting for Bill</div>
    <div class="table-responsive">
      <table class="table table-hover">
        <thead>
          <tr><th>Order #</th><th>Customer</th><th>Type</th><th>Amount</th><th>Payment</th><th>Date</th><th>Action</th></tr>
        </thead>
        <tbody>
          <?php
          $ub_count = mysqli_num_rows($unbilled);
          if ($ub_count === 0): ?>
          <tr><td colspan="7" style="text-align:center;color:#8a6a4a;padding:20px;">No unbilled orders!</td></tr>
          <?php else: while ($u = mysqli_fetch_assoc($unbilled)): ?>
          <tr>
            <td><strong>#<?= $u['order_id'] ?></strong></td>
            <td><?= htmlspecialchars($u['customer'] ?? 'Walk-in') ?></td>
            <td style="font-size:12px;color:#8a6a4a;"><?= ucfirst($u['order_type']) ?></td>
            <td><strong>Rs. <?= number_format($u['total_amount'],2) ?></strong></td>
            <td style="font-size:12px;"><?= ucfirst($u['payment_method']) ?></td>
            <td style="font-size:12px;color:#8a6a4a;"><?= date('d M Y', strtotime($u['created_at'])) ?></td>
            <td>
              <button class="btn-gold"
                onclick="openGenerateModal(<?= $u['order_id'] ?>, <?= $u['total_amount'] ?>, '<?= htmlspecialchars($u['customer'] ?? 'Walk-in') ?>')">
                <i class="bi bi-receipt"></i> Generate Bill
              </button>
            </td>
          </tr>
          <?php endwhile; endif; ?>
        </tbody>
      </table>
    </div>
  </div>

  <!-- ALL BILLS -->
  <div class="card-box">
    <div class="card-title"><i class="bi bi-collection me-2" style="color:#c9973a;"></i>All Bills</div>
    <div class="table-responsive">
      <table class="table table-hover">
        <thead>
          <tr><th>Bill No</th><th>Order #</th><th>Customer</th><th>Subtotal</th><th>Discount</th><th>Total</th><th>Payment</th><th>Date</th><th>Actions</th></tr>
        </thead>
        <tbody>
          <?php
          $b_count = mysqli_num_rows($bills);
          if ($b_count === 0): ?>
          <tr><td colspan="9" style="text-align:center;color:#8a6a4a;padding:20px;">No bills yet!</td></tr>
          <?php else: while ($b = mysqli_fetch_assoc($bills)): ?>
          <tr>
            <td><strong style="color:#c9973a;"><?= $b['bill_number'] ?></strong></td>
            <td>#<?= $b['order_id'] ?></td>
            <td><?= htmlspecialchars($b['customer'] ?? 'Walk-in') ?></td>
            <td>Rs. <?= number_format($b['subtotal'],2) ?></td>
            <td style="color:#e65c00;"><?= $b['discount']>0 ? '- Rs. '.number_format($b['discount'],2) : '—' ?></td>
            <td><strong style="color:#2e7d32;">Rs. <?= number_format($b['total'],2) ?></strong></td>
            <td style="font-size:12px;"><?= ucfirst($b['payment_method']) ?></td>
            <td style="font-size:12px;color:#8a6a4a;"><?= date('d M Y, h:i A', strtotime($b['issued_at'])) ?></td>
            <td style="display:flex;gap:6px;">
              <a href="bill_print.php?id=<?= $b['bill_id'] ?>" class="btn-view"><i class="bi bi-eye"></i></a>
              <a href="bill_print.php?id=<?= $b['bill_id'] ?>&print=1" class="btn-print" target="_blank"><i class="bi bi-printer"></i></a>
            </td>
          </tr>
          <?php endwhile; endif; ?>
        </tbody>
      </table>
    </div>
  </div>
</div>

<!-- GENERATE BILL MODAL -->
<div class="modal fade" id="generateModal" tabindex="-1">
  <div class="modal-dialog modal-dialog-centered">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title"><i class="bi bi-receipt me-2" style="color:#c9973a;"></i>Generate Bill</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
      </div>
      <div class="modal-body" style="padding:24px;">
        <form method="POST">
          <input type="hidden" name="order_id" id="modal_order_id">
          <input type="hidden" name="generate_bill" value="1">
          <div class="mb-3">
            <label class="form-label">Customer</label>
            <input type="text" id="modal_customer" class="form-control" readonly style="background:#fdf5ec;">
          </div>
          <div class="mb-3">
            <label class="form-label">Order Amount</label>
            <div class="input-group">
              <span class="input-group-text" style="background:#fdf5ec;border-color:#e8d8c0;font-size:13px;">Rs.</span>
              <input type="text" id="modal_amount" class="form-control" readonly style="background:#fdf5ec;">
            </div>
          </div>
          <div class="mb-3">
            <label class="form-label">Discount (Rs.)</label>
            <div class="input-group">
              <span class="input-group-text" style="background:#fdf5ec;border-color:#e8d8c0;font-size:13px;">Rs.</span>
              <input type="number" name="discount" id="modal_discount" class="form-control" value="0" min="0" oninput="calcTotal()">
            </div>
          </div>
          <div class="mb-4">
            <label class="form-label">Total Amount</label>
            <div class="input-group">
              <span class="input-group-text" style="background:#e8f5e9;border-color:#a5d6a7;font-size:13px;color:#2e7d32;">Rs.</span>
              <input type="text" id="modal_total" class="form-control" readonly style="background:#e8f5e9;color:#1b5e20;font-weight:700;font-size:15px;">
            </div>
          </div>
          <button type="submit" class="btn-gold w-100" style="padding:12px;font-size:15px;justify-content:center;">
            <i class="bi bi-receipt-cutoff me-2"></i>Generate Bill
          </button>
        </form>
      </div>
    </div>
  </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script>
let currentAmount = 0;
function openGenerateModal(orderId, amount, customer) {
  currentAmount = parseFloat(amount);
  document.getElementById('modal_order_id').value = orderId;
  document.getElementById('modal_customer').value = customer;
  document.getElementById('modal_amount').value   = amount.toFixed(2);
  document.getElementById('modal_discount').value = '0';
  document.getElementById('modal_total').value    = amount.toFixed(2);
  new bootstrap.Modal(document.getElementById('generateModal')).show();
}
function calcTotal() {
  const discount = parseFloat(document.getElementById('modal_discount').value) || 0;
  document.getElementById('modal_total').value = (currentAmount - discount).toFixed(2);
}
</script>
</body>
</html>
