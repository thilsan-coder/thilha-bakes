<?php $current = basename($_SERVER['PHP_SELF']); ?>
<style>
.sidebar{width:240px;height:100vh;background:#1a0a0f;border-right:3px solid #c9973a;position:fixed;top:0;left:0;display:flex;flex-direction:column;z-index:100;overflow:hidden; font-family: 'Poppins', sans-serif;}
.sidebar-brand{padding:20px 20px 16px;border-bottom:1px solid #3a1a24;text-align:center;flex-shrink:0;}
.sidebar-brand .name{font-family:Georgia,serif;font-size:20px;font-weight:bold;color:#e8c870;letter-spacing:3px;}
.sidebar-brand .sub{font-family:Georgia,serif;font-size:11px;color:#c9973a;letter-spacing:4px;margin-top:2px;}
.sidebar-brand .loc{font-size:10px;color:#7a5a28;margin-top:3px;}
.sidebar nav{flex:1;overflow-y:auto;padding-top:6px;}
.sidebar nav::-webkit-scrollbar{width:3px;}
.sidebar nav::-webkit-scrollbar-track{background:transparent;}
.sidebar nav::-webkit-scrollbar-thumb{background:#3a1a24;border-radius:3px;}
.sidebar nav::-webkit-scrollbar-thumb:hover{background:#c9973a;}
.nav-section{font-size:10px;color:#5a3a28;letter-spacing:2px;padding:12px 20px 4px;text-transform:uppercase;}
.nav-link{color:#c9b090;font-size:13px;padding:10px 20px;display:flex;align-items:center;gap:10px;border-left:3px solid transparent;transition:all .15s;text-decoration:none;}
.nav-link:hover,.nav-link.active{color:#e8c870;background:#2a1018;border-left-color:#c9973a;}
.sidebar-footer{padding:14px 20px;border-top:1px solid #3a1a24;flex-shrink:0;}
.main{margin-left:240px;padding:28px;}
</style>

<div class="sidebar">
  <div class="sidebar-brand">
    <div class="name">THILHA</div>
    <div class="sub">DIVINE BAKES</div>
    <div class="loc">~ Central Camp, Ampara ~</div>
  </div>
  <nav>
    <div class="nav-section">Main</div>
    <a href="dashboard.php"  class="nav-link <?= $current==='dashboard.php' ?'active':'' ?>"><i class="bi bi-grid-fill"></i> Dashboard</a>
    
    <!-- POS Icon Fixed below -->
    <a href="pos.php"        class="nav-link <?= $current==='pos.php'       ?'active':'' ?>"><i class="bi bi-calculator-fill"></i> POS</a>
    
    <a href="orders.php"     class="nav-link <?= $current==='orders.php'    ?'active':'' ?>"><i class="bi bi-bag-fill"></i> Orders</a>
    <a href="billing.php"    class="nav-link <?= $current==='billing.php'   ?'active':'' ?>"><i class="bi bi-receipt-cutoff"></i> Billing</a>
    
    <div class="nav-section">Manage</div>
    <a href="products.php"   class="nav-link <?= $current==='products.php'  ?'active':'' ?>"><i class="bi bi-box-seam"></i> Products</a>
    <a href="inventory.php"  class="nav-link <?= $current==='inventory.php' ?'active':'' ?>"><i class="bi bi-layers-fill"></i> Inventory</a>
    <a href="staff.php"      class="nav-link <?= $current==='staff.php'     ?'active':'' ?>"><i class="bi bi-people-fill"></i> Staff</a>
    <a href="delivery.php"   class="nav-link <?= $current==='delivery.php'  ?'active':'' ?>"><i class="bi bi-truck"></i> Delivery</a>
    <a href="suppliers.php"  class="nav-link <?= $current==='suppliers.php' ?'active':'' ?>"><i class="bi bi-building"></i> Suppliers</a>
    <a href="production.php" class="nav-link <?= $current==='production.php'?'active':'' ?>"><i class="bi bi-clipboard2-check"></i> Production</a>
    
    <div class="nav-section">Finance</div>
    <a href="expenses.php"   class="nav-link <?= $current==='expenses.php'  ?'active':'' ?>"><i class="bi bi-wallet2"></i> Expenses</a>
    <a href="reports.php"    class="nav-link <?= $current==='reports.php'   ?'active':'' ?>"><i class="bi bi-bar-chart-fill"></i> Reports</a>
    <a href="analytics.php"  class="nav-link <?= $current==='analytics.php' ?'active':'' ?>"><i class="bi bi-graph-up"></i> Analytics</a>
    
    <div class="nav-section">System</div>
    <a href="settings.php"      class="nav-link <?= $current==='settings.php'      ?'active':'' ?>"><i class="bi bi-gear-fill"></i> Settings</a>
    <a href="admin_profile.php" class="nav-link <?= $current==='admin_profile.php' ?'active':'' ?>"><i class="bi bi-person-gear"></i> My Profile</a>
  </nav>
  
  <div class="sidebar-footer">
    <a href="../logout.php" class="nav-link" style="padding:8px 0;color:#e87878;">
      <i class="bi bi-box-arrow-left"></i> Logout
    </a>
  </div>
</div>