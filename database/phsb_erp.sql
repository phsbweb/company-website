-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Mar 30, 2026 at 09:45 AM
-- Server version: 10.4.32-MariaDB
-- PHP Version: 8.2.12

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `phsb_erp`
--

-- --------------------------------------------------------

--
-- Table structure for table `admin_password_resets`
--

CREATE TABLE `admin_password_resets` (
  `id` int(11) NOT NULL,
  `username` varchar(50) NOT NULL,
  `otp_code` varchar(10) NOT NULL,
  `otp_expires_at` datetime NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;

-- --------------------------------------------------------

--
-- Table structure for table `admin_users`
--

CREATE TABLE `admin_users` (
  `id` int(11) NOT NULL,
  `username` varchar(50) NOT NULL,
  `password` varchar(255) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;

--
-- Dumping data for table `admin_users`
--

INSERT INTO `admin_users` (`id`, `username`, `password`, `created_at`) VALUES
(1, 'phsb_adm', '$2y$10$MTY5MzQ3OTEzNDY1ZjRjN.I8f3y7G9F1u6K1j6S.T5r4V3U2W1X0Y9Z8A', '2026-03-02 05:58:26');

-- --------------------------------------------------------

--
-- Table structure for table `attendance`
--

CREATE TABLE `attendance` (
  `id` int(11) NOT NULL,
  `employee_id` int(11) NOT NULL,
  `check_in` timestamp NULL DEFAULT NULL,
  `check_out` timestamp NULL DEFAULT NULL,
  `status` enum('checked_in','checked_out') NOT NULL DEFAULT 'checked_out',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `location_in` varchar(255) DEFAULT NULL,
  `location_out` varchar(255) DEFAULT NULL,
  `is_late` tinyint(1) DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;

--
-- Dumping data for table `attendance`
--

INSERT INTO `attendance` (`id`, `employee_id`, `check_in`, `check_out`, `status`, `created_at`, `location_in`, `location_out`, `is_late`) VALUES
(1, 1, '2026-01-29 02:05:21', '2026-01-29 02:05:24', 'checked_out', '2026-01-29 09:05:21', NULL, NULL, 0),
(2, 1, '2026-01-29 02:05:44', '2026-01-29 02:06:10', 'checked_out', '2026-01-29 09:05:44', NULL, NULL, 0),
(3, 2, '2026-01-29 02:06:04', '2026-01-29 02:06:20', 'checked_out', '2026-01-29 09:06:04', NULL, NULL, 0),
(4, 1, '2026-01-29 09:15:59', '2026-01-29 09:16:28', 'checked_out', '2026-01-29 09:15:59', NULL, NULL, 0),
(5, 1, '2026-01-29 09:19:36', '2026-01-29 09:19:40', 'checked_out', '2026-01-29 09:19:36', NULL, NULL, 0),
(6, 1, '2026-01-29 09:32:49', '2026-01-29 09:48:09', 'checked_out', '2026-01-29 09:32:49', NULL, NULL, 0),
(7, 1, '2026-01-29 10:12:56', '2026-01-29 10:13:03', 'checked_out', '2026-01-29 10:12:56', NULL, NULL, 0),
(8, 2, '2026-01-29 10:25:30', '2026-01-29 10:26:47', 'checked_out', '2026-01-29 10:25:30', NULL, NULL, 0),
(9, 2, '2026-01-29 10:26:56', '2026-01-29 10:27:39', 'checked_out', '2026-01-29 10:26:56', NULL, NULL, 0),
(10, 2, '2026-01-29 10:28:17', '2026-01-29 10:29:26', 'checked_out', '2026-01-29 10:28:17', NULL, NULL, 0),
(11, 1, '2026-01-29 10:29:36', '2026-01-29 10:29:40', 'checked_out', '2026-01-29 10:29:36', NULL, NULL, 0),
(12, 2, '2026-02-12 01:43:45', '2026-02-12 01:44:11', 'checked_out', '2026-02-12 01:43:45', 'Location denied', '2.9558638164421254, 101.65101815720159', 0),
(13, 2, '2026-02-12 01:48:46', '2026-02-12 01:49:01', 'checked_out', '2026-02-12 01:48:46', 'Location access denied', 'Pusat Industri Sinar Meranti, Kampung Pulau Meranti, Sepang, Selangor, 62300, Malaysia', 0),
(14, 2, '2026-02-12 01:58:31', '2026-02-12 03:58:00', 'checked_out', '2026-02-12 01:58:31', 'Pusat Industri Sinar Meranti, Kampung Pulau Meranti, Sepang, Selangor, 62300, Malaysia', 'Pusat Industri Sinar Meranti, Kampung Pulau Meranti, Sepang, Selangor, 62300, Malaysia', 0),
(15, 1, '2026-02-26 00:51:50', '2026-02-25 16:00:00', 'checked_out', '2026-02-26 00:51:50', 'Pusat Industri Sinar Meranti, Kampung Pulau Meranti, Sepang, Selangor, 62300, Malaysia', 'Pusat Industri Sinar Meranti, Kampung Pulau Meranti, Sepang, Selangor, 62300, Malaysia', 0),
(16, 1, '2026-02-27 01:09:35', '2026-02-27 09:15:20', 'checked_out', '2026-02-27 01:09:35', 'Pusat Industri Sinar Meranti, Kampung Pulau Meranti, Sepang, Selangor, 62300, Malaysia', 'Pusat Industri Sinar Meranti, Kampung Pulau Meranti, Sepang, Selangor, 62300, Malaysia', 0),
(17, 1, '2026-03-02 05:59:58', '2026-03-03 00:50:44', 'checked_out', '2026-03-02 05:59:58', 'Pusat Industri Sinar Meranti, Kampung Pulau Meranti, Sepang, Selangor, 62300, Malaysia', 'Pusat Industri Sinar Meranti, Kampung Pulau Meranti, Sepang, Selangor, 62300, Malaysia', 0),
(18, 1, '2026-03-03 04:01:25', '2026-03-03 09:30:00', 'checked_out', '2026-03-03 04:01:25', 'Pusat Industri Sinar Meranti, Kampung Pulau Meranti, Sepang, Selangor, 62300, Malaysia', 'Auto-Checkout (System)', 0),
(19, 1, '2026-03-04 04:20:43', '2026-03-04 09:30:00', 'checked_out', '2026-03-04 04:20:43', 'Pusat Industri Sinar Meranti, Kampung Pulau Meranti, Sepang, Selangor, 62300, Malaysia', 'Auto-Checkout (System)', 0),
(20, 1, '2026-03-05 05:44:51', '2026-03-05 09:30:00', 'checked_out', '2026-03-05 05:44:51', 'Pusat Industri Sinar Meranti, Kampung Pulau Meranti, Sepang, Selangor, 62300, Malaysia', 'Auto-Checkout (System)', 0),
(21, 1, '2026-03-06 00:45:07', '2026-03-06 09:09:46', 'checked_out', '2026-03-06 00:45:07', 'Pusat Industri Sinar Meranti, Kampung Pulau Meranti, Sepang, Selangor, 62300, Malaysia', 'Pusat Industri Sinar Meranti, Kampung Pulau Meranti, Sepang, Selangor, 62300, Malaysia', 0),
(22, 1, '2026-03-09 01:41:57', '2026-03-09 01:45:26', 'checked_out', '2026-03-09 01:41:57', 'Pusat Industri Sinar Meranti, Kampung Pulau Meranti, Sepang, Selangor, 62300, Malaysia', 'Pusat Industri Sinar Meranti, Kampung Pulau Meranti, Sepang, Selangor, 62300, Malaysia', 0),
(23, 1, '2026-03-11 02:16:58', '2026-03-11 09:30:00', 'checked_out', '2026-03-11 02:16:58', 'Pusat Industri Sinar Meranti, Kampung Pulau Meranti, Sepang, Selangor, 62300, Malaysia', 'Auto-Checkout (System)', 0),
(24, 1, '2026-03-16 02:47:08', '2026-03-16 09:30:00', 'checked_out', '2026-03-16 02:47:08', 'Pusat Industri Sinar Meranti, Kampung Pulau Meranti, Sepang, Selangor, 62300, Malaysia', 'Auto-Checkout (System)', 1),
(25, 1, '2026-03-27 01:11:24', '2026-03-27 09:30:00', 'checked_out', '2026-03-27 01:11:24', 'Pusat Industri Sinar Meranti, Kampung Pulau Meranti, Sepang, Selangor, 62300, Malaysia', 'Auto-Checkout (System)', 1);

-- --------------------------------------------------------

--
-- Table structure for table `departments`
--

CREATE TABLE `departments` (
  `id` int(11) NOT NULL,
  `name` varchar(100) NOT NULL,
  `created_at` datetime DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `departments`
--

INSERT INTO `departments` (`id`, `name`, `created_at`) VALUES
(1, 'Accounting', '2026-03-05 09:53:55'),
(2, 'HR', '2026-03-05 09:53:55');

-- --------------------------------------------------------

--
-- Table structure for table `device_tokens`
--

CREATE TABLE `device_tokens` (
  `id` int(11) NOT NULL,
  `employee_id` int(11) NOT NULL,
  `token` varchar(64) NOT NULL,
  `user_agent` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;

-- --------------------------------------------------------

--
-- Table structure for table `employees`
--

CREATE TABLE `employees` (
  `id` int(11) NOT NULL,
  `username` varchar(50) NOT NULL,
  `password` varchar(255) NOT NULL,
  `full_name` varchar(100) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `working_shift` enum('8-5','830-530') DEFAULT '8-5',
  `department_id` int(11) DEFAULT NULL,
  `annual_leave_entitlement` int(11) DEFAULT 18,
  `medical_leave_entitlement` int(11) DEFAULT 14
) ENGINE=InnoDB DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;

--
-- Dumping data for table `employees`
--

INSERT INTO `employees` (`id`, `username`, `password`, `full_name`, `created_at`, `working_shift`, `department_id`, `annual_leave_entitlement`, `medical_leave_entitlement`) VALUES
(1, 'admin', '$2y$10$gojZC56gwgcaRqMjNabb8OfUD7suQycRn/bg49vxUZO2/YAJQlwF6', 'Test Admin', '2026-01-29 08:38:26', '830-530', 2, 18, 14),
(2, 'employee1', '$2y$10$gojZC56gwgcaRqMjNabb8OfUD7suQycRn/bg49vxUZO2/YAJQlwF6', 'John Doe', '2026-01-29 08:38:26', '8-5', 1, 18, 14);

-- --------------------------------------------------------

--
-- Table structure for table `holidays`
--

CREATE TABLE `holidays` (
  `id` int(11) NOT NULL,
  `start_date` date NOT NULL,
  `end_date` date NOT NULL,
  `name` varchar(100) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;

--
-- Dumping data for table `holidays`
--

INSERT INTO `holidays` (`id`, `start_date`, `end_date`, `name`, `created_at`) VALUES
(2, '2026-03-20', '2026-03-22', 'hari raya', '2026-03-09 07:32:30');

-- --------------------------------------------------------

--
-- Table structure for table `leaves`
--

CREATE TABLE `leaves` (
  `id` int(11) NOT NULL,
  `employee_id` int(11) NOT NULL,
  `leave_type` varchar(50) NOT NULL,
  `start_date` date NOT NULL,
  `end_date` date NOT NULL,
  `reason` text DEFAULT NULL,
  `status` enum('pending','approved','rejected','cancelled') DEFAULT 'pending',
  `created_at` datetime DEFAULT current_timestamp(),
  `day_session` varchar(20) DEFAULT 'Full Day',
  `total_days` decimal(4,1) DEFAULT 1.0,
  `document_path` varchar(255) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `leaves`
--

INSERT INTO `leaves` (`id`, `employee_id`, `leave_type`, `start_date`, `end_date`, `reason`, `status`, `created_at`, `day_session`, `total_days`, `document_path`) VALUES
(1, 1, 'Annual', '2026-03-09', '2026-03-11', 'balik kampung', 'rejected', '2026-03-05 13:37:37', 'Full Day', 3.0, NULL),
(2, 1, 'Annual', '2026-03-09', '2026-03-11', 'balik kampung', 'rejected', '2026-03-05 13:37:55', 'Full Day', 3.0, NULL),
(3, 1, 'Annual', '2026-03-09', '2026-03-11', 'balik kampung', 'approved', '2026-03-05 13:38:00', 'Full Day', 3.0, NULL),
(4, 1, 'Unpaid', '2026-03-25', '2026-03-25', 'sleep', 'approved', '2026-03-05 14:20:38', 'Afternoon', 0.5, NULL),
(5, 1, 'Medical', '2026-03-16', '2026-03-17', 'cut leg', 'approved', '2026-03-05 16:56:05', 'Full Day', 2.0, NULL),
(6, 1, 'Annual', '2026-03-27', '2026-03-27', '', 'cancelled', '2026-03-11 11:42:17', 'Full Day', 1.0, NULL),
(7, 1, 'Annual', '2026-03-20', '2026-03-20', '', 'cancelled', '2026-03-11 14:32:03', 'Full Day', 0.0, NULL),
(8, 1, 'Annual', '2026-03-31', '2026-03-31', '', 'cancelled', '2026-03-27 11:55:16', 'Full Day', 1.0, NULL),
(9, 1, 'Annual', '2026-04-01', '2026-04-01', 'just a prank haha perhaps', 'approved', '2026-03-27 15:39:44', 'Full Day', 1.0, NULL);

-- --------------------------------------------------------

--
-- Table structure for table `system_logs`
--

CREATE TABLE `system_logs` (
  `id` int(11) NOT NULL,
  `admin_id` int(11) DEFAULT NULL,
  `admin_username` varchar(100) DEFAULT NULL,
  `action` varchar(100) NOT NULL,
  `target_type` varchar(50) DEFAULT NULL,
  `target_id` int(11) DEFAULT NULL,
  `details` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;

--
-- Dumping data for table `system_logs`
--

INSERT INTO `system_logs` (`id`, `admin_id`, `admin_username`, `action`, `target_type`, `target_id`, `details`, `created_at`) VALUES
(1, NULL, 'phsb_adm', 'Approved Leave', 'Leave', 5, 'Leave request status changed to approved', '2026-03-09 07:36:22'),
(2, 1, 'phsb_adm', 'Login', 'Admin', 1, 'Admin logged in successfully', '2026-03-11 01:16:37'),
(3, 1, 'Test Admin', 'Cancel Leave', 'Leave', 7, 'Employee cancelled their own pending leave request', '2026-03-11 06:32:05'),
(4, 1, 'phsb_adm', 'Login', 'Admin', 1, 'Admin logged in successfully', '2026-03-24 06:04:08'),
(5, 1, 'phsb_adm', 'Login', 'Admin', 1, 'Admin logged in successfully', '2026-03-25 01:08:58'),
(6, 1, 'phsb_adm', 'Login', 'Admin', 1, 'Admin logged in successfully', '2026-03-26 07:05:30'),
(7, 1, 'phsb_adm', 'Login', 'Admin', 1, 'Admin logged in successfully', '2026-03-27 03:02:51'),
(8, 1, 'Test Admin', 'Cancel Leave', 'Leave', 8, 'Employee cancelled their own pending leave request', '2026-03-27 03:55:21'),
(9, 1, 'phsb_adm', 'Approved Leave', 'Leave', 9, 'Leave request status changed to approved', '2026-03-27 08:43:48');

--
-- Indexes for dumped tables
--

--
-- Indexes for table `admin_password_resets`
--
ALTER TABLE `admin_password_resets`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `admin_users`
--
ALTER TABLE `admin_users`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `username` (`username`);

--
-- Indexes for table `attendance`
--
ALTER TABLE `attendance`
  ADD PRIMARY KEY (`id`),
  ADD KEY `employee_id` (`employee_id`);

--
-- Indexes for table `departments`
--
ALTER TABLE `departments`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `name` (`name`);

--
-- Indexes for table `device_tokens`
--
ALTER TABLE `device_tokens`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `token` (`token`),
  ADD KEY `employee_id` (`employee_id`);

--
-- Indexes for table `employees`
--
ALTER TABLE `employees`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `username` (`username`),
  ADD KEY `fk_employee_department` (`department_id`);

--
-- Indexes for table `holidays`
--
ALTER TABLE `holidays`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `leaves`
--
ALTER TABLE `leaves`
  ADD PRIMARY KEY (`id`),
  ADD KEY `employee_id` (`employee_id`);

--
-- Indexes for table `system_logs`
--
ALTER TABLE `system_logs`
  ADD PRIMARY KEY (`id`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `admin_password_resets`
--
ALTER TABLE `admin_password_resets`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `admin_users`
--
ALTER TABLE `admin_users`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `attendance`
--
ALTER TABLE `attendance`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=26;

--
-- AUTO_INCREMENT for table `departments`
--
ALTER TABLE `departments`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `device_tokens`
--
ALTER TABLE `device_tokens`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=20;

--
-- AUTO_INCREMENT for table `employees`
--
ALTER TABLE `employees`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `holidays`
--
ALTER TABLE `holidays`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `leaves`
--
ALTER TABLE `leaves`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=10;

--
-- AUTO_INCREMENT for table `system_logs`
--
ALTER TABLE `system_logs`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=10;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `attendance`
--
ALTER TABLE `attendance`
  ADD CONSTRAINT `attendance_ibfk_1` FOREIGN KEY (`employee_id`) REFERENCES `employees` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `device_tokens`
--
ALTER TABLE `device_tokens`
  ADD CONSTRAINT `device_tokens_ibfk_1` FOREIGN KEY (`employee_id`) REFERENCES `employees` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `employees`
--
ALTER TABLE `employees`
  ADD CONSTRAINT `fk_employee_department` FOREIGN KEY (`department_id`) REFERENCES `departments` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `leaves`
--
ALTER TABLE `leaves`
  ADD CONSTRAINT `leaves_ibfk_1` FOREIGN KEY (`employee_id`) REFERENCES `employees` (`id`) ON DELETE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
