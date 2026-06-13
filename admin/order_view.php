<?php
require_once '../includes/db.php';
if (!isLoggedIn() || !isAdmin()) redirect('../login.php');

$order_id = (int)$_GET['id'];

// direct bill generate from order view
if (isset($_POST['generate_bill_direct'])) {
    $discount = (float)$_POST['discount'];
    $order_amt= (float)$_POST['order_amount'];
    $total    = $order_amt - $discount;
    $last     = mysqli_fetch_assoc(mysqli_query($conn,
        "SELECT bill_id FROM bills ORDER BY bill_id DESC LIMIT 1"));
    $next_id     = $last ? $last['bill_id'] + 1 : 1;
    $bill_number = 'TDB-' . date('Y') . '-' . str_pad($next_id, 4, '0', STR_PAD_LEFT);
    $exists = mysqli_fetch_assoc(mysqli_query($conn,
        "SELECT bill_id FROM bills WHERE order_id=$order_id"));
    if (!$exists) {
        mysqli_query($conn,
            "INSERT INTO bills (order_id, bill_number, subtotal, discount, total)
             VALUES ($order_id, '$bill_number', $order_amt, $discount, $total)");
        mysqli_query($conn,
            "UPDATE orders SET payment_status='paid' WHERE order_id=$order_id");
    }
    redirect('order_view.php?id='.$order_id.'&billed=1');
}

// update status
if (isset($_POST['update_status'])) {
    $status = mysqli_real_escape_string($conn, $_POST['status']);
    mysqli_query($conn, "UPDATE orders SET status='$status' WHERE order_id=$order_id");
    redirect('order_view.php?id='.$order_id.'&updated=1');
}

$order = mysqli_fetch_assoc(mysqli_query($conn,
    "SELECT o.*, u.name as customer, u.phone, u.email, u.address
     FROM orders o LEFT JOIN users u ON o.user_id=u.user_id
     WHERE o.order_id=$order_id"));
if (!$order) redirect('orders.php');

$items = mysqli_query($conn,
    "SELECT oi.*, p.name as product_name, p.category
     FROM order_items oi JOIN products p ON oi.product_id=p.product_id
     WHERE oi.order_id=$order_id");

$bill = mysqli_fetch_assoc(mysqli_query($conn,
    "SELECT * FROM bills WHERE order_id=$order_id"));
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>Order #<?= $order_id ?> — Thilha Divine Bakes</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
<link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css" rel="stylesheet">
<style>
*{box-sizing:border-box;}
body{background:#f5f0eb;font-family:'Segoe UI',sans-serif;color:#2a1a10;margin:0;}
.page-title{font-size:22px;font-weight:700;color:#1a0a0f;margin-bottom:2px;}
.page-sub{font-size:13px;color:#8a6a4a;margin-bottom:24px;}
.card-box{background:#fff;border:1px solid #e8d8c0;border-radius:14px;padding:22px;margin-bottom:20px;}
.card-title{font-size:15px;font-weight:600;color:#1a0a0f;margin-bottom:16px;padding-bottom:10px;border-bottom:1px solid #f0e4d0;}
.info-item .lbl{font-size:11px;color:#c9973a;font-weight:700;letter-spacing:1px;text-transform:uppercase;margin-bottom:4px;}
.info-item .val{font-size:14px;color:#2a1a10;font-weight:500;}
.status-steps{display:flex;align-items:center;margin-bottom:20px;overflow-x:auto;}
.status-step{display:flex;flex-direction:column;align-items:center;gap:4px;flex-shrink:0;}
.status-dot{width:32px;height:32px;border-radius:50%;display:flex;align-items:center;justify-content:center;font-size:13px;}
.status-dot.done{background:#c9973a;color:#fff;}
.status-dot.active{background:#1a0a0f;color:#e8c870;border:2px solid #c9973a;}
.status-dot.todo{background:#f0e4d0;color:#b0a090;}
.status-label{font-size:11px;font-weight:600;white-space:nowrap;}
.status-label.done{color:#c9973a;}
.status-label.active{color:#1a0a0f;}
.status-label.todo{color:#b0a090;}
.status-line{flex:1;height:2px;background:#f0e4d0;margin:0 6px;margin-bottom:16px;min-width:24px;}
.status-line.done{background:#c9973a;}
.table{color:#2a1a10;margin-bottom:0;}
.table thead th{background:#fdf5ec;color:#8a5a20;font-size:12px;font-weight:600;border-color:#e8d8c0;padding:10px 14px;}
.table tbody td{border-color:#f5ede0;font-size:13px;padding:11px 14px;vertical-align:middle;}
.total-box{background:#1a0a0f;border-radius:10px;padding:16px 20px;}
.total-row{display:flex;justify-content:space-between;font-size:14px;padding:4px 0;color:#c9b090;}
.total-row.grand{font-size:18px;font-weight:700;color:#e8c870;border-top:1px solid #3a1a24;margin-top:8px;padding-top:10px;}
.btn-gold{background:#c9973a;border:none;border-radius:8px;color:#fff;font-weight:600;padding:9px 20px;font-size:13px;cursor:pointer;text-decoration:none;display:inline-flex;align-items:center;gap:6px;}
.btn-gold:hover{background:#a07828;color:#fff;}
.btn-back{background:#fff;border:1px solid #e8d8c0;border-radius:8px;color:#2a1a10;font-weight:600;padding:9px 20px;font-size:13px;text-decoration:none;display:inline-flex;align-items:center;gap:6px;}
.btn-back:hover{background:#fdf5ec;}
.btn-green{background:#2e7d32;border:none;border-radius:8px;color:#fff;font-weight:600;padding:9px 20px;font-size:13px;cursor:pointer;text-decoration:none;display:inline-flex;align-items:center;gap:6px;}
.btn-green:hover{background:#1b5e20;color:#fff;}
.alert-s{background:#e8f5e9;border:1px solid #a5d6a7;border-left:4px solid #2e7d32;border-radius:10px;padding:12px 16px;font-size:13px;color:#1b5e20;margin-bottom:20px;display:flex;align-items:center;gap:8px;}
.form-select{border:1px solid #e8d8c0;border-radius:8px;font-size:13px;color:#2a1a10;padding:9px 12px;}
.form-select:focus{border-color:#c9973a;box-shadow:0 0 0 3px rgba(201,151,58,0.15);}
.form-control{border:1px solid #e8d8c0;border-radius:8px;font-size:13px;color:#2a1a10;padding:9px 12px;}
.form-control:focus{border-color:#c9973a;box-shadow:0 0 0 3px rgba(201,151,58,0.15);}
.form-label{font-size:13px;color:#8a6a4a;font-weight:600;}
.modal-content{border-radius:14px;border:1px solid #e8d8c0;}
.modal-header{background:#fdf5ec;border-bottom:1px solid #e8d8c0;border-radius:14px 14px 0 0;}
.cat-badge{font-size:11px;padding:3px 9px;border-radius:99px;font-weight:500;}
.cat-cakes{background:#fce4ec;color:#880e4f;}
.cat-brownies{background:#fff8e1;color:#f57f17;}
.cat-cupcakes{background:#f3e5f5;color:#6a1b9a;}
.cat-pastries{background:#e8f5e9;color:#1b5e20;}
.cat-breads{background:#e3f2fd;color:#0d47a1;}
</style>
</head>
<body>
<?php include '../includes/sidebar.php'; ?>
<div class="main">

  <!-- TOP BUTTONS -->
  <div style="display:flex;align-items:center;gap:10px;margin-bottom:20px;flex-wrap:wrap;">
    <a href="orders.php" class="btn-back"><i class="bi bi-arrow-left"></i> Back</a>
    <?php if ($bill): ?>
      <a href="bill_print.php?id=<?= $bill['bill_id'] ?>" class="btn-gold"><i class="bi bi-receipt"></i> View Bill</a>
      <a href="bill_print.php?id=<?= $bill['bill_id'] ?>&print=1" class="btn-green" target="_blank"><i class="bi bi-printer"></i> Print Bill</a>
    <?php else: ?>
      <button onclick="document.getElementById('billModal').style.display='flex'" class="btn-gold">
        <i class="bi bi-receipt-cutoff"></i> Generate Bill
      </button>
    <?php endif; ?>
  </div>

  <div class="page-title"><i class="bi bi-bag me-2" style="color:#c9973a;"></i>Order #<?= $order_id ?></div>
  <div class="page-sub"><?= date('l, d F Y, h:i A', strtotime($order['created_at'])) ?></div>

  <?php if (isset($_GET['updated'])): ?>
  <div class="alert-s"><i class="bi bi-check-circle-fill"></i> Order status updated!</div>
  <?php elseif (isset($_GET['billed'])): ?>
  <div class="alert-s"><i class="bi bi-check-circle-fill"></i> Bill generated successfully!</div>
  <?php endif; ?>

  <div class="row g-4">
    <div class="col-md-8">

      <!-- STATUS -->
      <div class="card-box">
        <div class="card-title"><i class="bi bi-activity me-2" style="color:#c9973a;"></i>Order Status</div>
        <?php
        $statuses = ['pending','confirmed','baking','ready','delivered'];
        $icons    = ['bi-clock','bi-check','bi-fire','bi-box','bi-check-all'];
        $curr_idx = array_search($order['status'], $statuses);
        ?>
        <div class="status-steps" style="margin-bottom:20px;">
          <?php foreach ($statuses as $idx => $st):
            $state = $idx < $curr_idx ? 'done' : ($idx == $curr_idx ? 'active' : 'todo');
          ?>
          <div class="status-step">
            <div class="status-dot <?= $state ?>">
              <?php if ($state==='done'): ?><i class="bi bi-check"></i>
              <?php elseif ($state==='active'): ?><i class="bi <?= $icons[$idx] ?>"></i>
              <?php else: ?><?= $idx+1 ?>
              <?php endif; ?>
            </div>
            <span class="status-label <?= $state ?>"><?= ucfirst($st) ?></span>
          </div>
          <?php if ($idx < count($statuses)-1): ?>
          <div class="status-line <?= $idx < $curr_idx ? 'done' : '' ?>"></div>
          <?php endif; ?>
          <?php endforeach; ?>
        </div>
        <form method="POST" style="display:flex;align-items:center;gap:10px;flex-wrap:wrap;">
          <input type="hidden" name="update_status" value="1">
          <select name="status" class="form-select" style="width:200px;">
            <?php foreach ($statuses as $st): ?>
            <option value="<?= $st ?>" <?= $order['status']===$st?'selected':'' ?>><?= ucfirst($st) ?></option>
            <?php endforeach; ?>
          </select>
          <button type="submit" class="btn-gold"><i class="bi bi-check-lg"></i> Update Status</button>
        </form>
      </div>

      <!-- ORDER ITEMS -->
      <div class="card-box">
        <div class="card-title"><i class="bi bi-list-ul me-2" style="color:#c9973a;"></i>Order Items</div>
        <div class="table-responsive">
          <table class="table table-hover">
            <thead>
              <tr><th>#</th><th>Product</th><th>Category</th><th style="text-align:center;">Qty</th><th style="text-align:right;">Unit Price</th><th style="text-align:right;">Total</th></tr>
            </thead>
            <tbody>
              <?php
              $i = 1; $grand = 0;
              $icons_cat = ['cakes'=>'🎂','brownies'=>'🍫','cupcakes'=>'🧁','pastries'=>'🥐','breads'=>'🍞'];
              while ($item = mysqli_fetch_assoc($items)):
                $line = $item['quantity'] * $item['unit_price'];
                $grand += $line;
              ?>
              <tr>
                <td style="color:#8a6a4a;"><?= $i++ ?></td>
                <td><?= $icons_cat[$item['category']] ?? '🍰' ?> <strong><?= htmlspecialchars($item['product_name']) ?></strong></td>
                <td><span class="cat-badge cat-<?= $item['category'] ?>"><?= ucfirst($item['category']) ?></span></td>
                <td style="text-align:center;font-weight:600;"><?= $item['quantity'] ?></td>
                <td style="text-align:right;">Rs. <?= number_format($item['unit_price'],2) ?></td>
                <td style="text-align:right;"><strong>Rs. <?= number_format($line,2) ?></strong></td>
              </tr>
              <?php endwhile; ?>
            </tbody>
          </table>
        </div>
        <div style="display:flex;justify-content:flex-end;margin-top:16px;">
          <div class="total-box" style="min-width:260px;">
            <div class="total-row"><span>Subtotal</span><span>Rs. <?= number_format($grand,2) ?></span></div>
            <?php if ($bill && $bill['discount'] > 0): ?>
            <div class="total-row"><span>Discount</span><span style="color:#f0a050;">- Rs. <?= number_format($bill['discount'],2) ?></span></div>
            <?php endif; ?>
            <div class="total-row grand"><span>Grand Total</span><span>Rs. <?= number_format($order['total_amount'],2) ?></span></div>
          </div>
        </div>
      </div>
    </div>

    <div class="col-md-4">
      <!-- CUSTOMER INFO -->
      <div class="card-box">
        <div class="card-title"><i class="bi bi-person me-2" style="color:#c9973a;"></i>Customer Info</div>
        <div style="display:flex;flex-direction:column;gap:12px;">
          <div class="info-item"><div class="lbl">Name</div><div class="val"><?= htmlspecialchars($order['customer'] ?? 'Walk-in Customer') ?></div></div>
          <?php if ($order['phone']): ?><div class="info-item"><div class="lbl">Phone</div><div class="val"><?= $order['phone'] ?></div></div><?php endif; ?>
          <?php if ($order['email']): ?><div class="info-item"><div class="lbl">Email</div><div class="val" style="font-size:13px;"><?= $order['email'] ?></div></div><?php endif; ?>
          <?php if ($order['address']): ?><div class="info-item"><div class="lbl">Address</div><div class="val"><?= htmlspecialchars($order['address']) ?></div></div><?php endif; ?>
        </div>
      </div>

      <!-- ORDER INFO -->
      <div class="card-box">
        <div class="card-title"><i class="bi bi-info-circle me-2" style="color:#c9973a;"></i>Order Info</div>
        <div style="display:flex;flex-direction:column;gap:12px;">
          <div class="info-item"><div class="lbl">Order Type</div><div class="val"><?= ucfirst($order['order_type']) ?></div></div>
          <div class="info-item"><div class="lbl">Payment Method</div><div class="val"><?= ucfirst($order['payment_method']) ?></div></div>
          <div class="info-item">
            <div class="lbl">Payment Status</div>
            <div class="val">
              <?php if ($order['payment_status']==='paid'): ?>
                <span style="color:#2e7d32;font-weight:600;">✅ Paid</span>
              <?php else: ?>
                <span style="color:#e65c00;font-weight:600;">⏳ Unpaid</span>
              <?php endif; ?>
            </div>
          </div>
          <?php if ($order['notes']): ?>
          <div class="info-item"><div class="lbl">Notes</div><div class="val" style="font-size:13px;color:#8a6a4a;"><?= htmlspecialchars($order['notes']) ?></div></div>
          <?php endif; ?>
          <?php if ($bill): ?>
          <div class="info-item"><div class="lbl">Bill Number</div><div class="val" style="color:#c9973a;font-weight:700;"><?= $bill['bill_number'] ?></div></div>
          <?php endif; ?>
        </div>
      </div>
    </div>
  </div>
</div>

<!-- DIRECT BILL GENERATE MODAL -->
<?php if (!$bill): ?>
<div id="billModal" style="display:none;position:fixed;top:0;left:0;width:100%;height:100%;background:rgba(0,0,0,0.5);z-index:9999;align-items:center;justify-content:center;">
  <div style="background:#fff;border-radius:14px;padding:28px;width:100%;max-width:420px;margin:20px;">
    <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:20px;">
      <h5 style="margin:0;font-size:16px;font-weight:600;"><i class="bi bi-receipt-cutoff me-2" style="color:#c9973a;"></i>Generate Bill</h5>
      <button onclick="document.getElementById('billModal').style.display='none'" style="background:none;border:none;font-size:20px;cursor:pointer;color:#8a6a4a;">×</button>
    </div>
    <form method="POST">
      <input type="hidden" name="generate_bill_direct" value="1">
      <input type="hidden" name="order_amount" value="<?= $order['total_amount'] ?>">
      <div class="mb-3">
        <label class="form-label">Order Amount</label>
        <input type="text" class="form-control" value="Rs. <?= number_format($order['total_amount'],2) ?>" readonly style="background:#fdf5ec;">
      </div>
      <div class="mb-3">
        <label class="form-label">Discount (Rs.)</label>
        <input type="number" name="discount" id="disc" class="form-control" value="0" min="0" oninput="updTotal()">
      </div>
      <div class="mb-4">
        <label class="form-label">Total Amount</label>
        <input type="text" id="tot" class="form-control" value="Rs. <?= number_format($order['total_amount'],2) ?>" readonly style="background:#e8f5e9;color:#1b5e20;font-weight:700;">
      </div>
      <button type="submit" class="btn-gold w-100" style="padding:12px;font-size:15px;justify-content:center;">
        <i class="bi bi-receipt-cutoff me-2"></i>Generate Bill
      </button>
    </form>
  </div>
</div>
<script>
function updTotal(){
  const disc = parseFloat(document.getElementById('disc').value)||0;
  const total = <?= $order['total_amount'] ?> - disc;
  document.getElementById('tot').value = 'Rs. ' + total.toFixed(2);
}
</script>
<?php endif; ?>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
