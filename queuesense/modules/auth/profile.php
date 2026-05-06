<?php
/**
 * QueueSense — User Profile (BCP SMS Pixel-Perfect Replica)
 */

require_once __DIR__ . '/../../config.php';
require_once __DIR__ . '/../../includes/functions.php';

$required_role = null; 
require_once __DIR__ . '/../../includes/auth_check.php';

$db = db_connect();
$user = current_user();

// Handle Cropped Avatar Upload
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['cropped_avatar'])) {
    $data = $_POST['cropped_avatar'];
    if (preg_match('/^data:image\/(\w+);base64,/', $data, $type)) {
        $data = substr($data, strpos($data, ',') + 1);
        $type = strtolower($type[1]);

        if (!in_array($type, ['jpg', 'jpeg', 'gif', 'png'])) {
            header("Location: profile.php?error=invalid_type");
            exit;
        }
        $data = base64_decode($data);
        if ($data === false) {
            header("Location: profile.php?error=decode_failed");
            exit;
        }

        $filename = 'avatar_' . $user['id'] . '_' . time() . '.' . $type;
        $target = __DIR__ . '/../../assets/uploads/avatars/' . $filename;
        
        if (file_put_contents($target, $data)) {
            $path = 'assets/uploads/avatars/' . $filename;
            $db->query("UPDATE users SET avatar = '$path' WHERE id = " . $user['id']);
            header("Location: profile.php?success=avatar_updated");
            exit;
        }
    }
}

// Handle Password Change
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['change_password'])) {
    require_csrf();
    
    $current_pass = $_POST['current_password'] ?? '';
    $new_pass     = $_POST['new_password'] ?? '';
    $confirm_pass = $_POST['confirm_password'] ?? '';

    // Verify current password
    if (!password_verify($current_pass, $user['password_hash'])) {
        header("Location: profile.php?tab=security&error=wrong_current");
        exit;
    }

    // Validate new password
    if ($new_pass !== $confirm_pass) {
        header("Location: profile.php?tab=security&error=mismatch");
        exit;
    }

    if (strlen($new_pass) < 8) {
        header("Location: profile.php?tab=security&error=too_short");
        exit;
    }

    $hashed = password_hash($new_pass, PASSWORD_DEFAULT);
    $stmt = $db->prepare("UPDATE users SET password_hash = ? WHERE id = ?");
    $stmt->bind_param("si", $hashed, $user['id']);
    
    if ($stmt->execute()) {
        log_action('PASSWORD_CHANGED', "User changed their password.");
        header("Location: profile.php?tab=security&success=password_updated");
    } else {
        header("Location: profile.php?tab=security&error=db_error");
    }
    $stmt->close();
    exit;
}



$page_title = 'SMS Profile';
$active_page = 'profile';
?>

<?php include __DIR__ . '/../../includes/header.php'; ?>

<!-- Include Cropper.js CDN -->
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/cropperjs/1.5.13/cropper.min.css">
<script src="https://cdnjs.cloudflare.com/ajax/libs/cropperjs/1.5.13/cropper.min.js"></script>

<style>
    /* 3-Column Layout Overrides */
    body { background-color: #f1f5f9; overflow: hidden; } /* Fixed outer body */
    
    .qs-main-wrapper {
        display: flex;
        height: 100vh;
        width: 100vw;
    }

    /* Layout Overrides for Unified Footer */
    .settings-main-layout {
        flex: 1;
        display: flex;
        flex-direction: column;
        height: 100vh;
        overflow: hidden;
        margin-left: 280px; /* Restore main sidebar space */
        transition: margin-left 0.18s ease; /* Match sidebar transition */
    }

    /* Handle sidebar toggle */
    .sidebar-collapsed .settings-main-layout {
        margin-left: 0;
    }

    .settings-columns-row {
        display: flex;
        flex: 1;
        overflow: hidden;
        padding-top: 0; /* Content starts below header */
    }
    
    /* Column 2: Settings Sidebar (Adjusted) */
    .settings-nav-sidebar {
        width: 320px;
        background: #f8fafc;
        border-right: 1px solid #e2e8f0;
        padding-top: 80px; /* Increased to clear header */
        flex-shrink: 0;
        height: 100%;
        overflow-y: auto;
    }
    
    .settings-header-title {
        padding: 0 32px 32px; /* Reset inner padding */
        font-size: 2.2rem;
        font-weight: 800;
        color: #1e293b;
    }

    .settings-nav-item {
        display: flex;
        align-items: center;
        gap: 16px;
        padding: 18px 32px;
        color: #64748b;
        text-decoration: none;
        transition: all 0.2s;
        border-left: 4px solid transparent; /* Changed to left for BCP parity */
        font-weight: 500;
    }
    .settings-nav-item i { font-size: 1.25rem; color: #94a3b8; }
    .settings-nav-item:hover { background: #f1f5f9; color: #1e293b; }
    .settings-nav-item.active {
        background: #eff6ff;
        color: #2563eb;
        border-left: 4px solid #2563eb; /* Institutional Left Border */
    }
    .settings-nav-item.active i { color: #2563eb; }
    .settings-nav-text-wrap { display: flex; flex-direction: column; }
    .nav-title { font-size: 0.95rem; font-weight: 700; }
    .nav-sub { font-size: 0.72rem; opacity: 0.8; margin-top: 2px; }

    /* Column 3: Main Content (Adjusted) */
    .settings-content-area {
        flex: 1;
        height: 100%;
        overflow-y: auto;
        padding-top: 80px; /* Match sidebar padding to clear header */
        background: #f1f5f9;
        display: flex;
        flex-direction: column;
    }

    .settings-content-wrapper {
        padding: 40px 60px;
        flex: 1;
    }

    /* White Card Styling (Converted to Transparent for BCP Look) */
    .profile-card {
        background: transparent; /* Remove white box */
        border-radius: 0;
        padding: 0; 
        width: 100%;
        max-width: 1200px;
        margin: 0;
        box-shadow: none;
    }

    .section-header { font-size: 1.5rem; font-weight: 800; color: #1e293b; margin-bottom: 8px; }
    .section-subtext { font-size: 0.85rem; color: #64748b; margin-bottom: 32px; }

    /* Avatar BCP Style */
    .avatar-upload-section { margin-bottom: 48px; }
    .avatar-label-top { font-weight: 700; color: #1e293b; font-size: 0.95rem; margin-bottom: 4px; }
    .avatar-sub-top { font-size: 0.8rem; color: #64748b; margin-bottom: 16px; }
    
    .avatar-circle-bcp {
        width: 120px; height: 120px;
        background: #71717a;
        border: none; /* Flat look like BCP */
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
        color: white;
        cursor: pointer;
        box-shadow: 0 0 0 1px #e2e8f0;
        overflow: hidden;
        transition: 0.3s;
    }
    .avatar-circle-bcp:hover { transform: scale(1.02); }
    .avatar-circle-bcp img { width: 100%; height: 100%; object-fit: cover; }
    
    /* Form Grid */
    .bcp-form-grid {
        display: grid;
        grid-template-columns: repeat(2, 1fr);
        gap: 32px;
    }
    
    .form-group-bcp { display: flex; flex-direction: column; }
    .form-label-bcp { font-size: 0.85rem; font-weight: 600; color: #475569; margin-bottom: 8px; }
    
    .input-wrap-bcp {
        position: relative;
        background: #ffffff;
        border: 1px solid #e2e8f0;
        border-radius: 6px;
        transition: 0.2s;
    }
    .input-wrap-bcp:focus-within { border-color: #6366f1; box-shadow: 0 0 0 3px rgba(99, 102, 241, 0.1); }
    
    .input-icon-bcp {
        position: absolute;
        left: 16px;
        top: 50%;
        transform: translateY(-50%);
        color: #94a3b8;
        font-size: 1.1rem;
    }
    
    .input-bcp {
        width: 100%;
        background: transparent;
        border: none;
        padding: 14px 16px 14px 48px;
        font-size: 0.95rem;
        color: #1e293b;
        font-weight: 500;
    }
    .input-bcp:disabled { color: #64748b; cursor: not-allowed; }
    .input-bcp:focus { outline: none; }

    /* Buttons */
    .btn-bcp-save {
        background: #72B944; color: white; border: none; padding: 12px 28px;
        border-radius: 50px; font-weight: 700; display: flex; align-items: center; gap: 10px; font-size: 0.95rem;
    }
    .btn-bcp-save:hover { background: #61a137; }
    .btn-bcp-cancel {
        background: #ffffff; color: #64748b; border: 1px solid #e2e8f0;
        padding: 12px 28px; border-radius: 50px; font-weight: 700; font-size: 0.95rem;
    }

    /* Stable Square Cropper (Facebook Dark Theme) */
    #cropModal .modal-content { border-radius: 12px; border: none; box-shadow: 0 12px 28px rgba(0,0,0,0.3); overflow: hidden; }
    #cropModal .modal-header { border-bottom: 1px solid #dddfe2; padding: 18px 24px; display: flex; align-items: center; justify-content: space-between; }
    #cropModal .modal-title { font-size: 1.15rem; font-weight: 700; color: #1c1e21; margin: 0 auto; }
    #cropModal .btn-close { background-color: #ebedf0; border-radius: 50%; padding: 8px; opacity: 1; }
    
    #cropModal .modal-body { padding: 0; background: #000; }
    
    .fb-crop-viewport {
        position: relative;
        width: 100%;
        height: 400px;
        background: #000;
        overflow: hidden;
    }

    /* Clean Library UI */
    .cropper-view-box { outline: 1px solid rgba(255, 255, 255, 0.5) !important; }
    .cropper-line, .cropper-point, .cropper-center { display: none !important; }
    .cropper-modal { opacity: 0.6 !important; background-color: #000 !important; }

    .fb-controls-area { padding: 20px 24px; background: #fff; }
    .fb-zoom-bar { display: flex; align-items: center; justify-content: center; gap: 15px; max-width: 320px; margin: 0 auto 10px; }
    .fb-zoom-bar i { font-size: 1.4rem; color: #65676b; }
    
    .fb-slider {
        flex: 1; height: 4px; background: #dddfe2; border-radius: 2px;
        outline: none; -webkit-appearance: none; cursor: pointer;
    }
    .fb-slider::-webkit-slider-thumb {
        -webkit-appearance: none; width: 22px; height: 22px; background: #1877f2;
        border-radius: 50%; border: 2px solid white; box-shadow: 0 1px 3px rgba(0,0,0,0.2);
    }

    .fb-privacy-note { display: flex; align-items: center; justify-content: center; gap: 8px; color: #65676b; font-size: 0.85rem; }
    .modal-footer-fb { padding: 12px 20px; display: flex; justify-content: flex-end; gap: 12px; border-top: 1px solid #dddfe2; background: #fff; }
    .btn-fb-cancel { background: none; border: none; color: #1877f2; font-weight: 600; padding: 8px 16px; font-size: 0.95rem; }
    .btn-fb-save { background: #72B944; color: white; border: none; font-weight: 700; padding: 10px 45px; border-radius: 6px; font-size: 0.95rem; }

    /* Hide the original image ghosting */
    #imageToCrop { opacity: 0 !important; }
    .cropper-hidden { display: none !important; }

    /* --- Responsive Profile Styles --- */
    @media (max-width: 1200px) {
        .settings-content-wrapper { padding: 30px 40px; }
    }

    @media (max-width: 991.98px) {
        body { overflow: auto; height: auto; }
        .qs-main-wrapper { height: auto; display: block; }
        .settings-main-layout { margin-left: 0 !important; height: auto; overflow: visible; }
        .settings-columns-row { flex-direction: column; height: auto; overflow: visible; }
        .settings-nav-sidebar { width: 100%; height: auto; padding-top: 20px; border-right: none; border-bottom: 1px solid #e2e8f0; }
        .settings-header-title { padding: 0 20px 20px; font-size: 1.8rem; }
        .settings-nav-item { padding: 15px 20px; }
        .settings-content-area { height: auto; overflow: visible; padding-top: 20px; }
        .settings-content-wrapper { padding: 20px; }
        .bcp-form-grid { grid-template-columns: 1fr; gap: 20px; }
        .profile-card { padding: 0; }
        .section-header { font-size: 1.3rem; }
    }

    @media (max-width: 576px) {
        .settings-header-title { font-size: 1.5rem; }
        .nav-title { font-size: 0.85rem; }
        .nav-sub { display: none; }
    }
</style>

<div class="qs-main-wrapper">
    <!-- COLUMN 1: MAIN SIDEBAR -->
    <?php include __DIR__ . '/../../includes/sidebar.php'; ?>

    <!-- UNIFIED CONTENT AREA (COL 2 + COL 3 + FOOTER) -->
    <div class="settings-main-layout">
        <div class="settings-columns-row">
            <!-- COLUMN 2: SETTINGS NAVIGATION -->
            <aside class="settings-nav-sidebar">
                <h2 class="settings-header-title">Settings</h2>
                <nav>
                    <a href="?tab=account" class="settings-nav-item <?= !isset($_GET['tab']) || $_GET['tab'] === 'account' ? 'active' : '' ?>">
                        <i class="bi bi-person"></i>
                        <div class="settings-nav-text-wrap">
                            <span class="nav-title">Account</span>
                            <span class="nav-sub">Manage your public profile and private information</span>
                        </div>
                    </a>
                    <a href="?tab=security" class="settings-nav-item <?= isset($_GET['tab']) && $_GET['tab'] === 'security' ? 'active' : '' ?>">
                        <i class="bi bi-lock"></i>
                        <div class="settings-nav-text-wrap">
                            <span class="nav-title">Security</span>
                            <span class="nav-sub">Manage your password</span>
                        </div>
                    </a>
                </nav>
            </aside>

            <!-- COLUMN 3: MAIN CONTENT AREA -->
            <main class="settings-content-area">
                <div class="settings-content-wrapper">
                    <div class="profile-card qs-animate-in">
                        <?php if (!isset($_GET['tab']) || $_GET['tab'] === 'account'): ?>
                            <!-- ACCOUNT SECTION -->
                            <h3 class="section-header">Account</h3>
                            
                            <!-- Avatar Upload -->
                            <div class="avatar-upload-section">
                                <div class="avatar-label-top">Avatar</div>
                                <div class="avatar-sub-top">Display Picture</div>
                                
                                <div class="avatar-circle-bcp" id="avatarTrigger">
                                    <?php if ($user['avatar']): ?>
                                        <img src="<?= BASE_URL ?>/<?= $user['avatar'] ?>" alt="Profile">
                                    <?php else: ?>
                                        <i class="bi bi-camera fs-3"></i>
                                    <?php endif; ?>
                                </div>
                                <input type="file" id="avatarInput" class="d-none" accept="image/*">
                            </div>

                            <!-- Profile Info -->
                            <div class="mb-2">
                                <h4 class="section-header" style="font-size: 1.25rem;">Profile</h4>
                                <p class="section-subtext">Following information is publicly displayed.</p>
                            </div>

                            <div class="bcp-form-grid mb-5">
                                <div class="form-group-bcp">
                                    <label class="form-label-bcp">First Name</label>
                                    <div class="input-wrap-bcp">
                                        <i class="bi bi-person input-icon-bcp"></i>
                                        <input type="text" class="input-bcp" value="<?= explode(' ', $user['full_name'])[0] ?>" disabled>
                                    </div>
                                </div>
                                <div class="form-group-bcp">
                                    <label class="form-label-bcp">Middle Name</label>
                                    <div class="input-wrap-bcp">
                                        <i class="bi bi-person input-icon-bcp"></i>
                                        <input type="text" class="input-bcp" value="<?= $user['middle_name'] ?? 'Estiler' ?>" disabled>
                                    </div>
                                </div>
                                <div class="form-group-bcp">
                                    <label class="form-label-bcp">Last Name</label>
                                    <div class="input-wrap-bcp">
                                        <i class="bi bi-person input-icon-bcp"></i>
                                        <?php $names_parts = explode(' ', $user['full_name']); ?>
                                        <input type="text" class="input-bcp" value="<?= end($names_parts) ?>" disabled>
                                    </div>
                                </div>
                                <div class="form-group-bcp">
                                    <label class="form-label-bcp">Suffix</label>
                                    <div class="input-wrap-bcp">
                                        <i class="bi bi-person input-icon-bcp"></i>
                                        <input type="text" class="input-bcp" value="" disabled>
                                    </div>
                                </div>
                                <div class="form-group-bcp">
                                    <label class="form-label-bcp">Username</label>
                                    <div class="input-wrap-bcp">
                                        <i class="bi bi-person-vcard input-icon-bcp"></i>
                                        <input type="text" class="input-bcp" value="<?= $user['student_id'] ?>" disabled>
                                    </div>
                                </div>
                                <div class="form-group-bcp">
                                    <label class="form-label-bcp">Role/s</label>
                                    <div class="input-wrap-bcp">
                                        <i class="bi bi-shield-check input-icon-bcp"></i>
                                        <input type="text" class="input-bcp" value="<?= ucfirst($user['role']) ?>" disabled>
                                    </div>
                                </div>
                                <div class="form-group-bcp">
                                    <label class="form-label-bcp">Employee ID</label>
                                    <div class="input-wrap-bcp">
                                        <i class="bi bi-bookmark input-icon-bcp"></i>
                                        <input type="text" class="input-bcp" value="<?= $user['student_id'] ?>" disabled>
                                    </div>
                                </div>
                                <div class="form-group-bcp">
                                    <label class="form-label-bcp">Company / School</label>
                                    <div class="input-wrap-bcp">
                                        <i class="bi bi-building input-icon-bcp"></i>
                                        <input type="text" class="input-bcp" value="Bestlink College of the Philippines" disabled>
                                    </div>
                                </div>
                            </div>

                            <!-- Personal Info -->
                            <div class="mt-5 pt-5 border-top">
                                <h4 class="section-header" style="font-size: 1.25rem;">Personal Information</h4>
                                <p class="section-subtext">Communication details in case we want to connect with you.</p>
                            </div>

                            <div class="bcp-form-grid">
                                <div class="form-group-bcp">
                                    <label class="form-label-bcp">Email</label>
                                    <div class="input-wrap-bcp">
                                        <i class="bi bi-envelope input-icon-bcp"></i>
                                        <input type="text" class="input-bcp" value="<?= $user['email'] ?>" disabled>
                                    </div>
                                </div>
                                <div class="form-group-bcp">
                                    <label class="form-label-bcp">Personal Email</label>
                                    <div class="input-wrap-bcp">
                                        <i class="bi bi-envelope input-icon-bcp"></i>
                                        <input type="text" class="input-bcp" value="<?= $user['personal_email'] ?>" disabled>
                                    </div>
                                </div>
                                <div class="form-group-bcp">
                                    <label class="form-label-bcp">Contact Number</label>
                                    <div class="input-wrap-bcp">
                                        <i class="bi bi-phone input-icon-bcp"></i>
                                        <input type="text" class="input-bcp" value="<?= $user['contact_number'] ?>" disabled>
                                    </div>
                                </div>
                                <div class="form-group-bcp">
                                    <label class="form-label-bcp">Civil Status</label>
                                    <div class="input-wrap-bcp">
                                        <i class="bi bi-person input-icon-bcp"></i>
                                        <input type="text" class="input-bcp" value="<?= $user['civil_status'] ?? 'Single' ?>" disabled>
                                    </div>
                                </div>
                                <div class="form-group-bcp">
                                    <label class="form-label-bcp">Birthday</label>
                                    <div class="input-wrap-bcp">
                                        <i class="bi bi-calendar3 input-icon-bcp"></i>
                                        <input type="text" class="input-bcp" value="<?= date('n/j/Y', strtotime($user['birthday'] ?? '2003-08-12')) ?>" disabled>
                                    </div>
                                </div>
                            </div>

                        <?php elseif ($_GET['tab'] === 'security'): ?>
                            <!-- SECURITY SECTION -->
                            <h3 class="section-header">Security</h3>
                            <div class="mb-4">
                                <h4 class="section-header" style="font-size: 1.25rem;">Change your password</h4>
                                <p class="section-subtext">All fields with * are required.</p>
                            </div>

                            <?php if (isset($_GET['error'])): ?>
                                <div class="alert alert-danger py-2 px-3 small mb-4" style="border-radius:10px;">
                                    <?php
                                        if($_GET['error'] === 'wrong_current') echo "Incorrect current password.";
                                        elseif($_GET['error'] === 'mismatch') echo "New passwords do not match.";
                                        elseif($_GET['error'] === 'too_short') echo "Password must be at least 8 characters.";
                                        else echo "An error occurred. Please try again.";
                                    ?>
                                </div>
                            <?php endif; ?>

                            <?php if (isset($_GET['success'])): ?>
                                <div class="alert alert-success py-2 px-3 small mb-4" style="border-radius:10px;">
                                    Password updated successfully.
                                </div>
                            <?php endif; ?>

                            <form method="POST" action="profile.php?tab=security">
                                <input type="hidden" name="csrf_token" value="<?= csrf_token() ?>">
                                <div class="row g-4" style="max-width: 600px;">
                                <div class="col-12 mb-3">
                                    <label class="form-label-bcp">Current password *</label>
                                    <div class="input-wrap-bcp">
                                        <i class="bi bi-key input-icon-bcp"></i>
                                        <input type="password" name="current_password" class="input-bcp" placeholder="" required>
                                    </div>
                                </div>
                                <div class="col-12 mb-3">
                                    <label class="form-label-bcp">New password *</label>
                                    <div class="input-wrap-bcp">
                                        <i class="bi bi-key input-icon-bcp"></i>
                                        <input type="password" name="new_password" class="input-bcp" placeholder="" required>
                                    </div>
                                    <p class="password-req-box">Minimum 8 characters, at least one number, at least one lower case letter, at least one upper case letter, at least one non-alphanumeric character</p>
                                </div>
                                <div class="col-12 mb-3">
                                    <label class="form-label-bcp">Confirm password *</label>
                                    <div class="input-wrap-bcp">
                                        <i class="bi bi-key input-icon-bcp"></i>
                                        <input type="password" name="confirm_password" class="input-bcp" placeholder="" required>
                                    </div>
                                </div>
                            </div>

                            <div class="d-flex justify-content-end gap-3 mt-5 pt-4 border-top">
                                <button type="button" onclick="window.location.href='profile.php'" class="btn btn-bcp-cancel">Cancel</button>
                                <button type="submit" name="change_password" class="btn btn-bcp-save">
                                    <i class="bi bi-save"></i> Save
                                </button>
                            </div>
                        </form>
                        <?php endif; ?>
                    </div>
                </div>
            </main>
        </div>
        <!-- GLOBAL FOOTER (Unified for Col 2 & 3) -->
        <?php include __DIR__ . '/../../includes/footer.php'; ?>
    </div>
</div>

<!-- CROP MODAL (FACEBOOK STYLE) -->
<div class="modal fade" id="cropModal" tabindex="-1" aria-hidden="true" data-bs-backdrop="static">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Choose profile picture</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <div class="fb-crop-viewport">
                    <img id="imageToCrop" src="" style="display: block; max-width: 100%;">
                </div>
                <div class="fb-controls-area">
                    <div class="fb-zoom-bar">
                        <i class="bi bi-dash"></i>
                        <input type="range" class="fb-slider" id="zoomSlider" min="0.1" max="3" step="0.01" value="1">
                        <i class="bi bi-plus"></i>
                    </div>
                    <div class="fb-privacy-note"><i class="bi bi-globe-americas"></i> Your profile picture is public.</div>
                </div>
            </div>
            <div class="modal-footer-fb">
                <button type="button" class="btn-fb-cancel" data-bs-dismiss="modal">Cancel</button>
                <button type="button" class="btn-fb-save" id="saveCropBtn">Save</button>
            </div>
        </div>
    </div>
</div>

<form id="croppedForm" action="profile.php" method="POST" class="d-none">
    <input type="hidden" name="cropped_avatar" id="croppedAvatarInput">
</form>

<script>
document.addEventListener('DOMContentLoaded', function() {
    let cropper;
    const avatarInput = document.getElementById('avatarInput');
    const imageToCrop = document.getElementById('imageToCrop');
    const zoomSlider = document.getElementById('zoomSlider');
    const cropModal = new bootstrap.Modal(document.getElementById('cropModal'));

    document.getElementById('avatarTrigger').onclick = () => avatarInput.click();

    avatarInput.onchange = (e) => {
        if (e.target.files && e.target.files[0]) {
            const reader = new FileReader();
            reader.onload = (ev) => {
                imageToCrop.src = ev.target.result;
                cropModal.show();
            };
            reader.readAsDataURL(e.target.files[0]);
        }
    };

    document.getElementById('cropModal').addEventListener('shown.bs.modal', () => {
        if (cropper) cropper.destroy();
        cropper = new Cropper(imageToCrop, {
            aspectRatio: 1, viewMode: 1, dragMode: 'move', autoCropArea: 1,
            guides: false, center: false, highlight: false, cropBoxMovable: false, cropBoxResizable: false,
            toggleDragModeOnDblclick: false, background: false,
            ready: function() { zoomSlider.value = 1; }
        });
    });

    zoomSlider.oninput = (e) => { if (cropper) cropper.zoomTo(e.target.value); };

    document.getElementById('saveCropBtn').onclick = () => {
        if (!cropper) return;
        const canvas = cropper.getCroppedCanvas({ width: 400, height: 400 });
        document.getElementById('croppedAvatarInput').value = canvas.toDataURL('image/jpeg', 0.9);
        document.getElementById('croppedForm').submit();
    };
});
</script>

</body>
</html>