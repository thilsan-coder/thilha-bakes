<?php
require_once '../includes/db.php';
if (!isLoggedIn() || !isAdmin()) redirect('../login.php');

// delete
if (isset($_GET['delete'])) {
    $id = (int)$_GET['delete'];
    mysqli_query($conn, "DELETE FROM production_plans WHERE plan_id=$id");
    redirect('production.php?deleted=1');
}

// update status
if (isset($_POST['update_status'])) {
    checkCSRF();
    $id     = (int)$_POST['plan_id'];
    $status = e($conn, $_POST['status']);
    $actual = (int)$_POST['actual_qty'];
    mysqli_query($conn,
        "UPDATE production_plans
         SET status='$status', actual_qty=$actual
         WHERE plan_id=$id");
    redirect('production.php?updated=1');
}

// add / edit
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['save_plan'])) {
    checkCSRF();
    $date       = e($conn, $_POST['plan_date']);
    $product_id = (int)$_POST['product_id'];
    $planned    = (int)$_POST['planned_qty'];
    $assigned   = $_POST['assigned_to'] ? (int)$_POST['assigned_to'] : 'NULL';
    $notes      = e($conn, $_POST['notes'] ?? '');
    $uid        = (int)$_SESSION['user_id'];

    if (isset($_POST['plan_id']) && $_POST['plan_id']) {
        $id = (int)$_POST['plan_id'];
        mysqli_query($conn,
            "UPDATE production_plans SET
             plan_date='$date', product_id=$product_id,
             planned_qty=$planned, assigned_to=$assigned, notes='$notes'
             WHERE plan_id=$id");
        redirect('production.php?updated=1');
    } else {
        mysqli_query($conn,
            "INSERT INTO production_plans
             (plan_date,product_id,planned_qty,assigned_to,notes,created_by)
             VALUES ('$date',$product_id,$planned,$assigned,'$notes',$uid)");
        redirect('production.php?added=1');
    }
}

// date filter
$view_date = isset($_GET['date']) ? $_GET['date'] : date('Y-m-d');
$view_week = isset($_GET['week']) ? 1 : 0;

if ($view_week) {
    $from = date('Y-m-d', strtotime('monday this week'));
    $to   = date('Y-m-d', strtotime('sunday this week'));
    $where = "WHERE pp.plan_date BETWEEN '$from' AND '$to'";
} else {
    $where = "WHERE pp.plan_date = '$view_date'";
}

$plans = mysqli_query($conn,
    "SELECT pp.*, p.name as product_name, p.category,
            u.name as assigned_name
     FROM production_plans pp
     LEFT JOIN products p ON pp.product_id = p.product_id
     LEFT JOIN users u ON pp.assigned_to = u.user_id
     $where
     ORDER BY pp.plan_date ASC, pp.plan_id ASC");

// stats
$today_planned   = mysqli_fetch_assoc(mysqli_query($conn,
    "SELECT COALESCE(SUM(planned_qty),0) as t FROM production_plans
     WHERE plan_date=CURDATE()"))['t'];
$today_completed = mysqli_fetch_assoc(mysqli_query($conn,
    "SELECT COALESCE(SUM(actual_qty),0) as t FROM production_plans
     WHERE plan_date=CURDATE() AND status='completed'"))['t'];
$in_progress     = mysqli_fetch_assoc(mysqli_query($conn,
    "SELECT COUNT(*) as c FROM production_plans
     WHERE status='in_progress'"))['c'];
$week_total      = mysqli_fetch_assoc(mysqli_query($conn,
    "SELECT COALESCE(SUM(planned_qty),0) as t FROM production_plans
     WHERE plan_date BETWEEN '".date('Y-m-d',strtotime('monday this week'))."'
     AND '".date('Y-m-d',strtotime('sunday this week'))."'"))['t'];

// products list
$products = mysqli_query($conn,
    "SELECT product_id, name, category FROM products
     WHERE stock_status='available' ORDER BY name");

// staff list
$staff = mysqli_query($conn,
    "SELECT user_id, name FROM users
     WHERE role IN ('staff','admin') ORDER BY name");

$icons = [
    'cakes'=>'🎂','brownies'=>'🍫',
    'cupcakes'=>'🧁','pastries'=>'🥐','breads'=>'🍞'
];
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>Production Planning — Thilha Divine Bakes</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
<link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css" rel="stylesheet">
<style>
*{box-sizing:border-box;}
body{background:#f5f0eb;font-family:'Segoe UI',sans-serif;color:#2a1a10;margin:0;}
.page-title{font-size:22px;font-weight:700;color:#1a0a0f;margin-bottom:2px;}
.page-sub{font-size:13px;color:#8a6a4a;margin-bottom:24px;}

.stat-row{display:grid;grid-template-columns:repeat(4,1fr);gap:14px;margin-bottom:24px;}
.stat-card{background:#fff;border:1px solid #e8d8c0;border-top:4px solid #c9973a;border-radius:12px;padding:16px 18px;}
.stat-num{font-size:22px;font-weight:700;color:#1a0a0f;margin-bottom:3px;}
.stat-label{font-size:12px;color:#8a6a4a;font-weight:500;}

.date-nav{background:#fff;border:1px solid #e8d8c0;border-radius:12px;padding:14px 20px;margin-bottom:20px;display:flex;align-items:center;gap:12px;flex-wrap:wrap;}
.date-nav-btn{background:#fff;border:1px solid #e8d8c0;border-radius:8px;padding:7px 16px;font-size:13px;font-weight:600;cursor:pointer;color:#2a1a10;text-decoration:none;display:inline-flex;align-items:center;gap:6px;}
.date-nav-btn:hover{background:#fdf5ec;border-color:#c9973a;color:#c9973a;}
.date-nav-btn.active{background:#c9973a;border-color:#c9973a;color:#fff;}
.date-input{border:1px solid #e8d8c0;border-radius:8px;padding:7px 12px;font-size:13px;color:#2a1a10;}
.date-input:focus{border-color:#c9973a;outline:none;}

.card-box{background:#fff;border:1px solid #e8d8c0;border-radius:14px;padding:20px;margin-bottom:20px;}
.card-title{font-size:16px;font-weight:600;color:#1a0a0f;margin-bottom:16px;padding-bottom:12px;border-bottom:1px solid #f0e4d0;display:flex;align-items:center;justify-content:space-between;}

.plan-card{background:#fff;border:1px solid #e8d8c0;border-radius:12px;padding:16px;margin-bottom:12px;border-left:4px solid #e8d8c0;transition:box-shadow .15s;}
.plan-card:hover{box-shadow:0 4px 12px rgba(0,0,0,0.06);}
.plan-card.planned{border-left-color:#8a6a4a;}
.plan-card.in_progress{border-left-color:#c9973a;}
.plan-card.completed{border-left-color:#2e7d32;}
.plan-card.cancelled{border-left-color:#c62828;}

.plan-header{display:flex;align-items:flex-start;justify-content:space-between;margin-bottom:12px;flex-wrap:wrap;gap:8px;}
.plan-product{font-size:16px;font-weight:600;color:#1a0a0f;display:flex;align-items:center;gap:8px;}
.plan-date{font-size:12px;color:#8a6a4a;}

.progress-wrap{height:8px;background:#f0e4d0;border-radius:4px;overflow:hidden;margin:10px 0;}
.progress-fill{height:100%;border-radius:4px;transition:width .3s;}

.status-planned{background:#f5f0eb;color:#8a6a4a;font-size:11px;padding:3px 10px;border-radius:99px;font-weight:600;border:1px solid #e8d8c0;}
.status-in_progress{background:#fff8e1;color:#f57f17;font-size:11px;padding:3px 10px;border-radius:99px;font-weight:600;border:1px solid #ffe082;}
.status-completed{background:#e8f5e9;color:#2e7d32;font-size:11px;padding:3px 10px;border-radius:99px;font-weight:600;border:1px solid #a5d6a7;}
.status-cancelled{background:#ffebee;color:#c62828;font-size:11px;padding:3px 10px;border-radius:99px;font-weight:600;border:1px solid #ffcdd2;}

.cat-badge{font-size:11px;padding:2px 8px;border-radius:99px;font-weight:500;}
.cat-cakes{background:#fce4ec;color:#880e4f;}
.cat-brownies{background:#fff8e1;color:#f57f17;}
.cat-cupcakes{background:#f3e5f5;color:#6a1b9a;}
.cat-pastries{background:#e8f5e9;color:#1b5e20;}
.cat-breads{background:#e3f2fd;color:#0d47a1;}

.plan-actions{display:flex;gap:6px;flex-wrap:wrap;margin-top:12px;padding-top:12px;border-top:1px solid #f0e4d0;}

.btn-gold{background:#c9973a;border:none;border-radius:8px;color:#fff;font-weight:600;padding:8px 18px;font-size:13px;cursor:pointer;display:inline-flex;align-items:center;gap:6px;text-decoration:none;}
.btn-gold:hover{background:#a07828;color:#fff;}
.btn-sm{padding:5px 12px;font-size:12px;}
.btn-edit{background:#e3f2fd;border:none;border-radius:6px;color:#1565c0;font-weight:600;padding:5px 12px;font-size:12px;cursor:pointer;}
.btn-del{background:#ffebee;border:none;border-radius:6px;color:#c62828;font-weight:600;padding:5px 12px;font-size:12px;cursor:pointer;text-decoration:none;display:inline-block;}

.alert-box{border-radius:10px;padding:12px 16px;font-size:13px;margin-bottom:20px;display:flex;align-items:center;gap:8px;}
.alert-success{background:#e8f5e9;border:1px solid #a5d6a7;border-left:4px solid #2e7d32;color:#1b5e20;}
.alert-deleted{background:#ffebee;border:1px solid #ffcdd2;border-left:4px solid #c62828;color:#b71c1c;}

.form-label{font-size:13px;color:#8a6a4a;font-weight:600;}
.form-control,.form-select{border:1px solid #e8d8c0;border-radius:8px;font-size:13px;color:#2a1a10;padding:9px 12px;}
.form-control:focus,.form-select:focus{border-color:#c9973a;box-shadow:0 0 0 3px rgba(201,151,58,0.15);}
.modal-content{border-radius:14px;border:1px solid #e8d8c0;}
.modal-header{background:#fdf5ec;border-bottom:1px solid #e8d8c0;border-radius:14px 14px 0 0;}

.empty-state{text-align:center;padding:48px 20px;color:#8a6a4a;}
.empty-state i{font-size:48px;color:#e8d8c0;display:block;margin-bottom:12px;}

.week-grid{display:grid;grid-template-columns:repeat(7,1fr);gap:10px;margin-bottom:20px;}
.week-day{background:#fff;border:1px solid #e8d8c0;border-radius:10px;padding:12px;text-align:center;}
.week-day.today{border-color:#c9973a;background:#fdf5ec;}
.week-day-label{font-size:11px;font-weight:600;color:#8a6a4a;margin-bottom:4px;}
.week-day-date{font-size:16px;font-weight:700;color:#1a0a0f;margin-bottom:6px;}
.week-day-count{font-size:12px;color:#c9973a;font-weight:600;}
</style>
</head>
<body>
<?php include '../includes/sidebar.php'; ?>
<div class="main">
  <div class="page-title">
    <i class="bi bi-clipboard2-check me-2" style="color:#c9973a;"></i>Production Planning
  </div>
  <div class="page-sub">Plan and track daily bakery production — <?= date('l, d F Y') ?></div>

  <?php if (isset($_GET['added'])): ?>
  <div class="alert-box alert-success"><i class="bi bi-check-circle-fill"></i> Production plan added!</div>
  <?php elseif (isset($_GET['updated'])): ?>
  <div class="alert-box alert-success"><i class="bi bi-check-circle-fill"></i> Updated!</div>
  <?php elseif (isset($_GET['deleted'])): ?>
  <div class="alert-box alert-deleted"><i class="bi bi-trash-fill"></i> Deleted!</div>
  <?php endif; ?>

  <!-- STATS -->
  <div class="stat-row">
    <div class="stat-card">
      <div class="stat-label">Today Planned</div>
      <div class="stat-num"><?= $today_planned ?> <small style="font-size:13px;color:#8a6a4a;">items</small></div>
    </div>
    <div class="stat-card" style="border-top-color:#66bb6a;">
      <div class="stat-label">Today Completed</div>
      <div class="stat-num" style="color:#2e7d32;"><?= $today_completed ?> <small style="font-size:13px;color:#8a6a4a;">items</small></div>
    </div>
    <div class="stat-card" style="border-top-color:#e65c00;">
      <div class="stat-label">In Progress</div>
      <div class="stat-num" style="color:#e65c00;"><?= $in_progress ?></div>
    </div>
    <div class="stat-card" style="border-top-color:#42a5f5;">
      <div class="stat-label">This Week Total</div>
      <div class="stat-num"><?= $week_total ?> <small style="font-size:13px;color:#8a6a4a;">items</small></div>
    </div>
  </div>

  <!-- WEEK OVERVIEW -->
  <?php
  $week_start = strtotime('monday this week');
  $week_data  = [];
  for ($i = 0; $i < 7; $i++) {
      $d = date('Y-m-d', strtotime("+$i days", $week_start));
      $cnt = mysqli_fetch_assoc(mysqli_query($conn,
          "SELECT COUNT(*) as c, COALESCE(SUM(planned_qty),0) as t
           FROM production_plans WHERE plan_date='$d'"));
      $week_data[] = ['date'=>$d,'count'=>$cnt['c'],'total'=>$cnt['t']];
  }
  ?>
  <div class="card-box">
    <div class="card-title">
      <span><i class="bi bi-calendar-week me-2" style="color:#c9973a;"></i>This Week Overview</span>
    </div>
    <div class="week-grid">
      <?php foreach ($week_data as $wd): ?>
      <a href="production.php?date=<?= $wd['date'] ?>"
         style="text-decoration:none;">
        <div class="week-day <?= $wd['date']===date('Y-m-d')?'today':'' ?>">
          <div class="week-day-label"><?= date('D', strtotime($wd['date'])) ?></div>
          <div class="week-day-date"><?= date('d', strtotime($wd['date'])) ?></div>
          <?php if ($wd['count'] > 0): ?>
          <div class="week-day-count"><?= $wd['count'] ?> plans<br><?= $wd['total'] ?> items</div>
          <?php else: ?>
          <div style="font-size:11px;color:#c9b090;">No plans</div>
          <?php endif; ?>
        </div>
      </a>
      <?php endforeach; ?>
    </div>
  </div>

  <!-- DATE NAVIGATION -->
  <div class="date-nav">
    <a href="production.php?date=<?= date('Y-m-d', strtotime($view_date.' -1 day')) ?>"
       class="date-nav-btn"><i class="bi bi-chevron-left"></i></a>

    <form method="GET" style="display:flex;align-items:center;gap:8px;">
      <input type="date" name="date" class="date-input"
             value="<?= $view_date ?>"
             onchange="this.form.submit()">
    </form>

    <a href="production.php?date=<?= date('Y-m-d') ?>"
       class="date-nav-btn <?= $view_date===date('Y-m-d')&&!$view_week?'active':'' ?>">
      Today
    </a>
    <a href="production.php?date=<?= date('Y-m-d', strtotime($view_date.' +1 day')) ?>"
       class="date-nav-btn"><i class="bi bi-chevron-right"></i></a>

    <div style="margin-left:auto;">
      <button class="btn-gold btn-sm" onclick="openAddModal()">
        <i class="bi bi-plus-lg"></i> Add Plan
      </button>
    </div>
  </div>

  <!-- PLANS LIST -->
  <div class="card-box">
    <div class="card-title">
      <span>
        <i class="bi bi-list-check me-2" style="color:#c9973a;"></i>
        Production Plans —
        <?= $view_week ? 'This Week' : date('l, d F Y', strtotime($view_date)) ?>
      </span>
      <span style="font-size:13px;color:#8a6a4a;"><?= mysqli_num_rows($plans) ?> plans</span>
    </div>

    <?php
    $plan_count = mysqli_num_rows($plans);
    if ($plan_count === 0):
    ?>
    <div class="empty-state">
      <i class="bi bi-clipboard-x"></i>
      <p>No production plans for this date!</p>
      <button class="btn-gold btn-sm" onclick="openAddModal()">
        <i class="bi bi-plus-lg me-1"></i>Add Plan
      </button>
    </div>
    <?php else: ?>

    <?php
    mysqli_data_seek($plans, 0);
    while ($plan = mysqli_fetch_assoc($plans)):
      $pct = $plan['planned_qty'] > 0
           ? min(100, round(($plan['actual_qty'] / $plan['planned_qty']) * 100))
           : 0;
      $bar_color = $pct >= 100 ? '#2e7d32'
                 : ($pct >= 50  ? '#c9973a' : '#ef5350');
    ?>
    <div class="plan-card <?= $plan['status'] ?>">
      <div class="plan-header">
        <div>
          <div class="plan-product">
            <?= $icons[$plan['category']] ?? '🍰' ?>
            <?= htmlspecialchars($plan['product_name'] ?? 'Unknown') ?>
            <span class="cat-badge cat-<?= $plan['category'] ?>"><?= ucfirst($plan['category']) ?></span>
          </div>
          <div class="plan-date">
            <i class="bi bi-calendar3 me-1"></i>
            <?= date('d M Y', strtotime($plan['plan_date'])) ?>
            <?php if ($plan['assigned_name']): ?>
              &nbsp;·&nbsp;<i class="bi bi-person me-1"></i><?= htmlspecialchars($plan['assigned_name']) ?>
            <?php endif; ?>
          </div>
        </div>
        <span class="status-<?= $plan['status'] ?>"><?= ucfirst(str_replace('_',' ',$plan['status'])) ?></span>
      </div>

      <!-- PROGRESS -->
      <div style="display:flex;align-items:center;gap:12px;">
        <div style="flex:1;">
          <div style="display:flex;justify-content:space-between;font-size:12px;color:#8a6a4a;margin-bottom:4px;">
            <span>Progress</span>
            <span><?= $plan['actual_qty'] ?> / <?= $plan['planned_qty'] ?> items (<?= $pct ?>%)</span>
          </div>
          <div class="progress-wrap">
            <div class="progress-fill"
                 style="width:<?= $pct ?>%;background:<?= $bar_color ?>;">
            </div>
          </div>
        </div>
        <div style="text-align:right;">
          <div style="font-size:20px;font-weight:700;color:<?= $bar_color ?>;"><?= $pct ?>%</div>
          <div style="font-size:11px;color:#8a6a4a;">complete</div>
        </div>
      </div>

      <?php if ($plan['notes']): ?>
      <div style="background:#fdf5ec;border-radius:8px;padding:8px 12px;font-size:13px;color:#8a5a20;margin-top:8px;">
        <i class="bi bi-chat-text me-1"></i><?= htmlspecialchars($plan['notes']) ?>
      </div>
      <?php endif; ?>

      <!-- ACTIONS -->
      <div class="plan-actions">
        <!-- Quick status update -->
        <form method="POST" style="display:flex;align-items:center;gap:6px;flex:1;">
          <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?>">
          <input type="hidden" name="plan_id" value="<?= $plan['plan_id'] ?>">
          <input type="hidden" name="update_status" value="1">
          <input type="number" name="actual_qty"
                 value="<?= $plan['actual_qty'] ?>"
                 min="0" max="<?= $plan['planned_qty'] * 2 ?>"
                 style="width:80px;border:1px solid #e8d8c0;border-radius:6px;padding:5px 8px;font-size:13px;"
                 placeholder="Actual">
          <select name="status"
                  style="border:1px solid #e8d8c0;border-radius:6px;padding:5px 8px;font-size:12px;background:#fff;cursor:pointer;">
            <option value="planned"     <?= $plan['status']==='planned'     ?'selected':'' ?>>Planned</option>
            <option value="in_progress" <?= $plan['status']==='in_progress' ?'selected':'' ?>>In Progress</option>
            <option value="completed"   <?= $plan['status']==='completed'   ?'selected':'' ?>>Completed</option>
            <option value="cancelled"   <?= $plan['status']==='cancelled'   ?'selected':'' ?>>Cancelled</option>
          </select>
          <button type="submit" class="btn-gold btn-sm">
            <i class="bi bi-check-lg"></i> Update
          </button>
        </form>
        <button class="btn-edit"
          onclick="openEditModal(
            <?= $plan['plan_id'] ?>,
            '<?= $plan['plan_date'] ?>',
            <?= $plan['product_id'] ?>,
            <?= $plan['planned_qty'] ?>,
            '<?= $plan['assigned_to'] ?>',
            '<?= addslashes($plan['notes'] ?? '') ?>'
          )">
          <i class="bi bi-pencil"></i>
        </button>
        <a href="production.php?delete=<?= $plan['plan_id'] ?>"
           class="btn-del"
           onclick="return confirm('Delete this plan?')">
          <i class="bi bi-trash"></i>
        </a>
      </div>
    </div>
    <?php endwhile; ?>
    <?php endif; ?>
  </div>
</div>

<!-- ADD/EDIT MODAL -->
<div class="modal fade" id="planModal" tabindex="-1">
  <div class="modal-dialog modal-dialog-centered">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title" id="planModalTitle">
          <i class="bi bi-plus-lg me-2" style="color:#c9973a;"></i>Add Production Plan
        </h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
      </div>
      <div class="modal-body" style="padding:24px;">
        <form method="POST">
          <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?>">
          <input type="hidden" name="save_plan" value="1">
          <input type="hidden" name="plan_id" id="plan_id">
          <div class="row g-3">
            <div class="col-12">
              <label class="form-label">Production Date *</label>
              <input type="date" name="plan_date" id="f_date"
                     class="form-control"
                     value="<?= $view_date ?>" required>
            </div>
            <div class="col-12">
              <label class="form-label">Product *</label>
              <select name="product_id" id="f_product" class="form-select" required>
                <option value="">Select Product</option>
                <?php
                mysqli_data_seek($products, 0);
                while ($p = mysqli_fetch_assoc($products)):
                ?>
                <option value="<?= $p['product_id'] ?>">
                  <?= ($icons[$p['category']]??'') ?> <?= htmlspecialchars($p['name']) ?>
                </option>
                <?php endwhile; ?>
              </select>
            </div>
            <div class="col-md-6">
              <label class="form-label">Planned Quantity *</label>
              <input type="number" name="planned_qty" id="f_qty"
                     class="form-control" min="1" required
                     placeholder="How many to make?">
            </div>
            <div class="col-md-6">
              <label class="form-label">Assign To
                <small style="color:#8a6a4a;font-weight:400;">(optional)</small>
              </label>
              <select name="assigned_to" id="f_assign" class="form-select">
                <option value="">Unassigned</option>
                <?php
                mysqli_data_seek($staff, 0);
                while ($s = mysqli_fetch_assoc($staff)):
                ?>
                <option value="<?= $s['user_id'] ?>"><?= htmlspecialchars($s['name']) ?></option>
                <?php endwhile; ?>
              </select>
            </div>
            <div class="col-12">
              <label class="form-label">Notes
                <small style="color:#8a6a4a;font-weight:400;">(optional)</small>
              </label>
              <textarea name="notes" id="f_notes" class="form-control"
                        rows="2" placeholder="Special instructions..."></textarea>
            </div>
          </div>
          <button type="submit" class="btn-gold w-100 mt-3"
                  style="padding:12px;font-size:15px;justify-content:center;">
            <i class="bi bi-check-lg me-2"></i>Save Plan
          </button>
        </form>
      </div>
    </div>
  </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script>
function openAddModal() {
  document.getElementById('planModalTitle').innerHTML =
    '<i class="bi bi-plus-lg me-2" style="color:#c9973a;"></i>Add Production Plan';
  document.getElementById('plan_id').value    = '';
  document.getElementById('f_date').value     = '<?= $view_date ?>';
  document.getElementById('f_product').value  = '';
  document.getElementById('f_qty').value      = '';
  document.getElementById('f_assign').value   = '';
  document.getElementById('f_notes').value    = '';
  new bootstrap.Modal(document.getElementById('planModal')).show();
}

function openEditModal(id, date, product_id, qty, assigned, notes) {
  document.getElementById('planModalTitle').innerHTML =
    '<i class="bi bi-pencil me-2" style="color:#c9973a;"></i>Edit Plan';
  document.getElementById('plan_id').value    = id;
  document.getElementById('f_date').value     = date;
  document.getElementById('f_product').value  = product_id;
  document.getElementById('f_qty').value      = qty;
  document.getElementById('f_assign').value   = assigned;
  document.getElementById('f_notes').value    = notes;
  new bootstrap.Modal(document.getElementById('planModal')).show();
}
</script>
</body>
</html>