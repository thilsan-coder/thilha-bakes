<?php
require_once '../includes/db.php';
if (!isLoggedIn() || !isCustomer()) redirect('../login.php');
if (isset($_POST['update_qty'])){$pid=(int)$_POST['product_id'];$qty=(int)$_POST['qty'];if($qty<=0)unset($_SESSION['cart'][$pid]);else $_SESSION['cart'][$pid]['qty']=$qty;redirect('cart.php');}
if (isset($_GET['remove'])){unset($_SESSION['cart'][(int)$_GET['remove']]);redirect('cart.php');}
$cart=$_SESSION['cart']??[];$subtotal=0;
foreach($cart as $item) $subtotal+=$item['price']*$item['qty'];
$delivery=150;$total=$subtotal+$delivery;
$loyalty=mysqli_fetch_assoc(mysqli_query($conn,"SELECT loyalty_points FROM users WHERE user_id={$_SESSION['user_id']}"))['loyalty_points'];
$icons=['cakes'=>'🎂','brownies'=>'🍫','cupcakes'=>'🧁','pastries'=>'🥐','breads'=>'🍞'];
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width,initial-scale=1">
<title>Cart — Thilha Divine Bakes</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
<link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css" rel="stylesheet">
<link href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@700;900&family=Inter:wght@300;400;600;700&display=swap" rel="stylesheet">
<style>
:root{--gold:#c9973a;--gold-light:#e8c870;--gold-dark:#8a6520;--dark:#1a0a0f;--cream:#fdf5ec;--cream2:#f5f0eb;--border:#e8d8c0;--text:#2a1a10;--muted:#8a6a4a;--white:#ffffff;--pink:#e8789a;}
*{box-sizing:border-box;margin:0;padding:0;}
body{background:var(--cream2);font-family:'Inter',sans-serif;color:var(--text);min-height:100vh;}
.page-wrap{padding:36px 5%;}
.page-heading{font-family:'Playfair Display',serif;font-size:32px;font-weight:900;color:var(--text);margin-bottom:6px;}
.page-sub{font-size:14px;color:var(--muted);margin-bottom:32px;}
.cart-item{background:var(--white);border:1.5px solid var(--border);border-radius:16px;padding:20px;margin-bottom:14px;display:flex;align-items:center;gap:20px;transition:all .2s;box-shadow:0 2px 8px rgba(0,0,0,0.04);}
.cart-item:hover{border-color:var(--gold);box-shadow:0 6px 20px rgba(201,151,58,0.1);}
.ci-icon{font-size:38px;flex-shrink:0;width:62px;height:62px;background:var(--cream2);border-radius:12px;display:flex;align-items:center;justify-content:center;border:1.5px solid var(--border);}
.ci-info{flex:1;}
.ci-name{font-family:'Playfair Display',serif;font-size:17px;font-weight:700;color:var(--text);margin-bottom:4px;}
.ci-unit{font-size:13px;color:var(--muted);}
.ci-qty{display:flex;align-items:center;gap:10px;}
.qty-btn{width:32px;height:32px;border-radius:8px;border:1.5px solid var(--border);background:var(--cream2);color:var(--dark);font-size:16px;cursor:pointer;display:flex;align-items:center;justify-content:center;font-weight:700;transition:all .2s;}
.qty-btn:hover{background:var(--gold);color:#fff;border-color:var(--gold);}
.qty-num{font-size:16px;font-weight:700;color:var(--text);min-width:28px;text-align:center;}
.ci-total{font-size:20px;font-weight:700;color:var(--gold);min-width:100px;text-align:right;}
.ci-del{background:#ffebee;border:1px solid #ffcdd2;border-radius:8px;color:#c62828;padding:8px 12px;cursor:pointer;font-size:14px;text-decoration:none;display:inline-flex;align-items:center;transition:all .2s;}
.ci-del:hover{background:#c62828;color:#fff;}
.summary-box{background:var(--white);border:1.5px solid var(--border);border-radius:20px;padding:28px;position:sticky;top:20px;box-shadow:0 4px 16px rgba(0,0,0,0.06);}
.summary-title{font-family:'Playfair Display',serif;font-size:20px;font-weight:700;color:var(--text);margin-bottom:18px;padding-bottom:14px;border-bottom:1px solid var(--border);}
.summary-row{display:flex;justify-content:space-between;font-size:14px;padding:7px 0;color:var(--muted);}
.summary-row.grand{font-size:22px;font-weight:700;color:var(--text);border-top:1.5px solid var(--border);margin-top:8px;padding-top:16px;}
.loyalty-box{background:linear-gradient(135deg,#fff8e8,var(--cream));border:1.5px solid #f0d080;border-radius:12px;padding:14px;margin:14px 0;font-size:13px;color:var(--gold-dark);display:flex;align-items:center;gap:8px;}
.btn-checkout{width:100%;background:linear-gradient(135deg,var(--gold),var(--gold-dark));color:#fff;border:none;border-radius:99px;padding:15px;font-size:15px;font-weight:700;cursor:pointer;margin-top:14px;transition:all .2s;display:flex;align-items:center;justify-content:center;gap:10px;text-decoration:none;box-shadow:0 4px 14px rgba(201,151,58,0.25);}
.btn-checkout:hover{background:linear-gradient(135deg,var(--gold-light),var(--gold));color:var(--dark);transform:translateY(-2px);box-shadow:0 8px 24px rgba(201,151,58,0.35);}
.btn-continue{width:100%;background:var(--cream2);color:var(--muted);border:1.5px solid var(--border);border-radius:99px;padding:12px;font-size:14px;font-weight:700;cursor:pointer;margin-top:10px;transition:all .2s;text-decoration:none;display:flex;align-items:center;justify-content:center;gap:8px;}
.btn-continue:hover{border-color:var(--gold);color:var(--gold);}
.empty-cart{text-align:center;padding:80px 20px;}
.empty-cart i{font-size:80px;color:var(--border);display:block;margin-bottom:20px;}
</style>
</head>
<body>
<?php include 'customer_nav.php'; ?>
<div class="page-wrap">
  <div class="page-heading">My <span style="color:var(--gold);font-style:italic;">Cart</span></div>
  <div class="page-sub"><?= count($cart) ?> item(s) in your cart</div>

  <?php if(empty($cart)): ?>
  <div class="empty-cart">
    <i class="bi bi-bag-x"></i>
    <p style="font-size:22px;color:var(--muted);margin-bottom:8px;">Your cart is empty!</p>
    <p style="font-size:14px;color:var(--muted);margin-bottom:24px;">Add some delicious items to get started.</p>
    <a href="home.php" style="background:linear-gradient(135deg,var(--gold),var(--gold-dark));color:#fff;border-radius:99px;padding:14px 36px;font-weight:700;text-decoration:none;font-size:15px;display:inline-flex;align-items:center;gap:8px;box-shadow:0 4px 14px rgba(201,151,58,0.25);">
      <i class="bi bi-bag-heart"></i> Shop Now
    </a>
  </div>
  <?php else: ?>
  <div class="row g-4">
    <div class="col-lg-8">
      <?php foreach($cart as $pid=>$item):
        $prod=mysqli_fetch_assoc(mysqli_query($conn,"SELECT category FROM products WHERE product_id=$pid"));
        $cat=$prod?$prod['category']:'cakes';
      ?>
      <div class="cart-item">
        <div class="ci-icon"><?= $icons[$cat]??'🍰' ?></div>
        <div class="ci-info">
          <div class="ci-name"><?= htmlspecialchars($item['name']) ?></div>
          <div class="ci-unit">Rs. <?= number_format($item['price'],2) ?> each</div>
        </div>
        <div class="ci-qty">
          <form method="POST" style="display:flex;align-items:center;gap:10px;">
            <input type="hidden" name="product_id" value="<?= $pid ?>">
            <input type="hidden" name="update_qty" value="1">
            <button type="submit" name="qty" value="<?= $item['qty']-1 ?>" class="qty-btn">−</button>
            <span class="qty-num"><?= $item['qty'] ?></span>
            <button type="submit" name="qty" value="<?= $item['qty']+1 ?>" class="qty-btn">+</button>
          </form>
        </div>
        <div class="ci-total">Rs. <?= number_format($item['price']*$item['qty'],2) ?></div>
        <a href="cart.php?remove=<?= $pid ?>" class="ci-del" onclick="return confirm('Remove?')"><i class="bi bi-trash"></i></a>
      </div>
      <?php endforeach; ?>
    </div>
    <div class="col-lg-4">
      <div class="summary-box">
        <div class="summary-title">Order Summary</div>
        <div class="summary-row"><span>Subtotal</span><span>Rs. <?= number_format($subtotal,2) ?></span></div>
        <div class="summary-row"><span>Delivery</span><span style="color:#2e7d32;font-weight:600;">Rs. <?= number_format($delivery,2) ?></span></div>
        <?php if($loyalty>0): ?>
        <div class="loyalty-box"><i class="bi bi-star-fill"></i><strong><?= $loyalty ?> loyalty points</strong> — worth Rs. <?= floor($loyalty/10) ?> at checkout!</div>
        <?php endif; ?>
        <div class="summary-row grand"><span>Total</span><span style="color:var(--gold);">Rs. <?= number_format($total,2) ?></span></div>
        <a href="checkout.php" class="btn-checkout"><i class="bi bi-lock-fill"></i> Proceed to Checkout</a>
        <a href="home.php" class="btn-continue"><i class="bi bi-arrow-left"></i> Continue Shopping</a>
      </div>
    </div>
  </div>
  <?php endif; ?>
</div>
</body>
</html>