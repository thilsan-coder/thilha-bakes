<?php
require_once '../includes/db.php';
$featured    = mysqli_query($conn,"SELECT * FROM products WHERE stock_status='available' ORDER BY product_id ASC LIMIT 6");
$bestsellers = mysqli_query($conn,"SELECT p.*,COALESCE(SUM(oi.quantity),0) as sold FROM products p LEFT JOIN order_items oi ON p.product_id=oi.product_id WHERE p.stock_status='available' GROUP BY p.product_id ORDER BY sold DESC LIMIT 3");
$icons = ['cakes'=>'🎂','brownies'=>'🍫','cupcakes'=>'🧁','pastries'=>'🥐','breads'=>'🍞'];
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width,initial-scale=1">
<title>Thilha Divine Bakes — Central Camp, Ampara</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
<link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css" rel="stylesheet">
<link href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@400;700;900&family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
<style>
:root{
  --gold:#c9973a;--gold-light:#e8c870;--gold-dark:#8a6520;
  --dark:#1a0a0f;--dark2:#220d14;
  --cream:#fdf5ec;--cream2:#f5f0eb;--cream3:#f0e8dd;
  --border:#e8d8c0;--text:#2a1a10;--muted:#8a6a4a;
  --pink:#e8789a;--white:#ffffff;
}
*{box-sizing:border-box;margin:0;padding:0;}
html{scroll-behavior:smooth;}
body{background:var(--cream2);font-family:'Inter',sans-serif;color:var(--text);overflow-x:hidden;}
::-webkit-scrollbar{width:5px;}
::-webkit-scrollbar-track{background:var(--cream2);}
::-webkit-scrollbar-thumb{background:var(--gold);border-radius:3px;}

/* HERO */
.hero{
  min-height:92vh;
  background:linear-gradient(135deg,#1a0a0f 0%,#220d14 40%,#2a1018 70%,#1a0a0f 100%);
  display:flex;align-items:center;
  padding:80px 5% 60px;
  position:relative;overflow:hidden;
}
.hero-pattern{position:absolute;inset:0;opacity:0.04;background-image:radial-gradient(circle,var(--gold) 1px,transparent 1px);background-size:40px 40px;}
.hero-glow{position:absolute;width:500px;height:500px;border-radius:50%;background:radial-gradient(circle,rgba(201,151,58,0.12),transparent 70%);top:-100px;right:-80px;}
.hero-content{position:relative;z-index:2;max-width:580px;}
.hero-tag{display:inline-flex;align-items:center;gap:8px;background:rgba(201,151,58,0.12);border:1px solid rgba(201,151,58,0.25);border-radius:99px;padding:6px 18px;font-size:12px;color:var(--gold-light);font-weight:700;letter-spacing:2px;text-transform:uppercase;margin-bottom:24px;}
.hero-title{font-family:'Playfair Display',serif;font-size:clamp(40px,6vw,76px);font-weight:900;line-height:1.05;color:#fff;margin-bottom:18px;}
.hero-title em{color:var(--gold-light);font-style:italic;}
.hero-desc{font-size:16px;color:rgba(240,224,192,0.7);line-height:1.8;margin-bottom:36px;font-weight:300;}
.hero-btns{display:flex;gap:14px;flex-wrap:wrap;}
.btn-gold{background:linear-gradient(135deg,var(--gold),var(--gold-dark));color:#fff;border:none;border-radius:99px;padding:14px 36px;font-size:15px;font-weight:700;cursor:pointer;text-decoration:none;display:inline-flex;align-items:center;gap:8px;transition:all .2s;box-shadow:0 4px 16px rgba(201,151,58,0.3);}
.btn-gold:hover{background:linear-gradient(135deg,var(--gold-light),var(--gold));color:var(--dark);transform:translateY(-2px);box-shadow:0 8px 28px rgba(201,151,58,0.4);}
.btn-outline-dark{background:transparent;color:#fff;border:1.5px solid rgba(255,255,255,0.3);border-radius:99px;padding:14px 36px;font-size:15px;font-weight:700;cursor:pointer;text-decoration:none;display:inline-flex;align-items:center;gap:8px;transition:all .2s;}
.btn-outline-dark:hover{background:rgba(255,255,255,0.08);color:#fff;transform:translateY(-2px);}
.hero-visual{position:absolute;right:5%;top:50%;transform:translateY(-50%);z-index:2;}
.hero-cake{font-size:clamp(100px,13vw,170px);filter:drop-shadow(0 20px 60px rgba(201,151,58,0.3));animation:float 4s ease-in-out infinite;}
@keyframes float{0%,100%{transform:translateY(0);}50%{transform:translateY(-20px);}}
.hero-stats{position:absolute;bottom:50px;left:5%;z-index:2;display:flex;gap:36px;flex-wrap:wrap;}
.hero-stat-num{font-family:'Playfair Display',serif;font-size:30px;font-weight:900;color:var(--gold-light);line-height:1;}
.hero-stat-label{font-size:12px;color:rgba(154,122,88,0.8);margin-top:3px;letter-spacing:1px;}

/* MARQUEE */
.marquee-strip{background:var(--gold);padding:11px 0;overflow:hidden;white-space:nowrap;}
.marquee-inner{display:inline-block;animation:marquee 25s linear infinite;}
.marquee-inner span{font-size:12px;font-weight:700;color:var(--dark);letter-spacing:2px;text-transform:uppercase;padding:0 24px;}
.marquee-inner span::after{content:'✦';margin-left:24px;}
@keyframes marquee{from{transform:translateX(0);}to{transform:translateX(-50%);}}

/* SECTION */
.section{padding:80px 5%;}
.section-tag{font-size:11px;font-weight:700;color:var(--gold);letter-spacing:3px;text-transform:uppercase;margin-bottom:10px;}
.section-title{font-family:'Playfair Display',serif;font-size:clamp(28px,4vw,48px);font-weight:900;color:var(--text);line-height:1.15;margin-bottom:12px;}
.section-title em{color:var(--gold);font-style:italic;}
.section-desc{font-size:15px;color:var(--muted);line-height:1.7;max-width:480px;}

/* PRODUCTS SECTION */
.products-section{background:var(--white);}
.products-grid{display:grid;grid-template-columns:repeat(auto-fill,minmax(260px,1fr));gap:20px;margin-top:44px;}
.product-card{background:var(--cream);border:1.5px solid var(--border);border-radius:20px;overflow:hidden;transition:all .3s;cursor:pointer;text-decoration:none;display:block;}
.product-card:hover{border-color:var(--gold);transform:translateY(-6px);box-shadow:0 20px 40px rgba(201,151,58,0.15);}
.product-img-wrap{height:175px;background:var(--cream2);display:flex;align-items:center;justify-content:center;font-size:64px;position:relative;overflow:hidden;}
.product-img-wrap img{width:100%;height:100%;object-fit:cover;}
.product-cat-tag{position:absolute;top:12px;right:12px;background:var(--gold);color:#fff;font-size:10px;font-weight:700;letter-spacing:1px;padding:4px 10px;border-radius:99px;text-transform:uppercase;}
.product-body{padding:18px 20px;}
.product-name{font-family:'Playfair Display',serif;font-size:17px;font-weight:700;color:var(--text);margin-bottom:12px;line-height:1.3;}
.product-footer{display:flex;align-items:center;justify-content:space-between;}
.product-price{font-size:20px;font-weight:700;color:var(--gold);}
.add-btn{width:38px;height:38px;border-radius:50%;background:linear-gradient(135deg,var(--gold),var(--gold-dark));color:#fff;border:none;font-size:20px;cursor:pointer;display:flex;align-items:center;justify-content:center;transition:all .2s;text-decoration:none;}
.add-btn:hover{transform:scale(1.1);box-shadow:0 4px 12px rgba(201,151,58,0.35);}

/* BESTSELLERS */
.bs-section{background:var(--cream2);}
.bs-grid{display:grid;grid-template-columns:repeat(3,1fr);gap:20px;margin-top:44px;}
.bs-card{background:var(--white);border:1.5px solid var(--border);border-radius:20px;padding:28px;text-align:center;position:relative;overflow:hidden;transition:all .3s;}
.bs-card:hover{border-color:var(--gold);transform:translateY(-4px);box-shadow:0 16px 40px rgba(201,151,58,0.12);}
.bs-card::before{content:'';position:absolute;top:0;left:0;right:0;height:4px;background:linear-gradient(90deg,var(--gold),var(--gold-light));}
.bs-rank{font-family:'Playfair Display',serif;font-size:72px;font-weight:900;color:rgba(201,151,58,0.06);position:absolute;top:-10px;left:12px;line-height:1;}
.bs-icon{font-size:52px;margin-bottom:14px;}
.bs-name{font-family:'Playfair Display',serif;font-size:18px;font-weight:700;color:var(--text);margin-bottom:8px;}
.bs-sold{font-size:13px;color:var(--muted);margin-bottom:14px;}
.bs-price{font-size:22px;font-weight:700;color:var(--gold);}

/* WHY US */
.why-section{background:var(--white);}
.why-grid{display:grid;grid-template-columns:repeat(auto-fit,minmax(220px,1fr));gap:18px;margin-top:44px;}
.why-card{background:var(--cream);border:1.5px solid var(--border);border-radius:18px;padding:26px;text-align:center;transition:all .3s;}
.why-card:hover{border-color:var(--gold);background:var(--cream2);transform:translateY(-3px);}
.why-icon{width:60px;height:60px;border-radius:16px;background:linear-gradient(135deg,var(--gold),var(--gold-dark));display:flex;align-items:center;justify-content:center;font-size:26px;margin:0 auto 14px;box-shadow:0 4px 12px rgba(201,151,58,0.25);}
.why-title{font-family:'Playfair Display',serif;font-size:16px;font-weight:700;color:var(--text);margin-bottom:8px;}
.why-desc{font-size:13px;color:var(--muted);line-height:1.6;}

/* TESTIMONIALS */
.test-section{background:var(--cream2);}
.test-grid{display:grid;grid-template-columns:repeat(auto-fit,minmax(280px,1fr));gap:18px;margin-top:44px;}
.test-card{background:var(--white);border:1.5px solid var(--border);border-radius:18px;padding:26px;transition:all .3s;position:relative;}
.test-card:hover{border-color:var(--gold);box-shadow:0 8px 24px rgba(201,151,58,0.1);}
.test-stars{color:var(--gold);font-size:14px;margin-bottom:12px;}
.test-text{font-size:14px;color:var(--muted);line-height:1.7;font-style:italic;margin-bottom:18px;}
.test-author{display:flex;align-items:center;gap:12px;}
.test-avatar{width:42px;height:42px;border-radius:50%;background:linear-gradient(135deg,var(--gold),var(--gold-dark));display:flex;align-items:center;justify-content:center;font-size:16px;font-weight:700;color:#fff;font-family:'Playfair Display',serif;flex-shrink:0;}
.test-name{font-size:14px;font-weight:700;color:var(--text);}
.test-loc{font-size:12px;color:var(--muted);}

/* CTA */
.cta-section{background:linear-gradient(135deg,#1a0a0f,#2a1018,#1a0a0f);padding:80px 5%;text-align:center;position:relative;overflow:hidden;}
.cta-section::before{content:'';position:absolute;inset:0;background:radial-gradient(ellipse 70% 70% at 50% 50%,rgba(201,151,58,0.1),transparent);}
.cta-title{font-family:'Playfair Display',serif;font-size:clamp(32px,5vw,56px);font-weight:900;color:#fff;position:relative;z-index:2;margin-bottom:12px;}
.cta-title em{color:var(--gold-light);}
.cta-desc{font-size:16px;color:rgba(154,122,88,0.8);margin-bottom:32px;position:relative;z-index:2;}
.cta-btns{display:flex;gap:14px;justify-content:center;flex-wrap:wrap;position:relative;z-index:2;}

/* FOOTER */
.footer{background:var(--cream3);padding:60px 5% 28px;border-top:2px solid var(--border);}
.footer-grid{display:grid;grid-template-columns:2fr 1fr 1fr 1fr;gap:40px;margin-bottom:36px;}
.footer-brand-name{font-family:'Playfair Display',serif;font-size:22px;font-weight:900;color:var(--dark);letter-spacing:3px;margin-bottom:4px;}
.footer-brand-sub{font-size:10px;color:var(--gold);letter-spacing:4px;margin-bottom:14px;}
.footer-desc{font-size:13px;color:var(--muted);line-height:1.7;margin-bottom:18px;}
.footer-social{display:flex;gap:10px;}
.social-btn{width:36px;height:36px;border-radius:9px;background:var(--white);border:1.5px solid var(--border);display:flex;align-items:center;justify-content:center;color:var(--gold);font-size:15px;text-decoration:none;transition:all .2s;}
.social-btn:hover{background:var(--gold);color:#fff;border-color:var(--gold);}
.footer-heading{font-size:11px;font-weight:700;color:var(--gold);letter-spacing:2px;text-transform:uppercase;margin-bottom:14px;}
.footer-links{list-style:none;}
.footer-links li{margin-bottom:10px;}
.footer-links a{font-size:14px;color:var(--muted);text-decoration:none;transition:color .2s;}
.footer-links a:hover{color:var(--gold);}
.footer-bottom{border-top:1px solid var(--border);padding-top:20px;display:flex;align-items:center;justify-content:space-between;flex-wrap:wrap;gap:10px;}
.footer-copy{font-size:13px;color:var(--muted);}

@media(max-width:768px){
  .hero-visual{display:none;}
  .bs-grid{grid-template-columns:1fr;}
  .footer-grid{grid-template-columns:1fr 1fr;}
  .hero-stats{gap:20px;}
}
</style>
</head>
<body>
<?php include 'customer_nav.php'; ?>

<!-- HERO -->
<section class="hero">
  <div class="hero-pattern"></div>
  <div class="hero-glow"></div>
  <div class="hero-content">
    <div class="hero-tag"><i class="bi bi-geo-alt-fill"></i> Central Camp, Ampara · Sri Lanka</div>
    <h1 class="hero-title">Baked with<br><em>Love &amp; Passion</em></h1>
    <p class="hero-desc">Artisan cakes, cupcakes, brownies, pastries and breads — freshly baked every day for you and your loved ones.</p>
    <div class="hero-btns">
      <a href="home.php" class="btn-gold"><i class="bi bi-bag-heart-fill"></i> Order Now</a>
      <a href="#products" class="btn-outline-dark"><i class="bi bi-grid"></i> View Menu</a>
    </div>
  </div>
  <div class="hero-visual"><div class="hero-cake">🎂</div></div>
  <div class="hero-stats">
    <div><div class="hero-stat-num">500+</div><div class="hero-stat-label">Happy Customers</div></div>
    <div><div class="hero-stat-num">50+</div><div class="hero-stat-label">Products</div></div>
    <div><div class="hero-stat-num">5★</div><div class="hero-stat-label">Rating</div></div>
  </div>
</section>

<!-- MARQUEE -->
<div class="marquee-strip">
  <div class="marquee-inner">
    <?php for($i=0;$i<3;$i++): ?>
    <span>Fresh Daily Bakes</span><span>Custom Cakes</span>
    <span>Free Delivery Above Rs.2000</span><span>Loyalty Rewards</span>
    <span>Central Camp Ampara</span><span>Order Online</span>
    <span>WhatsApp Orders</span><span>Premium Quality</span>
    <?php endfor; ?>
  </div>
</div>

<!-- PRODUCTS -->
<section class="section products-section" id="products">
  <div class="section-tag">Our Menu</div>
  <div style="display:flex;align-items:flex-end;justify-content:space-between;flex-wrap:wrap;gap:16px;">
    <div>
      <h2 class="section-title">Fresh Baked <em>Every Day</em></h2>
      <p class="section-desc">Handcrafted with the finest ingredients — made with love every morning.</p>
    </div>
    <a href="home.php" style="background:var(--cream2);border:1.5px solid var(--border);color:var(--gold);border-radius:99px;padding:10px 24px;font-size:14px;font-weight:700;text-decoration:none;display:inline-flex;align-items:center;gap:6px;transition:all .2s;" onmouseover="this.style.borderColor='var(--gold)'" onmouseout="this.style.borderColor='var(--border)'">View All →</a>
  </div>
  <div class="products-grid">
    <?php mysqli_data_seek($featured,0); while($p=mysqli_fetch_assoc($featured)): ?>
    <a href="home.php" class="product-card">
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
        <div class="product-footer">
          <div class="product-price">Rs. <?= number_format($p['price'],0) ?></div>
          <span class="add-btn"><i class="bi bi-plus"></i></span>
        </div>
      </div>
    </a>
    <?php endwhile; ?>
  </div>
</section>

<!-- BESTSELLERS -->
<section class="section bs-section" id="bestsellers">
  <div class="section-tag">Fan Favourites</div>
  <h2 class="section-title">Our <em>Best Sellers</em></h2>
  <p class="section-desc">The most loved products — tried, tested, and adored by our customers!</p>
  <div class="bs-grid">
    <?php $ranks=['🥇','🥈','🥉']; $ri=0; mysqli_data_seek($bestsellers,0); while($b=mysqli_fetch_assoc($bestsellers)): ?>
    <div class="bs-card">
      <div class="bs-rank"><?= $ri+1 ?></div>
      <div class="bs-icon"><?= $icons[$b['category']]??'🍰' ?></div>
      <div class="bs-name"><?= htmlspecialchars($b['name']) ?></div>
      <div class="bs-sold"><?= $ranks[$ri] ?> <?= $b['sold']>0?$b['sold'].' sold':'Popular choice' ?></div>
      <div class="bs-price">Rs. <?= number_format($b['price'],0) ?></div>
    </div>
    <?php $ri++; endwhile; ?>
  </div>
</section>

<!-- WHY US -->
<section class="section why-section" id="about">
  <div class="section-tag">Why Choose Us</div>
  <h2 class="section-title">More Than Just <em>a Bakery</em></h2>
  <div class="why-grid">
    <?php
    $features=[
      ['🌿','Fresh Ingredients','Finest, freshest ingredients sourced locally every day'],
      ['👨‍🍳','Expert Bakers','Years of experience and passion in every product'],
      ['🎁','Custom Orders','Special cakes tailored exactly to your requirements'],
      ['🚚','Fast Delivery','Quick delivery across Central Camp and surroundings'],
      ['⭐','Loyalty Rewards','Earn points with every order and redeem discounts'],
      ['💬','WhatsApp Orders','Order via WhatsApp — quick, easy and convenient'],
    ];
    foreach($features as $f): ?>
    <div class="why-card">
      <div class="why-icon"><?= $f[0] ?></div>
      <div class="why-title"><?= $f[1] ?></div>
      <div class="why-desc"><?= $f[2] ?></div>
    </div>
    <?php endforeach; ?>
  </div>
</section>

<!-- TESTIMONIALS -->
<section class="section test-section">
  <div class="section-tag">Customer Love</div>
  <h2 class="section-title">What Our <em>Customers Say</em></h2>
  <div class="test-grid">
    <?php
    $testimonials=[
      ['name'=>'Fathima Rizna','loc'=>'Ampara','text'=>'The chocolate cake was absolutely divine! Every celebration in our family now features a Thilha Divine Bakes cake. Quality and taste are unmatched!','stars'=>5],
      ['name'=>'Kasun Perera','loc'=>'Central Camp','text'=>'Ordered cupcakes for my daughter\'s birthday — everyone loved them! Beautiful presentation and delicious taste. Will definitely order again!','stars'=>5],
      ['name'=>'Nisha Fernando','loc'=>'Ampara Town','text'=>'The brownies are incredibly rich and fudgy. I order them every week! Fast delivery and always fresh. Thilha Divine Bakes never disappoints.','stars'=>5],
    ];
    foreach($testimonials as $t): ?>
    <div class="test-card">
      <div class="test-stars"><?= str_repeat('★',$t['stars']) ?></div>
      <p class="test-text">"<?= $t['text'] ?>"</p>
      <div class="test-author">
        <div class="test-avatar"><?= strtoupper(substr($t['name'],0,1)) ?></div>
        <div>
          <div class="test-name"><?= $t['name'] ?></div>
          <div class="test-loc"><i class="bi bi-geo-alt" style="color:var(--gold);margin-right:3px;"></i><?= $t['loc'] ?></div>
        </div>
      </div>
    </div>
    <?php endforeach; ?>
  </div>
</section>

<!-- CTA -->
<section class="cta-section" id="contact">
  <h2 class="cta-title">Ready to <em>Order?</em></h2>
  <p class="cta-desc">Browse our full menu — fresh bakes delivered to your door!</p>
  <div class="cta-btns">
    <a href="home.php" class="btn-gold" style="font-size:16px;padding:16px 40px;"><i class="bi bi-bag-heart-fill"></i> Shop Now</a>
    <?php if(!isLoggedIn()): ?>
    <a href="../register.php" style="background:transparent;color:#fff;border:1.5px solid rgba(255,255,255,0.3);border-radius:99px;padding:16px 40px;font-size:16px;font-weight:700;text-decoration:none;display:inline-flex;align-items:center;gap:8px;transition:all .2s;"><i class="bi bi-person-plus"></i> Join & Earn Rewards</a>
    <?php endif; ?>
    <a href="https://wa.me/+94770000000" style="background:transparent;color:#25d366;border:1.5px solid #25d366;border-radius:99px;padding:16px 40px;font-size:16px;font-weight:700;text-decoration:none;display:inline-flex;align-items:center;gap:8px;transition:all .2s;"><i class="bi bi-whatsapp"></i> WhatsApp</a>
  </div>
  <div style="margin-top:32px;font-size:13px;color:rgba(154,122,88,0.7);position:relative;z-index:2;">
    <i class="bi bi-geo-alt-fill" style="color:var(--gold);margin-right:6px;"></i>Central Camp, Ampara &nbsp;·&nbsp;
    <i class="bi bi-clock-fill" style="color:var(--gold);margin:0 6px;"></i>Mon–Sat: 7AM–8PM &nbsp;·&nbsp;
    <i class="bi bi-telephone-fill" style="color:var(--gold);margin:0 6px;"></i>+94 77 XXX XXXX
  </div>
</section>

<!-- FOOTER -->
<footer class="footer">
  <div class="footer-grid">
    <div>
      <div class="footer-brand-name">THILHA</div>
      <div class="footer-brand-sub">DIVINE BAKES</div>
      <p class="footer-desc">Artisan bakery in the heart of Central Camp, Ampara. Freshly baked goods made with love every single day.</p>
      <div class="footer-social">
        <a href="#" class="social-btn"><i class="bi bi-facebook"></i></a>
        <a href="#" class="social-btn"><i class="bi bi-instagram"></i></a>
        <a href="https://wa.me/+94770000000" class="social-btn"><i class="bi bi-whatsapp"></i></a>
      </div>
    </div>
    <div>
      <div class="footer-heading">Quick Links</div>
      <ul class="footer-links">
        <li><a href="landing.php">Home</a></li>
        <li><a href="home.php">Order Online</a></li>
        <li><a href="#products">Products</a></li>
        <li><a href="#bestsellers">Best Sellers</a></li>
        <li><a href="#about">About Us</a></li>
      </ul>
    </div>
    <div>
      <div class="footer-heading">My Account</div>
      <ul class="footer-links">
        <?php if(isLoggedIn()): ?>
        <li><a href="my_orders.php">My Orders</a></li>
        <li><a href="cart.php">My Cart</a></li>
        <li><a href="profile.php">My Profile</a></li>
        <li><a href="../logout.php">Logout</a></li>
        <?php else: ?>
        <li><a href="../login.php">Login</a></li>
        <li><a href="../register.php">Register</a></li>
        <?php endif; ?>
      </ul>
    </div>
    <div>
      <div class="footer-heading">Contact</div>
      <ul class="footer-links">
        <li><a href="#"><i class="bi bi-geo-alt me-2" style="color:var(--gold);"></i>Central Camp, Ampara</a></li>
        <li><a href="tel:+94770000000"><i class="bi bi-telephone me-2" style="color:var(--gold);"></i>+94 77 XXX XXXX</a></li>
        <li><a href="mailto:info@thilhabakes.com"><i class="bi bi-envelope me-2" style="color:var(--gold);"></i>info@thilhabakes.com</a></li>
        <li><a href="#"><i class="bi bi-clock me-2" style="color:var(--gold);"></i>Mon–Sat: 7AM–8PM</a></li>
      </ul>
    </div>
  </div>
  <div class="footer-bottom">
    <div class="footer-copy">© <?= date('Y') ?> Thilha Divine Bakes. All rights reserved.</div>
    <div class="footer-copy">Made with ❤️ in Ampara, Sri Lanka</div>
  </div>
</footer>
</body>
</html>