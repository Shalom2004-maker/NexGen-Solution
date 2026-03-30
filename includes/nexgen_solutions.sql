-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Mar 29, 2026 at 08:10 AM
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
-- Table structure for table `categories`
--

CREATE TABLE `categories` (
  `CategoryID` int(11) NOT NULL,
  `CategoryName` varchar(100) NOT NULL,
  `Description` text DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `categories`
--

INSERT INTO `categories` (`CategoryID`, `CategoryName`, `Description`) VALUES
(1, 'Cloud Services', 'Computing and storage solutions'),
(2, 'Cybersecurity', 'Network and data protection'),
(3, 'Data Analytics', 'Business intelligence and reporting');

-- --------------------------------------------------------

--
-- Table structure for table `site_settings`
--

CREATE TABLE `site_settings` (
  `setting_key` varchar(64) NOT NULL,
  `setting_value` text DEFAULT NULL,
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `site_settings`
--

INSERT INTO `site_settings` (`setting_key`, `setting_value`) VALUES
('home_hero_eyebrow', 'Intelligent Workforce Platform'),
('home_hero_title', 'Manage your team with precision'),
('home_hero_summary', 'Browse a live catalog of services, solutions, and support coverage powered by your latest published data.'),
('home_services_intro', 'Browse the current service catalog, grouped by tier and category so visitors can quickly understand what you offer.'),
('home_solutions_intro', 'Highlighted active solutions are now pulled directly from your latest published entries.'),
('home_support_intro', 'A live operational summary generated from support activity without exposing private ticket details.');

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
(0, 126, 'QA Analyst', 'Testing', '2026-03-25', 50000.00, 'active');

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
(7, 'Shalom', 'phoneappinfos@gmail.com', 'NexGen Solutions', 'I did not get what I wanted from you as I put my trust I you, Yoooh', 'replied', '2026-02-03 22:12:16', 'Complaint');

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
(132, 6, '2026-03-28', '2026-03-31', 'personal', 'Rest for a while', 'pending', NULL, NULL, '2026-03-25 16:29:34');

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
(12, 27, 3, 2025, 4.00, 109.00, 34.00, 3, 'pending', '2026-02-08 13:36:05');

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
(1, 'Website Revamp', 'Company website upgrade', 2, '2024-01-01', '2024-06-30'),
(2, 'HR System', 'Internal HR portal', 2, '2024-03-10', '2024-09-10'),
(3, 'Mobile App', 'Customer app', 2, '2024-03-01', '2024-09-01'),
(4, 'Cloud Computing', 'Teaching the Basics of Cloud Computing.', 5, '2026-02-27', '2026-03-05'),
(5, '3D Web Development', 'Building a website using React,TypeScript,NextJS and Tailwind.', 1, '2025-01-02', '2025-04-02'),
(6, 'Vibe Coding with AI', 'Auto project', 1, '2025-01-03', '2025-04-03'),
(7, 'Web Programming', 'Upgrading the website', 1, '2025-01-04', '2025-04-04'),
(8, 'Project 4', 'Auto project', NULL, '2025-01-05', '2025-04-05'),
(9, 'Software Engineering', 'Auto project', 126, '2025-01-06', '2025-04-06'),
(10, 'Project 6', 'Auto project', NULL, '2025-01-07', '2025-04-07');

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
(1, 'Admin'),
(4, 'Employee'),
(5, 'Guest'),
(3, 'HR'),
(2, 'ProjectLeader');

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
(5, 2, 5, 2024, 40000.00, 12000.00, 10000.00, 1000.00, 50000.00, 4, '2026-01-03 16:58:43'),
(6, 1, 3, 2024, 90000.00, 12000.00, 20000.00, 0.00, 100000.00, 4, '2026-01-03 16:58:43');

-- --------------------------------------------------------

--
-- Table structure for table `services`
--

CREATE TABLE `services` (
  `ServiceID` int(11) NOT NULL,
  `ServiceName` varchar(150) NOT NULL,
  `ServiceTier` varchar(50) DEFAULT NULL,
  `HourlyRate` decimal(10,2) DEFAULT NULL,
  `CategoryID` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `services`
--

INSERT INTO `services` (`ServiceID`, `ServiceName`, `ServiceTier`, `HourlyRate`, `CategoryID`) VALUES
(201, 'Cloud Migration', 'Premium', 150.00, 1),
(202, 'Security Audit', 'Standard', 200.00, 2),
(203, 'SQL Optimization', 'Basic', 100.00, 3);

-- --------------------------------------------------------

--
-- Table structure for table `solutions`
--

CREATE TABLE `solutions` (
  `SolutionID` int(11) NOT NULL,
  `Title` varchar(255) NOT NULL,
  `Description` text DEFAULT NULL,
  `CategoryID` int(11) DEFAULT NULL,
  `DateCreated` date NOT NULL,
  `IsActive` bit(1) DEFAULT b'1'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `solutions`
--

INSERT INTO `solutions` (`SolutionID`, `Title`, `Description`, `CategoryID`, `DateCreated`, `IsActive`) VALUES
(101, 'Automated Backups', 'Daily encrypted backups to cloud storage', 1, '2023-10-01', b'1'),
(102, 'Firewall Config', 'Enterprise-grade perimeter defense setup', 2, '2023-10-05', b'1'),
(103, 'Real-time Dashboard', 'Live data visualization for sales teams', 3, '2023-10-10', b'1');

-- --------------------------------------------------------

--
-- Table structure for table `support`
--

CREATE TABLE `support` (
  `ID` int(11) NOT NULL,
  `Subject` varchar(255) NOT NULL,
  `Status` varchar(50) DEFAULT 'Open',
  `Priority` int(11) DEFAULT 3,
  `SolutionID` int(11) DEFAULT NULL,
  `ServiceID` int(11) DEFAULT NULL,
  `CreatedAt` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `support`
--

INSERT INTO `support` (`ID`, `Subject`, `Status`, `Priority`, `SolutionID`, `ServiceID`, `CreatedAt`) VALUES
(301, 'Backup Failure', 'Open', 1, 101, 201, '2026-03-29 06:01:30'),
(302, 'Login Issue', 'In Progress', 2, 102, 202, '2026-03-29 06:01:30'),
(303, 'Report Slow', 'Resolved', 3, 103, 203, '2026-03-29 06:01:30');

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
(13, 8, 2, 3, 'Task 4: Project 4', 'Auto-generated task 4 for Project 4.', 'done', '2026-03-11', '2026-02-08 13:50:04'),
(14, 9, NULL, 3, 'Task 5: Project 5', 'Auto-generated task 5 for Project 5.', 'in_progress', '2026-03-12', '2026-02-08 13:50:04'),
(15, 10, NULL, 3, 'Task 6: Project 6', 'Auto-generated task 6 for Project 6.', 'done', '2026-03-13', '2026-02-08 13:50:04'),
(16, 11, NULL, 3, 'Task 7: Project 7', 'Auto-generated task 7 for Project 7.', 'todo', '2026-03-14', '2026-02-08 13:50:04'),
(17, 12, NULL, 3, 'Task 8: Project 8', 'Auto-generated task 8 for Project 8.', 'done', '2026-03-15', '2026-02-08 13:50:04'),
(18, 13, 7, 3, 'Task 9: Project 9', 'Auto-generated task 9 for Project 9.', 'done', '2026-03-16', '2026-02-08 13:50:04'),
(19, 14, 4, 3, 'Task 10: Project 10', 'Auto-generated task 10 for Project 10.', 'in_progress', '2026-03-17', '2026-02-08 13:50:04'),
(20, 15, 6, 3, 'Task 11: Project 11', 'Auto-generated task 11 for Project 11.', 'done', '2026-03-18', '2026-02-08 13:50:04'),
(21, 16, NULL, 3, 'Task 12: Project 12', 'Auto-generated task 12 for Project 12.', 'done', '2026-03-19', '2026-02-08 13:50:04'),
(23, 18, NULL, 3, 'Task 14: Project 14', 'Auto-generated task 14 for Project 14.', 'todo', '2026-03-21', '2026-02-08 13:50:04'),
(24, 19, NULL, 3, 'Task 15: Project 15', 'Auto-generated task 15 for Project 15.', 'done', '2026-03-22', '2026-02-08 13:50:04'),
(25, 20, 8, 3, 'Task 16: Project 16', 'Auto-generated task 16 for Project 16.', 'todo', '2026-03-23', '2026-02-08 13:50:04'),
(26, 21, NULL, 3, 'Task 17: Project 17', 'Auto-generated task 17 for Project 17.', 'in_progress', '2026-03-24', '2026-02-08 13:50:04'),
(27, 22, 8, 3, 'Task 18: Project 18', 'Auto-generated task 18 for Project 18.', 'done', '2026-03-25', '2026-02-08 13:50:04'),
(50, 9, 8, 3, 'Task 7: Project 7', 'Auto-generated task 7 for Project 7.', 'done', '2026-03-25', '2026-03-25 16:28:14');

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `id` int(11) NOT NULL,
  `full_name` varchar(100) NOT NULL,
  `email` varchar(100) NOT NULL,
  `password_hash` varchar(255) NOT NULL,
  `password` varchar(255) NOT NULL,
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

INSERT INTO `users` (`id`, `full_name`, `email`, `password_hash`, `password`, `role_id`, `status`, `profile_photo`, `password_reset_token`, `password_reset_expires_at`, `created_at`) VALUES
(1, 'Admin User', 'admin@spycray.com', '$2y$10$2GWFIRJ33YWVIijMKuCSIu8IcD4k6M1u4JNaLw0qfsSVXInmMo8Ni', '', 1, 'active', 'assets/avatars/user_1_3c280c7edaec6d8f.png', NULL, NULL, '2026-01-03 10:21:13'),
(2, 'Benevolent Eager', 'benevolenteager@gmail.com', '$2y$10$4Y275/M.hfmV7WQ1l7zQdera8ye1FeWEgbUcTKr.aRyKVcbdPlrLO', '', 1, 'active', 'assets/avatars/user_5_17bc450c2e85e8da.jpg', 'c743ca31562730bed2bce5f2146228cd823c1888c20205bb5af7bd66ca49a3ed', '2026-03-27 14:17:26', '2026-02-04 03:30:00'),
(3, 'Project Leader', 'leader@spycray.com', '$2y$10$8/amw5dJ4dtqOmGI9awKZuAo.UCu4MlB7blwV3YlQq.q3zwvH3JsS', '', 2, 'active', 'assets/avatars/user_3_9559e2d983f826f8.png', NULL, NULL, '2026-01-03 10:21:13'),
(4, 'HR Manager', 'hr@spycray.com', '$2y$10$uXZYvYDlvekVjyP4P162E.8JZCbeV6oq7pULUrF6hjFK2hVkFi.pe', 'HRManager', 3, 'active', 'assets/avatars/user_2_3bd7f4bb74f90212.png', NULL, NULL, '2026-01-03 10:21:13'),
(5, 'Employee', 'employee@spycray.com', '$2y$10$tfqZlGPYo5J01b80VU/kJOZUugNqXE/xS7tgnaBS0jOy39yNJ2eS2', '', 4, 'active', 'assets/avatars/user_4_b54684fa037577cb.jpg', NULL, NULL, '2026-01-05 07:03:32'),
(6, 'Employee 1001', 'user1001@company.com', '$2y$10$Z6XzIPrqnNTLxZTrLsCsWOQFYRcqvjdD0SNGY1cA1BSAqR2ZJaMva', 'password123', 4, 'active', NULL, NULL, NULL, '2026-02-08 08:02:31'),
(7, 'Employee 1002', 'user1002@company.com', '$2y$10$CJDfMguX8o9vvz3iyf5vH.u61DKPeuGS0HIFszZcNNy30J9G8ShO2', 'password123', 4, 'disabled', NULL, NULL, NULL, '2026-02-08 08:02:31'),
(8, 'Neha Chaudhary', 'nchaudhary187@rku.ac.in', '$2y$10$J9MpZ1nzCSARGzGtOWCRfuU7ieVLrgiX9UDtgagN5kz2Xq2PFrQcC', '', 4, 'active', NULL, NULL, NULL, '2026-03-25 13:56:54'),
(127, 'Phoneapp Infos', 'phoneappinfos@gmail.com', '$2y$10$7qkGjA5qj6hxlU0cUOY2xOjf3Xt8F7in44hDVGsple9.YqAC3VPHC', '', 3, 'active', NULL, 'bcbae2176e1166216be43a7cb2d3ffb4f931a6345c1e83eb601b38a4a0eff129', '2026-03-28 19:11:48', '2026-03-28 15:55:20');

--
-- Indexes for dumped tables
--

--
-- Indexes for table `categories`
--
ALTER TABLE `categories`
  ADD PRIMARY KEY (`CategoryID`),
  ADD UNIQUE KEY `CategoryName` (`CategoryName`);

--
-- Indexes for table `site_settings`
--
ALTER TABLE `site_settings`
  ADD PRIMARY KEY (`setting_key`);

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
-- Indexes for table `services`
--
ALTER TABLE `services`
  ADD PRIMARY KEY (`ServiceID`),
  ADD KEY `CategoryID` (`CategoryID`);

--
-- Indexes for table `solutions`
--
ALTER TABLE `solutions`
  ADD PRIMARY KEY (`SolutionID`),
  ADD KEY `CategoryID` (`CategoryID`);

--
-- Indexes for table `support`
--
ALTER TABLE `support`
  ADD PRIMARY KEY (`ID`),
  ADD KEY `SolutionID` (`SolutionID`),
  ADD KEY `ServiceID` (`ServiceID`);

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
-- AUTO_INCREMENT for table `categories`
--
ALTER TABLE `categories`
  MODIFY `CategoryID` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

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
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=133;

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
-- AUTO_INCREMENT for table `services`
--
ALTER TABLE `services`
  MODIFY `ServiceID` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=204;

--
-- AUTO_INCREMENT for table `support`
--
ALTER TABLE `support`
  MODIFY `ID` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=304;

--
-- AUTO_INCREMENT for table `tasks`
--
ALTER TABLE `tasks`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=51;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=128;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `services`
--
ALTER TABLE `services`
  ADD CONSTRAINT `services_ibfk_1` FOREIGN KEY (`CategoryID`) REFERENCES `categories` (`CategoryID`);

--
-- Constraints for table `solutions`
--
ALTER TABLE `solutions`
  ADD CONSTRAINT `solutions_ibfk_1` FOREIGN KEY (`CategoryID`) REFERENCES `categories` (`CategoryID`);

--
-- Constraints for table `support`
--
ALTER TABLE `support`
  ADD CONSTRAINT `support_ibfk_1` FOREIGN KEY (`SolutionID`) REFERENCES `solutions` (`SolutionID`),
  ADD CONSTRAINT `support_ibfk_2` FOREIGN KEY (`ServiceID`) REFERENCES `services` (`ServiceID`);
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
