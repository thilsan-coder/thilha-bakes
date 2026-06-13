<?php
require_once '../includes/db.php';
if (!isLoggedIn() || !isAdmin()) redirect('../login.php');

$uid = (int)$_SESSION['user_id'];
$msg = ''; $err = '';

// Upload profile photo
if (isset($_POST['upload_photo'])) {
    checkCSRF();
    if (!empty($_FILES['profile_photo']['name'])) {
        $allowed_mime = ['image/jpeg','image/png','image/gif','image/webp'];
        $allowed_ext  = ['jpg','jpeg','png','gif','webp'];
        $file         = $_FILES['profile_photo'];

        if ($file['error'] !== UPLOAD_ERR_OK) {
            $err = 'Upload failed!';
        } elseif ($file['size'] > 3 * 1024 * 1024) {
            $err = 'Image too large! Max 3MB.';
        } else {
            $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
            $finfo = finfo_open(FILEINFO_MIME_TYPE);
            $mime  = finfo_file($finfo, $file['tmp_name']);
            finfo_close($finfo);

            if (!in_array($ext, $allowed_ext) || !in_array($mime, $allowed_mime)) {
                $err = 'Only JPG, PNG, GIF, WEBP allowed!';
            } else {
                // Delete old photo
                $old = mysqli_fetch_assoc(mysqli_query($conn,
                    "SELECT profile_photo FROM users WHERE user_id=$uid"))['profile_photo'];
                if ($old && file_exists('../assets/images/'.$old)) {
                    unlink('../assets/images/'.$old);
                }
                $filename = 'profile_'.$uid.'_'.time().'.'.$ext;
                if (move_uploaded_file($file['tmp_name'], '../assets/images/'.$filename)) {
                    mysqli_query($conn,
                        "UPDATE users SET profile_photo='$filename' WHERE user_id=$uid");
                    $_SESSION['profile_photo'] = $filename;
                    $msg = 'Profile photo updated!';
                } else {
                    $err = 'Failed to save image!';
                }
            }
        }
    }
}

// Remove photo
if (isset($_POST['remove_photo'])) {
    checkCSRF();
    $old = mysqli_fetch_assoc(mysqli_query($conn,
        "SELECT profile_photo FROM users WHERE user_id=$uid"))['profile_photo'];
    if ($old && file_exists('../assets/images/'.$old)) unlink('../assets/images/'.$old);
    mysqli_query($conn, "UPDATE users SET profile_photo=NULL WHERE user_id=$uid");
    $_SESSION['profile_photo'] = null;
    $msg = 'Profile photo removed!';
}

// Update profile
if (isset($_POST['update_profile'])) {
    checkCSRF();
    $name  = e($conn, $_POST['name']);
    $email = e($conn, $_POST['email']);
    $phone = e($conn, $_POST['phone'] ?? '');
    mysqli_query($conn,
        "UPDATE users SET name='$name',email='$email',phone='$phone'
         WHERE user_id=$uid");
    $_SESSION['name'] = $name;
    $msg = 'Profile updated!';
}

// Change password
if (isset($_POST['change_password'])) {
    checkCSRF();
    $current = $_POST['current_password'];
    $new_pw  = $_POST['new_password'];
    $confirm = $_POST['confirm_password'];
    $user    = mysqli_fetch_assoc(mysqli_query($conn,
        "SELECT password FROM users WHERE user_id=$uid"));

    if (!verifyPassword($current, $user['password'])) {
        $err = 'Current password is incorrect!';
    } elseif (strlen($new_pw) < 6) {
        $err = 'New password must be at least 6 characters!';
    } elseif ($new_pw !== $confirm) {
        $err = 'Passwords do not match!';
    } else {
        $hash = hashPassword($new_pw);
        mysqli_query($conn,"UPDATE users SET password='$hash' WHERE user_id=$uid");
        $msg = 'Password changed!';
    }
}

$user = mysqli_fetch_assoc(mysqli_query($conn,"SELECT * FROM users WHERE user_id=$uid"));
// sync session photo
$_SESSION['profile_photo'] = $user['profile_photo'] ?? null;
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>My Profile — Thilha Divine Bakes</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
<link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css" rel="stylesheet">
<style>
*{box-sizing:border-box;}
body{background:#f5f0eb;font-family:'Segoe UI',sans-serif;color:#2a1a10;margin:0;}
.page-title{font-size:22px;font-weight:700;color:#1a0a0f;margin-bottom:2px;}
.page-sub{font-size:13px;color:#8a6a4a;margin-bottom:24px;}
.card-box{background:#fff;border:1px solid #e8d8c0;border-radius:14px;padding:24px;margin-bottom:20px;}
.card-title{font-size:16px;font-weight:600;color:#1a0a0f;margin-bottom:18px;padding-bottom:12px;border-bottom:1px solid #f0e4d0;}
.form-label{font-size:13px;color:#8a6a4a;font-weight:600;}
.form-control{border:1px solid #e8d8c0;border-radius:8px;font-size:14px;color:#2a1a10;padding:10px 14px;}
.form-control:focus{border-color:#c9973a;box-shadow:0 0 0 3px rgba(201,151,58,0.15);}
.btn-gold{background:#c9973a;border:none;border-radius:8px;color:#fff;font-weight:600;padding:10px 22px;font-size:13px;cursor:pointer;display:inline-flex;align-items:center;gap:6px;}
.btn-gold:hover{background:#a07828;color:#fff;}
.btn-danger{background:#ffebee;border:none;border-radius:8px;color:#c62828;font-weight:600;padding:10px 18px;font-size:13px;cursor:pointer;display:inline-flex;align-items:center;gap:6px;}
.btn-danger:hover{background:#ffcdd2;}
.alert-s{background:#e8f5e9;border:1px solid #a5d6a7;border-left:4px solid #2e7d32;border-radius:10px;padding:12px 16px;font-size:13px;color:#1b5e20;margin-bottom:20px;display:flex;align-items:center;gap:8px;}
.alert-e{background:#ffebee;border:1px solid #ffcdd2;border-left:4px solid #c62828;border-radius:10px;padding:12px 16px;font-size:13px;color:#c62828;margin-bottom:20px;display:flex;align-items:center;gap:8px;}

/* PHOTO SECTION */
.photo-section{display:flex;align-items:center;gap:28px;flex-wrap:wrap;}
.photo-display{position:relative;flex-shrink:0;}
.profile-photo-img{
  width:110px;height:110px;border-radius:50%;
  object-fit:cover;
  border:3px solid #c9973a;
  box-shadow:0 4px 16px rgba(201,151,58,0.25);
}
.profile-avatar{
  width:110px;height:110px;border-radius:50%;
  background:linear-gradient(135deg,#1a0a0f,#2a1018);
  border:3px solid #c9973a;
  display:flex;align-items:center;justify-content:center;
  font-family:Georgia,serif;font-size:40px;font-weight:900;
  color:#e8c870;
  box-shadow:0 4px 16px rgba(201,151,58,0.25);
}
.photo-info{flex:1;}
.photo-info h3{font-size:22px;font-weight:700;color:#1a0a0f;margin-bottom:4px;}
.photo-info p{font-size:14px;color:#8a6a4a;margin-bottom:12px;}
.photo-upload-area{
  border:2px dashed #e8d8c0;border-radius:10px;
  padding:16px;background:#fdf5ec;
  transition:border .2s;cursor:pointer;
  text-align:center;margin-bottom:12px;
}
.photo-upload-area:hover{border-color:#c9973a;background:#fff8ee;}
.photo-upload-area input{display:none;}
.upload-preview{
  width:60px;height:60px;border-radius:50%;
  object-fit:cover;border:2px solid #c9973a;
  margin:0 auto 8px;display:none;
}
</style>
</head>
<body>
<?php include '../includes/sidebar.php'; ?>
<div class="main">
  <div class="page-title"><i class="bi bi-person-gear me-2" style="color:#c9973a;"></i>My Profile</div>
  <div class="page-sub">Manage your admin account — <?= date('l, d F Y') ?></div>

  <?php if ($msg): ?>
  <div class="alert-s"><i class="bi bi-check-circle-fill"></i><?= htmlspecialchars($msg) ?></div>
  <?php endif; ?>
  <?php if ($err): ?>
  <div class="alert-e"><i class="bi bi-exclamation-circle-fill"></i><?= htmlspecialchars($err) ?></div>
  <?php endif; ?>

  <div class="row g-4">
    <div class="col-lg-8">

      <!-- PROFILE PHOTO CARD -->
      <div class="card-box">
        <div class="card-title"><i class="bi bi-camera me-2" style="color:#c9973a;"></i>Profile Photo</div>
        <div class="photo-section">
          <!-- CURRENT PHOTO -->
          <div class="photo-display">
            <?php if ($user['profile_photo'] && file_exists('../assets/images/'.$user['profile_photo'])): ?>
            <img src="../assets/images/<?= htmlspecialchars($user['profile_photo']) ?>"
                 class="profile-photo-img" id="currentPhotoDisplay">
            <?php else: ?>
            <div class="profile-avatar" id="currentPhotoDisplay">
              <?= strtoupper(substr($user['name'],0,1)) ?>
            </div>
            <?php endif; ?>
          </div>

          <div class="photo-info">
            <h3><?= htmlspecialchars($user['name']) ?></h3>
            <p><?= htmlspecialchars($user['email']) ?></p>

            <!-- UPLOAD FORM -->
            <form method="POST" enctype="multipart/form-data">
              <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?>">
              <input type="hidden" name="upload_photo" value="1">

              <div class="photo-upload-area" onclick="document.getElementById('photoInput').click()">
                <img id="uploadPreview" class="upload-preview" src="">
                <i class="bi bi-cloud-upload" style="font-size:24px;color:#c9973a;display:block;margin-bottom:8px;"></i>
                <div style="font-size:13px;font-weight:600;color:#c9973a;">Click to choose photo</div>
                <div style="font-size:12px;color:#8a6a4a;margin-top:4px;">JPG, PNG, WEBP — Max 3MB</div>
              </div>

              <input type="file" name="profile_photo" id="photoInput"
                     accept="image/*" onchange="previewPhoto(this)">

              <div style="display:flex;gap:10px;flex-wrap:wrap;">
                <button type="submit" class="btn-gold">
                  <i class="bi bi-upload"></i> Upload Photo
                </button>
                <?php if ($user['profile_photo']): ?>
                <button type="submit" name="remove_photo" value="1"
                        formnovalidate class="btn-danger"
                        onclick="return confirm('Remove profile photo?')">
                  <i class="bi bi-trash"></i> Remove
                </button>
                <?php endif; ?>
              </div>
            </form>
          </div>
        </div>
      </div>

      <!-- UPDATE PROFILE -->
      <div class="card-box">
        <div class="card-title"><i class="bi bi-person me-2" style="color:#c9973a;"></i>Account Details</div>
        <form method="POST">
          <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?>">
          <input type="hidden" name="update_profile" value="1">
          <div class="row g-3">
            <div class="col-md-6">
              <label class="form-label">Full Name</label>
              <input type="text" name="name" class="form-control"
                     value="<?= htmlspecialchars($user['name']) ?>" required>
            </div>
            <div class="col-md-6">
              <label class="form-label">Phone</label>
              <input type="text" name="phone" class="form-control"
                     value="<?= htmlspecialchars($user['phone']??'') ?>"
                     placeholder="+94 77 XXX XXXX">
            </div>
            <div class="col-12">
              <label class="form-label">Email Address</label>
              <input type="email" name="email" class="form-control"
                     value="<?= htmlspecialchars($user['email']) ?>" required>
            </div>
          </div>
          <div style="margin-top:18px;">
            <button type="submit" class="btn-gold">
              <i class="bi bi-check-lg"></i> Save Changes
            </button>
          </div>
        </form>
      </div>

      <!-- CHANGE PASSWORD -->
      <div class="card-box">
        <div class="card-title"><i class="bi bi-lock me-2" style="color:#c9973a;"></i>Change Password</div>
        <form method="POST">
          <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?>">
          <input type="hidden" name="change_password" value="1">
          <div class="row g-3">
            <div class="col-12">
              <label class="form-label">Current Password</label>
              <input type="password" name="current_password" class="form-control"
                     placeholder="Enter current password" required>
            </div>
            <div class="col-md-6">
              <label class="form-label">New Password</label>
              <input type="password" name="new_password" class="form-control"
                     placeholder="Min 6 characters" required>
            </div>
            <div class="col-md-6">
              <label class="form-label">Confirm Password</label>
              <input type="password" name="confirm_password" class="form-control"
                     placeholder="Repeat new password" required>
            </div>
          </div>
          <div style="margin-top:18px;">
            <button type="submit" class="btn-gold">
              <i class="bi bi-lock-fill"></i> Change Password
            </button>
          </div>
        </form>
      </div>

    </div>

    <!-- QUICK INFO -->
    <div class="col-lg-4">
      <div class="card-box">
        <div class="card-title"><i class="bi bi-info-circle me-2" style="color:#c9973a;"></i>Account Info</div>
        <div style="display:flex;flex-direction:column;gap:14px;">
          <div>
            <div style="font-size:11px;color:#c9973a;font-weight:700;letter-spacing:1px;text-transform:uppercase;margin-bottom:4px;">Role</div>
            <div style="font-size:14px;font-weight:600;">
              <span style="background:#fdf5ec;border:1px solid #e8d8c0;border-radius:99px;padding:4px 14px;color:#c9973a;">
                👑 Admin
              </span>
            </div>
          </div>
          <div>
            <div style="font-size:11px;color:#c9973a;font-weight:700;letter-spacing:1px;text-transform:uppercase;margin-bottom:4px;">Member Since</div>
            <div style="font-size:14px;font-weight:600;"><?= date('d M Y', strtotime($user['created_at'])) ?></div>
          </div>
          <div>
            <div style="font-size:11px;color:#c9973a;font-weight:700;letter-spacing:1px;text-transform:uppercase;margin-bottom:4px;">Bakery</div>
            <div style="font-size:14px;font-weight:600;">Thilha Divine Bakes</div>
            <div style="font-size:12px;color:#8a6a4a;">Central Camp, Ampara</div>
          </div>
        </div>
      </div>
    </div>
  </div>
</div>

<script>
function previewPhoto(input) {
  if (input.files && input.files[0]) {
    var reader = new FileReader();
    reader.onload = function(e) {
      var preview = document.getElementById('uploadPreview');
      preview.src   = e.target.result;
      preview.style.display = 'block';
      // Update the main photo display
      var display = document.getElementById('currentPhotoDisplay');
      if (display.tagName === 'IMG') {
        display.src = e.target.result;
      } else {
        // Replace avatar div with img
        var img = document.createElement('img');
        img.src = e.target.result;
        img.className = 'profile-photo-img';
        img.id = 'currentPhotoDisplay';
        display.parentNode.replaceChild(img, display);
      }
    };
    reader.readAsDataURL(input.files[0]);
  }
}
</script>
</body>
</html>
