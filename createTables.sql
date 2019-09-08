-- CreateCardsTable:
CREATE TABLE `cards` (
    `id` int unsigned NOT NULL AUTO_INCREMENT PRIMARY KEY,
    `user_id` int unsigned NOT NULL,
    `registration_id` varchar(255) NOT NULL,
    `bin` varchar(255) NOT NULL,
    `last_4_digits` varchar(255) NOT NULL,
    `holder` varchar(255) NOT NULL,
    `expiry_month` varchar(255) NOT NULL,
    `expiry_year` varchar(255) NOT NULL,
    `created_at` timestamp NULL,
    `updated_at` timestamp NULL)
DEFAULT character
SET utf8mb4 COLLATE 'utf8mb4_unicode_ci';

-- CreateCardsTable:

ALTER TABLE `cards`
    ADD CONSTRAINT `cards_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

-- CreatePaymentsTable:

CREATE TABLE `hyperpay_payments` (
    `id` int unsigned NOT NULL AUTO_INCREMENT PRIMARY KEY,
    `customer_id` int unsigned NOT NULL,
    `payment_id` varchar(255) NOT NULL,
    `type` varchar(255) NOT NULL,
    `amount` varchar(255) NOT NULL,
    `currency` varchar(255) NOT NULL,
    `status` varchar(255) NOT NULL,
    `created_at` timestamp NULL,
    `updated_at` timestamp NULL)
DEFAULT character
SET utf8mb4 COLLATE 'utf8mb4_unicode_ci';

-- CreatePaymentsTable:

ALTER TABLE `payments`
    ADD CONSTRAINT `payments_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

