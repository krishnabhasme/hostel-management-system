-- ==========================================================
-- Sipna College Hostel Management System - Database
-- Aligned 100% with the System ER Diagram
-- DBMS Mini Project
-- ==========================================================

CREATE DATABASE IF NOT EXISTS `hostel_db`;
USE `hostel_db`;

-- 1. ADMIN Table
DROP TABLE IF EXISTS `complaints`;
DROP TABLE IF EXISTS `fees`;
DROP TABLE IF EXISTS `allocations`;
DROP TABLE IF EXISTS `students`;
DROP TABLE IF EXISTS `rooms`;
DROP TABLE IF EXISTS `blocks`;
DROP TABLE IF EXISTS `admin`;

CREATE TABLE `admin` (
  `username` VARCHAR(50) NOT NULL PRIMARY KEY,
  `password` VARCHAR(100) NOT NULL,
  `full_name` VARCHAR(100) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

INSERT INTO `admin` (`username`, `password`, `full_name`) VALUES
('admin', 'admin123', 'Administrator');

-- 2. BLOCKS Table
CREATE TABLE `blocks` (
  `block_id` INT AUTO_INCREMENT PRIMARY KEY,
  `block_name` VARCHAR(50) NOT NULL,
  `type` VARCHAR(50) NOT NULL,
  `warden_name` VARCHAR(100) NOT NULL,
  `warden_contact` VARCHAR(20) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

INSERT INTO `blocks` (`block_id`, `block_name`, `type`, `warden_name`, `warden_contact`) VALUES
(1, 'Block A', 'Boys Hostel', 'Mr. R. Sharma', '+91 98231 44556'),
(2, 'Block B', 'Girls Hostel', 'Mrs. S. Patil', '+91 98232 55667'),
(3, 'Block C', 'Boys Hostel', 'Mr. A. Deshmukh', '+91 98233 66778');

-- 3. ROOMS Table
CREATE TABLE `rooms` (
  `room_id` INT AUTO_INCREMENT PRIMARY KEY,
  `block_id` INT NOT NULL,
  `room_number` VARCHAR(20) NOT NULL,
  `room_type` VARCHAR(50) NOT NULL DEFAULT 'Double',
  `capacity` INT NOT NULL DEFAULT 2,
  `fee` DECIMAL(10,2) NOT NULL DEFAULT 45000.00,
  `status` VARCHAR(50) NOT NULL DEFAULT 'Available',
  FOREIGN KEY (`block_id`) REFERENCES `blocks` (`block_id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

INSERT INTO `rooms` (`room_id`, `block_id`, `room_number`, `room_type`, `capacity`, `fee`, `status`) VALUES
(1, 1, '101', 'Double', 2, 45000.00, 'Available'),
(2, 1, '102', 'Double', 2, 45000.00, 'Available'),
(3, 1, '103', 'Single', 1, 60000.00, 'Available'),
(4, 1, '201', 'Triple', 3, 35000.00, 'Available'),
(5, 2, '101', 'Double', 2, 45000.00, 'Available'),
(6, 2, '102', 'Double', 2, 45000.00, 'Available'),
(7, 2, '201', 'Triple', 3, 35000.00, 'Available'),
(8, 3, '101', 'Triple', 3, 35000.00, 'Available'),
(9, 3, '102', 'Double', 2, 45000.00, 'Available');

-- 4. STUDENTS Table
CREATE TABLE `students` (
  `student_id` VARCHAR(50) NOT NULL PRIMARY KEY,
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
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

INSERT INTO `students` (`student_id`, `name`, `email`, `contact`, `gender`, `department`, `year`, `address`, `guardian_name`, `guardian_contact`, `status`) VALUES
('STU-2024-001', 'Amit Patel', 'amit.patel@student.sipna.edu', '+91 98765 12345', 'Male', 'Computer Engineering', '3rd Year', 'Pune, Maharashtra', 'Mr. K. Patel', '+91 98000 11111', 'Active'),
('STU-2024-002', 'Pooja Deshmukh', 'pooja.d@student.sipna.edu', '+91 98765 23456', 'Female', 'Information Technology', '2nd Year', 'Nagpur, Maharashtra', 'Mrs. R. Deshmukh', '+91 98000 22222', 'Active'),
('STU-2024-003', 'Rohan Verma', 'rohan.v@student.sipna.edu', '+91 98765 34567', 'Male', 'Mechanical Engineering', '1st Year', 'Amravati, Maharashtra', 'Mr. M. Verma', '+91 98000 33333', 'Active');

-- 5. ALLOCATIONS Table
CREATE TABLE `allocations` (
  `allocation_id` INT AUTO_INCREMENT PRIMARY KEY,
  `student_id` VARCHAR(50) NOT NULL,
  `room_id` INT NOT NULL,
  `bed` VARCHAR(50) NOT NULL DEFAULT 'Bed A',
  `allocation_date` DATE NOT NULL,
  `status` VARCHAR(20) NOT NULL DEFAULT 'Active',
  FOREIGN KEY (`student_id`) REFERENCES `students` (`student_id`) ON DELETE CASCADE,
  FOREIGN KEY (`room_id`) REFERENCES `rooms` (`room_id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

INSERT INTO `allocations` (`allocation_id`, `student_id`, `room_id`, `bed`, `allocation_date`, `status`) VALUES
(1, 'STU-2024-001', 1, 'Bed A', '2026-07-15', 'Active'),
(2, 'STU-2024-002', 5, 'Bed A', '2026-07-18', 'Active');

-- 6. FEES Table
CREATE TABLE `fees` (
  `receipt_no` VARCHAR(50) NOT NULL PRIMARY KEY,
  `student_id` VARCHAR(50) NOT NULL,
  `amount` DECIMAL(10,2) NOT NULL,
  `payment_date` DATE NOT NULL,
  `payment_method` VARCHAR(50) NOT NULL DEFAULT 'UPI',
  `status` VARCHAR(20) NOT NULL DEFAULT 'Paid',
  FOREIGN KEY (`student_id`) REFERENCES `students` (`student_id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

INSERT INTO `fees` (`receipt_no`, `student_id`, `amount`, `payment_date`, `payment_method`, `status`) VALUES
('RCP-24-1001', 'STU-2024-001', 45000.00, '2026-07-15', 'UPI', 'Paid'),
('RCP-24-1002', 'STU-2024-002', 45000.00, '2026-07-18', 'Bank Transfer', 'Paid');

-- 7. COMPLAINTS Table
CREATE TABLE `complaints` (
  `ticket_no` VARCHAR(50) NOT NULL PRIMARY KEY,
  `student_id` VARCHAR(50) NOT NULL,
  `category` VARCHAR(50) NOT NULL,
  `subject` VARCHAR(255) NOT NULL,
  `description` TEXT NOT NULL,
  `priority` VARCHAR(20) NOT NULL DEFAULT 'Medium',
  `status` VARCHAR(20) NOT NULL DEFAULT 'Open',
  `created_at` DATE NOT NULL,
  FOREIGN KEY (`student_id`) REFERENCES `students` (`student_id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

INSERT INTO `complaints` (`ticket_no`, `student_id`, `category`, `subject`, `description`, `priority`, `status`, `created_at`) VALUES
('CMP-1001', 'STU-2024-001', 'Plumbing', 'Bathroom tap leaking', 'Room 101 bathroom tap has a persistent leak.', 'Medium', 'In Progress', '2026-08-10'),
('CMP-1002', 'STU-2024-002', 'Electrical', 'Study lamp socket issue', 'Power outlet near bed A is sparking intermittently.', 'High', 'Open', '2026-08-14');
