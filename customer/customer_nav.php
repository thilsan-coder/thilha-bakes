<?php
$current_page = basename($_SERVER['PHP_SELF']);
$cart_count   = isset($_SESSION['cart']) ? array_sum(array_column($_SESSION['cart'],'qty')) : 0;
$user_name    = $_SESSION['name'] ?? '';
?>
<style>
:root{
  --gold:#c9973a;--gold-light:#e8c870;--gold-dark:#8a6520;
  --dark:#1a0a0f;--dark2:#220d14;
  --cream:#fdf5ec;--cream2:#f5f0eb;--cream3:#f0e8dd;
  --border:#e8d8c0;--text:#2a1a10;--muted:#8a6a4a;
  --pink:#e8789a;
}
*{box-sizing:border-box;margin:0;padding:0;}
body{background:var(--cream2);font-family:'Segoe UI',sans-serif;color:var(--text);}

/* NAVBAR */
.cust-nav{
  position:sticky;top:0;z-index:999;
  background:linear-gradient(90deg,#1a0a0f 0%,#220d14 50%,#1a0a0f 100%);
  border-bottom:3px solid var(--gold);
  padding:0 5%;
  display:flex;align-items:center;
  height:64px;
  box-shadow:0 4px 20px rgba(0,0,0,0.2);
}
.cust-brand{display:flex;align-items:center;gap:10px;text-decoration:none;margin-right:auto;}
.cust-brand-icon{font-size:26px;}
.cust-brand-text .name{font-family:Georgia,serif;font-size:16px;font-weight:900;color:#e8c870;letter-spacing:3px;line-height:1;}
.cust-brand-text .sub{font-size:9px;color:var(--gold);letter-spacing:3px;margin-top:1px;}
.cust-nav-links{display:flex;align-items:center;gap:4px;margin:0 16px;}
.cust-nav-link{
  color:rgba(201,176,144,0.75);font-size:13px;font-weight:600;
  text-decoration:none;padding:6px 14px;border-radius:8px;
  transition:all .2s;display:flex;align-items:center;gap:6px;white-space:nowrap;
}
.cust-nav-link:hover,.cust-nav-link.active{color:#e8c870;background:rgba(201,151,58,0.12);}
.cust-cart-btn{
  background:linear-gradient(135deg,var(--gold),var(--gold-dark));
  color:#fff;font-size:13px;font-weight:700;
  text-decoration:none;padding:8px 18px;border-radius:99px;
  display:flex;align-items:center;gap:8px;
  transition:all .2s;box-shadow:0 2px 8px rgba(201,151,58,0.3);
  white-space:nowrap;margin-left:8px;
}
.cust-cart-btn:hover{transform:translateY(-1px);box-shadow:0 4px 14px rgba(201,151,58,0.45);color:#fff;}
.cart-badge{background:var(--pink);color:#fff;font-size:10px;font-weight:700;width:18px;height:18px;border-radius:50%;display:flex;align-items:center;justify-content:center;flex-shrink:0;}
.cust-user-avatar{width:30px;height:30px;border-radius:50%;background:rgba(201,151,58,0.2);border:1.5px solid rgba(201,151,58,0.4);display:flex;align-items:center;justify-content:center;font-size:13px;font-weight:700;color:var(--gold);text-decoration:none;transition:all .2s;margin-left:8px;}
.cust-user-avatar:hover{background:rgba(201,151,58,0.35);}
.mobile-menu-btn{display:none;background:none;border:none;color:var(--gold);font-size:22px;cursor:pointer;padding:4px;margin-left:8px;}
@media(max-width:768px){
  .cust-nav-links{display:none;}
  .cust-nav-links.open{display:flex;flex-direction:column;position:absolute;top:64px;left:0;right:0;background:#1a0a0f;border-bottom:2px solid var(--gold);padding:12px 5%;gap:4px;z-index:998;}
  .mobile-menu-btn{display:block;}
}
</style>

<nav class="cust-nav">
  <a href="landing.php" class="cust-brand">
    <div class="cust-brand-icon">🎂</div>
    <div class="cust-brand-text">
      <div class="name">THILHA</div>
      <div class="sub">DIVINE BAKES</div>
    </div>
  </a>
  <div class="cust-nav-links" id="navLinks">
    <a href="landing.php" class="cust-nav-link <?= $current_page==='landing.php'?'active':'' ?>"><i class="bi bi-house-fill"></i> Home</a>
    <a href="home.php"    class="cust-nav-link <?= $current_page==='home.php'   ?'active':'' ?>"><i class="bi bi-bag-heart-fill"></i> Shop</a>
    <a href="my_orders.php" class="cust-nav-link <?= $current_page==='my_orders.php'?'active':'' ?>"><i class="bi bi-bag-check-fill"></i> My Orders</a>
    <a href="profile.php" class="cust-nav-link <?= $current_page==='profile.php'?'active':'' ?>"><i class="bi bi-person-fill"></i> Profile</a>
  </div>
  <a href="cart.php" class="cust-cart-btn">
    <i class="bi bi-bag"></i> Cart
    <?php if($cart_count>0): ?><span class="cart-badge"><?= $cart_count ?></span><?php endif; ?>
  </a>
  <a href="profile.php" class="cust-user-avatar" title="<?= htmlspecialchars($user_name) ?>">
    <?= strtoupper(substr($user_name,0,1)) ?>
  </a>
  <button class="mobile-menu-btn" onclick="toggleNav()"><i class="bi bi-list" id="menuIcon"></i></button>
</nav>
<script>
function toggleNav(){
  var l=document.getElementById('navLinks'),i=document.getElementById('menuIcon');
  var o=l.classList.toggle('open');
  i.className=o?'bi bi-x-lg':'bi bi-list';
}
</script>