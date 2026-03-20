<?php
require_once __DIR__ . '/../config/session.php';
requireLogin();
if (isAdmin()) { header('Location:/app/admin/dashboard.php'); exit; }

$user = currentUser();

$allowedTypes = ['image/jpeg', 'image/png', 'application/pdf'];
$maxSize = 5 * 1024 * 1024; // 5MB

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['update_profile'])) {
        $name = trim($_POST['name'] ?? $user['name']);
        $age = (!empty($_POST['age']) && is_numeric($_POST['age'])) ? (int)$_POST['age'] : null;
        $gender = in_array($_POST['gender']??"", ['Male','Female','Other']) ? $_POST['gender'] : null;
        $address = trim($_POST['address'] ?? '');
        $bank = trim($_POST['bank_account'] ?? '');

        // Handle Avatar Upload
        $profile_pic = $user['profile_pic'] ?? '/assets/default_avatar.png';
        if (isset($_FILES['avatar']) && $_FILES['avatar']['error'] === UPLOAD_ERR_OK) {
            if (in_array($_FILES['avatar']['type'], ['image/jpeg', 'image/png']) && $_FILES['avatar']['size'] <= $maxSize) {
                $ext = pathinfo($_FILES['avatar']['name'], PATHINFO_EXTENSION);
                $newName = 'avatar_' . $user['id'] . '_' . time() . '.' . $ext;
                $dest = __DIR__ . '/../../uploads/avatars/' . $newName;
                if (move_uploaded_file($_FILES['avatar']['tmp_name'], $dest)) {
                    $profile_pic = '/uploads/avatars/' . $newName;
                }
            } else {
                flash('danger', 'Invalid avatar image (Max 5MB, JPG/PNG).');
            }
        }

        db()->prepare("UPDATE users SET name=?, profile_pic=?, age=?, gender=?, address=?, bank_account=? WHERE id=?")
            ->execute([$name, $profile_pic, $age, $gender, $address, $bank, $user['id']]);
        flash('success', 'Profile updated successfully.');
        header('Location: profile.php'); exit;
    }
    
    if (isset($_POST['upload_kyc'])) {
        if ($user['kyc_status'] === 'approved') {
            flash('danger', 'Your KYC is already approved.');
        } elseif (isset($_FILES['kyc_doc']) && $_FILES['kyc_doc']['error'] === UPLOAD_ERR_OK) {
            if (in_array($_FILES['kyc_doc']['type'], $allowedTypes) && $_FILES['kyc_doc']['size'] <= $maxSize) {
                $ext = pathinfo($_FILES['kyc_doc']['name'], PATHINFO_EXTENSION);
                $newName = 'kyc_' . $user['id'] . '_' . time() . '.' . $ext;
                $dest = __DIR__ . '/../../uploads/kyc/' . $newName;
                if (move_uploaded_file($_FILES['kyc_doc']['tmp_name'], $dest)) {
                    db()->prepare("UPDATE users SET kyc_document=?, kyc_status='pending' WHERE id=?")
                        ->execute(['/uploads/kyc/' . $newName, $user['id']]);
                    flash('success', 'KYC document uploaded successfully. Waiting for Admin approval.');
                }
            } else {
                flash('danger', 'Invalid KYC document (Max 5MB, JPG/PNG/PDF).');
            }
        } else {
            flash('danger', 'Please select a valid document to upload.');
        }
        header('Location: profile.php'); exit;
    }
}

// Refresh user data
$user = currentUser();

$pageTitle = 'My Profile';
include __DIR__ . '/../includes/header.php';
?>
<div class="topbar"><h1>My Profile & Verification</h1></div>

<div style="display:grid;grid-template-columns:1fr 1fr;gap:2rem">
  <!-- Profile Form -->
  <div class="card">
    <h3 style="margin-bottom:1.5rem">Personal Details</h3>
    <form method="POST" enctype="multipart/form-data">
        <div style="display:flex;align-items:center;gap:1.5rem;margin-bottom:2rem">
            <img src="<?= htmlspecialchars($user['profile_pic']??'/assets/default_avatar.png') ?>" style="width:80px;height:80px;border-radius:50%;object-fit:cover;border:2px solid var(--primary)">
            <div>
                <label style="color:var(--primary);cursor:pointer;font-size:.9rem;font-weight:600">
                    <i class="fas fa-camera"></i> Change Photo
                    <input type="file" name="avatar" accept="image/png, image/jpeg" style="display:none">
                </label>
                <div style="font-size:.8rem;color:var(--muted);margin-top:.2rem">Max 5MB (JPG/PNG)</div>
            </div>
        </div>
        
        <div style="margin-bottom:1.2rem">
            <label style="color:var(--muted);font-size:.85rem;display:block;margin-bottom:.5rem">Full Name</label>
            <input type="text" name="name" value="<?= htmlspecialchars($user['name']) ?>" required style="width:100%;padding:.8rem;background:rgba(255,255,255,.04);border:1px solid var(--border);border-radius:8px;color:var(--text);outline:none">
        </div>
        
        <div style="display:grid;grid-template-columns:1fr 1fr;gap:1rem;margin-bottom:1.2rem">
            <div>
                <label style="color:var(--muted);font-size:.85rem;display:block;margin-bottom:.5rem">Age</label>
                <input type="number" name="age" value="<?= htmlspecialchars($user['age']??'') ?>" min="18" max="120" style="width:100%;padding:.8rem;background:rgba(255,255,255,.04);border:1px solid var(--border);border-radius:8px;color:var(--text);outline:none">
            </div>
            <div>
                <label style="color:var(--muted);font-size:.85rem;display:block;margin-bottom:.5rem">Gender</label>
                <select name="gender" style="width:100%;padding:.8rem;background:rgba(255,255,255,.04);border:1px solid var(--border);border-radius:8px;color:var(--text);outline:none">
                    <option value="" <?= empty($user['gender'])?'selected':'' ?>>Select...</option>
                    <option value="Male" <?= ($user['gender']??'')==='Male'?'selected':'' ?>>Male</option>
                    <option value="Female" <?= ($user['gender']??'')==='Female'?'selected':'' ?>>Female</option>
                    <option value="Other" <?= ($user['gender']??'')==='Other'?'selected':'' ?>>Other</option>
                </select>
            </div>
        </div>

        <div style="margin-bottom:1.2rem">
            <label style="color:var(--muted);font-size:.85rem;display:block;margin-bottom:.5rem">Full Address</label>
            <textarea name="address" rows="3" style="width:100%;padding:.8rem;background:rgba(255,255,255,.04);border:1px solid var(--border);border-radius:8px;color:var(--text);outline:none"><?= htmlspecialchars($user['address']??'') ?></textarea>
        </div>

        <div style="margin-bottom:1.5rem">
            <label style="color:var(--muted);font-size:.85rem;display:block;margin-bottom:.5rem">Bank Account Details (Fiat Withdrawals)</label>
            <input type="text" name="bank_account" value="<?= htmlspecialchars($user['bank_account']??'') ?>" placeholder="Bank Name, Account #, SWIFT/Routing" style="width:100%;padding:.8rem;background:rgba(255,255,255,.04);border:1px solid var(--border);border-radius:8px;color:var(--text);outline:none">
        </div>

        <button type="submit" name="update_profile" class="btn btn-primary" style="width:100%">Save Profile changes</button>
    </form>
  </div>

  <!-- KYC Form -->
  <div>
      <div class="card" style="margin-bottom:1.5rem">
        <h3 style="margin-bottom:1.5rem">KYC Verification</h3>
        
        <?php if($user['kyc_status'] === 'approved'): ?>
            <div class="alert alert-success"><i class="fas fa-check-circle"></i> Your KYC has been approved. Your account is fully verified.</div>
        <?php elseif($user['kyc_status'] === 'pending'): ?>
            <div class="alert" style="background:rgba(255,171,0,.15);color:var(--warning);border:1px solid rgba(255,171,0,.3)"><i class="fas fa-clock"></i> Your KYC document is currently pending review by an administrator.</div>
        <?php else: ?>
            <?php if($user['kyc_status'] === 'rejected'): ?>
            <div class="alert alert-danger"><i class="fas fa-exclamation-triangle"></i> Your previous KYC submission was rejected. Please upload a clear, valid Government ID.</div>
            <?php else: ?>
            <div class="alert" style="background:rgba(255,255,255,.05);color:var(--muted);border:1px solid var(--border)"><i class="fas fa-info-circle"></i> KYC Verification is required. Please upload a valid Government ID (Passport, Driver's License, National ID).</div>
            <?php endif; ?>
            
            <form method="POST" enctype="multipart/form-data">
                <div style="margin-bottom:1.2rem">
                    <label style="color:var(--muted);font-size:.85rem;display:block;margin-bottom:.5rem">Upload Government ID</label>
                    <input type="file" name="kyc_doc" accept="image/png, image/jpeg, application/pdf" required style="width:100%;padding:.8rem;background:rgba(255,255,255,.04);border:1px solid var(--border);border-radius:8px;color:var(--text);outline:none">
                    <div style="font-size:.8rem;color:var(--muted);margin-top:.4rem">Max 5MB. Formats: JPG, PNG, PDF.</div>
                </div>
                <button type="submit" name="upload_kyc" class="btn" style="background:var(--primary);color:#000;width:100%"><i class="fas fa-upload"></i> Submit KYC Document</button>
            </form>
        <?php endif; ?>
      </div>
  </div>
</div>
<?php include __DIR__ . '/../includes/footer.php'; ?>
