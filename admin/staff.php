<?php
require_once '../includes/db.php';
if (!isLoggedIn() || !isAdmin()) redirect('../login.php');

// delete staff
if (isset($_GET['delete'])) {
    $id = (int)$_GET['delete'];
    mysqli_query($conn, "DELETE FROM users WHERE user_id=$id AND role='staff'");
    redirect('staff.php?deleted=1');
}

// add / edit staff
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name    = mysqli_real_escape_string($conn, $_POST['name']);
    $email   = mysqli_real_escape_string($conn, $_POST['email']);
    $phone   = mysqli_real_escape_string($conn, $_POST['phone']);
    $address = mysqli_real_escape_string($conn, $_POST['address']);

    if (isset($_POST['user_id']) && $_POST['user_id']) {
        $id = (int)$_POST['user_id'];
        mysqli_query($conn,
            "UPDATE users SET name='$name', email='$email',
             phone='$phone', address='$address'
             WHERE user_id=$id AND role='staff'");
        redirect('staff.php?updated=1');
    } else {
        $password = MD5($_POST['password']);
        $check    = mysqli_fetch_assoc(mysqli_query($conn,
            "SELECT user_id FROM users WHERE email='$email'"));
        if ($check) {
            redirect('staff.php?exists=1');
        } else {
            mysqli_query($conn,
                "INSERT INTO users
                 (name, email, password, phone, address, role)
                 VALUES
                 ('$name','$email','$password','$phone','$address','staff')");
            redirect('staff.php?added=1');
        }
    }
}

$staff = mysqli_query($conn,
    "SELECT u.*,
     (SELECT COUNT(*) FROM staff_orders so WHERE so.staff_id=u.user_id) as assigned_orders
     FROM users u
     WHERE u.role='staff'
     ORDER BY u.created_at DESC");

$total_staff = mysqli_fetch_assoc(mysqli_query($conn,
    "SELECT COUNT(*) as cnt FROM users WHERE role='staff'"))['cnt'];
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>Staff — Thilha Divine Bakes</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
<link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css" rel="stylesheet">
<style>
  *{box-sizing:border-box;}
  body{background:#f5f0eb;font-family:'Segoe UI',sans-serif;color:#2a1a10;margin:0;}

  
  
  
  
  
  
  
  
  

  .main{margin-left:240px;padding:30px;}
  .page-title{font-size:22px;font-weight:700;color:#1a0a0f;margin-bottom:2px;}
  .page-sub{font-size:13px;color:#8a6a4a;margin-bottom:24px;}

  .card-box{background:#fff;border:1px solid #e8d8c0;border-radius:14px;padding:20px;margin-bottom:24px;}
  .card-title{font-size:16px;font-weight:600;color:#1a0a0f;margin-bottom:16px;padding-bottom:12px;border-bottom:1px solid #f0e4d0;display:flex;align-items:center;justify-content:space-between;}

  .btn-gold{background:#c9973a;border:none;border-radius:8px;color:#fff;font-weight:600;padding:8px 20px;font-size:13px;cursor:pointer;text-decoration:none;display:inline-flex;align-items:center;gap:6px;}
  .btn-gold:hover{background:#a07828;color:#fff;}
  .btn-edit{background:#e3f2fd;border:none;border-radius:6px;color:#1565c0;font-weight:600;padding:5px 12px;font-size:12px;cursor:pointer;}
  .btn-edit:hover{background:#bbdefb;}
  .btn-del{background:#ffebee;border:none;border-radius:6px;color:#c62828;font-weight:600;padding:5px 12px;font-size:12px;cursor:pointer;text-decoration:none;display:inline-block;}
  .btn-del:hover{background:#ffcdd2;}

  .staff-grid{display:grid;grid-template-columns:repeat(auto-fill,minmax(280px,1fr));gap:16px;margin-bottom:24px;}
  .staff-card{background:#fff;border:1px solid #e8d8c0;border-radius:14px;padding:20px;}
  .staff-avatar{width:56px;height:56px;border-radius:50%;background:#fdf5ec;border:2px solid #c9973a;display:flex;align-items:center;justify-content:center;font-size:22px;font-weight:700;color:#c9973a;flex-shrink:0;}
  .staff-name{font-size:16px;font-weight:600;color:#1a0a0f;margin-bottom:2px;}
  .staff-email{font-size:12px;color:#8a6a4a;}
  .staff-info{font-size:13px;color:#5a3a28;margin-top:8px;display:flex;flex-direction:column;gap:4px;}
  .staff-info span{display:flex;align-items:center;gap:6px;}
  .staff-actions{margin-top:14px;padding-top:14px;border-top:1px solid #f0e4d0;display:flex;gap:8px;}
  .orders-badge{background:#fdf5ec;border:1px solid #e8d8c0;border-radius:99px;font-size:11px;color:#c9973a;font-weight:600;padding:3px 10px;}

  .alert-box{border-radius:10px;padding:12px 16px;font-size:13px;margin-bottom:20px;display:flex;align-items:center;gap:8px;}
  .alert-success{background:#e8f5e9;border:1px solid #a5d6a7;border-left:4px solid #2e7d32;color:#1b5e20;}
  .alert-deleted{background:#ffebee;border:1px solid #ffcdd2;border-left:4px solid #c62828;color:#b71c1c;}
  .alert-warn{background:#fff3e0;border:1px solid #ffcc80;border-left:4px solid #e65c00;color:#bf360c;}

  .form-label{font-size:13px;color:#8a6a4a;font-weight:600;}
  .form-control{border:1px solid #e8d8c0;border-radius:8px;font-size:13px;color:#2a1a10;padding:9px 12px;}
  .form-control:focus{border-color:#c9973a;box-shadow:0 0 0 3px rgba(201,151,58,0.15);}
  .modal-content{border-radius:14px;border:1px solid #e8d8c0;}
  .modal-header{background:#fdf5ec;border-bottom:1px solid #e8d8c0;border-radius:14px 14px 0 0;}
</style>
</head>
<body>

<?php include '../includes/sidebar.php'; ?>
  
<!-- MAIN -->
<div class="main">
  <div class="page-title"><i class="bi bi-people-fill me-2" style="color:#c9973a;"></i>Staff</div>
  <div class="page-sub">Manage bakery staff members — <?= date('l, d F Y') ?></div>

  <?php if (isset($_GET['added'])): ?>
  <div class="alert-box alert-success"><i class="bi bi-check-circle-fill"></i> Staff member added!</div>
  <?php elseif (isset($_GET['updated'])): ?>
  <div class="alert-box alert-success"><i class="bi bi-check-circle-fill"></i> Staff updated!</div>
  <?php elseif (isset($_GET['deleted'])): ?>
  <div class="alert-box alert-deleted"><i class="bi bi-trash-fill"></i> Staff member removed!</div>
  <?php elseif (isset($_GET['exists'])): ?>
  <div class="alert-box alert-warn"><i class="bi bi-exclamation-triangle-fill"></i> Email already exists!</div>
  <?php endif; ?>

  <!-- STAFF CARDS -->
  <div class="card-box">
    <div class="card-title">
      <span><i class="bi bi-people me-2" style="color:#c9973a;"></i>
        Staff Members
        <span style="font-size:13px;color:#8a6a4a;margin-left:8px;"><?= $total_staff ?> total</span>
      </span>
      <button class="btn-gold" onclick="openAddModal()">
        <i class="bi bi-person-plus"></i> Add Staff
      </button>
    </div>

    <div class="staff-grid">
      <?php while ($s = mysqli_fetch_assoc($staff)): ?>
      <div class="staff-card">
        <div style="display:flex;align-items:center;gap:14px;margin-bottom:4px;">
          <div class="staff-avatar">
            <?= strtoupper(substr($s['name'], 0, 1)) ?>
          </div>
          <div>
            <div class="staff-name"><?= htmlspecialchars($s['name']) ?></div>
            <div class="staff-email"><?= htmlspecialchars($s['email']) ?></div>
            <span class="orders-badge"><?= $s['assigned_orders'] ?> orders assigned</span>
          </div>
        </div>
        <div class="staff-info">
          <?php if ($s['phone']): ?>
          <span><i class="bi bi-telephone" style="color:#c9973a;"></i><?= $s['phone'] ?></span>
          <?php endif; ?>
          <?php if ($s['address']): ?>
          <span><i class="bi bi-geo-alt" style="color:#c9973a;"></i><?= htmlspecialchars($s['address']) ?></span>
          <?php endif; ?>
          <span><i class="bi bi-calendar" style="color:#c9973a;"></i>Joined: <?= date('d M Y', strtotime($s['created_at'])) ?></span>
        </div>
        <div class="staff-actions">
          <button class="btn-edit" style="flex:1;"
            onclick="openEditModal(
              <?= $s['user_id'] ?>,
              '<?= addslashes($s['name']) ?>',
              '<?= addslashes($s['email']) ?>',
              '<?= $s['phone'] ?>',
              '<?= addslashes($s['address']) ?>'
            )">
            <i class="bi bi-pencil me-1"></i>Edit
          </button>
          <a href="staff.php?delete=<?= $s['user_id'] ?>"
             class="btn-del" style="flex:1;text-align:center;"
             onclick="return confirm('Remove this staff member?')">
            <i class="bi bi-trash me-1"></i>Remove
          </a>
        </div>
      </div>
      <?php endwhile; ?>

      <?php if ($total_staff == 0): ?>
      <div style="grid-column:1/-1;text-align:center;padding:40px;color:#8a6a4a;">
        <i class="bi bi-people" style="font-size:40px;color:#e8d8c0;"></i>
        <p style="margin-top:12px;font-size:14px;">No staff members yet. Add your first staff!</p>
      </div>
      <?php endif; ?>
    </div>
  </div>
</div>

<!-- ADD/EDIT MODAL -->
<div class="modal fade" id="staffModal" tabindex="-1">
  <div class="modal-dialog modal-dialog-centered">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title" id="staffModalTitle">
          <i class="bi bi-person-plus me-2" style="color:#c9973a;"></i>Add Staff
        </h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
      </div>
      <div class="modal-body" style="padding:24px;">
        <form method="POST">
          <input type="hidden" name="user_id" id="user_id">
          <div class="mb-3">
            <label class="form-label">Full Name</label>
            <input type="text" name="name" id="f_name"
                   class="form-control" placeholder="Staff full name" required>
          </div>
          <div class="mb-3">
            <label class="form-label">Email</label>
            <input type="email" name="email" id="f_email"
                   class="form-control" placeholder="staff@email.com" required>
          </div>
          <div class="mb-3">
            <label class="form-label">Phone</label>
            <input type="text" name="phone" id="f_phone"
                   class="form-control" placeholder="07X XXX XXXX">
          </div>
          <div class="mb-3">
            <label class="form-label">Address</label>
            <input type="text" name="address" id="f_address"
                   class="form-control" placeholder="Staff address">
          </div>
          <div class="mb-4" id="password_field">
            <label class="form-label">Password</label>
            <input type="password" name="password" id="f_password"
                   class="form-control" placeholder="Set login password">
          </div>
          <button type="submit" class="btn-gold w-100" style="padding:12px;font-size:15px;">
            <i class="bi bi-check-lg me-2"></i>Save Staff
          </button>
        </form>
      </div>
    </div>
  </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script>
function openAddModal() {
  document.getElementById('staffModalTitle').innerHTML =
    '<i class="bi bi-person-plus me-2" style="color:#c9973a;"></i>Add Staff';
  document.getElementById('user_id').value   = '';
  document.getElementById('f_name').value    = '';
  document.getElementById('f_email').value   = '';
  document.getElementById('f_phone').value   = '';
  document.getElementById('f_address').value = '';
  document.getElementById('f_password').value= '';
  document.getElementById('password_field').style.display = 'block';
  document.getElementById('f_password').required = true;
  new bootstrap.Modal(document.getElementById('staffModal')).show();
}

function openEditModal(id, name, email, phone, address) {
  document.getElementById('staffModalTitle').innerHTML =
    '<i class="bi bi-pencil me-2" style="color:#c9973a;"></i>Edit Staff';
  document.getElementById('user_id').value   = id;
  document.getElementById('f_name').value    = name;
  document.getElementById('f_email').value   = email;
  document.getElementById('f_phone').value   = phone;
  document.getElementById('f_address').value = address;
  document.getElementById('password_field').style.display = 'none';
  document.getElementById('f_password').required = false;
  new bootstrap.Modal(document.getElementById('staffModal')).show();
}
</script>
</body>
</html>