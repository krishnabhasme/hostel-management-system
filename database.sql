-- ==========================================================
-- Sipna College Hostel Management System - Database
-- DBMS Mini Project
-- ==========================================================

CREATE DATABASE IF NOT EXISTS `hostel_db`;
USE `hostel_db`;

-- 1. Admin Table
DROP TABLE IF EXISTS `admin`;
CREATE TABLE `admin` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `username` VARCHAR(50) NOT NULL UNIQUE,
  `password` VARCHAR(100) NOT NULL,
  `full_name` VARCHAR(100) NOT NULL
);

INSERT INTO `admin` (`username`, `password`, `full_name`) VALUES
('admin', 'admin123', 'Administrator');

-- 2. Hostel Blocks Table
DROP TABLE IF EXISTS `blocks`;
CREATE TABLE `blocks` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `block_name` VARCHAR(50) NOT NULL,
  `type` VARCHAR(50) NOT NULL,
  `warden_name` VARCHAR(100) NOT NULL,
  `warden_contact` VARCHAR(20) DEFAULT NULL
);

INSERT INTO `blocks` (`id`, `block_name`, `type`, `warden_name`, `warden_contact`) VALUES
(1, 'Block A', 'Boys Hostel', 'Mr. R. Sharma', '+91 98231 44556'),
(2, 'Block B', 'Girls Hostel', 'Mrs. S. Patil', '+91 98232 55667'),
(3, 'Block C', 'Boys Hostel', 'Mr. A. Deshmukh', '+91 98233 66778');

-- 3. Rooms Table
DROP TABLE IF EXISTS `rooms`;
CREATE TABLE `rooms` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `block_id` INT NOT NULL,
  `room_number` VARCHAR(20) NOT NULL,
  `room_type` VARCHAR(50) NOT NULL DEFAULT 'Double',
  `capacity` INT NOT NULL DEFAULT 2,
  `fee` DECIMAL(10,2) NOT NULL DEFAULT 45000.00,
  `status` VARCHAR(50) NOT NULL DEFAULT 'Available',
  FOREIGN KEY (`block_id`) REFERENCES `blocks` (`id`) ON DELETE CASCADE
);

INSERT INTO `rooms` (`id`, `block_id`, `room_number`, `room_type`, `capacity`, `fee`, `status`) VALUES
(1, 1, '101', 'Double', 2, 45000.00, 'Available'),
(2, 1, '102', 'Double', 2, 45000.00, 'Available'),
(3, 1, '103', 'Single', 1, 60000.00, 'Available'),
(4, 1, '201', 'Triple', 3, 35000.00, 'Available'),
(5, 2, '101', 'Double', 2, 45000.00, 'Available'),
(6, 2, '102', 'Double', 2, 45000.00, 'Available'),
(7, 2, '201', 'Triple', 3, 35000.00, 'Available'),
(8, 3, '101', 'Triple', 3, 35000.00, 'Available'),
(9, 3, '102', 'Double', 2, 45000.00, 'Available');

-- 4. Students Table (Initially Empty - Admin adds students)
DROP TABLE IF EXISTS `students`;
CREATE TABLE `students` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `student_id` VARCHAR(50) NOT NULL UNIQUE,
  `name` VARCHAR(100) NOT NULL,
  `email` VARCHAR(100) NOT NULL,
  `contact` VARCHAR(20) NOT NULL,
  `gender` VARCHAR(20) NOT NULL DEFAULT 'Male',
  `department` VARCHAR(100) NOT NULL,
  `year` VARCHAR(20) NOT NULL,
  `address` TEXT DEFAULT NULL,
  `guardian_name` VARCHAR(100) DEFAULT NULL,
  `guardian_contact` VARCHAR(20) DEFAULT NULL,
  `status` VARCHAR(20) NOT NULL DEFAULT 'Active'
);

-- 5. Allocations Table (Initially Empty)
DROP TABLE IF EXISTS `allocations`;
CREATE TABLE `allocations` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `student_id` INT NOT NULL,
  `room_id` INT NOT NULL,
  `bed` VARCHAR(50) NOT NULL DEFAULT 'Bed A',
  `allocation_date` DATE NOT NULL,
  `status` VARCHAR(20) NOT NULL DEFAULT 'Active',
  FOREIGN KEY (`student_id`) REFERENCES `students` (`id`) ON DELETE CASCADE,
  FOREIGN KEY (`room_id`) REFERENCES `rooms` (`id`) ON DELETE CASCADE
);

-- 6. Fees Table (Initially Empty)
DROP TABLE IF EXISTS `fees`;
CREATE TABLE `fees` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `receipt_no` VARCHAR(50) NOT NULL UNIQUE,
  `student_id` INT NOT NULL,
  `amount` DECIMAL(10,2) NOT NULL,
  `payment_date` DATE NOT NULL,
  `payment_method` VARCHAR(50) NOT NULL DEFAULT 'UPI',
  `status` VARCHAR(20) NOT NULL DEFAULT 'Paid',
  FOREIGN KEY (`student_id`) REFERENCES `students` (`id`) ON DELETE CASCADE
);

-- 7. Complaints Table (Initially Empty)
DROP TABLE IF EXISTS `complaints`;
CREATE TABLE `complaints` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `ticket_no` VARCHAR(50) NOT NULL UNIQUE,
  `student_id` INT NOT NULL,
  `category` VARCHAR(50) NOT NULL,
  `subject` VARCHAR(255) NOT NULL,
  `description` TEXT NOT NULL,
  `priority` VARCHAR(20) NOT NULL DEFAULT 'Medium',
  `status` VARCHAR(20) NOT NULL DEFAULT 'Open',
  `created_at` DATE NOT NULL,
  FOREIGN KEY (`student_id`) REFERENCES `students` (`id`) ON DELETE CASCADE
);
