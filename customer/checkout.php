<?php
require_once '../includes/db.php';
if (!isLoggedIn() || !isCustomer()) redirect('../login.php');

$uid  = (int)$_SESSION['user_id'];
$cart = $_SESSION['cart'] ?? [];
if (empty($cart)) redirect('home.php');

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['place_order'])) {
    checkCSRF();
    $payment     = e($conn, $_POST['payment_method']);
    $notes       = e($conn, $_POST['notes'] ?? '');
    $use_loyalty = isset($_POST['use_loyalty']) ? 1 : 0;

    $subtotal = 0;
    foreach ($cart as $item) $subtotal += $item['price'] * $item['qty'];
    $delivery_fee = 150;

    $loyalty_deduct = 0;
    if ($use_loyalty) {
        $user_pts = mysqli_fetch_assoc(mysqli_query($conn,"SELECT loyalty_points FROM users WHERE user_id=$uid"))['loyalty_points'];
        $max_deduct = floor($user_pts / 10);
        $loyalty_deduct = min($max_deduct, $subtotal);
        $pts_used = $loyalty_deduct * 10;
    }

    $total = $subtotal + $delivery_fee - $loyalty_deduct;

    $stmt = mysqli_prepare($conn,
        "INSERT INTO orders (user_id,total_amount,status,order_type,payment_method,payment_status,notes)
         VALUES (?,?,'pending','online',?,'unpaid',?)");
    mysqli_stmt_bind_param($stmt,'idss',$uid,$total,$payment,$notes);
    mysqli_stmt_execute($stmt);
    $order_id = mysqli_insert_id($conn);

    foreach ($cart as $pid => $item) {
        $qty=$item['qty']; $price=$item['price'];
        $s2=mysqli_prepare($conn,"INSERT INTO order_items (order_id,product_id,quantity,unit_price) VALUES (?,?,?,?)");
        mysqli_stmt_bind_param($s2,'iiid',$order_id,$pid,$qty,$price);
        mysqli_stmt_execute($s2);
    }

    $pts_earned = floor($subtotal / 10);
    if ($pts_earned > 0) {
        mysqli_query($conn,"UPDATE users SET loyalty_points=loyalty_points+$pts_earned WHERE user_id=$uid");
        mysqli_query($conn,"INSERT INTO loyalty_log (user_id,points,type,order_id) VALUES ($uid,$pts_earned,'earn',$order_id)");
    }
    if ($use_loyalty && isset($pts_used) && $pts_used > 0) {
        mysqli_query($conn,"UPDATE users SET loyalty_points=loyalty_points-$pts_used WHERE user_id=$uid");
        mysqli_query($conn,"INSERT INTO loyalty_log (user_id,points,type,order_id) VALUES ($uid,$pts_used,'redeem',$order_id)");
    }

    $_SESSION['cart'] = [];
    redirect('my_orders.php?ordered=1');
}

$subtotal = 0;
foreach ($cart as $item) $subtotal += $item['price'] * $item['qty'];
$delivery_fee = 150;
$total = $subtotal + $delivery_fee;
$user = mysqli_fetch_assoc(mysqli_query($conn,"SELECT * FROM users WHERE user_id=$uid"));
$loyalty_pts = $user['loyalty_points'];
$loyalty_value = floor($loyalty_pts / 10);
$icons = ['cakes'=>'🎂','brownies'=>'🍫','cupcakes'=>'🧁','pastries'=>'🥐','breads'=>'🍞'];
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width,initial-scale=1">
<title>Checkout — Thilha Divine Bakes</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
<link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css" rel="stylesheet">
<link href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@700;900&family=Inter:wght@300;400;600;700&display=swap" rel="stylesheet">
<style>
:root{--gold:#c9973a;--gold-light:#e8c870;--gold-dark:#8a6520;--dark:#1a0a0f;--cream:#fdf5ec;--cream2:#f5f0eb;--cream3:#f0e8dd;--border:#e8d8c0;--text:#2a1a10;--muted:#8a6a4a;--white:#ffffff;}
*{box-sizing:border-box;margin:0;padding:0;}
body{background:var(--cream2);font-family:'Inter',sans-serif;color:var(--text);min-height:100vh;}
.page-wrap{padding:36px 5%;}
.page-heading{font-family:'Playfair Display',serif;font-size:32px;font-weight:900;color:var(--text);margin-bottom:6px;}
.page-sub{font-size:14px;color:var(--muted);margin-bottom:32px;}

/* STEPS */
.checkout-steps{display:flex;align-items:center;gap:0;margin-bottom:32px;}
.step{display:flex;align-items:center;gap:8px;font-size:13px;font-weight:700;}
.step-num{width:28px;height:28px;border-radius:50%;display:flex;align-items:center;justify-content:center;font-size:12px;font-weight:700;}
.step.done .step-num{background:var(--gold);color:#fff;}
.step.active .step-num{background:var(--cream3);border:2px solid var(--gold);color:var(--gold);}
.step.todo .step-num{background:var(--cream3);color:var(--border);}
.step.done .step-label{color:var(--gold);}
.step.active .step-label{color:var(--text);}
.step.todo .step-label{color:var(--border);}
.step-line{flex:1;height:2px;background:var(--border);margin:0 12px;}
.step-line.done{background:var(--gold);}

/* FORM CARD */
.form-card{background:var(--white);border:1.5px solid var(--border);border-radius:16px;padding:24px;margin-bottom:20px;box-shadow:0 2px 8px rgba(0,0,0,0.04);}
.form-title{font-family:'Playfair Display',serif;font-size:18px;font-weight:700;color:var(--text);margin-bottom:18px;padding-bottom:14px;border-bottom:1px solid var(--border);display:flex;align-items:center;gap:8px;}
.form-title i{color:var(--gold);}
.form-label{font-size:12px;font-weight:700;color:var(--muted);margin-bottom:6px;display:block;letter-spacing:.5px;text-transform:uppercase;}
.form-control{width:100%;background:var(--cream);border:1.5px solid var(--border);border-radius:10px;padding:12px 16px;font-size:14px;color:var(--text);outline:none;transition:all .2s;font-family:'Inter',sans-serif;}
.form-control:focus{border-color:var(--gold);background:var(--white);box-shadow:0 0 0 3px rgba(201,151,58,0.1);}
.form-control::placeholder{color:var(--border);}
.form-control:disabled{opacity:.6;cursor:not-allowed;}

/* PAYMENT METHODS */
.pay-options{display:grid;grid-template-columns:repeat(3,1fr);gap:12px;margin-top:4px;}
.pay-option{background:var(--cream);border:2px solid var(--border);border-radius:12px;padding:16px;text-align:center;cursor:pointer;transition:all .2s;}
.pay-option:hover{border-color:var(--gold);}
.pay-option.selected{border-color:var(--gold);background:linear-gradient(135deg,#fff8e8,var(--cream));}
.pay-option input{display:none;}
.pay-icon{font-size:28px;margin-bottom:8px;}
.pay-name{font-size:13px;font-weight:700;color:var(--text);}

/* ORDER SUMMARY */
.summary-box{background:var(--white);border:1.5px solid var(--border);border-radius:20px;padding:26px;position:sticky;top:20px;box-shadow:0 4px 16px rgba(0,0,0,0.06);}
.summary-title{font-family:'Playfair Display',serif;font-size:20px;font-weight:700;color:var(--text);margin-bottom:18px;padding-bottom:14px;border-bottom:1px solid var(--border);}
.s-item{display:flex;align-items:center;justify-content:space-between;padding:10px 0;border-bottom:1px solid rgba(232,216,192,0.4);}
.s-item:last-of-type{border-bottom:none;}
.s-item-icon{font-size:20px;margin-right:8px;}
.s-item-name{font-size:13px;color:var(--muted);flex:1;}
.s-item-qty{font-size:13px;color:var(--muted);margin-right:10px;}
.s-item-price{font-size:14px;font-weight:700;color:var(--text);}
.summary-divider{border:none;border-top:1px solid var(--border);margin:14px 0;}
.summary-row{display:flex;justify-content:space-between;font-size:14px;padding:5px 0;color:var(--muted);}
.summary-row.grand{font-size:22px;font-weight:700;color:var(--text);padding:14px 0 0;border-top:1.5px solid var(--border);margin-top:6px;}

/* LOYALTY TOGGLE */
.loyalty-toggle{background:linear-gradient(135deg,#fff8e8,var(--cream));border:1.5px solid #f0d080;border-radius:12px;padding:14px 16px;display:flex;align-items:center;justify-content:space-between;gap:12px;margin:14px 0;cursor:pointer;transition:all .2s;}
.loyalty-toggle:hover{border-color:var(--gold);}
.toggle-switch{width:44px;height:24px;background:var(--border);border-radius:12px;position:relative;flex-shrink:0;transition:background .2s;}
.toggle-switch.on{background:var(--gold);}
.toggle-switch::after{content:'';position:absolute;top:3px;left:3px;width:18px;height:18px;border-radius:50%;background:#fff;transition:left .2s;box-shadow:0 1px 4px rgba(0,0,0,0.2);}
.toggle-switch.on::after{left:23px;}

/* PLACE ORDER BUTTON */
.btn-place-order{width:100%;background:linear-gradient(135deg,var(--gold),var(--gold-dark));color:#fff;border:none;border-radius:99px;padding:16px;font-size:16px;font-weight:700;cursor:pointer;margin-top:18px;transition:all .2s;display:flex;align-items:center;justify-content:center;gap:10px;letter-spacing:.3px;box-shadow:0 4px 14px rgba(201,151,58,0.25);}
.btn-place-order:hover{background:linear-gradient(135deg,var(--gold-light),var(--gold));color:var(--dark);transform:translateY(-2px);box-shadow:0 8px 28px rgba(201,151,58,0.35);}
.btn-back{display:inline-flex;align-items:center;gap:6px;color:var(--muted);text-decoration:none;font-size:14px;font-weight:600;margin-bottom:20px;transition:color .2s;}
.btn-back:hover{color:var(--gold);}

@media(max-width:768px){.pay-options{grid-template-columns:1fr 1fr;}}
</style>
</head>
<body>
<?php include 'customer_nav.php'; ?>

<div class="page-wrap">
  <a href="cart.php" class="btn-back"><i class="bi bi-arrow-left"></i> Back to Cart</a>
  <div class="page-heading">Checkout</div>
  <div class="page-sub">Complete your order</div>

  <!-- STEPS -->
  <div class="checkout-steps">
    <div class="step done"><div class="step-num"><i class="bi bi-check"></i></div><span class="step-label">Cart</span></div>
    <div class="step-line done"></div>
    <div class="step active"><div class="step-num">2</div><span class="step-label">Checkout</span></div>
    <div class="step-line"></div>
    <div class="step todo"><div class="step-num">3</div><span class="step-label">Confirmed</span></div>
  </div>

  <form method="POST">
    <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?>">
    <input type="hidden" name="place_order" value="1">
    <input type="hidden" name="use_loyalty" id="use_loyalty_input" value="0">

    <div class="row g-4">
      <div class="col-lg-7">

        <!-- DELIVERY INFO -->
        <div class="form-card">
          <div class="form-title"><i class="bi bi-geo-alt-fill"></i>Delivery Information</div>
          <div class="row g-3">
            <div class="col-md-6">
              <label class="form-label">Full Name</label>
              <input type="text" class="form-control" value="<?= htmlspecialchars($user['name']) ?>" disabled>
            </div>
            <div class="col-md-6">
              <label class="form-label">Phone Number</label>
              <input type="text" class="form-control" value="<?= htmlspecialchars($user['phone']??'') ?>" disabled>
            </div>
            <div class="col-12">
              <label class="form-label">Delivery Address</label>
              <input type="text" class="form-control" value="<?= htmlspecialchars($user['address']??'') ?>" disabled>
              <div style="font-size:12px;color:var(--muted);margin-top:6px;">
                <i class="bi bi-info-circle me-1" style="color:var(--gold);"></i>
                Update your address in <a href="profile.php" style="color:var(--gold);font-weight:700;">Profile</a>
              </div>
            </div>
            <div class="col-12">
              <label class="form-label">Order Notes <small style="color:var(--border);font-weight:400;text-transform:none;">(optional)</small></label>
              <textarea name="notes" class="form-control" rows="2" placeholder="Special instructions, custom messages..."></textarea>
            </div>
          </div>
        </div>

        <!-- PAYMENT METHOD -->
        <div class="form-card">
          <div class="form-title"><i class="bi bi-credit-card-fill"></i>Payment Method</div>
          <div class="pay-options">
            <label class="pay-option selected" id="pay-cash" onclick="selectPay('cash',this)">
              <input type="radio" name="payment_method" value="cash" checked>
              <div class="pay-icon">💵</div>
              <div class="pay-name">Cash on Delivery</div>
            </label>
            <label class="pay-option" id="pay-card" onclick="selectPay('card',this)">
              <input type="radio" name="payment_method" value="card">
              <div class="pay-icon">💳</div>
              <div class="pay-name">Card Payment</div>
            </label>
            <label class="pay-option" id="pay-online" onclick="selectPay('online',this)">
              <input type="radio" name="payment_method" value="online">
              <div class="pay-icon">📱</div>
              <div class="pay-name">Online Transfer</div>
            </label>
          </div>
        </div>

      </div>

      <!-- ORDER SUMMARY -->
      <div class="col-lg-5">
        <div class="summary-box">
          <div class="summary-title">Order Summary</div>

          <?php foreach($cart as $pid=>$item):
            $prod=mysqli_fetch_assoc(mysqli_query($conn,"SELECT category FROM products WHERE product_id=$pid"));
            $cat=$prod?$prod['category']:'cakes';
          ?>
          <div class="s-item">
            <span class="s-item-icon"><?= $icons[$cat]??'🍰' ?></span>
            <span class="s-item-name"><?= htmlspecialchars($item['name']) ?></span>
            <span class="s-item-qty">×<?= $item['qty'] ?></span>
            <span class="s-item-price">Rs. <?= number_format($item['price']*$item['qty'],0) ?></span>
          </div>
          <?php endforeach; ?>

          <hr class="summary-divider">
          <div class="summary-row"><span>Subtotal</span><span>Rs. <?= number_format($subtotal,0) ?></span></div>
          <div class="summary-row"><span>Delivery Fee</span><span style="color:#2e7d32;font-weight:600;">Rs. <?= number_format($delivery_fee,0) ?></span></div>

          <?php if($loyalty_pts >= 10): ?>
          <div class="loyalty-toggle" onclick="toggleLoyalty()" id="loyaltyToggle">
            <div>
              <div style="font-size:14px;color:var(--gold-dark);font-weight:700;">
                <i class="bi bi-star-fill me-2" style="color:var(--gold);"></i>Redeem Loyalty Points
              </div>
              <div style="font-size:12px;color:var(--muted);margin-top:3px;">
                <?= $loyalty_pts ?> pts = Rs. <?= $loyalty_value ?> discount
              </div>
            </div>
            <div class="toggle-switch" id="loyaltySwitch"></div>
          </div>
          <div class="summary-row" id="loyaltyRow" style="display:none;color:#2e7d32;">
            <span>Loyalty Discount</span>
            <span>- Rs. <span id="loyaltyAmt"><?= $loyalty_value ?></span></span>
          </div>
          <?php endif; ?>

          <div class="summary-row grand">
            <span>Total</span>
            <span style="color:var(--gold);" id="grandTotal">Rs. <?= number_format($total,0) ?></span>
          </div>

          <button type="submit" class="btn-place-order">
            <i class="bi bi-bag-check-fill"></i> Place Order
          </button>

          <div style="text-align:center;font-size:12px;color:var(--muted);margin-top:12px;">
            <i class="bi bi-shield-check me-1" style="color:var(--gold);"></i>
            Secure checkout — your details are safe
          </div>
        </div>
      </div>
    </div>
  </form>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script>
function selectPay(method, el) {
  document.querySelectorAll('.pay-option').forEach(o => o.classList.remove('selected'));
  el.classList.add('selected');
  el.querySelector('input').checked = true;
}

let loyaltyOn = false;
const subtotal    = <?= $subtotal ?>;
const delivery    = <?= $delivery_fee ?>;
const loyaltyVal  = <?= $loyalty_value ?>;

function toggleLoyalty() {
  loyaltyOn = !loyaltyOn;
  const sw   = document.getElementById('loyaltySwitch');
  const row  = document.getElementById('loyaltyRow');
  const tot  = document.getElementById('grandTotal');
  const inp  = document.getElementById('use_loyalty_input');

  sw.classList.toggle('on', loyaltyOn);
  row.style.display = loyaltyOn ? 'flex' : 'none';
  inp.value = loyaltyOn ? '1' : '0';

  const finalTotal = subtotal + delivery - (loyaltyOn ? loyaltyVal : 0);
  tot.textContent = 'Rs. ' + finalTotal.toLocaleString();
}
</script>
</body>
</html>