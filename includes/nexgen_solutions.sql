-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Feb 12, 2026 at 04:14 PM
-- Server version: 10.4.32-MariaDB
-- PHP Version: 8.1.25

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `nexgen_solutions`
--

-- --------------------------------------------------------

--
-- Table structure for table `employees`
--

CREATE TABLE `employees` (
  `id` int(11) NOT NULL,
  `user_id` int(11) DEFAULT NULL,
  `job_title` varchar(100) DEFAULT NULL,
  `department` varchar(100) DEFAULT NULL,
  `hire_date` date DEFAULT NULL,
  `salary_base` decimal(10,2) DEFAULT NULL,
  `status` enum('active','resigned') DEFAULT 'active'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `employees`
--

INSERT INTO `employees` (`id`, `user_id`, `job_title`, `department`, `hire_date`, `salary_base`, `status`) VALUES
(1, 1, 'Software Developer', 'IT', '2023-05-01', 100000.00, 'active'),
(2, 2, 'UI Designer', 'Design', '2023-07-05', 50000.00, 'active'),
(3, 3, 'Project Manager', 'IT', '2022-09-09', 75000.00, 'active'),
(4, 6, 'Designer', 'Design', '2024-01-02', 51500.00, 'resigned'),
(5, 7, 'Accountant', 'Finance', '2024-01-03', 53000.00, 'active'),
(6, 8, 'Manager', 'Management', '2024-01-04', 54500.00, 'active'),
(7, 9, 'HR Specialist', 'HR', '2024-01-05', 56000.00, 'active'),
(8, 10, 'IT Specialist', 'IT', '2024-01-06', 57500.00, 'active'),
(9, 11, 'QA Analyst', 'Testing', '2024-01-07', 59000.00, 'active'),
(10, 12, 'Accountant', 'Finance', '2024-01-08', 60500.00, 'active'),
(11, 13, 'QA Analyst', 'Testing', '2024-01-09', 62000.00, 'active'),
(12, 14, 'QA Analyst', 'Testing', '2024-01-10', 63500.00, 'active'),
(13, 15, 'Manager', 'Management', '2024-01-11', 50000.00, 'resigned'),
(14, 16, 'Manager', 'Management', '2024-01-12', 51500.00, 'active'),
(15, 17, 'Support Specialist', 'Support', '2024-01-13', 53000.00, 'resigned'),
(16, 18, 'Support Specialist', 'Support', '2024-01-14', 54500.00, 'active'),
(17, 19, 'Accountant', 'Finance', '2024-01-15', 56000.00, 'resigned'),
(18, 20, 'QA Analyst', 'Testing', '2024-01-16', 57500.00, 'resigned'),
(19, 21, 'IT Specialist', 'IT', '2024-01-17', 59000.00, 'resigned'),
(20, 22, 'Designer', 'Design', '2024-01-18', 60500.00, 'resigned'),
(21, 23, 'Designer', 'Design', '2024-01-19', 62000.00, 'resigned'),
(22, 24, 'Designer', 'Design', '2024-01-20', 63500.00, 'resigned'),
(23, 25, 'HR Specialist', 'HR', '2024-01-21', 50000.00, 'resigned'),
(24, 26, 'Designer', 'Design', '2024-01-22', 51500.00, 'active'),
(25, 27, 'Support Specialist', 'Support', '2024-01-23', 53000.00, 'active'),
(26, 28, 'Manager', 'Management', '2024-01-24', 54500.00, 'resigned'),
(27, 29, 'Designer', 'Design', '2024-01-25', 56000.00, 'resigned'),
(28, 30, 'Support Specialist', 'Support', '2024-01-26', 57500.00, 'active'),
(29, 31, 'Designer', 'Design', '2024-01-27', 59000.00, 'active'),
(30, 32, 'Accountant', 'Finance', '2024-01-28', 60500.00, 'active'),
(31, 33, 'Designer', 'Design', '2024-01-29', 62000.00, 'resigned'),
(32, 34, 'IT Specialist', 'IT', '2024-01-30', 63500.00, 'active'),
(33, 35, 'Accountant', 'Finance', '2024-01-31', 50000.00, 'active'),
(34, 36, 'Accountant', 'Finance', '2024-02-01', 51500.00, 'active'),
(35, 37, 'Manager', 'Management', '2024-02-02', 53000.00, 'active'),
(36, 38, 'Designer', 'Design', '2024-02-03', 54500.00, 'resigned'),
(37, 39, 'IT Specialist', 'IT', '2024-02-04', 56000.00, 'resigned'),
(38, 40, 'Designer', 'Design', '2024-02-05', 57500.00, 'active'),
(39, 41, 'Support Specialist', 'Support', '2024-02-06', 59000.00, 'active'),
(40, 42, 'IT Specialist', 'IT', '2024-02-07', 60500.00, 'resigned'),
(41, 43, 'HR Specialist', 'HR', '2024-02-08', 62000.00, 'active'),
(42, 44, 'HR Specialist', 'HR', '2024-02-09', 63500.00, 'active'),
(43, 45, 'QA Analyst', 'Testing', '2024-02-10', 50000.00, 'resigned'),
(44, 46, 'Manager', 'Management', '2024-02-11', 51500.00, 'active'),
(45, 47, 'Accountant', 'Finance', '2024-02-12', 53000.00, 'active'),
(46, 48, 'IT Specialist', 'IT', '2024-02-13', 54500.00, 'active'),
(47, 49, 'Support Specialist', 'Support', '2024-02-14', 56000.00, 'resigned'),
(48, 50, 'Manager', 'Management', '2024-02-15', 57500.00, 'active'),
(49, 51, 'Support Specialist', 'Support', '2024-02-16', 59000.00, 'active'),
(50, 52, 'Support Specialist', 'Support', '2024-02-17', 60500.00, 'active'),
(51, 53, 'HR Specialist', 'HR', '2024-02-18', 62000.00, 'active'),
(52, 54, 'Support Specialist', 'Support', '2024-02-19', 63500.00, 'active'),
(53, 55, 'HR Specialist', 'HR', '2024-02-20', 50000.00, 'resigned'),
(54, 56, 'HR Specialist', 'HR', '2024-02-21', 51500.00, 'active'),
(55, 57, 'Designer', 'Design', '2024-02-22', 53000.00, 'resigned'),
(56, 58, 'Manager', 'Management', '2024-02-23', 54500.00, 'resigned'),
(57, 59, 'HR Specialist', 'HR', '2024-02-24', 56000.00, 'resigned'),
(58, 60, 'Manager', 'Management', '2024-02-25', 57500.00, 'active'),
(59, 61, 'HR Specialist', 'HR', '2024-02-26', 59000.00, 'active'),
(60, 62, 'IT Specialist', 'IT', '2024-02-27', 60500.00, 'resigned'),
(61, 63, 'Designer', 'Design', '2024-02-28', 62000.00, 'active'),
(62, 64, 'Designer', 'Design', '2024-02-29', 63500.00, 'active'),
(63, 65, 'QA Analyst', 'Testing', '2024-03-01', 50000.00, 'resigned'),
(64, 66, 'QA Analyst', 'Testing', '2024-03-02', 51500.00, 'active'),
(65, 67, 'Support Specialist', 'Support', '2024-03-03', 53000.00, 'resigned'),
(66, 68, 'QA Analyst', 'Testing', '2024-03-04', 54500.00, 'active'),
(67, 69, 'Manager', 'Management', '2024-03-05', 56000.00, 'resigned'),
(68, 70, 'IT Specialist', 'IT', '2024-03-06', 57500.00, 'active'),
(69, 71, 'Manager', 'Management', '2024-03-07', 59000.00, 'resigned'),
(70, 72, 'Support Specialist', 'Support', '2024-03-08', 60500.00, 'resigned'),
(71, 73, 'QA Analyst', 'Testing', '2024-03-09', 62000.00, 'resigned'),
(72, 74, 'Accountant', 'Finance', '2024-03-10', 63500.00, 'active'),
(73, 75, 'Support Specialist', 'Support', '2024-03-11', 50000.00, 'resigned'),
(74, 76, 'Designer', 'Design', '2024-03-12', 51500.00, 'active'),
(75, 77, 'Support Specialist', 'Support', '2024-03-13', 53000.00, 'resigned'),
(76, 78, 'IT Specialist', 'IT', '2024-03-14', 54500.00, 'resigned'),
(77, 79, 'Support Specialist', 'Support', '2024-03-15', 56000.00, 'active'),
(78, 80, 'Accountant', 'Finance', '2024-03-16', 57500.00, 'resigned'),
(79, 81, 'Support Specialist', 'Support', '2024-03-17', 59000.00, 'active'),
(80, 82, 'HR Specialist', 'HR', '2024-03-18', 60500.00, 'active'),
(81, 83, 'Manager', 'Management', '2024-03-19', 62000.00, 'resigned'),
(82, 84, 'HR Specialist', 'HR', '2024-03-20', 63500.00, 'active'),
(83, 85, 'HR Specialist', 'HR', '2024-03-21', 50000.00, 'resigned'),
(84, 86, 'Support Specialist', 'Support', '2024-03-22', 51500.00, 'resigned'),
(85, 87, 'Manager', 'Management', '2024-03-23', 53000.00, 'active'),
(86, 88, 'Accountant', 'Finance', '2024-03-24', 54500.00, 'active'),
(87, 89, 'Designer', 'Design', '2024-03-25', 56000.00, 'active'),
(88, 90, 'QA Analyst', 'Testing', '2024-03-26', 57500.00, 'active'),
(89, 91, 'Accountant', 'Finance', '2024-03-27', 59000.00, 'active'),
(90, 92, 'Support Specialist', 'Support', '2024-03-28', 60500.00, 'resigned'),
(91, 93, 'Manager', 'Management', '2024-03-29', 62000.00, 'resigned'),
(92, 94, 'Accountant', 'Finance', '2024-03-30', 63500.00, 'active'),
(93, 95, 'Manager', 'Management', '2024-03-31', 50000.00, 'active'),
(94, 96, 'Manager', 'Management', '2024-04-01', 51500.00, 'resigned'),
(95, 97, 'HR Specialist', 'HR', '2024-04-02', 53000.00, 'resigned'),
(96, 98, 'Manager', 'Management', '2024-04-03', 54500.00, 'resigned'),
(97, 99, 'Manager', 'Management', '2024-04-04', 56000.00, 'active'),
(98, 100, 'Manager', 'Management', '2024-04-05', 57500.00, 'resigned'),
(99, 101, 'QA Analyst', 'Testing', '2024-04-06', 59000.00, 'resigned'),
(100, 102, 'Support Specialist', 'Support', '2024-04-07', 60500.00, 'active'),
(101, 103, 'HR Specialist', 'HR', '2024-04-08', 62000.00, 'resigned'),
(102, 104, 'Support Specialist', 'Support', '2024-04-09', 63500.00, 'resigned'),
(103, 105, 'QA Analyst', 'Testing', '2024-04-10', 50000.00, 'active'),
(104, 106, 'IT Specialist', 'IT', '2024-04-11', 51500.00, 'active'),
(105, 107, 'IT Specialist', 'IT', '2024-04-12', 53000.00, 'resigned'),
(106, 108, 'QA Analyst', 'Testing', '2024-04-13', 54500.00, 'resigned'),
(107, 109, 'HR Specialist', 'HR', '2024-04-14', 56000.00, 'active'),
(108, 110, 'Support Specialist', 'Support', '2024-04-15', 57500.00, 'active'),
(109, 111, 'Support Specialist', 'Support', '2024-04-16', 59000.00, 'active'),
(110, 112, 'Manager', 'Management', '2024-04-17', 60500.00, 'resigned'),
(111, 113, 'HR Specialist', 'HR', '2024-04-18', 62000.00, 'resigned'),
(112, 114, 'Accountant', 'Finance', '2024-04-19', 63500.00, 'active'),
(113, 115, 'Designer', 'Design', '2024-04-20', 50000.00, 'active'),
(114, 116, 'Designer', 'Design', '2024-04-21', 51500.00, 'resigned'),
(115, 117, 'Support Specialist', 'Support', '2024-04-22', 53000.00, 'active'),
(116, 118, 'HR Specialist', 'HR', '2024-04-23', 54500.00, 'resigned'),
(117, 119, 'HR Specialist', 'HR', '2024-04-24', 56000.00, 'active'),
(118, 120, 'HR Specialist', 'HR', '2024-04-25', 57500.00, 'resigned'),
(119, 121, 'Designer', 'Design', '2024-04-26', 59000.00, 'resigned'),
(120, 122, 'Manager', 'Management', '2024-04-27', 60500.00, 'active'),
(121, 123, 'HR Specialist', 'HR', '2024-04-28', 62000.00, 'resigned'),
(122, 124, 'IT Specialist', 'IT', '2024-04-29', 63500.00, 'active'),
(123, 125, 'Manager', 'Management', '2024-04-30', 50000.00, 'resigned');

-- --------------------------------------------------------

--
-- Table structure for table `inquiries`
--

CREATE TABLE `inquiries` (
  `id` int(11) NOT NULL,
  `name` varchar(100) DEFAULT NULL,
  `email` varchar(100) DEFAULT NULL,
  `company` varchar(100) DEFAULT NULL,
  `message` text DEFAULT NULL,
  `status` enum('new','replied','closed') DEFAULT 'new',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `category` varchar(70) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `inquiries`
--

INSERT INTO `inquiries` (`id`, `name`, `email`, `company`, `message`, `status`, `created_at`, `category`) VALUES
(1, 'Mark', 'mark@mail.com', 'Alpha Ltd', 'Need a project quote', 'new', '2026-01-03 16:08:12', 'Service Request'),
(2, 'Lisa', 'lisa@mail.com', 'Beta Corp', 'Interested in your software', 'replied', '2026-01-03 16:08:12', 'Others'),
(3, 'Tom', 'tom@mail.com', 'Gamma LLC', 'Request a demo', 'new', '2026-01-03 16:08:12', 'General Inquiry'),
(4, 'Phoneapp Shalom', 'phoneappinfos@gmail.com', 'Beta Corp', 'The Service Request I need is to ask for help with the implementation of my Project regarding the Student Management System\r\n\r\nPreferred Contact Time: 2026-01-14 06:00 PM\r\nInquiry Type: Service Request', 'closed', '2026-01-05 13:18:56', 'Support'),
(6, 'Benevolent', 'benevolenteager@gmail.com', 'NexGen Solutions', 'The integration of APIs and my project.', 'closed', '2026-02-03 17:57:15', 'HR'),
(7, 'Shalom', 'phoneappinfos@gmail.com', 'NexGen Solutions', 'I did not get what I wanted from you as I put my trust I you, Yoooh', 'replied', '2026-02-03 22:12:16', 'Complaint'),
(8, 'Shalom', 'phoneappinfos@gmail.com', 'NexGen Solutions', 'I did not get what I wanted from you as I put my trust I you, Yoooh', 'new', '2026-02-03 22:17:34', 'Complaint'),
(9, 'Phoneapp Infos', 'phoneappinfos@gmail.com', '', 'smnbm,bjkjqjkdljdbqwjdqwlo', 'new', '2026-02-11 17:16:27', NULL);

-- --------------------------------------------------------

--
-- Table structure for table `inquiry_table`
--

CREATE TABLE `inquiry_table` (
  `InquiryId` int(11) NOT NULL,
  `YouName` varchar(255) NOT NULL,
  `Email` varchar(100) NOT NULL,
  `PhoneNumber` int(11) NOT NULL,
  `SelectedService` varchar(255) NOT NULL,
  `Message` varchar(255) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `inquiry_table`
--

INSERT INTO `inquiry_table` (`InquiryId`, `YouName`, `Email`, `PhoneNumber`, `SelectedService`, `Message`) VALUES
(2, 'desktopappinfos', 'desktopappinfos@gmail.com', 798621126, 'UI/UX Design', 'Hello, I\'m looking to add a new record to the `inquiry_table` in the `nexgen_solutions` database.'),
(3, 'Shalom', 'phoneappinfos@gmail.com', 743381386, 'IT Consultant', 'Greetings! I\'m Phoneapp Infos, a creative professional passionate about blending design and code to build intuitive interfaces and engaging web experiences.');

-- --------------------------------------------------------

--
-- Table structure for table `leave_requests`
--

CREATE TABLE `leave_requests` (
  `id` int(11) NOT NULL,
  `employee_id` int(11) DEFAULT NULL,
  `start_date` date DEFAULT NULL,
  `end_date` date DEFAULT NULL,
  `leave_type` enum('sick','annual','unpaid','personal','vacation') DEFAULT NULL,
  `reason` text DEFAULT NULL,
  `status` enum('pending','leader_approved','hr_approved','rejected') DEFAULT 'pending',
  `leader_id` int(11) DEFAULT NULL,
  `hr_id` int(11) DEFAULT NULL,
  `applied_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `leave_requests`
--

INSERT INTO `leave_requests` (`id`, `employee_id`, `start_date`, `end_date`, `leave_type`, `reason`, `status`, `leader_id`, `hr_id`, `applied_at`) VALUES
(7, 1, '2024-06-10', '2024-06-12', 'annual', 'Vacation', 'pending', 3, 2, '2026-01-03 16:41:45'),
(8, 2, '2024-06-05', '2024-06-06', 'sick', 'Flu', 'rejected', 3, 2, '2026-01-03 16:41:45'),
(9, 1, '2024-05-01', '2024-05-02', 'unpaid', 'Personal', 'hr_approved', 3, 2, '2026-01-03 16:41:45'),
(10, 1, '2026-01-30', '2026-02-08', 'personal', 'I\'d like to spend the time thinking about myself.', 'pending', NULL, NULL, '2026-01-11 17:05:30'),
(11, 1, '2026-02-06', '2026-02-08', 'personal', 'It will be Sabbath; I need to need as it is commanded', 'hr_approved', NULL, NULL, '2026-02-04 05:08:18'),
(12, 123, '2025-01-22', '2025-01-25', 'sick', 'Auto reason', 'leader_approved', 3, NULL, '2026-02-08 13:35:44'),
(13, 84, '2025-01-11', '2025-01-14', 'personal', 'Auto reason', 'leader_approved', 3, NULL, '2026-02-08 13:35:44'),
(14, 120, '2025-03-23', '2025-03-26', 'sick', 'Auto reason', 'pending', NULL, NULL, '2026-02-08 13:35:44'),
(15, 32, '2025-06-06', '2025-06-09', 'sick', 'Auto reason', 'leader_approved', 3, NULL, '2026-02-08 13:35:44'),
(16, 13, '2025-01-07', '2025-01-10', 'annual', 'Auto reason', 'rejected', NULL, NULL, '2026-02-08 13:35:44'),
(17, 24, '2025-01-03', '2025-01-06', 'annual', 'Auto reason', 'rejected', NULL, NULL, '2026-02-08 13:35:44'),
(18, 31, '2025-07-04', '2025-07-07', 'unpaid', 'Auto reason', 'leader_approved', 3, NULL, '2026-02-08 13:35:44'),
(19, 87, '2025-04-13', '2025-04-16', 'annual', 'Auto reason', 'leader_approved', 3, NULL, '2026-02-08 13:35:44'),
(20, 27, '2025-04-09', '2025-04-12', 'sick', 'Auto reason', 'rejected', NULL, NULL, '2026-02-08 13:35:44'),
(21, 120, '2025-06-05', '2025-06-08', 'annual', 'Auto reason', 'leader_approved', 3, NULL, '2026-02-08 13:35:44'),
(22, 105, '2025-01-24', '2025-01-27', 'sick', 'Auto reason', 'pending', NULL, NULL, '2026-02-08 13:35:44'),
(23, 35, '2025-06-19', '2025-06-22', 'annual', 'Auto reason', 'leader_approved', 3, NULL, '2026-02-08 13:35:44'),
(24, 58, '2025-01-22', '2025-01-25', 'unpaid', 'Auto reason', 'pending', NULL, NULL, '2026-02-08 13:35:44'),
(25, 22, '2025-05-27', '2025-05-30', 'personal', 'Auto reason', 'pending', NULL, NULL, '2026-02-08 13:35:44'),
(26, 109, '2025-03-18', '2025-03-21', 'personal', 'Auto reason', 'rejected', NULL, NULL, '2026-02-08 13:35:44'),
(27, 11, '2025-01-08', '2025-01-11', 'personal', 'Auto reason', 'leader_approved', 3, NULL, '2026-02-08 13:35:44'),
(28, 103, '2025-04-16', '2025-04-19', 'sick', 'Auto reason', 'leader_approved', 3, NULL, '2026-02-08 13:35:44'),
(29, 26, '2025-02-07', '2025-02-10', 'unpaid', 'Auto reason', 'leader_approved', 3, NULL, '2026-02-08 13:35:44'),
(30, 42, '2025-05-08', '2025-05-11', 'sick', 'Auto reason', 'pending', NULL, NULL, '2026-02-08 13:35:44'),
(31, 50, '2025-06-06', '2025-06-09', 'annual', 'Auto reason', 'leader_approved', 3, NULL, '2026-02-08 13:35:44'),
(32, 45, '2025-02-18', '2025-02-21', 'unpaid', 'Auto reason', 'leader_approved', 3, NULL, '2026-02-08 13:35:44'),
(33, 119, '2025-06-05', '2025-06-08', 'sick', 'Auto reason', 'pending', NULL, NULL, '2026-02-08 13:35:44'),
(34, 19, '2025-01-30', '2025-02-02', 'annual', 'Auto reason', 'leader_approved', 3, NULL, '2026-02-08 13:35:44'),
(35, 47, '2025-03-15', '2025-03-18', 'personal', 'Auto reason', 'pending', NULL, NULL, '2026-02-08 13:35:44'),
(36, 66, '2025-01-19', '2025-01-22', 'sick', 'Auto reason', 'pending', NULL, NULL, '2026-02-08 13:35:44'),
(37, 70, '2025-02-22', '2025-02-25', 'unpaid', 'Auto reason', 'leader_approved', 3, NULL, '2026-02-08 13:35:44'),
(38, 87, '2025-03-22', '2025-03-25', 'sick', 'Auto reason', 'pending', NULL, NULL, '2026-02-08 13:35:44'),
(39, 75, '2025-03-06', '2025-03-09', 'personal', 'Auto reason', 'pending', NULL, NULL, '2026-02-08 13:35:44'),
(40, 60, '2025-07-02', '2025-07-05', 'personal', 'Auto reason', 'pending', NULL, NULL, '2026-02-08 13:35:44'),
(41, 58, '2025-03-07', '2025-03-10', 'annual', 'Auto reason', 'leader_approved', 3, NULL, '2026-02-08 13:35:44'),
(42, 119, '2025-06-18', '2025-06-21', 'annual', 'Auto reason', 'pending', NULL, NULL, '2026-02-08 13:35:44'),
(43, 74, '2025-03-22', '2025-03-25', 'sick', 'Auto reason', 'pending', NULL, NULL, '2026-02-08 13:35:44'),
(44, 86, '2025-06-27', '2025-06-30', 'annual', 'Auto reason', 'rejected', NULL, NULL, '2026-02-08 13:35:44'),
(45, 88, '2025-07-16', '2025-07-19', 'annual', 'Auto reason', 'leader_approved', 3, NULL, '2026-02-08 13:35:44'),
(46, 81, '2025-02-21', '2025-02-24', 'sick', 'Auto reason', 'leader_approved', 3, NULL, '2026-02-08 13:35:44'),
(47, 17, '2025-05-24', '2025-05-27', 'personal', 'Auto reason', 'rejected', NULL, NULL, '2026-02-08 13:35:44'),
(48, 104, '2025-04-13', '2025-04-16', 'annual', 'Auto reason', 'leader_approved', 3, NULL, '2026-02-08 13:35:44'),
(49, 6, '2025-07-17', '2025-07-20', 'personal', 'Auto reason', 'pending', NULL, NULL, '2026-02-08 13:35:44'),
(50, 30, '2025-03-04', '2025-03-07', 'sick', 'Auto reason', 'pending', NULL, NULL, '2026-02-08 13:35:44'),
(51, 109, '2025-03-01', '2025-03-04', 'sick', 'Auto reason', 'rejected', NULL, NULL, '2026-02-08 13:35:44'),
(52, 58, '2025-07-03', '2025-07-06', 'sick', 'Auto reason', 'leader_approved', 3, NULL, '2026-02-08 13:35:44'),
(53, 33, '2025-04-04', '2025-04-07', 'annual', 'Auto reason', 'pending', NULL, NULL, '2026-02-08 13:35:44'),
(54, 30, '2025-06-18', '2025-06-21', 'annual', 'Auto reason', 'rejected', NULL, NULL, '2026-02-08 13:35:44'),
(55, 107, '2025-01-24', '2025-01-27', 'personal', 'Auto reason', 'leader_approved', 3, NULL, '2026-02-08 13:35:44'),
(56, 10, '2025-07-17', '2025-07-20', 'sick', 'Auto reason', 'pending', NULL, NULL, '2026-02-08 13:35:44'),
(57, 35, '2025-04-04', '2025-04-07', 'annual', 'Auto reason', 'pending', NULL, NULL, '2026-02-08 13:35:44'),
(58, 60, '2025-05-27', '2025-05-30', 'annual', 'Auto reason', 'pending', NULL, NULL, '2026-02-08 13:35:44'),
(59, 76, '2025-02-21', '2025-02-24', 'unpaid', 'Auto reason', 'leader_approved', 3, NULL, '2026-02-08 13:35:44'),
(60, 85, '2025-03-14', '2025-03-17', 'annual', 'Auto reason', 'leader_approved', 3, NULL, '2026-02-08 13:35:44'),
(61, 49, '2025-05-23', '2025-05-26', 'unpaid', 'Auto reason', 'pending', NULL, NULL, '2026-02-08 13:35:44'),
(62, 24, '2025-01-10', '2025-01-13', 'annual', 'Auto reason', 'rejected', NULL, NULL, '2026-02-08 13:35:44'),
(63, 57, '2025-06-17', '2025-06-20', 'annual', 'Auto reason', 'pending', NULL, NULL, '2026-02-08 13:35:44'),
(64, 113, '2025-02-23', '2025-02-26', 'annual', 'Auto reason', 'pending', NULL, NULL, '2026-02-08 13:35:44'),
(65, 120, '2025-06-18', '2025-06-21', 'annual', 'Auto reason', 'rejected', NULL, NULL, '2026-02-08 13:35:44'),
(66, 100, '2025-03-18', '2025-03-21', 'sick', 'Auto reason', 'rejected', NULL, NULL, '2026-02-08 13:35:44'),
(67, 36, '2025-01-04', '2025-01-07', 'annual', 'Auto reason', 'pending', NULL, NULL, '2026-02-08 13:35:44'),
(68, 103, '2025-06-21', '2025-06-24', 'sick', 'Auto reason', 'leader_approved', 3, NULL, '2026-02-08 13:35:44'),
(69, 89, '2025-06-03', '2025-06-06', 'personal', 'Auto reason', 'rejected', NULL, NULL, '2026-02-08 13:35:44'),
(70, 16, '2025-01-29', '2025-02-01', 'sick', 'Auto reason', 'rejected', NULL, NULL, '2026-02-08 13:35:44'),
(71, 10, '2025-02-05', '2025-02-08', 'personal', 'Auto reason', 'leader_approved', 3, NULL, '2026-02-08 13:35:44'),
(72, 57, '2025-02-05', '2025-02-08', 'sick', 'Auto reason', 'pending', NULL, NULL, '2026-02-08 13:35:44'),
(73, 51, '2025-01-20', '2025-01-23', 'annual', 'Auto reason', 'leader_approved', 3, NULL, '2026-02-08 13:35:44'),
(74, 73, '2025-07-07', '2025-07-10', 'annual', 'Auto reason', 'leader_approved', 3, NULL, '2026-02-08 13:35:44'),
(75, 95, '2025-04-20', '2025-04-23', 'sick', 'Auto reason', 'leader_approved', 3, NULL, '2026-02-08 13:35:44'),
(76, 20, '2025-06-28', '2025-07-01', 'annual', 'Auto reason', 'rejected', NULL, NULL, '2026-02-08 13:35:44'),
(77, 73, '2025-04-16', '2025-04-19', 'sick', 'Auto reason', 'rejected', NULL, NULL, '2026-02-08 13:35:44'),
(78, 67, '2025-04-10', '2025-04-13', 'sick', 'Auto reason', 'leader_approved', 3, NULL, '2026-02-08 13:35:44'),
(79, 83, '2025-03-28', '2025-03-31', 'annual', 'Auto reason', 'leader_approved', 3, NULL, '2026-02-08 13:35:44'),
(80, 23, '2025-02-23', '2025-02-26', 'annual', 'Auto reason', 'leader_approved', 3, NULL, '2026-02-08 13:35:44'),
(81, 94, '2025-03-20', '2025-03-23', 'sick', 'Auto reason', 'leader_approved', 3, NULL, '2026-02-08 13:35:44'),
(82, 106, '2025-07-04', '2025-07-07', 'personal', 'Auto reason', 'pending', NULL, NULL, '2026-02-08 13:35:44'),
(83, 66, '2025-05-09', '2025-05-12', 'sick', 'Auto reason', 'pending', NULL, NULL, '2026-02-08 13:35:44'),
(84, 32, '2025-02-21', '2025-02-24', 'sick', 'Auto reason', 'hr_approved', 3, NULL, '2026-02-08 13:35:44'),
(85, 11, '2025-02-14', '2025-02-17', 'sick', 'Auto reason', 'pending', NULL, NULL, '2026-02-08 13:35:44'),
(86, 20, '2025-02-06', '2025-02-09', 'annual', 'Auto reason', 'rejected', NULL, NULL, '2026-02-08 13:35:44'),
(87, 12, '2025-06-22', '2025-06-25', 'sick', 'Auto reason', 'rejected', NULL, NULL, '2026-02-08 13:35:44'),
(88, 70, '2025-03-27', '2025-03-30', 'sick', 'Auto reason', 'pending', NULL, NULL, '2026-02-08 13:35:44'),
(89, 27, '2025-03-11', '2025-03-14', 'annual', 'Auto reason', 'pending', NULL, NULL, '2026-02-08 13:35:44'),
(90, 49, '2025-01-13', '2025-01-16', 'annual', 'Auto reason', 'rejected', NULL, NULL, '2026-02-08 13:35:44'),
(91, 28, '2025-02-11', '2025-02-14', 'sick', 'Auto reason', 'rejected', NULL, NULL, '2026-02-08 13:35:44'),
(92, 26, '2025-01-18', '2025-01-21', 'personal', 'Auto reason', 'leader_approved', 3, NULL, '2026-02-08 13:35:44'),
(93, 11, '2025-01-16', '2025-01-19', 'personal', 'Auto reason', 'leader_approved', 3, NULL, '2026-02-08 13:35:44'),
(94, 66, '2025-02-16', '2025-02-19', 'unpaid', 'Auto reason', 'rejected', NULL, NULL, '2026-02-08 13:35:44'),
(95, 117, '2025-04-03', '2025-04-06', 'unpaid', 'Auto reason', 'pending', NULL, NULL, '2026-02-08 13:35:44'),
(96, 89, '2025-06-29', '2025-07-02', 'unpaid', 'Auto reason', 'pending', NULL, NULL, '2026-02-08 13:35:44'),
(97, 109, '2025-04-12', '2025-04-15', 'annual', 'Auto reason', 'pending', NULL, NULL, '2026-02-08 13:35:44'),
(98, 39, '2025-01-18', '2025-01-21', 'sick', 'Auto reason', 'pending', NULL, NULL, '2026-02-08 13:35:44'),
(99, 35, '2025-03-08', '2025-03-11', 'unpaid', 'Auto reason', 'rejected', NULL, NULL, '2026-02-08 13:35:44'),
(100, 78, '2025-03-12', '2025-03-15', 'personal', 'Auto reason', 'leader_approved', 3, NULL, '2026-02-08 13:35:44'),
(101, 5, '2025-04-10', '2025-04-13', 'annual', 'Auto reason', 'rejected', NULL, NULL, '2026-02-08 13:35:44'),
(102, 110, '2025-04-13', '2025-04-16', 'sick', 'Auto reason', 'leader_approved', NULL, NULL, '2026-02-08 13:35:45'),
(103, 86, '2025-07-06', '2025-07-09', 'sick', 'Auto reason', 'pending', NULL, NULL, '2026-02-08 13:35:45'),
(104, 9, '2025-07-13', '2025-07-16', 'sick', 'Auto reason', 'leader_approved', 3, NULL, '2026-02-08 13:35:45'),
(105, 56, '2025-06-09', '2025-06-12', 'sick', 'Auto reason', 'rejected', NULL, NULL, '2026-02-08 13:35:45'),
(106, 50, '2025-03-23', '2025-03-26', 'personal', 'Auto reason', 'hr_approved', NULL, NULL, '2026-02-08 13:35:45'),
(107, 21, '2025-05-29', '2025-06-01', 'annual', 'Auto reason', 'leader_approved', 3, NULL, '2026-02-08 13:35:45'),
(108, 34, '2025-05-02', '2025-05-05', 'sick', 'Auto reason', 'rejected', NULL, NULL, '2026-02-08 13:35:45'),
(109, 47, '2025-03-26', '2025-03-29', 'annual', 'Auto reason', 'leader_approved', 3, NULL, '2026-02-08 13:35:45'),
(110, 60, '2025-05-01', '2025-05-04', 'sick', 'Auto reason', 'leader_approved', 3, NULL, '2026-02-08 13:35:45'),
(111, 12, '2025-04-03', '2025-04-06', 'sick', 'Auto reason', 'rejected', NULL, NULL, '2026-02-08 13:35:45'),
(112, 61, '2025-05-18', '2025-05-21', 'annual', 'Auto reason', 'leader_approved', 3, NULL, '2026-02-08 13:35:45'),
(113, 73, '2025-03-26', '2025-03-29', 'personal', 'Auto reason', 'rejected', NULL, NULL, '2026-02-08 13:35:45'),
(114, 86, '2025-02-12', '2025-02-15', 'unpaid', 'Auto reason', 'leader_approved', 3, NULL, '2026-02-08 13:35:45'),
(115, 85, '2025-06-27', '2025-06-30', 'sick', 'Auto reason', 'pending', NULL, NULL, '2026-02-08 13:35:45'),
(116, 52, '2025-01-11', '2025-01-14', 'sick', 'Auto reason', 'leader_approved', 3, NULL, '2026-02-08 13:35:45'),
(117, 104, '2025-04-20', '2025-04-23', 'annual', 'Auto reason', 'pending', NULL, NULL, '2026-02-08 13:35:45'),
(118, 87, '2025-07-16', '2025-07-19', 'sick', 'Auto reason', 'pending', NULL, NULL, '2026-02-08 13:35:45'),
(119, 47, '2025-06-14', '2025-06-17', 'annual', 'Auto reason', 'leader_approved', 3, NULL, '2026-02-08 13:35:45'),
(120, 26, '2025-01-31', '2025-02-03', 'annual', 'Auto reason', 'pending', NULL, NULL, '2026-02-08 13:35:45'),
(121, 99, '2025-03-06', '2025-03-09', 'unpaid', 'Auto reason', 'pending', NULL, NULL, '2026-02-08 13:35:45'),
(122, 123, '2025-06-21', '2025-06-24', 'sick', 'Auto reason', 'pending', NULL, NULL, '2026-02-08 13:35:45'),
(123, 112, '2025-06-14', '2025-06-17', 'personal', 'Auto reason', 'leader_approved', 3, NULL, '2026-02-08 13:35:45'),
(124, 48, '2025-01-02', '2025-01-05', 'annual', 'Auto reason', 'rejected', NULL, NULL, '2026-02-08 13:35:45'),
(125, 92, '2025-04-13', '2025-04-16', 'annual', 'Auto reason', 'leader_approved', 3, NULL, '2026-02-08 13:35:45'),
(126, 109, '2025-01-24', '2025-01-27', 'personal', 'Auto reason', 'rejected', NULL, NULL, '2026-02-08 13:35:45'),
(127, 39, '2025-07-05', '2025-07-08', 'annual', 'Auto reason', 'leader_approved', 3, NULL, '2026-02-08 13:35:45'),
(128, 44, '2025-05-17', '2025-05-20', 'sick', 'Auto reason', 'pending', NULL, NULL, '2026-02-08 13:35:45'),
(129, 122, '2025-06-30', '2025-07-03', 'sick', 'Auto reason', 'pending', NULL, NULL, '2026-02-08 13:35:45'),
(130, 56, '2025-06-21', '2025-06-24', 'annual', 'Auto reason', 'pending', NULL, NULL, '2026-02-08 13:35:45'),
(131, 86, '2025-02-27', '2025-03-02', 'annual', 'Auto reason', 'pending', NULL, NULL, '2026-02-08 13:35:45');

-- --------------------------------------------------------

--
-- Table structure for table `payroll_inputs`
--

CREATE TABLE `payroll_inputs` (
  `id` int(11) NOT NULL,
  `employee_id` int(11) DEFAULT NULL,
  `month` int(11) DEFAULT NULL,
  `year` int(11) DEFAULT NULL,
  `overtime_hours` decimal(5,2) DEFAULT NULL,
  `bonus` decimal(10,2) DEFAULT NULL,
  `deductions` decimal(10,2) DEFAULT NULL,
  `submitted_by` int(11) DEFAULT NULL,
  `status` enum('pending','approved') DEFAULT 'pending',
  `submitted_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `payroll_inputs`
--

INSERT INTO `payroll_inputs` (`id`, `employee_id`, `month`, `year`, `overtime_hours`, `bonus`, `deductions`, `submitted_by`, `status`, `submitted_at`) VALUES
(4, 1, 5, 2024, 10.00, 12000.00, 0.00, 3, 'pending', '2026-01-03 16:49:16'),
(5, 2, 5, 2024, 5.00, 10000.00, 1000.00, 3, 'approved', '2026-01-03 16:49:16'),
(6, 1, 4, 2024, 8.00, 20000.00, 0.00, 3, 'approved', '2026-01-03 16:49:16'),
(7, 59, 4, 2025, 5.00, 67.00, 27.00, 3, 'approved', '2026-02-08 13:36:05'),
(8, 86, 12, 2025, 7.00, 6.00, 71.00, 3, 'pending', '2026-02-08 13:36:05'),
(9, 41, 12, 2025, 10.00, 54.00, 80.00, 3, 'pending', '2026-02-08 13:36:05'),
(10, 104, 11, 2025, 3.00, 178.00, 29.00, 3, 'pending', '2026-02-08 13:36:05'),
(11, 113, 3, 2025, 10.00, 23.00, 20.00, 3, 'approved', '2026-02-08 13:36:05'),
(12, 27, 3, 2025, 4.00, 109.00, 34.00, 3, 'pending', '2026-02-08 13:36:05'),
(13, 75, 4, 2025, 8.00, 165.00, 81.00, 3, 'approved', '2026-02-08 13:36:05'),
(14, 85, 3, 2025, 10.00, 60.00, 21.00, 3, 'approved', '2026-02-08 13:36:05'),
(15, 65, 1, 2025, 3.00, 169.00, 58.00, 3, 'pending', '2026-02-08 13:36:05'),
(16, 18, 10, 2025, 1.00, 105.00, 85.00, 3, 'pending', '2026-02-08 13:36:05'),
(17, 57, 8, 2025, 0.00, 145.00, 68.00, 3, 'approved', '2026-02-08 13:36:05'),
(18, 5, 12, 2025, 2.00, 45.00, 46.00, 3, 'pending', '2026-02-08 13:36:05'),
(19, 90, 1, 2025, 2.00, 477.00, 88.00, 3, 'pending', '2026-02-08 13:36:05'),
(20, 60, 4, 2025, 10.00, 25.00, 97.00, 3, 'approved', '2026-02-08 13:36:05'),
(21, 14, 2, 2025, 5.00, 411.00, 94.00, 3, 'approved', '2026-02-08 13:36:05'),
(22, 23, 2, 2025, 8.00, 464.00, 52.00, 3, 'pending', '2026-02-08 13:36:05'),
(23, 83, 10, 2025, 6.00, 338.00, 99.00, 3, 'approved', '2026-02-08 13:36:05'),
(24, 72, 11, 2025, 1.00, 215.00, 57.00, 3, 'pending', '2026-02-08 13:36:05'),
(25, 44, 1, 2025, 1.00, 35.00, 27.00, 3, 'pending', '2026-02-08 13:36:05'),
(26, 87, 4, 2025, 7.00, 481.00, 92.00, 3, 'pending', '2026-02-08 13:36:05'),
(27, 6, 12, 2025, 9.00, 343.00, 51.00, 3, 'pending', '2026-02-08 13:36:05'),
(28, 91, 7, 2025, 10.00, 103.00, 81.00, 3, 'approved', '2026-02-08 13:36:05'),
(29, 23, 5, 2025, 5.00, 299.00, 77.00, 3, 'pending', '2026-02-08 13:36:05'),
(30, 112, 12, 2025, 1.00, 256.00, 24.00, 3, 'approved', '2026-02-08 13:36:05'),
(31, 97, 9, 2025, 4.00, 410.00, 56.00, 3, 'pending', '2026-02-08 13:36:05'),
(32, 88, 3, 2025, 8.00, 78.00, 68.00, 3, 'pending', '2026-02-08 13:36:05'),
(33, 83, 6, 2025, 2.00, 176.00, 48.00, 3, 'pending', '2026-02-08 13:36:05'),
(34, 26, 8, 2025, 5.00, 418.00, 77.00, 3, 'pending', '2026-02-08 13:36:05'),
(35, 40, 7, 2025, 9.00, 262.00, 83.00, 3, 'approved', '2026-02-08 13:36:05'),
(36, 43, 8, 2025, 6.00, 412.00, 41.00, 3, 'approved', '2026-02-08 13:36:05'),
(37, 42, 3, 2025, 3.00, 69.00, 60.00, 3, 'pending', '2026-02-08 13:36:05'),
(38, 115, 11, 2025, 8.00, 318.00, 72.00, 3, 'pending', '2026-02-08 13:36:05'),
(39, 94, 1, 2025, 1.00, 8.00, 95.00, 3, 'pending', '2026-02-08 13:36:05'),
(40, 85, 3, 2025, 2.00, 4.00, 46.00, 3, 'approved', '2026-02-08 13:36:05'),
(41, 41, 12, 2025, 0.00, 66.00, 85.00, 3, 'pending', '2026-02-08 13:36:05'),
(42, 118, 9, 2025, 9.00, 79.00, 61.00, 3, 'pending', '2026-02-08 13:36:05'),
(43, 73, 4, 2025, 3.00, 191.00, 87.00, 3, 'approved', '2026-02-08 13:36:05'),
(44, 79, 7, 2025, 1.00, 280.00, 17.00, 3, 'pending', '2026-02-08 13:36:05'),
(45, 31, 12, 2025, 1.00, 417.00, 84.00, 3, 'pending', '2026-02-08 13:36:05'),
(46, 123, 12, 2025, 4.00, 397.00, 54.00, 3, 'pending', '2026-02-08 13:36:05'),
(47, 106, 3, 2025, 7.00, 158.00, 52.00, 3, 'approved', '2026-02-08 13:36:05'),
(48, 20, 8, 2025, 4.00, 196.00, 32.00, 3, 'approved', '2026-02-08 13:36:05'),
(49, 11, 3, 2025, 7.00, 491.00, 70.00, 3, 'approved', '2026-02-08 13:36:05'),
(50, 117, 7, 2025, 2.00, 249.00, 28.00, 3, 'pending', '2026-02-08 13:36:05'),
(51, 23, 10, 2025, 8.00, 239.00, 16.00, 3, 'approved', '2026-02-08 13:36:05'),
(52, 58, 8, 2025, 6.00, 141.00, 13.00, 3, 'pending', '2026-02-08 13:36:05'),
(53, 40, 6, 2025, 4.00, 236.00, 48.00, 3, 'pending', '2026-02-08 13:36:05'),
(54, 11, 5, 2025, 5.00, 119.00, 42.00, 3, 'pending', '2026-02-08 13:36:05'),
(55, 91, 5, 2025, 2.00, 74.00, 88.00, 3, 'pending', '2026-02-08 13:36:05'),
(56, 6, 4, 2025, 6.00, 315.00, 20.00, 3, 'approved', '2026-02-08 13:36:05'),
(57, 22, 10, 2025, 5.00, 397.00, 98.00, 3, 'pending', '2026-02-08 13:36:05'),
(58, 14, 1, 2025, 3.00, 41.00, 52.00, 3, 'pending', '2026-02-08 13:36:05'),
(59, 60, 5, 2025, 10.00, 331.00, 32.00, 3, 'pending', '2026-02-08 13:36:05'),
(60, 98, 5, 2025, 4.00, 233.00, 75.00, 3, 'pending', '2026-02-08 13:36:05'),
(61, 74, 6, 2025, 1.00, 73.00, 58.00, 3, 'approved', '2026-02-08 13:36:05'),
(62, 90, 1, 2025, 1.00, 58.00, 21.00, 3, 'pending', '2026-02-08 13:36:05'),
(63, 86, 3, 2025, 2.00, 259.00, 30.00, 3, 'pending', '2026-02-08 13:36:05'),
(64, 80, 2, 2025, 6.00, 345.00, 50.00, 3, 'approved', '2026-02-08 13:36:05'),
(65, 107, 4, 2025, 6.00, 222.00, 2.00, 3, 'pending', '2026-02-08 13:36:05'),
(66, 38, 4, 2025, 2.00, 183.00, 64.00, 3, 'approved', '2026-02-08 13:36:05'),
(67, 36, 9, 2025, 2.00, 329.00, 84.00, 3, 'pending', '2026-02-08 13:36:05'),
(68, 17, 7, 2025, 8.00, 377.00, 68.00, 3, 'pending', '2026-02-08 13:36:05'),
(69, 19, 5, 2025, 1.00, 307.00, 12.00, 3, 'approved', '2026-02-08 13:36:05'),
(70, 28, 4, 2025, 5.00, 280.00, 95.00, 3, 'pending', '2026-02-08 13:36:05'),
(71, 32, 2, 2025, 9.00, 401.00, 18.00, 3, 'approved', '2026-02-08 13:36:05'),
(72, 59, 9, 2025, 4.00, 38.00, 50.00, 3, 'pending', '2026-02-08 13:36:05'),
(73, 9, 10, 2025, 8.00, 10.00, 23.00, 3, 'pending', '2026-02-08 13:36:05'),
(74, 55, 3, 2025, 2.00, 488.00, 44.00, 3, 'approved', '2026-02-08 13:36:05'),
(75, 49, 5, 2025, 7.00, 453.00, 22.00, 3, 'approved', '2026-02-08 13:36:05'),
(76, 55, 10, 2025, 5.00, 226.00, 93.00, 3, 'pending', '2026-02-08 13:36:05'),
(77, 27, 5, 2025, 8.00, 294.00, 56.00, 3, 'pending', '2026-02-08 13:36:05'),
(78, 52, 10, 2025, 10.00, 164.00, 31.00, 3, 'pending', '2026-02-08 13:36:05'),
(79, 30, 1, 2025, 1.00, 5.00, 59.00, 3, 'pending', '2026-02-08 13:36:05'),
(80, 118, 4, 2025, 8.00, 339.00, 82.00, 3, 'pending', '2026-02-08 13:36:05'),
(81, 35, 6, 2025, 1.00, 287.00, 87.00, 3, 'pending', '2026-02-08 13:36:05'),
(82, 103, 3, 2025, 9.00, 377.00, 98.00, 3, 'approved', '2026-02-08 13:36:05'),
(83, 111, 11, 2025, 6.00, 219.00, 8.00, 3, 'approved', '2026-02-08 13:36:05'),
(84, 93, 8, 2025, 1.00, 216.00, 18.00, 3, 'approved', '2026-02-08 13:36:05'),
(85, 12, 6, 2025, 8.00, 295.00, 89.00, 3, 'pending', '2026-02-08 13:36:05'),
(86, 44, 12, 2025, 0.00, 242.00, 47.00, 3, 'pending', '2026-02-08 13:36:05'),
(87, 65, 2, 2025, 5.00, 147.00, 1.00, 3, 'approved', '2026-02-08 13:36:05'),
(88, 5, 2, 2025, 8.00, 195.00, 82.00, 3, 'approved', '2026-02-08 13:36:05'),
(89, 65, 11, 2025, 1.00, 240.00, 6.00, 3, 'approved', '2026-02-08 13:36:05'),
(90, 85, 10, 2025, 1.00, 409.00, 71.00, 3, 'approved', '2026-02-08 13:36:05'),
(91, 69, 8, 2025, 5.00, 236.00, 48.00, 3, 'approved', '2026-02-08 13:36:05'),
(92, 65, 9, 2025, 7.00, 142.00, 81.00, 3, 'pending', '2026-02-08 13:36:05'),
(93, 58, 9, 2025, 10.00, 315.00, 87.00, 3, 'pending', '2026-02-08 13:36:05'),
(94, 59, 2, 2025, 3.00, 258.00, 86.00, 3, 'approved', '2026-02-08 13:36:05'),
(95, 16, 11, 2025, 9.00, 172.00, 12.00, 3, 'approved', '2026-02-08 13:36:05'),
(96, 55, 12, 2025, 7.00, 318.00, 64.00, 3, 'pending', '2026-02-08 13:36:05'),
(97, 92, 1, 2025, 5.00, 487.00, 36.00, 3, 'pending', '2026-02-08 13:36:05'),
(98, 93, 8, 2025, 3.00, 14.00, 78.00, 3, 'pending', '2026-02-08 13:36:05'),
(99, 11, 10, 2025, 5.00, 63.00, 45.00, 3, 'pending', '2026-02-08 13:36:05'),
(100, 16, 9, 2025, 2.00, 211.00, 35.00, 3, 'approved', '2026-02-08 13:36:05'),
(101, 36, 3, 2025, 10.00, 294.00, 16.00, 3, 'pending', '2026-02-08 13:36:05'),
(102, 84, 9, 2025, 4.00, 310.00, 72.00, 3, 'pending', '2026-02-08 13:36:05'),
(103, 115, 6, 2025, 7.00, 34.00, 45.00, 3, 'pending', '2026-02-08 13:36:05'),
(104, 112, 12, 2025, 3.00, 491.00, 40.00, 3, 'pending', '2026-02-08 13:36:05'),
(105, 120, 5, 2025, 10.00, 324.00, 43.00, 3, 'pending', '2026-02-08 13:36:05'),
(106, 92, 11, 2025, 8.00, 58.00, 45.00, 3, 'pending', '2026-02-08 13:36:05'),
(107, 63, 1, 2025, 4.00, 78.00, 70.00, 3, 'approved', '2026-02-08 13:36:05'),
(108, 94, 12, 2025, 2.00, 331.00, 26.00, 3, 'approved', '2026-02-08 13:36:05'),
(109, 17, 1, 2025, 10.00, 37.00, 67.00, 3, 'approved', '2026-02-08 13:36:05'),
(110, 22, 10, 2025, 3.00, 449.00, 92.00, 3, 'approved', '2026-02-08 13:36:05'),
(111, 118, 9, 2025, 4.00, 199.00, 80.00, 3, 'approved', '2026-02-08 13:36:05'),
(112, 23, 11, 2025, 4.00, 358.00, 84.00, 3, 'pending', '2026-02-08 13:36:05'),
(113, 72, 9, 2025, 4.00, 119.00, 64.00, 3, 'pending', '2026-02-08 13:36:05'),
(114, 25, 6, 2025, 4.00, 223.00, 77.00, 3, 'pending', '2026-02-08 13:36:05'),
(115, 20, 5, 2025, 2.00, 151.00, 28.00, 3, 'pending', '2026-02-08 13:36:05'),
(116, 89, 10, 2025, 3.00, 494.00, 22.00, 3, 'approved', '2026-02-08 13:36:05'),
(117, 88, 7, 2025, 2.00, 277.00, 43.00, 3, 'pending', '2026-02-08 13:36:05'),
(118, 105, 6, 2025, 8.00, 185.00, 31.00, 3, 'approved', '2026-02-08 13:36:05'),
(119, 74, 4, 2025, 7.00, 165.00, 77.00, 3, 'approved', '2026-02-08 13:36:05'),
(120, 19, 10, 2025, 9.00, 442.00, 66.00, 3, 'pending', '2026-02-08 13:36:05'),
(121, 23, 6, 2025, 7.00, 455.00, 79.00, 3, 'pending', '2026-02-08 13:36:05'),
(122, 68, 8, 2025, 7.00, 361.00, 50.00, 3, 'approved', '2026-02-08 13:36:05'),
(123, 30, 12, 2025, 8.00, 225.00, 79.00, 3, 'pending', '2026-02-08 13:36:05'),
(124, 103, 8, 2025, 5.00, 81.00, 59.00, 3, 'pending', '2026-02-08 13:36:05'),
(125, 28, 2, 2025, 6.00, 100.00, 43.00, 3, 'pending', '2026-02-08 13:36:05'),
(126, 38, 12, 2025, 9.00, 375.00, 51.00, 3, 'pending', '2026-02-08 13:36:05');

-- --------------------------------------------------------

--
-- Table structure for table `projects`
--

CREATE TABLE `projects` (
  `id` int(11) NOT NULL,
  `project_name` varchar(150) DEFAULT NULL,
  `description` text DEFAULT NULL,
  `leader_id` int(11) DEFAULT NULL,
  `start_date` date DEFAULT NULL,
  `end_date` date DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `projects`
--

INSERT INTO `projects` (`id`, `project_name`, `description`, `leader_id`, `start_date`, `end_date`) VALUES
(1, 'Website Revamp', 'Company website upgrade', 3, '2024-01-01', '2024-06-30'),
(2, 'HR System', 'Internal HR portal', 3, '2024-03-10', '2024-09-10'),
(3, 'Mobile App', 'Customer app', 3, '2024-03-01', '2024-09-01'),
(4, 'Cloud Computing', 'Learn the basics of Cloud Computing.', 5, '2026-02-27', '2026-03-05'),
(5, 'Project 1', 'Auto project', NULL, '2025-01-02', '2025-04-02'),
(6, 'Project 2', 'Auto project', NULL, '2025-01-03', '2025-04-03'),
(7, 'Project 3', 'Auto project', NULL, '2025-01-04', '2025-04-04'),
(8, 'Project 4', 'Auto project', NULL, '2025-01-05', '2025-04-05'),
(9, 'Project 5', 'Auto project', NULL, '2025-01-06', '2025-04-06'),
(10, 'Project 6', 'Auto project', NULL, '2025-01-07', '2025-04-07'),
(11, 'Project 7', 'Auto project', NULL, '2025-01-08', '2025-04-08'),
(12, 'Project 8', 'Auto project', NULL, '2025-01-09', '2025-04-09'),
(13, 'Project 9', 'Auto project', NULL, '2025-01-10', '2025-04-10'),
(14, 'Project 10', 'Auto project', NULL, '2025-01-11', '2025-04-11'),
(15, 'Project 11', 'Auto project', NULL, '2025-01-12', '2025-04-12'),
(16, 'Project 12', 'Auto project', NULL, '2025-01-13', '2025-04-13'),
(17, 'Project 13', 'Auto project', NULL, '2025-01-14', '2025-04-14'),
(18, 'Project 14', 'Auto project', NULL, '2025-01-15', '2025-04-15'),
(19, 'Project 15', 'Auto project', NULL, '2025-01-16', '2025-04-16'),
(20, 'Project 16', 'Auto project', NULL, '2025-01-17', '2025-04-17'),
(21, 'Project 17', 'Auto project', NULL, '2025-01-18', '2025-04-18'),
(22, 'Project 18', 'Auto project', NULL, '2025-01-19', '2025-04-19'),
(23, 'Project 19', 'Auto project', NULL, '2025-01-20', '2025-04-20'),
(24, 'Project 20', 'Auto project', NULL, '2025-01-21', '2025-04-21'),
(25, 'Project 21', 'Auto project', NULL, '2025-01-22', '2025-04-22'),
(26, 'Project 22', 'Auto project', NULL, '2025-01-23', '2025-04-23'),
(27, 'Project 23', 'Auto project', NULL, '2025-01-24', '2025-04-24'),
(28, 'Project 24', 'Auto project', NULL, '2025-01-25', '2025-04-25'),
(29, 'Project 25', 'Auto project', NULL, '2025-01-26', '2025-04-26'),
(30, 'Project 26', 'Auto project', NULL, '2025-01-27', '2025-04-27'),
(31, 'Project 27', 'Auto project', NULL, '2025-01-28', '2025-04-28'),
(32, 'Project 28', 'Auto project', NULL, '2025-01-29', '2025-04-29'),
(33, 'Project 29', 'Auto project', NULL, '2025-01-30', '2025-04-30'),
(34, 'Project 30', 'Auto project', NULL, '2025-01-31', '2025-05-01'),
(35, 'Project 31', 'Auto project', NULL, '2025-02-01', '2025-05-02'),
(36, 'Project 32', 'Auto project', NULL, '2025-02-02', '2025-05-03'),
(37, 'Project 33', 'Auto project', NULL, '2025-02-03', '2025-05-04'),
(38, 'Project 34', 'Auto project', NULL, '2025-02-04', '2025-05-05'),
(39, 'Project 35', 'Auto project', NULL, '2025-02-05', '2025-05-06'),
(40, 'Project 36', 'Auto project', NULL, '2025-02-06', '2025-05-07'),
(41, 'Project 37', 'Auto project', NULL, '2025-02-07', '2025-05-08'),
(42, 'Project 38', 'Auto project', NULL, '2025-02-08', '2025-05-09'),
(43, 'Project 39', 'Auto project', NULL, '2025-02-09', '2025-05-10'),
(44, 'Project 40', 'Auto project', NULL, '2025-02-10', '2025-05-11'),
(45, 'Project 41', 'Auto project', NULL, '2025-02-11', '2025-05-12'),
(46, 'Project 42', 'Auto project', NULL, '2025-02-12', '2025-05-13'),
(47, 'Project 43', 'Auto project', NULL, '2025-02-13', '2025-05-14'),
(48, 'Project 44', 'Auto project', NULL, '2025-02-14', '2025-05-15'),
(49, 'Project 45', 'Auto project', NULL, '2025-02-15', '2025-05-16'),
(50, 'Project 46', 'Auto project', NULL, '2025-02-16', '2025-05-17'),
(51, 'Project 47', 'Auto project', NULL, '2025-02-17', '2025-05-18'),
(52, 'Project 48', 'Auto project', NULL, '2025-02-18', '2025-05-19'),
(53, 'Project 49', 'Auto project', NULL, '2025-02-19', '2025-05-20'),
(54, 'Project 50', 'Auto project', NULL, '2025-02-20', '2025-05-21'),
(55, 'Project 51', 'Auto project', NULL, '2025-02-21', '2025-05-22'),
(56, 'Project 52', 'Auto project', NULL, '2025-02-22', '2025-05-23'),
(57, 'Project 53', 'Auto project', NULL, '2025-02-23', '2025-05-24'),
(58, 'Project 54', 'Auto project', NULL, '2025-02-24', '2025-05-25'),
(59, 'Project 55', 'Auto project', NULL, '2025-02-25', '2025-05-26'),
(60, 'Project 56', 'Auto project', NULL, '2025-02-26', '2025-05-27'),
(61, 'Project 57', 'Auto project', NULL, '2025-02-27', '2025-05-28'),
(62, 'Project 58', 'Auto project', NULL, '2025-02-28', '2025-05-29'),
(63, 'Project 59', 'Auto project', NULL, '2025-03-01', '2025-05-30'),
(64, 'Project 60', 'Auto project', NULL, '2025-03-02', '2025-05-31'),
(65, 'Project 61', 'Auto project', NULL, '2025-03-03', '2025-06-01'),
(66, 'Project 62', 'Auto project', NULL, '2025-03-04', '2025-06-02'),
(67, 'Project 63', 'Auto project', NULL, '2025-03-05', '2025-06-03'),
(68, 'Project 64', 'Auto project', NULL, '2025-03-06', '2025-06-04'),
(69, 'Project 65', 'Auto project', NULL, '2025-03-07', '2025-06-05'),
(70, 'Project 66', 'Auto project', NULL, '2025-03-08', '2025-06-06'),
(71, 'Project 67', 'Auto project', NULL, '2025-03-09', '2025-06-07'),
(72, 'Project 68', 'Auto project', NULL, '2025-03-10', '2025-06-08'),
(73, 'Project 69', 'Auto project', NULL, '2025-03-11', '2025-06-09'),
(74, 'Project 70', 'Auto project', NULL, '2025-03-12', '2025-06-10'),
(75, 'Project 71', 'Auto project', NULL, '2025-03-13', '2025-06-11'),
(76, 'Project 72', 'Auto project', NULL, '2025-03-14', '2025-06-12'),
(77, 'Project 73', 'Auto project', NULL, '2025-03-15', '2025-06-13'),
(78, 'Project 74', 'Auto project', NULL, '2025-03-16', '2025-06-14'),
(79, 'Project 75', 'Auto project', NULL, '2025-03-17', '2025-06-15'),
(80, 'Project 76', 'Auto project', NULL, '2025-03-18', '2025-06-16'),
(82, 'Project 78', 'Auto project', NULL, '2025-03-20', '2025-06-18'),
(83, 'Project 79', 'Auto project', NULL, '2025-03-21', '2025-06-19'),
(84, 'Project 80', 'Auto project', NULL, '2025-03-22', '2025-06-20'),
(85, 'Project 81', 'Auto project', NULL, '2025-03-23', '2025-06-21'),
(86, 'Project 82', 'Auto project', NULL, '2025-03-24', '2025-06-22'),
(87, 'Project 83', 'Auto project', NULL, '2025-03-25', '2025-06-23'),
(88, 'Project 84', 'Auto project', NULL, '2025-03-26', '2025-06-24'),
(89, 'Project 85', 'Auto project', NULL, '2025-03-27', '2025-06-25'),
(90, 'Project 86', 'Auto project', NULL, '2025-03-28', '2025-06-26'),
(91, 'Project 87', 'Auto project', NULL, '2025-03-29', '2025-06-27'),
(92, 'Project 88', 'Auto project', NULL, '2025-03-30', '2025-06-28'),
(93, 'Project 89', 'Auto project', NULL, '2025-03-31', '2025-06-29'),
(94, 'Project 90', 'Auto project', NULL, '2025-04-01', '2025-06-30'),
(95, 'Project 91', 'Auto project', NULL, '2025-04-02', '2025-07-01'),
(96, 'Project 92', 'Auto project', NULL, '2025-04-03', '2025-07-02'),
(97, 'Project 93', 'Auto project', NULL, '2025-04-04', '2025-07-03'),
(98, 'Project 94', 'Auto project', NULL, '2025-04-05', '2025-07-04'),
(99, 'Project 95', 'Auto project', NULL, '2025-04-06', '2025-07-05'),
(100, 'Project 96', 'Auto project', NULL, '2025-04-07', '2025-07-06'),
(101, 'Project 97', 'Auto project', NULL, '2025-04-08', '2025-07-07'),
(102, 'Project 98', 'Auto project', 9, '2025-04-09', '2025-07-08'),
(103, 'Project 99', 'Auto project', NULL, '2025-04-10', '2025-07-09'),
(104, 'Project 100', 'Auto project', NULL, '2025-04-11', '2025-07-10'),
(105, 'Project 101', 'Auto project', NULL, '2025-04-12', '2025-07-11'),
(106, 'Project 102', 'Auto project', NULL, '2025-04-13', '2025-07-12'),
(107, 'Project 103', 'Auto project', NULL, '2025-04-14', '2025-07-13'),
(108, 'Project 104', 'Auto project', NULL, '2025-04-15', '2025-07-14'),
(109, 'Project 105', 'Auto project', NULL, '2025-04-16', '2025-07-15'),
(110, 'Project 106', 'Auto project', NULL, '2025-04-17', '2025-07-16'),
(111, 'Project 107', 'Auto project', NULL, '2025-04-18', '2025-07-17'),
(112, 'Project 108', 'Auto project', NULL, '2025-04-19', '2025-07-18'),
(113, 'Project 109', 'Auto project', NULL, '2025-04-20', '2025-07-19'),
(114, 'Project 110', 'Auto project', NULL, '2025-04-21', '2025-07-20'),
(115, 'Project 111', 'Auto project', NULL, '2025-04-22', '2025-07-21'),
(116, 'Project 112', 'Auto project', NULL, '2025-04-23', '2025-07-22'),
(117, 'Project 113', 'Auto project', NULL, '2025-04-24', '2025-07-23'),
(118, 'Project 114', 'Auto project', NULL, '2025-04-25', '2025-07-24'),
(119, 'Project 115', 'Auto project', 13, '2025-04-26', '2025-07-25'),
(120, 'Project 116', 'Auto project', NULL, '2025-04-27', '2025-07-26'),
(121, 'Project 117', 'Auto project', NULL, '2025-04-28', '2025-07-27'),
(122, 'Project 118', 'Auto project', 13, '2025-04-29', '2025-07-28'),
(123, 'Project 119', 'Auto project', 5, '2025-04-30', '2025-07-29'),
(124, 'Project 120', 'Auto project', 5, '2025-05-01', '2025-07-30');

-- --------------------------------------------------------

--
-- Table structure for table `roles`
--

CREATE TABLE `roles` (
  `id` int(11) NOT NULL,
  `role_name` varchar(50) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `roles`
--

INSERT INTO `roles` (`id`, `role_name`) VALUES
(5, 'Admin'),
(2, 'Employee'),
(1, 'Guest'),
(4, 'HR'),
(3, 'ProjectLeader');

-- --------------------------------------------------------

--
-- Table structure for table `salary_slips`
--

CREATE TABLE `salary_slips` (
  `id` int(11) NOT NULL,
  `employee_id` int(11) DEFAULT NULL,
  `month` int(11) DEFAULT NULL,
  `year` int(11) DEFAULT NULL,
  `base_salary` decimal(10,2) DEFAULT NULL,
  `overtime_pay` decimal(10,2) DEFAULT NULL,
  `bonus` decimal(10,2) DEFAULT NULL,
  `deductions` decimal(10,2) DEFAULT NULL,
  `net_salary` decimal(10,2) DEFAULT NULL,
  `generated_by` int(11) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `salary_slips`
--

INSERT INTO `salary_slips` (`id`, `employee_id`, `month`, `year`, `base_salary`, `overtime_pay`, `bonus`, `deductions`, `net_salary`, `generated_by`, `created_at`) VALUES
(4, 1, 4, 2024, 95000.00, 12000.00, 12000.00, 0.00, 100000.00, 1, '2026-01-03 16:58:43'),
(5, 2, 5, 2024, 40000.00, 12000.00, 10000.00, 1000.00, 50000.00, 2, '2026-01-03 16:58:43'),
(6, 1, 3, 2024, 90000.00, 12000.00, 20000.00, 0.00, 100000.00, 2, '2026-01-03 16:58:43');

-- --------------------------------------------------------

--
-- Table structure for table `tasks`
--

CREATE TABLE `tasks` (
  `id` int(11) NOT NULL,
  `project_id` int(11) DEFAULT NULL,
  `assigned_to` int(11) DEFAULT NULL,
  `created_by` int(11) DEFAULT NULL,
  `title` varchar(150) DEFAULT NULL,
  `description` text DEFAULT NULL,
  `status` enum('todo','in_progress','done') DEFAULT 'todo',
  `deadline` date DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `tasks`
--

INSERT INTO `tasks` (`id`, `project_id`, `assigned_to`, `created_by`, `title`, `description`, `status`, `deadline`, `created_at`) VALUES
(7, 1, 1, 3, 'Design Home Page', 'Create UI for homepage', 'todo', '2024-06-01', '2026-01-03 16:34:10'),
(8, 2, 2, 3, 'Design HR Forms', 'Create a leave form UI', 'done', '2026-01-30', '2026-01-03 16:34:10'),
(10, 3, 1, 1, 'Lets Chat', 'Let\'s focus on this thrilling journey until the end.', 'done', '2026-03-08', '2026-02-03 17:27:09'),
(13, 8, 8, 3, 'Task 4: Project 4', 'Auto-generated task 4 for Project 4.', 'done', '2026-03-11', '2026-02-08 13:50:04'),
(14, 9, 9, 3, 'Task 5: Project 5', 'Auto-generated task 5 for Project 5.', 'in_progress', '2026-03-12', '2026-02-08 13:50:04'),
(15, 10, 10, 3, 'Task 6: Project 6', 'Auto-generated task 6 for Project 6.', 'done', '2026-03-13', '2026-02-08 13:50:04'),
(16, 11, 11, 3, 'Task 7: Project 7', 'Auto-generated task 7 for Project 7.', 'todo', '2026-03-14', '2026-02-08 13:50:04'),
(17, 12, 12, 3, 'Task 8: Project 8', 'Auto-generated task 8 for Project 8.', 'done', '2026-03-15', '2026-02-08 13:50:04'),
(18, 13, 13, 3, 'Task 9: Project 9', 'Auto-generated task 9 for Project 9.', 'done', '2026-03-16', '2026-02-08 13:50:04'),
(19, 14, 14, 3, 'Task 10: Project 10', 'Auto-generated task 10 for Project 10.', 'in_progress', '2026-03-17', '2026-02-08 13:50:04'),
(20, 15, 15, 3, 'Task 11: Project 11', 'Auto-generated task 11 for Project 11.', 'done', '2026-03-18', '2026-02-08 13:50:04'),
(21, 16, 16, 3, 'Task 12: Project 12', 'Auto-generated task 12 for Project 12.', 'done', '2026-03-19', '2026-02-08 13:50:04'),
(23, 18, 18, 3, 'Task 14: Project 14', 'Auto-generated task 14 for Project 14.', 'todo', '2026-03-21', '2026-02-08 13:50:04'),
(24, 19, 19, 3, 'Task 15: Project 15', 'Auto-generated task 15 for Project 15.', 'done', '2026-03-22', '2026-02-08 13:50:04'),
(25, 20, 20, 3, 'Task 16: Project 16', 'Auto-generated task 16 for Project 16.', 'todo', '2026-03-23', '2026-02-08 13:50:04'),
(26, 21, 21, 3, 'Task 17: Project 17', 'Auto-generated task 17 for Project 17.', 'in_progress', '2026-03-24', '2026-02-08 13:50:04'),
(27, 22, 22, 3, 'Task 18: Project 18', 'Auto-generated task 18 for Project 18.', 'done', '2026-03-25', '2026-02-08 13:50:04'),
(28, 23, 23, 3, 'Task 19: Project 19', 'Auto-generated task 19 for Project 19.', 'done', '2026-03-26', '2026-02-08 13:50:04'),
(29, 24, 24, 3, 'Task 20: Project 20', 'Auto-generated task 20 for Project 20.', 'in_progress', '2026-03-27', '2026-02-08 13:50:04'),
(30, 25, 25, 3, 'Task 21: Project 21', 'Auto-generated task 21 for Project 21.', 'done', '2026-03-28', '2026-02-08 13:50:04'),
(31, 26, 26, 3, 'Task 22: Project 22', 'Auto-generated task 22 for Project 22.', 'todo', '2026-03-29', '2026-02-08 13:50:04'),
(32, 27, 3, 3, 'Task 23: Project 23', 'Auto-generated task 23 for Project 23.', 'in_progress', '2026-03-30', '2026-02-08 13:50:04'),
(33, 28, 5, 3, 'Task 24: Project 24', 'Auto-generated task 24 for Project 24.', 'done', '2026-03-31', '2026-02-08 13:50:04'),
(34, 29, 1, 3, 'Task 25: Project 25', 'Auto-generated task 25 for Project 25.', 'todo', '2026-04-01', '2026-02-08 13:50:04'),
(35, 30, 5, 3, 'Task 26: Project 26', 'Auto-generated task 26 for Project 26.', 'in_progress', '2026-04-02', '2026-02-08 13:50:04'),
(36, 31, 5, 3, 'Task 27: Project 27', 'Auto-generated task 27 for Project 27.', 'done', '2026-04-03', '2026-02-08 13:50:04'),
(37, 32, 32, 3, 'Task 28: Project 28', 'Auto-generated task 28 for Project 28.', 'done', '2026-04-04', '2026-02-08 13:50:04'),
(38, 33, 33, 3, 'Task 29: Project 29', 'Auto-generated task 29 for Project 29.', 'in_progress', '2026-04-05', '2026-02-08 13:50:04'),
(39, 34, 34, 3, 'Task 30: Project 30', 'Auto-generated task 30 for Project 30.', 'done', '2026-04-06', '2026-02-08 13:50:04'),
(40, 35, 35, 3, 'Task 31: Project 31', 'Auto-generated task 31 for Project 31.', 'todo', '2026-04-07', '2026-02-08 13:50:04'),
(41, 36, 36, 3, 'Task 32: Project 32', 'Auto-generated task 32 for Project 32.', 'in_progress', '2026-04-08', '2026-02-08 13:50:04'),
(42, 37, 37, 3, 'Task 33: Project 33', 'Auto-generated task 33 for Project 33.', 'done', '2026-04-09', '2026-02-08 13:50:04'),
(43, 38, 38, 3, 'Task 34: Project 34', 'Auto-generated task 34 for Project 34.', 'todo', '2026-04-10', '2026-02-08 13:50:04'),
(44, 39, 4, 3, 'Task 35: Project 35', 'Auto-generated task 35 for Project 35.', 'in_progress', '2026-04-11', '2026-02-08 13:50:04'),
(45, 40, 4, 3, 'Task 36: Project 36', 'Auto-generated task 36 for Project 36.', 'done', '2026-04-12', '2026-02-08 13:50:04'),
(46, 41, 5, 3, 'Task 37: Project 37', 'Auto-generated task 37 for Project 37.', 'todo', '2026-04-13', '2026-02-08 13:50:04'),
(47, 42, 42, 3, 'Task 38: Project 38', 'Auto-generated task 38 for Project 38.', 'done', '2026-04-14', '2026-02-08 13:50:04'),
(48, 43, 43, 3, 'Task 39: Project 39', 'Auto-generated task 39 for Project 39.', 'done', '2026-04-15', '2026-02-08 13:50:04'),
(49, 44, 4, 3, 'Task 40: Project 40', 'Auto-generated task 40 for Project 40.', 'in_progress', '2026-04-16', '2026-02-08 13:50:04');

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `id` int(11) NOT NULL,
  `full_name` varchar(100) NOT NULL,
  `email` varchar(100) NOT NULL,
  `password_hash` varchar(255) NOT NULL,
  `role_id` int(11) NOT NULL,
  `status` enum('active','disabled') DEFAULT 'active',
  `profile_photo` varchar(255) DEFAULT NULL,
  `password_reset_token` varchar(255) DEFAULT NULL,
  `password_reset_expires_at` datetime DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`id`, `full_name`, `email`, `password_hash`, `role_id`, `status`, `profile_photo`, `password_reset_token`, `password_reset_expires_at`, `created_at`) VALUES
(1, 'Admin User', 'admin@spycray.com', '$2y$10$2GWFIRJ33YWVIijMKuCSIu8IcD4k6M1u4JNaLw0qfsSVXInmMo8Ni\n', 5, 'active', 'assets/avatars/user_1_3c280c7edaec6d8f.png', NULL, NULL, '2026-01-03 15:51:13'),
(2, 'HR Manager', 'hr@spycray.com', '$2y$10$uXZYvYDlvekVjyP4P162E.8JZCbeV6oq7pULUrF6hjFK2hVkFi.pe\r\n', 4, 'active', 'assets/avatars/user_2_3bd7f4bb74f90212.png', NULL, NULL, '2026-01-03 15:51:13'),
(3, 'Project Leader', 'leader@spycray.com', '$2y$10$8/amw5dJ4dtqOmGI9awKZuAo.UCu4MlB7blwV3YlQq.q3zwvH3JsS\r\n', 3, 'active', 'assets/avatars/user_3_9559e2d983f826f8.png', NULL, NULL, '2026-01-03 15:51:13'),
(4, 'Employee', 'employee@spycray.com', '$2y$10$tfqZlGPYo5J01b80VU/kJOZUugNqXE/xS7tgnaBS0jOy39yNJ2eS2', 2, 'active', 'assets/avatars/user_4_b54684fa037577cb.jpg', NULL, NULL, '2026-01-05 12:33:32'),
(5, 'Benevolent Eager', 'benevolenteager@gmail.com', '$2y$10$4Y275/M.hfmV7WQ1l7zQdera8ye1FeWEgbUcTKr.aRyKVcbdPlrLO', 2, 'active', 'assets/avatars/user_5_17bc450c2e85e8da.jpg', '9390de8b102f6e38a2c2a220b9a433bdd7e7e9fd4b534a2ad3d8d8b4c6603d7a', '2026-02-05 15:46:52', '2026-02-04 09:00:00'),
(6, 'Employee 1001', 'user1001@company.com', '$2y$10$Z6XzIPrqnNTLxZTrLsCsWOQFYRcqvjdD0SNGY1cA1BSAqR2ZJaMva', 2, 'active', NULL, NULL, NULL, '2026-02-08 13:32:31'),
(7, 'Employee 1002', 'user1002@company.com', '$2y$10$CJDfMguX8o9vvz3iyf5vH.u61DKPeuGS0HIFszZcNNy30J9G8ShO2', 2, 'disabled', NULL, NULL, NULL, '2026-02-08 13:32:31'),
(8, 'Employee 1003', 'user1003@company.com', '$2y$10$txzdEWDJtscLsp05u4wgROXYDmF2PshLFlAq2suzsqjeAmbOil73e', 2, 'active', NULL, NULL, NULL, '2026-02-08 13:32:31'),
(9, 'Employee 1004', 'user1004@company.com', '$2y$10$20C7FhKfsBXD9WtFn/kYBujDL0MLlHJPvtMjE9saLtQC2XzyuOmTO', 2, 'active', NULL, NULL, NULL, '2026-02-08 13:32:32'),
(10, 'Employee 1005', 'user1005@company.com', '$2y$10$W609CYv569A9mwjxJE6b7e/orUvox61eQu3ijgAjv7KWfKAkFtS3u', 2, 'active', NULL, NULL, NULL, '2026-02-08 13:32:32'),
(11, 'Employee 1006', 'user1006@company.com', '$2y$10$DreAE7w8mratcgDuHR0ELOTWeGTJ0jukCmuESNtAkgne6pOTy8vhK', 2, 'active', NULL, NULL, NULL, '2026-02-08 13:32:32'),
(12, 'Employee 1007', 'user1007@company.com', '$2y$10$c4msTm03cbbg6ZsQ5iwS8uAkIkIkp4rQyJQFWBlhoN0x1rG8f4XG.', 2, 'active', NULL, NULL, NULL, '2026-02-08 13:32:32'),
(13, 'Employee 1008', 'user1008@company.com', '$2y$10$8FjokqXFqEwcSEzRgBZsMeBtP8IqOM8ntLPcs6qFcgXQv293qhLem', 2, 'active', NULL, NULL, NULL, '2026-02-08 13:32:32'),
(14, 'Employee 1009', 'user1009@company.com', '$2y$10$9hy9hOrnzmDWafvwADeJI.HquUkBT89NLHxiyPUSe3Eo74BxjpMlS', 2, 'active', NULL, NULL, NULL, '2026-02-08 13:32:32'),
(15, 'Employee 1010', 'user1010@company.com', '$2y$10$kiPso.r8XIphmaFgiSvXHuyQq/xrt3wfOycSBWx40lZlC5D1Rgb/m', 2, 'disabled', NULL, NULL, NULL, '2026-02-08 13:32:32'),
(16, 'Employee 1011', 'user1011@company.com', '$2y$10$7avesZSSKlc7GNnBS/QP0uBjg8.31GbWzBn4IF8l1q58jdfva4LpO', 2, 'active', NULL, NULL, NULL, '2026-02-08 13:32:32'),
(17, 'Employee 1012', 'user1012@company.com', '$2y$10$VFwneN8KAztQxxk39kR8xe70JvM4pmCiYskDipLLmdEnCXNjThlXm', 2, 'disabled', NULL, NULL, NULL, '2026-02-08 13:32:32'),
(18, 'Employee 1013', 'user1013@company.com', '$2y$10$1OPOlzLGBs0uooyuaWAeleQm1tM5AtxdZRbIwA1TCHm93cnTgoAKa', 2, 'active', NULL, NULL, NULL, '2026-02-08 13:32:32'),
(19, 'Employee 1014', 'user1014@company.com', '$2y$10$OMR/40g6ODpiLLJZrlLM1efY9q.gAj2kqIID0cZaa2Hh817rK0Dxa', 2, 'disabled', NULL, NULL, NULL, '2026-02-08 13:32:32'),
(20, 'Employee 1015', 'user1015@company.com', '$2y$10$hSKW7LqtK/mLsa2vCmfEs.FztWjRlNUzYqI3uHV3v/QYIj3Nwfqk6', 2, 'disabled', NULL, NULL, NULL, '2026-02-08 13:32:32'),
(21, 'Employee 1016', 'user1016@company.com', '$2y$10$M0Do2dad128jAuWVd2pIE.bhyta88Q9EvMVtTnlARBJUohPUiHWo.', 2, 'disabled', NULL, NULL, NULL, '2026-02-08 13:32:32'),
(22, 'Employee 1017', 'user1017@company.com', '$2y$10$xhXroYzKxYPxIZOMSK/ZIefjYPUthDoJlT5pNBRSc7R5bJYy676d6', 2, 'disabled', NULL, NULL, NULL, '2026-02-08 13:32:33'),
(23, 'Employee 1018', 'user1018@company.com', '$2y$10$uiEXpx3RJeVArs1B38LF0./qwmZf8mJSoNaFbReJKUXXJZu76O12y', 2, 'disabled', NULL, NULL, NULL, '2026-02-08 13:32:33'),
(24, 'Employee 1019', 'user1019@company.com', '$2y$10$/7pWUzkbDaPLcjtm5ZdLMekR9ph4/hDziccunHDLPltTIsLYQlNxK', 2, 'disabled', NULL, NULL, NULL, '2026-02-08 13:32:33'),
(25, 'Employee 1020', 'user1020@company.com', '$2y$10$/UCPAf9nx2eG.qAbrtRSJebkyCtRpuuq.o5YuPtKpmYfny3VScjFy', 2, 'disabled', NULL, NULL, NULL, '2026-02-08 13:32:33'),
(26, 'Employee 1021', 'user1021@company.com', '$2y$10$DISMtmvJSosGFVjWCNmUO.szPcY0qoh7eWgAB4iUmk/djHXIGyvjC', 2, 'active', NULL, NULL, NULL, '2026-02-08 13:32:33'),
(27, 'Employee 1022', 'user1022@company.com', '$2y$10$ibSZqsipaLJ4TC9KWb3HcuKZzy7raepRbrG01Pa/pb5ARvo/RoXCO', 2, 'active', NULL, NULL, NULL, '2026-02-08 13:32:33'),
(28, 'Employee 1023', 'user1023@company.com', '$2y$10$D48DxTw5v1fyZCLQrYdRG.F1lKq1fmDHe5FMG1XvUDnbTn2Gh7qne', 2, 'disabled', NULL, NULL, NULL, '2026-02-08 13:32:33'),
(29, 'Employee 1024', 'user1024@company.com', '$2y$10$7M4xUC7jTqpdTLceGuRKg.mwC9wDOSQeIq08roFhJu0MnNWbUh8pW', 2, 'disabled', NULL, NULL, NULL, '2026-02-08 13:32:33'),
(30, 'Employee 1025', 'user1025@company.com', '$2y$10$yQpNy3ryscWdYAK4k1fr9OFS1QyX//R6ZuV1JSzJdRNrHZqtOJNfS', 2, 'active', NULL, NULL, NULL, '2026-02-08 13:32:33'),
(31, 'Employee 1026', 'user1026@company.com', '$2y$10$qv00Ct8MqFY0MkqX6oqruOiMXzRUQU3BLafNKLYl.C6luf0XHg65a', 2, 'active', NULL, NULL, NULL, '2026-02-08 13:32:33'),
(32, 'Employee 1027', 'user1027@company.com', '$2y$10$nNwquQ71E5AMvPmcVYwvpuQWYrgNsBpYpoDMQyfW9tkIM1arYj106', 2, 'active', NULL, NULL, NULL, '2026-02-08 13:32:33'),
(33, 'Employee 1028', 'user1028@company.com', '$2y$10$lYu/RrPewdfwvp98Pb02eug300Vvq3Vpqs4eXoEQjVTYTnAYj9osC', 2, 'disabled', NULL, NULL, NULL, '2026-02-08 13:32:33'),
(34, 'Employee 1029', 'user1029@company.com', '$2y$10$61Q4xT8BBHzuwjCWfdVfNeT6cWVf.IqtSbSVvKi.tWQBDCPsh0yBC', 2, 'active', NULL, NULL, NULL, '2026-02-08 13:32:34'),
(35, 'Employee 1030', 'user1030@company.com', '$2y$10$g00d9Sg7c5jiVFI24tLsF.aUneBKOyz0TBOKewArp.yBSubI.KNXW', 2, 'active', NULL, NULL, NULL, '2026-02-08 13:32:34'),
(36, 'Employee 1031', 'user1031@company.com', '$2y$10$vzV92KS1t/YKyOWDvamf0uoDXpVXLLJ1orbvgJL9ipZDP3QNqOKQy', 2, 'active', NULL, NULL, NULL, '2026-02-08 13:32:34'),
(37, 'Employee 1032', 'user1032@company.com', '$2y$10$syNWl2iQoodSxSZSdfGUEevt23LEEpAAazh23.Fr2NHodoQOZBira', 2, 'active', NULL, NULL, NULL, '2026-02-08 13:32:34'),
(38, 'Employee 1033', 'user1033@company.com', '$2y$10$14BOOJGZMLYU.ysUHUJO9evAJ6EH6.fSrNWhAI901wtuJbb.5F9AK', 2, 'disabled', NULL, NULL, NULL, '2026-02-08 13:32:34'),
(39, 'Employee 1034', 'user1034@company.com', '$2y$10$UZuwH7adGtoonFY0WRb1NuymvsiBKw430EQARUFKZoVRWyqOcUP6S', 2, 'disabled', NULL, NULL, NULL, '2026-02-08 13:32:34'),
(40, 'Employee 1035', 'user1035@company.com', '$2y$10$4zXG5nPxpQPU1sJhMoxj3uruP/iBK6JwQJ7LoayXzuYCObCd/1UIC', 2, 'active', NULL, NULL, NULL, '2026-02-08 13:32:34'),
(41, 'Employee 1036', 'user1036@company.com', '$2y$10$gNJ5v0o5o6aw1qTlDR86cuCHwdez00KN2Dgr8ZS4WIB1rXENHnYei', 2, 'active', NULL, NULL, NULL, '2026-02-08 13:32:34'),
(42, 'Employee 1037', 'user1037@company.com', '$2y$10$m5h7j2GAaO2FDAAN5Ta7ru0Sw4OKhjMI6WqFqHWvFmgPvZDNpqxke', 2, 'disabled', NULL, NULL, NULL, '2026-02-08 13:32:34'),
(43, 'Employee 1038', 'user1038@company.com', '$2y$10$cWwBpP0RgiusNHXI8Lg.ceRQxCaDOEOPlE4X.jOIckRNcO/ift.XW', 2, 'active', NULL, NULL, NULL, '2026-02-08 13:32:34'),
(44, 'Employee 1039', 'user1039@company.com', '$2y$10$XLJ.STSxXbY2Nn3g9LUbl.t5Fc9aGm83fDc1hZXvRVkcV4CU3bzLi', 2, 'active', NULL, NULL, NULL, '2026-02-08 13:32:34'),
(45, 'Employee 1040', 'user1040@company.com', '$2y$10$SaCFd.vk3Ml8bK2v6AVgRegJhtQOgdMx5m5VC/gc1hTOWMSHGHjSi', 2, 'disabled', NULL, NULL, NULL, '2026-02-08 13:32:34'),
(46, 'Employee 1041', 'user1041@company.com', '$2y$10$/yitQw0a3ji.AqycoBtt9eZAspPR5zJAQGvOTWy1DdXoBUROIqZOC', 2, 'active', NULL, NULL, NULL, '2026-02-08 13:32:35'),
(47, 'Employee 1042', 'user1042@company.com', '$2y$10$fE7KxleDI/FmrMK1wcOWFOGYDQ4dnqAZn6ljub.RUhyG16zQbuzvq', 2, 'active', NULL, NULL, NULL, '2026-02-08 13:32:35'),
(48, 'Employee 1043', 'user1043@company.com', '$2y$10$i3kjbHc14aK2AQ3iGqdF9erw8w3TGEIHwG7cNqpcNQ7pEAcxRiAMa', 2, 'active', NULL, NULL, NULL, '2026-02-08 13:32:35'),
(49, 'Employee 1044', 'user1044@company.com', '$2y$10$t.j/X6dceuDgbz/MLJD2FeO2BAH/YmB..h33pzx8I4cRU6Xhw0M86', 2, 'disabled', NULL, NULL, NULL, '2026-02-08 13:32:35'),
(50, 'Employee 1045', 'user1045@company.com', '$2y$10$/MkXs8UQzsvG7L64rOu9pO48nDc24Nkvz/Gqj3nSmV8g8qM6Ao58u', 2, 'active', NULL, NULL, NULL, '2026-02-08 13:32:35'),
(51, 'Employee 1046', 'user1046@company.com', '$2y$10$jyQCe8ButwM7b3VUPkSuNOVIoTMPjE4xtZnurMGzIORe9v5GEpMvG', 2, 'active', NULL, NULL, NULL, '2026-02-08 13:32:35'),
(52, 'Employee 1047', 'user1047@company.com', '$2y$10$oAsVmcnVpN6aOcDj282.pewcH0cUDKa2Cw7Ddby8DvskStsL.Ok3W', 2, 'active', NULL, NULL, NULL, '2026-02-08 13:32:35'),
(53, 'Employee 1048', 'user1048@company.com', '$2y$10$i989X242oT99q.ZPc/YdxeWMX5zbd5ll/.TCIiVGo8JU5OH3Oo2iq', 2, 'active', NULL, NULL, NULL, '2026-02-08 13:32:35'),
(54, 'Employee 1049', 'user1049@company.com', '$2y$10$ON8LK1u9HiS69cA7567XzOHv5qWLQ27H/fF5jEO37WzbWJIwl9xFq', 2, 'active', NULL, NULL, NULL, '2026-02-08 13:32:35'),
(55, 'Employee 1050', 'user1050@company.com', '$2y$10$80LPOJQK7lIXfKFJCrdtd.jbHlQy4tGR9004D8qhIO8ZVSbIciPBO', 2, 'disabled', NULL, NULL, NULL, '2026-02-08 13:32:35'),
(56, 'Employee 1051', 'user1051@company.com', '$2y$10$n0gnQ31PBh0YIyOJQdEnOefsKHMZH4guaa.GEjRjrUoS0pPAAQG5e', 2, 'active', NULL, NULL, NULL, '2026-02-08 13:32:35'),
(57, 'Employee 1052', 'user1052@company.com', '$2y$10$CCn4xdLzqpeARzOPQqGbieS4oBD3moUTbX3DyPUwtmhBnoUi6Z9Bm', 2, 'disabled', NULL, NULL, NULL, '2026-02-08 13:32:35'),
(58, 'Employee 1053', 'user1053@company.com', '$2y$10$T9oUrDV/C/Z508D7xZGLYOzIsAD3/VMzgzlYfPlNdH..857LInWZy', 2, 'disabled', NULL, NULL, NULL, '2026-02-08 13:32:35'),
(59, 'Employee 1054', 'user1054@company.com', '$2y$10$EdELcJLF8YzHSzq2qej1Ue4SCIgSaul9rRmpbzUvBDj6.P27nwGHu', 2, 'disabled', NULL, NULL, NULL, '2026-02-08 13:32:36'),
(60, 'Employee 1055', 'user1055@company.com', '$2y$10$mQ99idoo6RwXxcr8Wr8Ie.f31CfiWvEdSpJsAHB9ajHEOEgyry5qm', 2, 'active', NULL, NULL, NULL, '2026-02-08 13:32:36'),
(61, 'Employee 1056', 'user1056@company.com', '$2y$10$3U7T7aVhzAN0lwJIsGXuRuNJCr9atJ8jjjYSedRI.AHfkB8VJPeN6', 2, 'active', NULL, NULL, NULL, '2026-02-08 13:32:36'),
(62, 'Employee 1057', 'user1057@company.com', '$2y$10$wJuPNET5CTa0LyyHIlpYkeD6fSkUaFNYVfFSdOnIx0EbT7H13szPG', 2, 'disabled', NULL, NULL, NULL, '2026-02-08 13:32:36'),
(63, 'Employee 1058', 'user1058@company.com', '$2y$10$YtMrcJQw07IGmDtXQTc..e159BwUOOr3urmo0xcljeHDhJTAnQKZy', 2, 'active', NULL, NULL, NULL, '2026-02-08 13:32:36'),
(64, 'Employee 1059', 'user1059@company.com', '$2y$10$MNMx11w8oSC8Ur1k9iTME.Lv3oiZSxj7mfPpQrE9FlDmCTlVKM3/.', 2, 'active', NULL, NULL, NULL, '2026-02-08 13:32:36'),
(65, 'Employee 1060', 'user1060@company.com', '$2y$10$Wons0GHECveR.rGzPbSGS.eT8c0eDClHZtTTqCbC8KLuh1nc4LL4a', 2, 'disabled', NULL, NULL, NULL, '2026-02-08 13:32:36'),
(66, 'Employee 1061', 'user1061@company.com', '$2y$10$l4V8GVI9dpGmrHk9EBVO1Ok4Pz18cfG9ybZ5jrRnuBtCg7MKt7Cj.', 2, 'active', NULL, NULL, NULL, '2026-02-08 13:32:36'),
(67, 'Employee 1062', 'user1062@company.com', '$2y$10$BxkEuXDQjIk.ReVWOwUZoOIUPj6sx/n7LPfK1ZmqLzV0uqILvZVma', 2, 'disabled', NULL, NULL, NULL, '2026-02-08 13:32:36'),
(68, 'Employee 1063', 'user1063@company.com', '$2y$10$2pvWRsG.AU9PL8kaJwOhoONbW5dpF2wYAXy1/gX0lZm3GXfMRsGiC', 2, 'active', NULL, NULL, NULL, '2026-02-08 13:32:36'),
(69, 'Employee 1064', 'user1064@company.com', '$2y$10$nRZv4q8ukhZShsXsQABwTOutC5EDpicf8qHz9WjPO7zWJ/o.R04Mu', 2, 'disabled', NULL, NULL, NULL, '2026-02-08 13:32:36'),
(70, 'Employee 1065', 'user1065@company.com', '$2y$10$JJTlRi.jRnlSrfJguXs0duUrBx8IrCJYWseZ2hFacVA5JG17B8mnC', 2, 'active', NULL, NULL, NULL, '2026-02-08 13:32:36'),
(71, 'Employee 1066', 'user1066@company.com', '$2y$10$NckTFNztURW.hakHy2QJkea2F99/od9PeZgYMJP9nIu2zjwGA9Gbe', 2, 'disabled', NULL, NULL, NULL, '2026-02-08 13:32:36'),
(72, 'Employee 1067', 'user1067@company.com', '$2y$10$oby88uHK/jSEyieje68H1eIlPDBZY99Cw.CY5n1MADVDLj9Yx4fKC', 2, 'disabled', NULL, NULL, NULL, '2026-02-08 13:32:37'),
(73, 'Employee 1068', 'user1068@company.com', '$2y$10$DzRv040FgCFfMosSoqhGeOncHm.M1CI0wpR6O4OyoWn6C7CnpzzLO', 2, 'disabled', NULL, NULL, NULL, '2026-02-08 13:32:37'),
(74, 'Employee 1069', 'user1069@company.com', '$2y$10$zYsuPGZZ2yeJS7cRNUxfPeLiiZclxhwTqqGUziVAj238OKmA6xB/S', 2, 'active', NULL, NULL, NULL, '2026-02-08 13:32:37'),
(75, 'Employee 1070', 'user1070@company.com', '$2y$10$Elgb6bqj9R4qv.3OMHNX6OsoWrfdaNz9WxkUp75pAIEPY0xlapnGC', 2, 'disabled', NULL, NULL, NULL, '2026-02-08 13:32:37'),
(76, 'Employee 1071', 'user1071@company.com', '$2y$10$su2K295P3kZ7wIZ/gj0jB.5RIeTB4oH/PES1HX/G8WlrlI2DDo0Pm', 2, 'active', NULL, NULL, NULL, '2026-02-08 13:32:37'),
(77, 'Employee 1072', 'user1072@company.com', '$2y$10$yXJCUlf0lu91PI3IvWjfluPBn1q2lOcgW7iDcHIdAXbkJdr90ydTC', 2, 'disabled', NULL, NULL, NULL, '2026-02-08 13:32:37'),
(78, 'Employee 1073', 'user1073@company.com', '$2y$10$STlhrcj19Oc4kJseNfpM3O/rx8Q20rIAkW/EMOWNtAcxbE5oM2p8i', 2, 'disabled', NULL, NULL, NULL, '2026-02-08 13:32:37'),
(79, 'Employee 1074', 'user1074@company.com', '$2y$10$u7oHC4em69yyrHZUm2nsKOSjETYQTSj110TnnnThQ4WxFamiS2DRW', 2, 'active', NULL, NULL, NULL, '2026-02-08 13:32:37'),
(80, 'Employee 1075', 'user1075@company.com', '$2y$10$Ddt87NXOgAAnZG5kgW.PQO8nUYdCONg1t3UrwcV6Fx5yw.pbSLsSG', 2, 'disabled', NULL, NULL, NULL, '2026-02-08 13:32:37'),
(81, 'Employee 1076', 'user1076@company.com', '$2y$10$RSeXwhS9decqwK0YT7I83.UB4Izn1OoCQDURyZbDFSdWDD03V3QMW', 2, 'active', NULL, NULL, NULL, '2026-02-08 13:32:37'),
(82, 'Employee 1077', 'user1077@company.com', '$2y$10$wJxROZVB3WTQhSeuXQWjletX/KGuzANcEutF4QOPOEowGSsjG67XW', 2, 'active', NULL, NULL, NULL, '2026-02-08 13:32:37'),
(83, 'Employee 1078', 'user1078@company.com', '$2y$10$xUFzOsc6OhaXk6gQu7QYAuTRx777WYb7eoZYbXbJVIHGfsYjA0QVG', 2, 'disabled', NULL, NULL, NULL, '2026-02-08 13:32:37'),
(84, 'Employee 1079', 'user1079@company.com', '$2y$10$MphjlQdShXBkkCqSa5jBW.u/XvYl2TGkcexabJluzB8wyfjFxh16u', 2, 'active', NULL, NULL, NULL, '2026-02-08 13:32:37'),
(85, 'Employee 1080', 'user1080@company.com', '$2y$10$Jeq.5oxOnZAU2QVWksDX2eYEe7DLH6tYqW1KGRySpVY5s7V89FK82', 2, 'disabled', NULL, NULL, NULL, '2026-02-08 13:32:38'),
(86, 'Employee 1081', 'user1081@company.com', '$2y$10$jzZUUc2vXPFjhDChrWGg5eDSpKMbnzfum7T9d2fM5TMIIAhMYfYDW', 2, 'disabled', NULL, NULL, NULL, '2026-02-08 13:32:38'),
(87, 'Employee 1082', 'user1082@company.com', '$2y$10$31f/QjTb8wPzuj1TTX4At.yoRVZBGFRJEkX.M19kPqSPG0zrR5swW', 2, 'active', NULL, NULL, NULL, '2026-02-08 13:32:38'),
(88, 'Employee 1083', 'user1083@company.com', '$2y$10$w/paT0E31K2AbdlEjQwUr.2MxvbwoSwJXyaWkOnxNFwa07MjTtnSa', 2, 'active', NULL, NULL, NULL, '2026-02-08 13:32:38'),
(89, 'Employee 1084', 'user1084@company.com', '$2y$10$CddtCPRKbkUMM.7nuKg9s.VHXRQoHEkrprk3rCg2KsXlpd5O8q8Pe', 2, 'active', NULL, NULL, NULL, '2026-02-08 13:32:38'),
(90, 'Employee 1085', 'user1085@company.com', '$2y$10$Ze4humLNTuYWRH/MPt/YEuN9uiLkMz507.YlBp49sVHtarRd27dfa', 2, 'active', NULL, NULL, NULL, '2026-02-08 13:32:38'),
(91, 'Employee 1086', 'user1086@company.com', '$2y$10$4qnu8Y4M0mlyH6IMg2bUs.KsMJ7d4ZDukRE.QTRsquxk8LMLeai7y', 2, 'active', NULL, NULL, NULL, '2026-02-08 13:32:38'),
(92, 'Employee 1087', 'user1087@company.com', '$2y$10$hliAJ/b7Y3Z0DPuHJwylguuNsj6xmFjdmAfICyx8e5aVwyVdXnIVa', 2, 'disabled', NULL, NULL, NULL, '2026-02-08 13:32:38'),
(93, 'Employee 1088', 'user1088@company.com', '$2y$10$ryfySFfN7F/l6LZTtwP/VuH6depjW/FFI9Ly0JXaPcUj3A9wfKfEe', 2, 'disabled', NULL, NULL, NULL, '2026-02-08 13:32:38'),
(94, 'Employee 1089', 'user1089@company.com', '$2y$10$H/pthktBK4Fwk8oSPR8.Auii/IR7tFGYQK3fpe3HrLOtkHL9/gywu', 2, 'active', NULL, NULL, NULL, '2026-02-08 13:32:38'),
(95, 'Employee 1090', 'user1090@company.com', '$2y$10$hz2BFqocALlWf3zvYNTfbeILOpaGUG/CCMdiXpgqdGbGngvE8ml6O', 2, 'active', NULL, NULL, NULL, '2026-02-08 13:32:38'),
(96, 'Employee 1091', 'user1091@company.com', '$2y$10$mo3i2jrLexzQSU9v7kTd4uFI56963aiHpMc9VQ562sX2A8cv.wMOG', 2, 'disabled', NULL, NULL, NULL, '2026-02-08 13:32:38'),
(97, 'Employee 1092', 'user1092@company.com', '$2y$10$HFHlseLZKGAj/Q922PMGYeFOtPiYd6SXSFMisWXM/adnTB7OuIe02', 2, 'disabled', NULL, NULL, NULL, '2026-02-08 13:32:39'),
(98, 'Employee 1093', 'user1093@company.com', '$2y$10$6ht9ubGuIDdZwTUsPS9nJ.gQTRCbOMX4gu4wYCcijAg7yUvUBB/WK', 2, 'disabled', NULL, NULL, NULL, '2026-02-08 13:32:39'),
(99, 'Employee 1094', 'user1094@company.com', '$2y$10$V6quqvf3J6eEo2//ae/E9ORTGZ1HHxHCDw0ZNlqx7sVD9FuH8HGkC', 2, 'active', NULL, NULL, NULL, '2026-02-08 13:32:39'),
(100, 'Employee 1095', 'user1095@company.com', '$2y$10$RvYzQSFoL6Cn21nyotXl2e5iXwD6lvjfk/tPs8v4CllzFDMuUCp.C', 2, 'disabled', NULL, NULL, NULL, '2026-02-08 13:32:39'),
(101, 'Employee 1096', 'user1096@company.com', '$2y$10$V6vZWet0H1KGQWwuKjnx1.hxoPMbBrpG1WBOQPJwh9/6fP7wd2QYS', 2, 'disabled', NULL, NULL, NULL, '2026-02-08 13:32:39'),
(102, 'Employee 1097', 'user1097@company.com', '$2y$10$XXRC6LzUCu3he48it9KxoOHuLTscwhUKFpOV2N0IRvocCKOeJ4A9G', 2, 'active', NULL, NULL, NULL, '2026-02-08 13:32:39'),
(103, 'Employee 1098', 'user1098@company.com', '$2y$10$77tNBfGfDXQLXWEa32GQf.bAhy.Dn0pQnLcicb1v0udACjsvJDmZy', 2, 'disabled', NULL, NULL, NULL, '2026-02-08 13:32:39'),
(104, 'Employee 1099', 'user1099@company.com', '$2y$10$7c.T8ZVDlPoWKXjJmuG26OBXkmZsh8c51Xajj6GIBYHruXcOEvkNu', 2, 'disabled', NULL, NULL, NULL, '2026-02-08 13:32:39'),
(105, 'Employee 1100', 'user1100@company.com', '$2y$10$pogZcKyh.LTnCBWbt4f0SOpzlJi7NKR9Hu5n8zbXOPP4iygkW2Ps.', 2, 'active', NULL, NULL, NULL, '2026-02-08 13:32:39'),
(106, 'Employee 1101', 'user1101@company.com', '$2y$10$cCMhrSaJZnJ7iyWUgYP/heOS4PmNPOpZ8W62GISfpmR5ZZNwmQm52', 2, 'active', NULL, NULL, NULL, '2026-02-08 13:32:39'),
(107, 'Employee 1102', 'user1102@company.com', '$2y$10$63oheC2eGv0IXjk282KzGu/Jh1pTXSD36REuJbp85jq1SoHerzxK2', 2, 'disabled', NULL, NULL, NULL, '2026-02-08 13:32:39'),
(108, 'Employee 1103', 'user1103@company.com', '$2y$10$X1RrcD5YJdU8kT0QocEkleXaH8xDV8/Z97.RXXOOYnQJozmz.w3Vm', 2, 'disabled', NULL, NULL, NULL, '2026-02-08 13:32:39'),
(109, 'Employee 1104', 'user1104@company.com', '$2y$10$2WmAjJz/cBX85LBXoQH6ROsaKSJNYZP86/XA19mrwiBNTHXvPMIyO', 2, 'active', NULL, NULL, NULL, '2026-02-08 13:32:39'),
(110, 'Employee 1105', 'user1105@company.com', '$2y$10$Q1pDBOFC6.1jN2nPhGEW2O0KP2RtHPHOFn57T4gwwhYWqaf5PT8wG', 2, 'active', NULL, NULL, NULL, '2026-02-08 13:32:40'),
(111, 'Employee 1106', 'user1106@company.com', '$2y$10$gnD8m.Qa8Ycfp/aU5YaAUOeWm0xcvbF0tNeTQaiAnz4yzun86.R1C', 2, 'active', NULL, NULL, NULL, '2026-02-08 13:32:40'),
(112, 'Employee 1107', 'user1107@company.com', '$2y$10$njvFDmmMLgsJ/kb19qpFWOKBzjWaCHIi31d0f5UZmUXEScnvHmJ5q', 2, 'disabled', NULL, NULL, NULL, '2026-02-08 13:32:40'),
(113, 'Employee 1108', 'user1108@company.com', '$2y$10$h37e/emkCsDd/PQNqCySbu5sSx.ph5/LU.DIC.R1AkAcfA6q6GXlG', 2, 'disabled', NULL, NULL, NULL, '2026-02-08 13:32:40'),
(114, 'Employee 1109', 'user1109@company.com', '$2y$10$8duDPE/l/mDi34774tzw/eRw63euN3B3gdigFX5XRMU8CEihFKDaS', 2, 'active', NULL, NULL, NULL, '2026-02-08 13:32:40'),
(115, 'Employee 1110', 'user1110@company.com', '$2y$10$MAtOA9VuYAVAgbQw1jPpFuG3Kfr4ZiJLkJ9vaqDTrPGR8wb4NmqK2', 2, 'active', NULL, NULL, NULL, '2026-02-08 13:32:40'),
(116, 'Employee 1111', 'user1111@company.com', '$2y$10$sh2E0AswFzTI.ybz88tubu4m034Kv9n1WQXH78SpsJjg.75sVrpW.', 2, 'disabled', NULL, NULL, NULL, '2026-02-08 13:32:40'),
(117, 'Employee 1112', 'user1112@company.com', '$2y$10$UdAck1DGnvBvbkMqiO1F6OJD4q6t6CyrH75RmTKsf6MoOY/s//oE2', 2, 'active', NULL, NULL, NULL, '2026-02-08 13:32:40'),
(118, 'Employee 1113', 'user1113@company.com', '$2y$10$A7TKFFoSGJiW0awgbq4PFOIUxQgqq3Nt9wOSh84awuSPCOI1NkCBO', 2, 'disabled', NULL, NULL, NULL, '2026-02-08 13:32:40'),
(119, 'Employee 1114', 'user1114@company.com', '$2y$10$k1U3kjpQkNJYjlUtaqoVIOskn6DAeQO/s0V0dQPBLUAsG4IHwlO3q', 2, 'active', NULL, NULL, NULL, '2026-02-08 13:32:40'),
(120, 'Employee 1115', 'user1115@company.com', '$2y$10$W5OVSp4PYhFfgbaOfVC5Oer5PmzmvpTlKkczZ9H3eXROEm8DCoSZy', 2, 'disabled', NULL, NULL, NULL, '2026-02-08 13:32:40'),
(121, 'Employee 1116', 'user1116@company.com', '$2y$10$K9yv6MkRkCLpZ0t0/v4pbeVkfpVjoNOLpc/GOcVRmnVTsrgyucmCu', 2, 'disabled', NULL, NULL, NULL, '2026-02-08 13:32:40'),
(122, 'Employee 1117', 'user1117@company.com', '$2y$10$oTkOglwwYOEjRia6AUeqBu1o9PiQ7./Mr3UaKhLV.seQyzEvbBtoW', 2, 'active', NULL, NULL, NULL, '2026-02-08 13:32:40'),
(123, 'Employee 1118', 'user1118@company.com', '$2y$10$f8p0Z6EJa0wDOFvLSXnf/euq8saaU3bxlO1Y5AsM76HDDv7c.SFxm', 2, 'disabled', NULL, NULL, NULL, '2026-02-08 13:32:41'),
(124, 'Employee 1119', 'user1119@company.com', '$2y$10$7JSqd4rj3CGCwTTMar6xyueTSujDwjPUXDuzCw7Kh2wp2oB/xtc/y', 2, 'active', NULL, NULL, NULL, '2026-02-08 13:32:41'),
(125, 'Employee 1120', 'user1120@company.com', '$2y$10$pbpIcZrKOWzwjbrt6OEhv.5Zmz5JQeKlGGwT/G6oPGY.t0i0E.R36', 2, 'disabled', NULL, NULL, NULL, '2026-02-08 13:32:41');

--
-- Indexes for dumped tables
--

--
-- Indexes for table `employees`
--
ALTER TABLE `employees`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `user_id` (`user_id`);

--
-- Indexes for table `inquiries`
--
ALTER TABLE `inquiries`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `inquiry_table`
--
ALTER TABLE `inquiry_table`
  ADD PRIMARY KEY (`InquiryId`);

--
-- Indexes for table `leave_requests`
--
ALTER TABLE `leave_requests`
  ADD PRIMARY KEY (`id`),
  ADD KEY `employee_id` (`employee_id`),
  ADD KEY `leader_id` (`leader_id`),
  ADD KEY `hr_id` (`hr_id`);

--
-- Indexes for table `payroll_inputs`
--
ALTER TABLE `payroll_inputs`
  ADD PRIMARY KEY (`id`),
  ADD KEY `employee_id` (`employee_id`),
  ADD KEY `submitted_by` (`submitted_by`);

--
-- Indexes for table `projects`
--
ALTER TABLE `projects`
  ADD PRIMARY KEY (`id`),
  ADD KEY `leader_id` (`leader_id`);

--
-- Indexes for table `roles`
--
ALTER TABLE `roles`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `role_name` (`role_name`);

--
-- Indexes for table `salary_slips`
--
ALTER TABLE `salary_slips`
  ADD PRIMARY KEY (`id`),
  ADD KEY `employee_id` (`employee_id`),
  ADD KEY `generated_by` (`generated_by`);

--
-- Indexes for table `tasks`
--
ALTER TABLE `tasks`
  ADD PRIMARY KEY (`id`),
  ADD KEY `project_id` (`project_id`),
  ADD KEY `assigned_to` (`assigned_to`),
  ADD KEY `created_by` (`created_by`);

--
-- Indexes for table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `email` (`email`),
  ADD KEY `role_id` (`role_id`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `employees`
--
ALTER TABLE `employees`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=124;

--
-- AUTO_INCREMENT for table `inquiries`
--
ALTER TABLE `inquiries`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=10;

--
-- AUTO_INCREMENT for table `inquiry_table`
--
ALTER TABLE `inquiry_table`
  MODIFY `InquiryId` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `leave_requests`
--
ALTER TABLE `leave_requests`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=132;

--
-- AUTO_INCREMENT for table `payroll_inputs`
--
ALTER TABLE `payroll_inputs`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=127;

--
-- AUTO_INCREMENT for table `projects`
--
ALTER TABLE `projects`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=125;

--
-- AUTO_INCREMENT for table `roles`
--
ALTER TABLE `roles`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT for table `salary_slips`
--
ALTER TABLE `salary_slips`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- AUTO_INCREMENT for table `tasks`
--
ALTER TABLE `tasks`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=50;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=126;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `employees`
--
ALTER TABLE `employees`
  ADD CONSTRAINT `employees_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`);

--
-- Constraints for table `leave_requests`
--
ALTER TABLE `leave_requests`
  ADD CONSTRAINT `leave_requests_ibfk_1` FOREIGN KEY (`employee_id`) REFERENCES `employees` (`id`),
  ADD CONSTRAINT `leave_requests_ibfk_2` FOREIGN KEY (`leader_id`) REFERENCES `users` (`id`),
  ADD CONSTRAINT `leave_requests_ibfk_3` FOREIGN KEY (`hr_id`) REFERENCES `users` (`id`);

--
-- Constraints for table `payroll_inputs`
--
ALTER TABLE `payroll_inputs`
  ADD CONSTRAINT `payroll_inputs_ibfk_1` FOREIGN KEY (`employee_id`) REFERENCES `employees` (`id`),
  ADD CONSTRAINT `payroll_inputs_ibfk_2` FOREIGN KEY (`submitted_by`) REFERENCES `users` (`id`);

--
-- Constraints for table `projects`
--
ALTER TABLE `projects`
  ADD CONSTRAINT `projects_ibfk_1` FOREIGN KEY (`leader_id`) REFERENCES `users` (`id`);

--
-- Constraints for table `salary_slips`
--
ALTER TABLE `salary_slips`
  ADD CONSTRAINT `salary_slips_ibfk_1` FOREIGN KEY (`employee_id`) REFERENCES `employees` (`id`),
  ADD CONSTRAINT `salary_slips_ibfk_2` FOREIGN KEY (`generated_by`) REFERENCES `users` (`id`);

--
-- Constraints for table `tasks`
--
ALTER TABLE `tasks`
  ADD CONSTRAINT `tasks_ibfk_1` FOREIGN KEY (`project_id`) REFERENCES `projects` (`id`),
  ADD CONSTRAINT `tasks_ibfk_2` FOREIGN KEY (`assigned_to`) REFERENCES `users` (`id`),
  ADD CONSTRAINT `tasks_ibfk_3` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`);

--
-- Constraints for table `users`
--
ALTER TABLE `users`
  ADD CONSTRAINT `users_ibfk_1` FOREIGN KEY (`role_id`) REFERENCES `roles` (`id`);
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
