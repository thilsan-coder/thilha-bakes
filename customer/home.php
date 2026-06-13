<?php
require_once '../includes/db.php';
if (!isLoggedIn() || !isCustomer()) redirect('../login.php');

$cat    = isset($_GET['cat'])    ? $_GET['cat']    : '';
$search = isset($_GET['search']) ? $_GET['search'] : '';
$where  = "WHERE stock_status='available'";
if ($cat)    $where .= " AND category='".e($conn,$cat)."'";
if ($search) $where .= " AND name LIKE '%".e($conn,$search)."%'";
$products   = mysqli_query($conn,"SELECT * FROM products $where ORDER BY category,name");
$loyalty    = mysqli_fetch_assoc(mysqli_query($conn,"SELECT loyalty_points FROM users WHERE user_id={$_SESSION['user_id']}"))['loyalty_points'];
$icons      = ['cakes'=>'🎂','brownies'=>'🍫','cupcakes'=>'🧁','pastries'=>'🥐','breads'=>'🍞'];
$cats_q     = mysqli_query($conn,"SELECT DISTINCT category FROM products WHERE stock_status='available' ORDER BY category");
$categories = [];
while($r=mysqli_fetch_assoc($cats_q)) $categories[]=$r['category'];
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width,initial-scale=1">
<title>Shop — Thilha Divine Bakes</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
<link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css" rel="stylesheet">
<link href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@700;900&family=Inter:wght@300;400;600;700&display=swap" rel="stylesheet">
<style>
:root{--gold:#c9973a;--gold-light:#e8c870;--gold-dark:#8a6520;--dark:#1a0a0f;--cream:#fdf5ec;--cream2:#f5f0eb;--cream3:#f0e8dd;--border:#e8d8c0;--text:#2a1a10;--muted:#8a6a4a;--white:#ffffff;--pink:#e8789a;}
*{box-sizing:border-box;margin:0;padding:0;}
body{background:var(--cream2);font-family:'Inter',sans-serif;color:var(--text);min-height:100vh;}

/* LOYALTY BAR */
.loyalty-bar{background:linear-gradient(90deg,var(--dark),#2a1018);padding:10px 5%;display:flex;align-items:center;gap:12px;flex-wrap:wrap;}
.loyalty-pts{font-size:13px;color:var(--gold-light);font-weight:700;display:flex;align-items:center;gap:6px;}

/* PAGE */
.page-wrap{padding:32px 5%;}
.page-heading{font-family:'Playfair Display',serif;font-size:32px;font-weight:900;color:var(--text);margin-bottom:6px;}
.page-sub{font-size:14px;color:var(--muted);margin-bottom:28px;}

/* SEARCH */
.search-bar{position:relative;margin-bottom:24px;}
.search-bar input{width:100%;background:var(--white);border:1.5px solid var(--border);border-radius:12px;padding:13px 20px 13px 48px;font-size:14px;color:var(--text);outline:none;transition:border .2s;font-family:'Inter',sans-serif;box-shadow:0 2px 8px rgba(0,0,0,0.04);}
.search-bar input:focus{border-color:var(--gold);box-shadow:0 0 0 3px rgba(201,151,58,0.1);}
.search-bar input::placeholder{color:var(--muted);}
.search-bar i{position:absolute;left:18px;top:50%;transform:translateY(-50%);color:var(--muted);font-size:16px;}

/* CATEGORY TABS */
.cat-tabs{display:flex;gap:10px;flex-wrap:wrap;margin-bottom:28px;}
.cat-tab{padding:8px 20px;border-radius:99px;border:1.5px solid var(--border);background:var(--white);font-size:13px;font-weight:700;color:var(--muted);cursor:pointer;transition:all .2s;text-decoration:none;display:inline-flex;align-items:center;gap:6px;box-shadow:0 2px 6px rgba(0,0,0,0.04);}
.cat-tab:hover{border-color:var(--gold);color:var(--gold);}
.cat-tab.active{background:var(--gold);border-color:var(--gold);color:#fff;box-shadow:0 4px 12px rgba(201,151,58,0.25);}

/* PRODUCTS GRID */
.products-grid{display:grid;grid-template-columns:repeat(auto-fill,minmax(240px,1fr));gap:20px;}
.product-card{background:var(--white);border:1.5px solid var(--border);border-radius:20px;overflow:hidden;transition:all .3s;box-shadow:0 2px 8px rgba(0,0,0,0.04);}
.product-card:hover{border-color:var(--gold);transform:translateY(-4px);box-shadow:0 16px 40px rgba(201,151,58,0.15);}
.product-img-wrap{height:165px;background:var(--cream2);display:flex;align-items:center;justify-content:center;font-size:60px;position:relative;overflow:hidden;}
.product-img-wrap img{width:100%;height:100%;object-fit:cover;}
.product-cat-tag{position:absolute;top:10px;right:10px;background:var(--gold);color:#fff;font-size:10px;font-weight:700;letter-spacing:1px;padding:4px 10px;border-radius:99px;text-transform:uppercase;}
.product-body{padding:16px 18px 18px;}
.product-name{font-family:'Playfair Display',serif;font-size:16px;font-weight:700;color:var(--text);margin-bottom:6px;line-height:1.3;}
.product-desc{font-size:12px;color:var(--muted);margin-bottom:12px;line-height:1.5;}
.product-footer{display:flex;align-items:center;justify-content:space-between;}
.product-price{font-size:20px;font-weight:700;color:var(--gold);}
.add-btn{background:linear-gradient(135deg,var(--gold),var(--gold-dark));color:#fff;border:none;border-radius:99px;padding:8px 20px;font-size:13px;font-weight:700;cursor:pointer;display:flex;align-items:center;gap:6px;transition:all .2s;}
.add-btn:hover{background:linear-gradient(135deg,var(--gold-light),var(--gold));color:var(--dark);transform:scale(1.05);}
.add-btn.adding{background:#2e7d32;}

/* EMPTY */
.empty-state{text-align:center;padding:80px 20px;}
.empty-state i{font-size:64px;color:var(--border);display:block;margin-bottom:16px;}

/* TOAST */
.toast-msg{position:fixed;bottom:24px;right:24px;z-index:9999;background:var(--white);border:1.5px solid var(--gold);border-radius:12px;padding:14px 20px;font-size:14px;color:var(--text);display:flex;align-items:center;gap:10px;transform:translateY(80px);opacity:0;transition:all .3s;box-shadow:0 8px 24px rgba(201,151,58,0.2);}
.toast-msg.show{transform:translateY(0);opacity:1;}
</style>
</head>
<body>
<?php include 'customer_nav.php'; ?>

<div class="loyalty-bar">
  <div class="loyalty-pts"><i class="bi bi-star-fill"></i><?= $loyalty ?> Loyalty Points</div>
  <span style="font-size:12px;color:rgba(232,200,112,0.6);">— Earn 1 point for every Rs.10 spent!</span>
</div>

<div class="page-wrap">
  <div class="page-heading">Our <span style="color:var(--gold);font-style:italic;">Menu</span></div>
  <div class="page-sub">Fresh baked every day — <?= mysqli_num_rows($products) ?> items available</div>

  <form method="GET">
    <div class="search-bar">
      <i class="bi bi-search"></i>
      <input type="text" name="search" placeholder="Search for cakes, brownies, cupcakes..." value="<?= htmlspecialchars($search) ?>" autocomplete="off">
      <?php if($cat): ?><input type="hidden" name="cat" value="<?= $cat ?>"><?php endif; ?>
    </div>
  </form>

  <div class="cat-tabs">
    <a href="home.php" class="cat-tab <?= !$cat?'active':'' ?>">🍽️ All</a>
    <?php foreach($categories as $c): ?>
    <a href="home.php?cat=<?= $c ?><?= $search?'&search='.urlencode($search):'' ?>" class="cat-tab <?= $cat===$c?'active':'' ?>">
      <?= $icons[$c]??'' ?> <?= ucfirst($c) ?>
    </a>
    <?php endforeach; ?>
  </div>

  <?php if(mysqli_num_rows($products)===0): ?>
  <div class="empty-state">
    <i class="bi bi-search"></i>
    <p style="font-size:18px;color:var(--muted);margin-bottom:12px;">No products found!</p>
    <a href="home.php" class="cat-tab active">View All Products</a>
  </div>
  <?php else: ?>
  <div class="products-grid">
    <?php while($p=mysqli_fetch_assoc($products)): ?>
    <div class="product-card">
      <div class="product-img-wrap">
        <?php if($p['image']&&file_exists('../assets/images/'.$p['image'])): ?>
          <img src="../assets/images/<?= $p['image'] ?>" alt="<?= $p['name'] ?>">
        <?php else: ?>
          <?= $icons[$p['category']]??'🍰' ?>
        <?php endif; ?>
        <span class="product-cat-tag"><?= ucfirst($p['category']) ?></span>
      </div>
      <div class="product-body">
        <div class="product-name"><?= htmlspecialchars($p['name']) ?></div>
        <?php if($p['description']): ?>
        <div class="product-desc"><?= htmlspecialchars(substr($p['description'],0,55)) ?>...</div>
        <?php endif; ?>
        <div class="product-footer">
          <div class="product-price">Rs. <?= number_format($p['price'],0) ?></div>
          <button class="add-btn" id="btn-<?= $p['product_id'] ?>"
            onclick="addToCart(<?= $p['product_id'] ?>,'<?= addslashes($p['name']) ?>',<?= $p['price'] ?>,this)">
            <i class="bi bi-plus"></i> Add
          </button>
        </div>
      </div>
    </div>
    <?php endwhile; ?>
  </div>
  <?php endif; ?>
</div>

<div class="toast-msg" id="toast">
  <i class="bi bi-check-circle-fill" style="color:var(--gold);"></i>
  <span id="toast-text">Added!</span>
</div>

<script>
function addToCart(id,name,price,btn){
  btn.classList.add('adding');
  btn.innerHTML='<i class="bi bi-hourglass"></i>';
  fetch('../includes/cart_add.php',{method:'POST',headers:{'Content-Type':'application/x-www-form-urlencoded'},body:'product_id='+id+'&name='+encodeURIComponent(name)+'&price='+price})
  .then(r=>r.json())
  .then(data=>{
    var badge=document.querySelector('.cart-badge');
    if(badge) badge.textContent=data.count;
    btn.classList.remove('adding');
    btn.innerHTML='<i class="bi bi-check-lg"></i> Added!';
    btn.style.background='#2e7d32';
    showToast(name+' added to cart!');
    setTimeout(()=>{btn.innerHTML='<i class="bi bi-plus"></i> Add';btn.style.background='';},1800);
  });
}
function showToast(msg){
  var t=document.getElementById('toast');
  document.getElementById('toast-text').textContent=msg;
  t.classList.add('show');
  setTimeout(()=>t.classList.remove('show'),2500);
}
</script>
</body>
</html>