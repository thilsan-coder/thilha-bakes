<?php
require_once '../includes/db.php';
if (!isLoggedIn()) redirect('../login.php');

$bill_id = (int)$_GET['id'];

$bill = mysqli_fetch_assoc(mysqli_query($conn,
    "SELECT b.*, o.order_type, o.payment_method, o.payment_status,
            o.notes, o.created_at as order_date,
            u.name as customer, u.phone, u.email, u.address
     FROM bills b
     JOIN orders o ON b.order_id=o.order_id
     LEFT JOIN users u ON o.user_id=u.user_id
     WHERE b.bill_id=$bill_id"));

if (!$bill) redirect('billing.php');

$items = mysqli_query($conn,
    "SELECT oi.*, p.name as product_name, p.category
     FROM order_items oi
     JOIN products p ON oi.product_id=p.product_id
     WHERE oi.order_id={$bill['order_id']}");

$settings = [];
$sq = mysqli_query($conn,"SELECT setting_key,setting_value FROM settings");
while($s=mysqli_fetch_assoc($sq)) $settings[$s['setting_key']]=$s['setting_value'];

$bakery_name    = $settings['bakery_name']    ?? 'Thilha Divine Bakes';
$bakery_address = $settings['bakery_address'] ?? 'Central Camp, Ampara, Sri Lanka';
$bakery_phone   = $settings['bakery_phone']   ?? '+94 77 XXX XXXX';
$bakery_email   = $settings['bakery_email']   ?? 'info@thilhabakes.com';
$bakery_hours   = $settings['bakery_hours']   ?? 'Mon-Sat: 7AM - 8PM';

$auto_print = isset($_GET['print']);
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>Receipt <?= $bill['bill_number'] ?></title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css" rel="stylesheet">
<style>
/* ===== SCREEN STYLES ===== */
* { box-sizing: border-box; margin: 0; padding: 0; }
body {
  background: #f0f0f0;
  font-family: 'Courier New', Courier, monospace;
  display: flex;
  flex-direction: column;
  align-items: center;
  padding: 30px 20px;
  min-height: 100vh;
}

/* ACTION BAR — screen only */
.action-bar {
  width: 100%;
  max-width: 400px;
  display: flex;
  gap: 10px;
  margin-bottom: 20px;
  flex-wrap: wrap;
}
.btn-print {
  flex: 1;
  background: #1a0a0f;
  color: #e8c870;
  border: none;
  border-radius: 8px;
  padding: 12px 20px;
  font-size: 14px;
  font-weight: 700;
  cursor: pointer;
  display: flex;
  align-items: center;
  justify-content: center;
  gap: 8px;
  transition: background .2s;
  font-family: sans-serif;
}
.btn-print:hover { background: #c9973a; color: #1a0a0f; }
.btn-back {
  background: #fff;
  color: #333;
  border: 1px solid #ddd;
  border-radius: 8px;
  padding: 12px 20px;
  font-size: 14px;
  font-weight: 700;
  cursor: pointer;
  text-decoration: none;
  display: flex;
  align-items: center;
  gap: 8px;
  font-family: sans-serif;
  transition: background .2s;
}
.btn-back:hover { background: #f5f5f5; }

/* RECEIPT WRAPPER */
.receipt-wrapper {
  background: #fff;
  width: 100%;
  max-width: 380px;
  box-shadow: 0 4px 20px rgba(0,0,0,0.15);
  border-radius: 4px;
  overflow: hidden;
}

/* RECEIPT TOP TEAR */
.tear-top {
  height: 16px;
  background: repeating-linear-gradient(
    90deg,
    #fff 0px, #fff 12px,
    transparent 12px, transparent 20px
  );
  background-color: #f0f0f0;
}

/* RECEIPT BODY */
.receipt {
  padding: 20px 24px;
  background: #fff;
}

/* HEADER */
.receipt-header {
  text-align: center;
  margin-bottom: 16px;
  padding-bottom: 14px;
  border-bottom: 1px dashed #ccc;
}
.receipt-logo {
  font-size: 36px;
  display: block;
  margin-bottom: 8px;
}
.receipt-name {
  font-size: 18px;
  font-weight: 700;
  letter-spacing: 3px;
  text-transform: uppercase;
  color: #1a0a0f;
  font-family: sans-serif;
}
.receipt-sub {
  font-size: 11px;
  letter-spacing: 2px;
  color: #c9973a;
  text-transform: uppercase;
  margin-top: 3px;
  font-family: sans-serif;
}
.receipt-info {
  font-size: 11px;
  color: #666;
  margin-top: 8px;
  line-height: 1.6;
  font-family: sans-serif;
}

/* BILL META */
.bill-meta {
  margin-bottom: 14px;
  padding-bottom: 14px;
  border-bottom: 1px dashed #ccc;
}
.meta-row {
  display: flex;
  justify-content: space-between;
  font-size: 12px;
  padding: 3px 0;
  font-family: sans-serif;
}
.meta-row .label { color: #888; }
.meta-row .value { color: #1a0a0f; font-weight: 600; text-align: right; max-width: 60%; }

/* BILL NUMBER HIGHLIGHT */
.bill-number-box {
  background: #1a0a0f;
  color: #e8c870;
  text-align: center;
  padding: 8px;
  border-radius: 4px;
  font-size: 14px;
  font-weight: 700;
  letter-spacing: 2px;
  margin-bottom: 14px;
  font-family: sans-serif;
}

/* ITEMS */
.items-header {
  display: flex;
  justify-content: space-between;
  font-size: 11px;
  font-weight: 700;
  color: #888;
  text-transform: uppercase;
  letter-spacing: 1px;
  padding: 6px 0;
  border-bottom: 1px solid #eee;
  font-family: sans-serif;
}
.item-row {
  padding: 8px 0;
  border-bottom: 1px solid #f5f5f5;
}
.item-name {
  font-size: 13px;
  font-weight: 700;
  color: #1a0a0f;
  margin-bottom: 3px;
  font-family: sans-serif;
}
.item-detail {
  display: flex;
  justify-content: space-between;
  font-size: 12px;
  color: #888;
  font-family: sans-serif;
}
.item-total {
  font-size: 13px;
  font-weight: 700;
  color: #1a0a0f;
  font-family: sans-serif;
}

/* TOTALS */
.totals-section {
  margin-top: 12px;
  padding-top: 12px;
  border-top: 1px dashed #ccc;
}
.total-row {
  display: flex;
  justify-content: space-between;
  font-size: 13px;
  padding: 4px 0;
  font-family: sans-serif;
}
.total-row .label { color: #666; }
.total-row .value { font-weight: 600; color: #1a0a0f; }
.total-row.discount .value { color: #c62828; }
.grand-total {
  display: flex;
  justify-content: space-between;
  font-size: 18px;
  font-weight: 700;
  padding: 12px 0 0;
  margin-top: 8px;
  border-top: 2px solid #1a0a0f;
  font-family: sans-serif;
}
.grand-total .label { color: #1a0a0f; }
.grand-total .value { color: #c9973a; }

/* PAYMENT STATUS */
.payment-status {
  text-align: center;
  margin-top: 14px;
  padding: 10px;
  border-radius: 4px;
  font-size: 13px;
  font-weight: 700;
  font-family: sans-serif;
  letter-spacing: 1px;
  text-transform: uppercase;
}
.status-paid { background: #e8f5e9; color: #2e7d32; border: 1px solid #a5d6a7; }
.status-unpaid { background: #fff3e0; color: #e65c00; border: 1px solid #ffcc80; }

/* BARCODE SIMULATION */
.barcode-area {
  text-align: center;
  margin: 16px 0;
  padding: 12px 0;
  border-top: 1px dashed #ccc;
  border-bottom: 1px dashed #ccc;
}
.barcode {
  display: flex;
  align-items: flex-end;
  justify-content: center;
  gap: 2px;
  height: 40px;
  margin-bottom: 6px;
}
.bar {
  background: #1a0a0f;
  width: 2px;
  border-radius: 1px;
}
.barcode-text {
  font-size: 11px;
  color: #666;
  letter-spacing: 3px;
  font-family: sans-serif;
}

/* FOOTER */
.receipt-footer {
  text-align: center;
  margin-top: 16px;
  font-family: sans-serif;
}
.footer-msg {
  font-size: 13px;
  font-weight: 700;
  color: #1a0a0f;
  margin-bottom: 6px;
}
.footer-sub {
  font-size: 11px;
  color: #888;
  line-height: 1.6;
  margin-bottom: 10px;
}
.footer-social {
  font-size: 11px;
  color: #c9973a;
  font-weight: 700;
  letter-spacing: 1px;
}

/* BOTTOM TEAR */
.tear-bottom {
  height: 16px;
  background: repeating-linear-gradient(
    90deg,
    #fff 0px, #fff 12px,
    transparent 12px, transparent 20px
  );
  background-color: #f0f0f0;
  transform: rotate(180deg);
}

/* ===== PRINT STYLES ===== */
@media print {
  * { -webkit-print-color-adjust: exact !important; print-color-adjust: exact !important; }
  body {
    background: #fff !important;
    padding: 0 !important;
    margin: 0 !important;
  }
  .action-bar { display: none !important; }
  .receipt-wrapper {
    box-shadow: none !important;
    border-radius: 0 !important;
    max-width: 100% !important;
    width: 80mm !important;
    margin: 0 auto !important;
  }
  .tear-top, .tear-bottom { display: none !important; }
  @page {
    size: 80mm auto;
    margin: 4mm;
  }
}
</style>
</head>
<body>

<!-- ACTION BAR -->
<div class="action-bar">
  <a href="<?= isAdmin() ? 'billing.php' : '../customer/my_orders.php' ?>" class="btn-back">
    <i class="bi bi-arrow-left"></i> Back
  </a>
  <button class="btn-print" onclick="window.print()">
    <i class="bi bi-printer-fill"></i> Print Receipt
  </button>
</div>

<!-- RECEIPT -->
<div class="receipt-wrapper">
  <div class="tear-top"></div>

  <div class="receipt">

    <!-- HEADER -->
    <div class="receipt-header">
      <span class="receipt-logo">🎂</span>
      <div class="receipt-name"><?= htmlspecialchars($bakery_name) ?></div>
      <div class="receipt-sub">Divine Bakes</div>
      <div class="receipt-info">
        <?= htmlspecialchars($bakery_address) ?><br>
        Tel: <?= htmlspecialchars($bakery_phone) ?><br>
        <?= htmlspecialchars($bakery_hours) ?>
      </div>
    </div>

    <!-- BILL NUMBER -->
    <div class="bill-number-box">
      RECEIPT: <?= $bill['bill_number'] ?>
    </div>

    <!-- META INFO -->
    <div class="bill-meta">
      <div class="meta-row">
        <span class="label">Date</span>
        <span class="value"><?= date('d M Y', strtotime($bill['issued_at'])) ?></span>
      </div>
      <div class="meta-row">
        <span class="label">Time</span>
        <span class="value"><?= date('h:i A', strtotime($bill['issued_at'])) ?></span>
      </div>
      <div class="meta-row">
        <span class="label">Order #</span>
        <span class="value">#<?= $bill['order_id'] ?></span>
      </div>
      <div class="meta-row">
        <span class="label">Order Type</span>
        <span class="value"><?= ucfirst($bill['order_type']) ?></span>
      </div>
      <?php if ($bill['customer']): ?>
      <div class="meta-row">
        <span class="label">Customer</span>
        <span class="value"><?= htmlspecialchars($bill['customer']) ?></span>
      </div>
      <?php endif; ?>
      <?php if ($bill['phone']): ?>
      <div class="meta-row">
        <span class="label">Phone</span>
        <span class="value"><?= $bill['phone'] ?></span>
      </div>
      <?php endif; ?>
      <div class="meta-row">
        <span class="label">Payment</span>
        <span class="value"><?= ucfirst($bill['payment_method']) ?></span>
      </div>
    </div>

    <!-- ITEMS -->
    <div class="items-header">
      <span>Item</span>
      <span>Amount</span>
    </div>

    <?php while ($item = mysqli_fetch_assoc($items)): ?>
    <div class="item-row">
      <div class="item-name"><?= htmlspecialchars($item['product_name']) ?></div>
      <div class="item-detail">
        <span><?= $item['quantity'] ?> x Rs. <?= number_format($item['unit_price'],2) ?></span>
        <span class="item-total">Rs. <?= number_format($item['quantity']*$item['unit_price'],2) ?></span>
      </div>
    </div>
    <?php endwhile; ?>

    <!-- TOTALS -->
    <div class="totals-section">
      <div class="total-row">
        <span class="label">Subtotal</span>
        <span class="value">Rs. <?= number_format($bill['subtotal'],2) ?></span>
      </div>
      <?php if ($bill['discount'] > 0): ?>
      <div class="total-row discount">
        <span class="label">Discount</span>
        <span class="value">- Rs. <?= number_format($bill['discount'],2) ?></span>
      </div>
      <?php endif; ?>
      <?php if ($bill['tax'] > 0): ?>
      <div class="total-row">
        <span class="label">Tax</span>
        <span class="value">Rs. <?= number_format($bill['tax'],2) ?></span>
      </div>
      <?php endif; ?>
      <div class="grand-total">
        <span class="label">TOTAL</span>
        <span class="value">Rs. <?= number_format($bill['total'],2) ?></span>
      </div>
    </div>

    <!-- PAYMENT STATUS -->
    <div class="payment-status <?= $bill['payment_status']==='paid' ? 'status-paid' : 'status-unpaid' ?>">
      <?php if ($bill['payment_status']==='paid'): ?>
        ✅ PAID — <?= ucfirst($bill['payment_method']) ?>
      <?php else: ?>
        ⏳ PAYMENT PENDING
      <?php endif; ?>
    </div>

    <?php if ($bill['notes']): ?>
    <div style="margin-top:12px;padding:10px;background:#fdf5ec;border-radius:4px;font-size:12px;color:#8a5a20;font-family:sans-serif;">
      <strong>Note:</strong> <?= htmlspecialchars($bill['notes']) ?>
    </div>
    <?php endif; ?>

    <!-- BARCODE -->
    <div class="barcode-area">
      <div class="barcode">
        <?php
        // Generate pseudo barcode from bill number
        $seed = crc32($bill['bill_number']);
        srand($seed);
        for ($i = 0; $i < 45; $i++) {
            $h = rand(15, 40);
            echo "<div class='bar' style='height:{$h}px;width:".rand(1,3)."px;'></div>";
        }
        ?>
      </div>
      <div class="barcode-text"><?= $bill['bill_number'] ?></div>
    </div>

    <!-- FOOTER -->
    <div class="receipt-footer">
      <div class="footer-msg">Thank You for Your Order! 🎂</div>
      <div class="footer-sub">
        We appreciate your business.<br>
        Visit us again at Thilha Divine Bakes!
      </div>
      <div class="footer-social">
        📱 WhatsApp: <?= htmlspecialchars($bakery_phone) ?>
      </div>
      <div style="margin-top:10px;font-size:10px;color:#ccc;font-family:sans-serif;">
        <?= htmlspecialchars($bakery_email) ?>
      </div>
    </div>

  </div>

  <div class="tear-bottom"></div>
</div>

<?php if ($auto_print): ?>
<script>window.onload = function(){ setTimeout(function(){ window.print(); }, 500); };</script>
<?php endif; ?>

</body>
</html>
