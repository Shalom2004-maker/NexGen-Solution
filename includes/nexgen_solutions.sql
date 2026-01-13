-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Jan 12, 2026 at 08:33 PM
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
(3, 3, 'Project Manager', 'IT', '2022-09-09', 75000.00, 'active');

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
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `inquiries`
--

INSERT INTO `inquiries` (`id`, `name`, `email`, `company`, `message`, `status`, `created_at`) VALUES
(1, 'Mark', 'mark@mail.com', 'Alpha Ltd', 'Need a project quote', 'new', '2026-01-03 16:08:12'),
(2, 'Lisa', 'lisa@mail.com', 'Beta Corp', 'Interested in your software', 'replied', '2026-01-03 16:08:12'),
(3, 'Tom', 'tom@mail.com', 'Gamma LLC', 'Request a demo', 'replied', '2026-01-03 16:08:12'),
(4, 'Phoneapp Shalom', 'phoneappinfos@gmail.com', 'Beta Corp', 'The Service Request I need is to ask for help with the implementation of my Project regarding the Student Management System\n\nPreferred Contact Time: 2026-01-14 06:00 PM\nInquiry Type: Service Request', 'new', '2026-01-05 13:18:56'),
(5, 'Phoneapp Shalom', 'phoneappinfos@gmail.com', 'Beta Corp', 'The Service Request I need is to ask for help with the implementation of my Project regarding the Student Management System\n\nPreferred Contact Time: 2026-01-14 06:00 PM\nInquiry Type: Service Request', 'new', '2026-01-05 13:19:43');

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
  `leave_type` enum('sick','annual','unpaid') DEFAULT NULL,
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
(7, 1, '2024-06-10', '2024-06-12', 'annual', 'Vacation', 'leader_approved', 3, 2, '2026-01-03 16:41:45'),
(8, 2, '2024-06-05', '2024-06-06', 'sick', 'Flu', 'leader_approved', 3, NULL, '2026-01-03 16:41:45'),
(9, 1, '2024-05-01', '2024-05-02', 'unpaid', 'Personal', 'hr_approved', 3, 2, '2026-01-03 16:41:45'),
(10, 1, '2026-01-30', '2026-02-08', '', 'I\'d like to spend the time thinking about myself, refreshing my mind, and improving my self-growth ', 'pending', NULL, NULL, '2026-01-11 17:05:30');

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
(6, 1, 4, 2024, 8.00, 20000.00, 0.00, 3, 'approved', '2026-01-03 16:49:16');

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
(3, 'Mobile App', 'Customer app', 3, '2024-03-01', '2024-09-01');

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
(7, 1, 1, 3, 'Design Home Page', 'Create UI for homepage', 'done', '2024-06-01', '2026-01-03 16:34:10'),
(8, 2, 2, 3, 'Design HR Forms', 'Create a leave form UI', 'done', '2026-01-30', '2026-01-03 16:34:10'),
(9, 3, 3, 3, 'API Setup', 'Set up backend APIs', 'done', '2024-06-10', '2026-01-03 16:34:10');

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
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`id`, `full_name`, `email`, `password_hash`, `role_id`, `status`, `created_at`) VALUES
(1, 'Admin User', 'admin@spycray.com', '$2y$10$2GWFIRJ33YWVIijMKuCSIu8IcD4k6M1u4JNaLw0qfsSVXInmMo8Ni\n', 5, 'active', '2026-01-03 15:51:13'),
(2, 'HR Manager', 'hr@spycray.com', '$2y$10$uXZYvYDlvekVjyP4P162E.8JZCbeV6oq7pULUrF6hjFK2hVkFi.pe\r\n', 4, 'active', '2026-01-03 15:51:13'),
(3, 'Project Leader', 'leader@spycray.com', '$2y$10$8/amw5dJ4dtqOmGI9awKZuAo.UCu4MlB7blwV3YlQq.q3zwvH3JsS\r\n', 3, 'active', '2026-01-03 15:51:13'),
(4, 'Employee', 'employee@spycray.com', '$2y$10$tfqZlGPYo5J01b80VU/kJOZUugNqXE/xS7tgnaBS0jOy39yNJ2eS2', 2, 'active', '2026-01-05 12:33:32');

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
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `inquiries`
--
ALTER TABLE `inquiries`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT for table `inquiry_table`
--
ALTER TABLE `inquiry_table`
  MODIFY `InquiryId` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `leave_requests`
--
ALTER TABLE `leave_requests`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=11;

--
-- AUTO_INCREMENT for table `payroll_inputs`
--
ALTER TABLE `payroll_inputs`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- AUTO_INCREMENT for table `projects`
--
ALTER TABLE `projects`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

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
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=10;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

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
