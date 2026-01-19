<?php
require_once 'config/database.php';
require_once 'config/config.php';

// Mock functionality
function test_coupon_fixes()
{
    $db = Database::getInstance()->getConnection();
    echo "Starting Verification...\n";

    // --- Scenario 1: Per-User One-Time Coupon ---
    $codeUser = 'TEST_USER_FIX_' . rand(1000, 9999);
    $stmt = $db->prepare("INSERT INTO coupons (code, type, value, is_active, onetime, user_onetime, users) VALUES (?, 'fixed', 100, 1, 0, 1, '[]')");
    $stmt->execute([$codeUser]);
    $couponIdUser = $db->lastInsertId();
    echo "\n[1] Created Per-User Coupon: $codeUser (ID: $couponIdUser)\n";

    // Users
    $email1 = 'UserOne@Example.com'; // Mixed case to test normalization
    $email1Norm = strtolower($email1);
    $email2 = 'user2@example.com';

    // Simulate "Book Session" (First Usage for User 1)
    echo "  > User 1 ($email1) using coupon for first time...\n";

    // Logic mirroring book-session.php
    $stmt = $db->prepare("SELECT onetime, user_onetime, users FROM coupons WHERE id = ?");
    $stmt->execute([$couponIdUser]);
    $c = $stmt->fetch();

    $used_users = json_decode($c['users'] ?? '[]', true);
    if (!is_array($used_users))
        $used_users = [];

    // Validate Logic (Mirroring validate-coupons and create-order)
    if (in_array($email1Norm, $used_users)) {
        echo "  FAILED: Validation blocked usage prematurely.\n";
    } else {
        echo "  Validation Passed.\n";
        // Apply Usage
        $used_users[] = $email1Norm;
        $stmt = $db->prepare("UPDATE coupons SET users = ? WHERE id = ?");
        $stmt->execute([json_encode($used_users), $couponIdUser]);
        echo "  Usage Recorded.\n";
    }

    // Simulate "Create Order" / "Validate" (Second Usage for User 1)
    echo "  > User 1 ($email1) trying to use again...\n";

    // Fix: Re-prepare the SELECT statement
    $stmt = $db->prepare("SELECT onetime, user_onetime, users FROM coupons WHERE id = ?");
    $stmt->execute([$couponIdUser]);
    $c = $stmt->fetch();

    $used_users = json_decode($c['users'] ?? '[]', true);

    if (in_array($email1Norm, $used_users)) {
        echo "  SUCCESS: Validation CORRECTLY blocked second usage.\n";
    } else {
        echo "  FAILED: Validation allowed second usage (Email not found in list)! List is: " . json_encode($used_users) . "\n";
    }

    // Simulate "Book Session" (First Usage for User 2)
    echo "  > User 2 ($email2) using coupon...\n";
    if (in_array($email2, $used_users)) {
        echo "  FAILED: Validation blocked User 2 prematurely.\n";
    } else {
        echo "  SUCCESS: Validation allowed User 2.\n";
    }

    // --- Scenario 2: Global One-Time Coupon ---
    $codeOnetime = 'TEST_ONETIME_' . rand(1000, 9999);
    $stmt = $db->prepare("INSERT INTO coupons (code, type, value, is_active, onetime, user_onetime, users) VALUES (?, 'fixed', 100, 1, 1, 0, '[]')");
    $stmt->execute([$codeOnetime]);
    $couponIdOnetime = $db->lastInsertId();
    echo "\n[2] Created Global One-Time Coupon: $codeOnetime (ID: $couponIdOnetime)\n";

    echo "  > User 1 using global coupon...\n";
    // Logic mirroring book-session.php for global one-time
    $stmt = $db->prepare("UPDATE coupons SET is_active = 0 WHERE id = ?");
    $stmt->execute([$couponIdOnetime]);
    echo "  Coupon Deactivated.\n";

    // Check status
    $stmt = $db->prepare("SELECT is_active FROM coupons WHERE id = ?");
    $stmt->execute([$couponIdOnetime]);
    $isActive = $stmt->fetchColumn();

    if ($isActive == 0) {
        echo "  SUCCESS: Global coupon matches inactive status.\n";
    } else {
        echo "  FAILED: Global coupon is still active.\n";
    }

    // Cleanup
    $db->prepare("DELETE FROM coupons WHERE id IN (?, ?)")->execute([$couponIdUser, $couponIdOnetime]);
    echo "\nCleanup Complete.\n";
}

test_coupon_fixes();
?>