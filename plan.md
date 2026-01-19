# Coupon Usage and Deactivation Plan

## Objective

This document outlines the investigation into whether one-time use coupons are deactivated upon a booked session and if the user's ID is associated with the used coupon.

## Analysis

An analysis of the existing codebase was performed, focusing on the following files:

- `config/database.php`
- `api/validate-coupons.php`
- `api/book-session.php`

### Findings

The investigation revealed that the functionality for handling one-time use coupons is already implemented in `api/book-session.php`.

1.  **One-Time Coupon Deactivation:**
    - When a session is booked with a coupon that has the `onetime` flag set to true, the system updates the `coupons` table and sets the `is_active` flag to `0`. This effectively deactivates the coupon, preventing it from being used again.

2.  **User-Specific One-Time Coupons:**
    - For coupons with the `user_onetime` flag set to true, the system records the user's email in the `users` column of the `coupons` table.
    - The `users` column stores a JSON array of email addresses that have used the coupon.
    - Before a coupon is applied, the system checks if the user's email is already in this list. If it is, the coupon is considered invalid for that user.
    - After a successful booking, the user's email is added to the list, preventing them from using the same coupon again.

## Conclusion

The current implementation correctly handles the deactivation of one-time use coupons and tracks usage for user-specific one-time coupons. No further code changes are required to meet the initial request.
