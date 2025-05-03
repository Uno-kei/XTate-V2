<?php
require_once '../inc/db.php';
require_once '../inc/functions.php';
require_once '../inc/auth.php';

// Start session if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Set header to return JSON
header('Content-Type: application/json');

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    echo json_encode([
        'success' => false,
        'message' => 'Not logged in'
    ]);
    exit;
}

$userId = $_SESSION['user_id'];
$action = $_POST['action'] ?? '';

switch ($action) {
    case 'update_profile':
        $fullName = sanitizeInput($_POST['full_name'] ?? '');
        $phone = sanitizeInput($_POST['phone'] ?? '');

        if (empty($fullName)) {
            echo json_encode([
                'success' => false,
                'message' => 'Full name is required'
            ]);
            exit;
        }

        try {
            $sql = "UPDATE users SET full_name = ?, phone = ?, updated_at = NOW() WHERE id = ?";
            $result = updateData($sql, "ssi", [$fullName, $phone, $userId]);

            if ($result) {
                $_SESSION['user_name'] = $fullName;
                echo json_encode([
                    'success' => true,
                    'message' => 'Profile updated successfully'
                ]);
            } else {
                echo json_encode([
                    'success' => false,
                    'message' => 'Failed to update profile'
                ]);
            }
        } catch (Exception $e) {
            error_log("Profile update error: " . $e->getMessage());
            echo json_encode([
                'success' => false,
                'message' => 'An error occurred while updating profile'
            ]);
        }
        break;

    case 'change_password':
        $currentPassword = $_POST['current_password'] ?? '';
        $newPassword = $_POST['new_password'] ?? '';
        $confirmPassword = $_POST['confirm_new_password'] ?? '';

        if (empty($currentPassword) || empty($newPassword) || empty($confirmPassword)) {
            echo json_encode([
                'success' => false,
                'message' => 'All password fields are required'
            ]);
            exit;
        }

        if ($newPassword !== $confirmPassword) {
            echo json_encode([
                'success' => false,
                'message' => 'New password and confirmation do not match'
            ]);
            exit;
        }

        try {
            $sql = "SELECT password FROM users WHERE id = ?";
            $user = fetchOne($sql, "i", [$userId]);

            if (!$user || !password_verify($currentPassword, $user['password'])) {
                echo json_encode([
                    'success' => false,
                    'message' => 'Current password is incorrect'
                ]);
                exit;
            }

            $hashedPassword = password_hash($newPassword, PASSWORD_DEFAULT);
            $sql = "UPDATE users SET password = ?, updated_at = NOW() WHERE id = ?";
            $result = updateData($sql, "si", [$hashedPassword, $userId]);

            if ($result) {
                echo json_encode([
                    'success' => true,
                    'message' => 'Password changed successfully'
                ]);
            } else {
                echo json_encode([
                    'success' => false,
                    'message' => 'Failed to change password'
                ]);
            }
        } catch (Exception $e) {
            error_log("Password change error: " . $e->getMessage());
            echo json_encode([
                'success' => false,
                'message' => 'An error occurred while changing password'
            ]);
        }
        break;

    default:
        echo json_encode([
            'success' => false,
            'message' => 'Invalid action'
        ]);
        break;
}

?>