-- SQL Script to Add USD Balance Fields to Users Table
-- Run this SQL directly in your database if migrations fail

-- Add USD balance fields to users table
ALTER TABLE `users` 
ADD COLUMN `usd_balance` DECIMAL(28,8) DEFAULT 0.00000000 AFTER `id`,
ADD COLUMN `usd_balance_in_order` DECIMAL(28,8) DEFAULT 0.00000000 AFTER `usd_balance`;

-- Create currency conversions table
CREATE TABLE IF NOT EXISTS `currency_conversions` (
  `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
  `user_id` int(11) DEFAULT 0,
  `currency_id` int(11) DEFAULT 0,
  `currency_symbol` varchar(10) NOT NULL,
  `conversion_type` enum('deposit','withdrawal','trade') DEFAULT 'deposit',
  `crypto_amount` decimal(28,8) DEFAULT 0.00000000,
  `usd_amount` decimal(28,8) DEFAULT 0.00000000,
  `conversion_rate` decimal(28,8) DEFAULT 0.00000000,
  `trx` varchar(40) DEFAULT NULL,
  `details` text DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `user_id` (`user_id`),
  KEY `currency_id` (`currency_id`),
  KEY `trx` (`trx`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Create user portfolios table for tracking crypto holdings
CREATE TABLE IF NOT EXISTS `user_portfolios` (
  `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
  `user_id` int(11) DEFAULT 0,
  `currency_id` int(11) DEFAULT 0,
  `amount` decimal(28,8) DEFAULT 0.00000000,
  `average_buy_price` decimal(28,8) DEFAULT 0.00000000,
  `total_invested_usd` decimal(28,8) DEFAULT 0.00000000,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `user_currency` (`user_id`, `currency_id`),
  KEY `user_id` (`user_id`),
  KEY `currency_id` (`currency_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Add USD and crypto fields to withdrawals table for conversion tracking
ALTER TABLE `withdrawals` 
ADD COLUMN IF NOT EXISTS `usd_amount` DECIMAL(28,8) DEFAULT 0.00000000 COMMENT 'Amount in USD deducted from user balance' AFTER `amount`,
ADD COLUMN IF NOT EXISTS `crypto_amount` DECIMAL(28,8) DEFAULT 0.00000000 COMMENT 'Actual crypto amount to be sent' AFTER `usd_amount`,
ADD COLUMN IF NOT EXISTS `conversion_rate` DECIMAL(28,8) DEFAULT 0.00000000 COMMENT 'USD to crypto rate at withdrawal time' AFTER `crypto_amount`;

-- Verify the changes
SELECT 'USD Balance fields, portfolio table, and withdrawal fields added successfully!' AS status;

