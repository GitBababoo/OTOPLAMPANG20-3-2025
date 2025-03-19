-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Mar 19, 2025 at 10:29 PM
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
-- Database: `test1`
--

DELIMITER $$
--
-- Procedures
--
CREATE DEFINER=`root`@`localhost` PROCEDURE `notify_all_users_new_discount` (IN `discount_id` INT, IN `discount_title` VARCHAR(255))   BEGIN
    -- Declare variables
    DECLARE user_id INT;
    DECLARE done INT DEFAULT FALSE;

    -- Declare cursor
    DECLARE user_cursor CURSOR FOR
        SELECT user_id
        FROM users
        WHERE is_active = 1; -- เลือกเฉพาะผู้ใช้งานที่ Active

    -- Declare handler for when cursor reaches end
    DECLARE CONTINUE HANDLER FOR NOT FOUND SET done = TRUE;

    -- Open the cursor
    OPEN user_cursor;

    -- Loop through each user
    read_loop: LOOP
        FETCH user_cursor INTO user_id;

        -- If no more users, exit the loop
        IF done THEN
            LEAVE read_loop;
        END IF;

        -- Insert notification for each user
        INSERT INTO notifications (type, notification_type, message, data, related_id)
        VALUES (
            'discount',
            'general',
            CONCAT('มีส่วนลดใหม่: ', discount_title),
            JSON_OBJECT('discount_id', discount_id, 'discount_title', discount_title),
            discount_id  -- ใส่ discount_id ใน related_id
        );

    END LOOP;

    -- Close the cursor
    CLOSE user_cursor;

END$$

DELIMITER ;

-- --------------------------------------------------------

--
-- Table structure for table `banners`
--

CREATE TABLE `banners` (
  `id` int(11) NOT NULL,
  `image` varchar(255) NOT NULL,
  `link` varchar(255) NOT NULL,
  `title` varchar(255) NOT NULL,
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `start_date` datetime DEFAULT NULL,
  `end_date` datetime DEFAULT NULL,
  `position` enum('header','sidebar','footer','อื่นๆ') DEFAULT 'header',
  `priority` int(11) DEFAULT 0,
  `alt_text` varchar(255) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `banners`
--

INSERT INTO `banners` (`id`, `image`, `link`, `title`, `is_active`, `start_date`, `end_date`, `position`, `priority`, `alt_text`) VALUES
(5, 'IMG_9831.jpg', 'discounts.php', 'คูปองส่วนลด', 1, '2025-03-18 21:11:00', '2035-01-18 21:11:00', 'sidebar', 3, 'คูปองส่วนลด');

-- --------------------------------------------------------

--
-- Table structure for table `cart`
--

CREATE TABLE `cart` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `product_id` int(11) NOT NULL,
  `quantity` int(11) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `price` decimal(10,2) NOT NULL CHECK (`price` >= 0),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `cart`
--

INSERT INTO `cart` (`id`, `user_id`, `product_id`, `quantity`, `created_at`, `price`, `updated_at`) VALUES
(17, 2, 43, 1, '2025-03-19 16:23:58', 135.00, '2025-03-19 16:23:58');

-- --------------------------------------------------------

--
-- Table structure for table `categories`
--

CREATE TABLE `categories` (
  `id` int(11) NOT NULL,
  `title` varchar(100) NOT NULL,
  `description` text DEFAULT NULL,
  `image` varchar(255) DEFAULT NULL,
  `link` varchar(255) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `categories`
--

INSERT INTO `categories` (`id`, `title`, `description`, `image`, `link`) VALUES
(5, 'ของฝากและขนมขบเคี้ยว', '', 'ของฝากและขนมขบเคี้ยว-20-02-2025.png', ''),
(8, 'สมุนไพรและผลิตภัณฑ์เพื่อสุขภาพ', '', 'Gemini_Generated_Image_vs2h9ovs2h9ovs2h.jpg', ''),
(10, 'หัตถกรรมและงานฝีมือ', '', 'หัตถกรรมและงานฝีมือ-04-02-2025.png', ''),
(12, 'อาหารแปรรูป', '', 'Gemini_Generated_Image_xjsc1gxjsc1gxjsc.jpg', '');

-- --------------------------------------------------------

--
-- Table structure for table `discounts`
--

CREATE TABLE `discounts` (
  `id` int(11) NOT NULL,
  `seller_id` int(11) DEFAULT NULL,
  `title` varchar(255) NOT NULL,
  `description` text NOT NULL,
  `discount_amount` decimal(10,2) NOT NULL,
  `min_spend` decimal(10,2) NOT NULL,
  `expiry_date` date NOT NULL,
  `code` varchar(50) NOT NULL,
  `discount_percent` int(3) NOT NULL,
  `max_uses` int(11) NOT NULL,
  `start_date` datetime NOT NULL,
  `end_date` datetime NOT NULL,
  `is_expired` tinyint(1) NOT NULL DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `discounts`
--

INSERT INTO `discounts` (`id`, `seller_id`, `title`, `description`, `discount_amount`, `min_spend`, `expiry_date`, `code`, `discount_percent`, `max_uses`, `start_date`, `end_date`, `is_expired`) VALUES
(1, NULL, 'ลดพิเศษฉลองเปิดฐานข้อมูลใหม่', 'ส่วนลดพิเศษสำหรับลูกค้าใหม่', 75.00, 350.00, '2025-04-15', 'NEWDB', 30, 5, '2025-03-19 00:00:00', '2025-03-31 23:59:59', 0),
(2, NULL, 'ทดสอบส่วนลด', 'ส่วนลดทดสอบการทำงาน', 25.00, 100.00, '2025-04-30', 'TESTNEW', 10, 5, '2025-03-19 00:00:00', '2025-04-30 23:59:59', 0);

--
-- Triggers `discounts`
--
DELIMITER $$
CREATE TRIGGER `new_discount_trigger` AFTER INSERT ON `discounts` FOR EACH ROW BEGIN
    CALL notify_all_users_new_discount(NEW.id, NEW.title);
END
$$
DELIMITER ;

-- --------------------------------------------------------

--
-- Table structure for table `featured_products`
--

CREATE TABLE `featured_products` (
  `id` int(11) NOT NULL,
  `seller_id` int(11) NOT NULL,
  `name` varchar(100) NOT NULL,
  `description` text DEFAULT NULL,
  `price` decimal(10,2) NOT NULL CHECK (`price` >= 0),
  `image` varchar(255) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `category_id` int(11) DEFAULT NULL,
  `stock` int(11) NOT NULL DEFAULT 0 CHECK (`stock` >= 0),
  `is_active` tinyint(1) DEFAULT 1
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `featured_products`
--

INSERT INTO `featured_products` (`id`, `seller_id`, `name`, `description`, `price`, `image`, `created_at`, `category_id`, `stock`, `is_active`) VALUES
(29, 9, 'หมูยอแท่ง', '', 25.00, 'หมูยอแท่ง_18-03-2025.png', '2025-03-18 15:01:28', 12, 88, 1),
(34, 9, 'หมูยอหนังหมู', '', 55.00, 'หมูยอหนังหมู_18-03-2025.png', '2025-03-18 15:04:15', 12, 100, 1),
(35, 9, 'ปลาไม้ดิ้นได้', '', 200.00, 'ปลาไม้ดิ้นได้_18-03-2025.jpg', '2025-03-18 15:08:50', 10, 94, 1),
(36, 9, 'โคมไฟ', '', 120.00, 'โคมไฟ_18-03-2025.jpg', '2025-03-18 15:09:31', 10, 100, 1),
(37, 9, 'น้ำพริก', '', 65.00, 'น้ำพริก_18-03-2025.jpg', '2025-03-18 15:10:19', 8, 98, 1),
(38, 9, 'กระเป๋า', '', 130.00, 'กระเป๋า_18-03-2025.jpg', '2025-03-18 15:10:40', 10, 99, 1),
(39, 9, 'ที่เก็บมีด', '', 45.00, 'ที่เก็บมีด_18-03-2025.jpg', '2025-03-18 15:10:58', 10, 99, 1),
(40, 9, 'ทองม้วน', '', 30.00, 'ทองม้วน_18-03-2025.jpg', '2025-03-18 15:11:11', 5, 100, 1),
(41, 9, 'สบู่ใบไม้', '', 25.00, 'สบู่ใบไม้_18-03-2025.jpg', '2025-03-18 15:11:26', 8, 100, 1),
(42, 9, 'ข้าวแต๋น', '', 25.00, 'ข้าวแต๋น_18-03-2025.jpg', '2025-03-18 15:11:40', 5, 100, 1),
(43, 9, 'ข้าวไรช์เบอรรี่', '', 135.00, 'ข้าวไรช์เบอรรี่_18-03-2025.jpg', '2025-03-18 15:11:59', 5, 99, 1),
(44, 9, 'ผ้าปัก', '', 140.00, 'ผ้าปัก_18-03-2025.jpg', '2025-03-18 15:12:59', 10, 100, 1),
(45, 9, 'สมุนไพรอบแห้ง (ขิง ขมิ้น ตะไคร้)', '', 95.00, 'สมุนไพรอบแห้ง__ขิง_ขมิ้น_ตะไคร้__18-03-2025.jpg', '2025-03-18 15:13:43', 8, 100, 1),
(46, 9, 'ชาสมุนไพร (ชาใบหม่อน)', '', 72.00, 'ชาสมุนไพร__ชาใบหม่อน__18-03-2025.png', '2025-03-18 15:14:08', 8, 100, 1),
(47, 9, 'น้ำผึงป่าแท้', '', 91.00, 'น้ำผึงป่าแท้_18-03-2025.jpg', '2025-03-18 15:14:39', 12, 100, 1),
(48, 9, 'หมูยอ', '', 89.00, 'หมูยอ_18-03-2025.png', '2025-03-18 15:14:52', 12, 100, 1),
(49, 9, 'แหนมหมู', '', 69.00, 'แหนมหมู_18-03-2025.png', '2025-03-18 15:15:17', 12, 100, 1),
(50, 9, 'ลูกชิ้นเนื้อ-ลูกใหญ่', '', 89.00, 'ลูกชิ้นเนื้อ-ลูกใหญ่_18-03-2025.png', '2025-03-18 15:15:28', 12, 100, 1),
(51, 9, 'ลูกชิ้นหมู-ลูกใหญ่', '', 89.00, 'ลูกชิ้นหมู-ลูกใหญ่_18-03-2025.png', '2025-03-18 15:15:39', 12, 100, 1),
(52, 9, 'กุนเชียง', '', 83.00, 'กุนเชียง_18-03-2025.png', '2025-03-18 15:15:50', 12, 100, 1),
(53, 9, 'ลูกชิ้นหมู จิ๋ว', '', 50.00, 'ลูกชิ้นหมู_จิ๋ว_18-03-2025.png', '2025-03-18 15:16:17', 12, 100, 1),
(54, 9, 'ลูกชิ้นหมู จัมโบ้', '', 59.00, 'ลูกชิ้นหมู_จัมโบ้_18-03-2025.png', '2025-03-18 15:16:28', 12, 100, 1),
(55, 9, 'ลูกชิ้นเนื้อ จัมโบ้', '', 59.00, 'ลูกชิ้นเนื้อ_จัมโบ้_18-03-2025.png', '2025-03-18 15:16:42', 12, 100, 1),
(56, 9, 'แหนมหม้อ', '', 69.00, 'แหนมหม้อ_18-03-2025.png', '2025-03-18 15:16:58', 12, 100, 1),
(57, 11, 'น้ำปู', '', 24.00, 'น้ำปู_19-03-2025_1742395730.jpg', '2025-03-19 14:48:50', 5, 100, 1),
(58, 11, 'น้ำพริกหนุ่ม', '', 128.00, 'น้ำพริกหนุ่ม_19-03-2025_1742395805.png', '2025-03-19 14:50:05', 5, 100, 1),
(59, 11, 'เมล็ดกาแฟคั่วป่าเหมี้ยง', '', 180.00, 'เมล็ดกาแฟคั่วป่าเหมี้ยง_19-03-2025_1742395869.png', '2025-03-19 14:51:09', 5, 100, 1),
(60, 11, 'ข้าวแต๋นน้ำแตงโม', '', 135.00, 'ข้าวแต๋นน้ำแตงโม_19-03-2025_1742395907.jpg', '2025-03-19 14:51:47', 5, 100, 1),
(61, 11, 'รถม้าลำปาง', '', 449.00, 'หัตถกรรมงานไม้_รถม้าลำปาง_19-03-2025_1742396233.png', '2025-03-19 14:57:13', 10, 100, 1),
(62, 11, 'ชามไก่แบบแพ็ค', '', 290.00, 'ชามไก่แบบแพ็ค_19-03-2025_1742396272.png', '2025-03-19 14:57:52', 10, 100, 1),
(63, 11, 'ไส้อั่วหมูสมุนไพร', '', 380.00, 'ไส้อั่วหมูสมุนไพร_19-03-2025_1742396308.png', '2025-03-19 14:58:28', 8, 99, 1),
(64, 11, 'เซ็ตกระถางปลูกต้นไม้ขนาดเล็ก', '', 250.00, '67dadd8131435_ซ็ตกระถางปลูกต้นไม้ขนาดเล็ก.png', '2025-03-19 15:06:41', 10, 99, 1),
(65, 11, 'เซ็ตจานใส่ขนมพร้อมฐานไม้', '', 450.00, '67daddeea6ed0_เซ็ตจานใส่ขนมพร้อมฐานไม้.png', '2025-03-19 15:08:30', 10, 97, 1),
(66, 11, 'เซ็ตแก้วกาแฟปากแคบมี', '', 450.00, '67dade252aa61_เซ็ตแก้วกาแฟปากแคบมี.png', '2025-03-19 15:09:25', 10, 96, 1);

-- --------------------------------------------------------

--
-- Table structure for table `notifications`
--

CREATE TABLE `notifications` (
  `id` int(11) NOT NULL,
  `seller_id` int(11) DEFAULT NULL,
  `notification_type` enum('seller_approval','order_status','general') NOT NULL DEFAULT 'general',
  `order_id` int(11) DEFAULT NULL,
  `message` varchar(255) NOT NULL,
  `data` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`data`)),
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `is_read` tinyint(1) DEFAULT 0,
  `type` enum('order','discount','message') NOT NULL DEFAULT 'order',
  `related_id` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `notifications`
--

INSERT INTO `notifications` (`id`, `seller_id`, `notification_type`, `order_id`, `message`, `data`, `created_at`, `is_read`, `type`, `related_id`) VALUES
(1, NULL, 'general', NULL, 'มีส่วนลดใหม่: ทดสอบส่วนลด', '{\"discount_id\": \"2\", \"discount_title\": \"ทดสอบส่วนลด\"}', '2025-03-18 10:02:20', 1, 'discount', 2);

-- --------------------------------------------------------

--
-- Table structure for table `orders`
--

CREATE TABLE `orders` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `total_price` decimal(12,2) NOT NULL CHECK (`total_price` >= 0),
  `status` enum('pending','paid','shipped','completed','cancelled') NOT NULL DEFAULT 'pending',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `discount_id` int(11) DEFAULT NULL,
  `payment_status` enum('pending','completed','failed') DEFAULT 'pending',
  `payment_method_id` int(11) DEFAULT NULL,
  `bank_account_id` int(11) DEFAULT NULL,
  `seller_id` int(11) DEFAULT NULL,
  `shipping_status` enum('pending','packing','shipped','arrived') DEFAULT 'pending'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `orders`
--

INSERT INTO `orders` (`id`, `user_id`, `total_price`, `status`, `created_at`, `updated_at`, `discount_id`, `payment_status`, `payment_method_id`, `bank_account_id`, `seller_id`, `shipping_status`) VALUES
(2, 4, 50.00, 'pending', '2025-03-19 12:24:59', '2025-03-19 12:43:34', 2, 'pending', 3, NULL, 9, 'pending'),
(4, 4, 200.00, 'pending', '2025-03-19 12:43:28', '2025-03-19 13:17:46', 1, 'pending', 3, NULL, 9, 'pending'),
(10, 4, 244.00, 'pending', '2025-03-19 13:33:35', '2025-03-19 13:33:35', NULL, 'pending', 3, NULL, 9, 'pending'),
(11, 2, 25.00, 'pending', '2025-03-19 15:27:26', '2025-03-19 15:27:26', 2, 'pending', 3, NULL, 9, 'pending'),
(12, 2, 700.00, 'pending', '2025-03-19 15:31:27', '2025-03-19 15:31:27', 1, 'pending', 3, NULL, 9, 'pending'),
(13, 2, 7245.00, 'cancelled', '2025-03-19 15:48:03', '2025-03-19 17:00:36', NULL, 'pending', 3, NULL, 11, 'packing'),
(14, 2, 450.00, 'cancelled', '2025-03-19 16:08:24', '2025-03-19 17:00:33', NULL, 'pending', 3, NULL, 11, 'packing'),
(15, 2, 45.00, 'pending', '2025-03-19 16:15:24', '2025-03-19 16:15:24', NULL, 'pending', 3, NULL, 9, 'pending'),
(16, 2, 900.00, 'pending', '2025-03-19 16:23:35', '2025-03-19 17:01:21', NULL, 'pending', 3, NULL, 11, 'pending'),
(17, 4, 2165.00, 'pending', '2025-03-19 19:24:50', '2025-03-19 19:24:50', NULL, 'pending', 3, NULL, 9, 'pending'),
(18, 4, 65.00, 'pending', '2025-03-19 19:33:10', '2025-03-19 19:33:10', NULL, 'pending', 3, NULL, 9, 'pending'),
(23, 4, 330.00, 'pending', '2025-03-19 19:53:12', '2025-03-19 19:53:12', NULL, 'pending', 3, NULL, 9, 'pending'),
(26, 4, 265.00, 'pending', '2025-03-19 19:59:27', '2025-03-19 19:59:27', NULL, 'pending', 3, NULL, 9, 'pending'),
(28, 4, 900.00, 'pending', '2025-03-19 20:26:28', '2025-03-19 20:26:28', NULL, 'pending', 3, NULL, 11, 'pending'),
(29, 4, 575.00, 'pending', '2025-03-19 20:33:11', '2025-03-19 20:33:11', 2, 'pending', 3, NULL, 9, 'pending'),
(35, 4, 250.00, 'pending', '2025-03-19 20:58:16', '2025-03-19 20:58:16', NULL, 'pending', 3, NULL, 11, 'pending'),
(40, 4, 200.00, 'pending', '2025-03-19 21:17:03', '2025-03-19 21:17:03', NULL, 'pending', 3, NULL, NULL, 'pending'),
(41, 4, 200.00, 'pending', '2025-03-19 21:22:49', '2025-03-19 21:22:49', NULL, 'pending', 3, NULL, 9, 'pending');

-- --------------------------------------------------------

--
-- Table structure for table `order_items`
--

CREATE TABLE `order_items` (
  `id` int(11) NOT NULL,
  `order_id` int(11) NOT NULL,
  `product_id` int(11) NOT NULL,
  `quantity` int(11) NOT NULL CHECK (`quantity` > 0),
  `price` decimal(10,2) NOT NULL CHECK (`price` >= 0)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `order_items`
--

INSERT INTO `order_items` (`id`, `order_id`, `product_id`, `quantity`, `price`) VALUES
(2, 2, 29, 2, 25.00),
(4, 4, 35, 1, 200.00),
(5, 10, 36, 1, 120.00),
(6, 10, 37, 1, 65.00),
(7, 10, 55, 1, 59.00),
(8, 11, 29, 1, 25.00),
(9, 12, 34, 1, 55.00),
(10, 12, 36, 6, 120.00),
(11, 13, 65, 10, 450.00),
(12, 13, 61, 5, 449.00),
(13, 13, 64, 2, 250.00),
(14, 14, 66, 1, 450.00),
(15, 15, 39, 1, 45.00),
(16, 16, 66, 1, 450.00),
(17, 16, 65, 1, 450.00),
(18, 17, 29, 12, 25.00),
(19, 17, 43, 1, 135.00),
(20, 17, 66, 3, 450.00),
(21, 17, 63, 1, 380.00),
(22, 18, 37, 1, 65.00),
(27, 23, 35, 1, 200.00),
(28, 23, 38, 1, 130.00),
(33, 26, 35, 1, 200.00),
(34, 26, 37, 1, 65.00),
(36, 28, 65, 2, 450.00),
(37, 29, 35, 3, 200.00),
(43, 35, 64, 1, 250.00),
(48, 40, 35, 1, 200.00),
(49, 41, 35, 1, 200.00);

-- --------------------------------------------------------

--
-- Table structure for table `payment_methods`
--

CREATE TABLE `payment_methods` (
  `id` int(11) NOT NULL,
  `name` varchar(100) NOT NULL,
  `description` text DEFAULT NULL,
  `is_active` tinyint(1) DEFAULT 1
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `payment_methods`
--

INSERT INTO `payment_methods` (`id`, `name`, `description`, `is_active`) VALUES
(1, 'บัตรเครดิต', 'ชำระเงินด้วยบัตรเครดิต', 1),
(2, 'โอนเงินผ่านธนาคาร', 'โอนเงินผ่านบัญชีธนาคาร', 1),
(3, 'ชำระเงินปลายทาง', 'ชำระเงินเมื่อได้รับสินค้า', 1);

-- --------------------------------------------------------

--
-- Table structure for table `permissions`
--

CREATE TABLE `permissions` (
  `permission_id` int(11) NOT NULL,
  `name` varchar(50) NOT NULL,
  `description` varchar(255) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `product_promotions`
--

CREATE TABLE `product_promotions` (
  `id` int(11) NOT NULL,
  `product_id` int(11) NOT NULL,
  `discount_id` int(11) NOT NULL,
  `start_date` datetime NOT NULL,
  `end_date` datetime NOT NULL,
  `is_active` tinyint(1) NOT NULL DEFAULT 1
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `reviews`
--

CREATE TABLE `reviews` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `product_id` int(11) NOT NULL,
  `seller_id` int(11) NOT NULL,
  `rating` tinyint(1) NOT NULL CHECK (`rating` between 1 and 5),
  `comment` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `reviews`
--

INSERT INTO `reviews` (`id`, `user_id`, `product_id`, `seller_id`, `rating`, `comment`, `created_at`) VALUES
(1, 4, 29, 9, 3, '1', '2025-03-19 13:52:54'),
(2, 4, 29, 9, 1, '2', '2025-03-19 13:53:16'),
(3, 4, 29, 9, 3, '1', '2025-03-19 14:01:51'),
(4, 2, 62, 11, 2, '2', '2025-03-19 17:05:59'),
(5, 2, 64, 11, 5, '5', '2025-03-19 17:11:39'),
(6, 2, 65, 11, 3, '1', '2025-03-19 17:11:43'),
(7, 4, 29, 9, 5, '5', '2025-03-19 19:13:57');

-- --------------------------------------------------------

--
-- Table structure for table `roles`
--

CREATE TABLE `roles` (
  `role_id` int(11) NOT NULL,
  `role_name` varchar(50) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `roles`
--

INSERT INTO `roles` (`role_id`, `role_name`) VALUES
(1, 'admin'),
(4, 'delivery'),
(3, 'seller'),
(2, 'user');

-- --------------------------------------------------------

--
-- Table structure for table `sellers`
--

CREATE TABLE `sellers` (
  `seller_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `store_name` varchar(100) NOT NULL,
  `store_description` text DEFAULT NULL,
  `phone_number` varchar(20) DEFAULT NULL,
  `commission_rate` decimal(5,2) DEFAULT NULL,
  `status` enum('active','inactive') NOT NULL DEFAULT 'active',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `seller_email` varchar(255) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `sellers`
--

INSERT INTO `sellers` (`seller_id`, `user_id`, `store_name`, `store_description`, `phone_number`, `commission_rate`, `status`, `created_at`, `updated_at`, `seller_email`) VALUES
(9, 4, 'ไก่', '123', '12312', NULL, 'active', '2025-03-18 12:48:52', '2025-03-19 17:31:28', '123555@gmail.com'),
(11, 2, 'นัทอ้วน', 'นัทอ้วน', '0958462520', NULL, 'active', '2025-03-19 14:40:20', '2025-03-19 17:28:23', 'dsad@gmail.com');

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `user_id` int(11) NOT NULL,
  `username` varchar(50) NOT NULL,
  `email` varchar(100) NOT NULL,
  `password` varchar(255) NOT NULL,
  `first_name` varchar(50) DEFAULT NULL,
  `last_name` varchar(50) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `reset_token` varchar(255) DEFAULT NULL,
  `reset_token_expires` int(11) DEFAULT NULL,
  `phone_number` varchar(20) DEFAULT NULL,
  `address` text DEFAULT NULL,
  `is_active` tinyint(1) NOT NULL DEFAULT 1
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`user_id`, `username`, `email`, `password`, `first_name`, `last_name`, `created_at`, `reset_token`, `reset_token_expires`, `phone_number`, `address`, `is_active`) VALUES
(1, 'testuser', 'testuser@example.com', '123', 'ทดสอบ', 'ผู้ใช้', '2025-03-18 10:01:51', NULL, NULL, '0958462520', '83/51', 1),
(2, 'admin', '123@gmail.com', '123', 'วงศธร', 'ฉาบสีทอง', '2025-03-18 11:06:08', NULL, NULL, '0958462520', '83/51', 1),
(3, 'admin1', '1234@gmail.com', '123', '123', '123', '2025-03-18 11:10:26', NULL, NULL, '123', NULL, 1),
(4, 'dang2551', '12345@gmail.com', '123', 'วงศธร', '123', '2025-03-18 11:12:01', NULL, NULL, '0958462520', '83/51', 1);

-- --------------------------------------------------------

--
-- Table structure for table `user_discounts`
--

CREATE TABLE `user_discounts` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `discount_id` int(11) NOT NULL,
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `received_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `user_discounts`
--

INSERT INTO `user_discounts` (`id`, `user_id`, `discount_id`, `is_active`, `received_at`) VALUES
(1, 4, 1, 1, '2025-03-19 12:03:58'),
(2, 4, 2, 0, '2025-03-19 12:04:02'),
(3, 2, 1, 1, '2025-03-19 15:16:18'),
(4, 2, 2, 1, '2025-03-19 15:16:19');

-- --------------------------------------------------------

--
-- Table structure for table `user_roles`
--

CREATE TABLE `user_roles` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `role_id` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `user_roles`
--

INSERT INTO `user_roles` (`id`, `user_id`, `role_id`) VALUES
(1, 2, 2),
(2, 3, 2),
(3, 4, 2),
(4, 4, 1),
(5, 4, 3),
(6, 2, 3),
(7, 4, 3),
(8, 4, 3);

--
-- Indexes for dumped tables
--

--
-- Indexes for table `banners`
--
ALTER TABLE `banners`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `cart`
--
ALTER TABLE `cart`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_id` (`user_id`),
  ADD KEY `product_id` (`product_id`);

--
-- Indexes for table `categories`
--
ALTER TABLE `categories`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `title` (`title`);

--
-- Indexes for table `discounts`
--
ALTER TABLE `discounts`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `title` (`title`),
  ADD UNIQUE KEY `title_2` (`title`),
  ADD KEY `seller_id` (`seller_id`);

--
-- Indexes for table `featured_products`
--
ALTER TABLE `featured_products`
  ADD PRIMARY KEY (`id`),
  ADD KEY `fk_category` (`category_id`),
  ADD KEY `fk_featured_products_seller` (`seller_id`),
  ADD KEY `idx_category_id` (`category_id`);

--
-- Indexes for table `notifications`
--
ALTER TABLE `notifications`
  ADD PRIMARY KEY (`id`),
  ADD KEY `seller_id` (`seller_id`),
  ADD KEY `order_id` (`order_id`);

--
-- Indexes for table `orders`
--
ALTER TABLE `orders`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_id` (`user_id`),
  ADD KEY `discount_id` (`discount_id`),
  ADD KEY `payment_method_id` (`payment_method_id`),
  ADD KEY `fk_orders_seller` (`seller_id`),
  ADD KEY `bank_account_id` (`bank_account_id`);

--
-- Indexes for table `order_items`
--
ALTER TABLE `order_items`
  ADD PRIMARY KEY (`id`),
  ADD KEY `order_id` (`order_id`),
  ADD KEY `product_id` (`product_id`);

--
-- Indexes for table `payment_methods`
--
ALTER TABLE `payment_methods`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `permissions`
--
ALTER TABLE `permissions`
  ADD PRIMARY KEY (`permission_id`),
  ADD UNIQUE KEY `name` (`name`);

--
-- Indexes for table `product_promotions`
--
ALTER TABLE `product_promotions`
  ADD PRIMARY KEY (`id`),
  ADD KEY `product_id` (`product_id`),
  ADD KEY `discount_id` (`discount_id`);

--
-- Indexes for table `reviews`
--
ALTER TABLE `reviews`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_id` (`user_id`),
  ADD KEY `product_id` (`product_id`),
  ADD KEY `fk_reviews_seller` (`seller_id`),
  ADD KEY `idx_product_id` (`product_id`);

--
-- Indexes for table `roles`
--
ALTER TABLE `roles`
  ADD PRIMARY KEY (`role_id`),
  ADD UNIQUE KEY `role_name` (`role_name`);

--
-- Indexes for table `sellers`
--
ALTER TABLE `sellers`
  ADD PRIMARY KEY (`seller_id`),
  ADD UNIQUE KEY `seller_email` (`seller_email`),
  ADD KEY `user_id` (`user_id`);

--
-- Indexes for table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`user_id`),
  ADD UNIQUE KEY `username` (`username`),
  ADD UNIQUE KEY `email` (`email`),
  ADD KEY `email_2` (`email`);

--
-- Indexes for table `user_discounts`
--
ALTER TABLE `user_discounts`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_id` (`user_id`),
  ADD KEY `discount_id` (`discount_id`);

--
-- Indexes for table `user_roles`
--
ALTER TABLE `user_roles`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_id` (`user_id`),
  ADD KEY `role_id` (`role_id`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `banners`
--
ALTER TABLE `banners`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT for table `cart`
--
ALTER TABLE `cart`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=33;

--
-- AUTO_INCREMENT for table `categories`
--
ALTER TABLE `categories`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=13;

--
-- AUTO_INCREMENT for table `discounts`
--
ALTER TABLE `discounts`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `featured_products`
--
ALTER TABLE `featured_products`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=67;

--
-- AUTO_INCREMENT for table `notifications`
--
ALTER TABLE `notifications`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `orders`
--
ALTER TABLE `orders`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=42;

--
-- AUTO_INCREMENT for table `order_items`
--
ALTER TABLE `order_items`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=50;

--
-- AUTO_INCREMENT for table `payment_methods`
--
ALTER TABLE `payment_methods`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `permissions`
--
ALTER TABLE `permissions`
  MODIFY `permission_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `product_promotions`
--
ALTER TABLE `product_promotions`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `reviews`
--
ALTER TABLE `reviews`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=8;

--
-- AUTO_INCREMENT for table `roles`
--
ALTER TABLE `roles`
  MODIFY `role_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT for table `sellers`
--
ALTER TABLE `sellers`
  MODIFY `seller_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=12;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `user_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT for table `user_discounts`
--
ALTER TABLE `user_discounts`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT for table `user_roles`
--
ALTER TABLE `user_roles`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=9;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `cart`
--
ALTER TABLE `cart`
  ADD CONSTRAINT `fk_cart_product` FOREIGN KEY (`product_id`) REFERENCES `featured_products` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_cart_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`) ON DELETE CASCADE;

--
-- Constraints for table `discounts`
--
ALTER TABLE `discounts`
  ADD CONSTRAINT `discounts_ibfk_1` FOREIGN KEY (`seller_id`) REFERENCES `sellers` (`seller_id`);

--
-- Constraints for table `featured_products`
--
ALTER TABLE `featured_products`
  ADD CONSTRAINT `fk_featured_products_category` FOREIGN KEY (`category_id`) REFERENCES `categories` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `fk_featured_products_seller` FOREIGN KEY (`seller_id`) REFERENCES `sellers` (`seller_id`) ON DELETE CASCADE;

--
-- Constraints for table `notifications`
--
ALTER TABLE `notifications`
  ADD CONSTRAINT `fk_notifications_seller` FOREIGN KEY (`seller_id`) REFERENCES `sellers` (`seller_id`) ON DELETE CASCADE,
  ADD CONSTRAINT `notifications_ibfk_2` FOREIGN KEY (`order_id`) REFERENCES `orders` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `orders`
--
ALTER TABLE `orders`
  ADD CONSTRAINT `fk_orders_seller` FOREIGN KEY (`seller_id`) REFERENCES `sellers` (`seller_id`) ON DELETE SET NULL,
  ADD CONSTRAINT `orders_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`) ON DELETE CASCADE,
  ADD CONSTRAINT `orders_ibfk_2` FOREIGN KEY (`discount_id`) REFERENCES `discounts` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `orders_ibfk_3` FOREIGN KEY (`payment_method_id`) REFERENCES `payment_methods` (`id`),
  ADD CONSTRAINT `orders_ibfk_4` FOREIGN KEY (`bank_account_id`) REFERENCES `bank_accounts` (`id`);

--
-- Constraints for table `order_items`
--
ALTER TABLE `order_items`
  ADD CONSTRAINT `order_items_ibfk_1` FOREIGN KEY (`order_id`) REFERENCES `orders` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `order_items_ibfk_2` FOREIGN KEY (`product_id`) REFERENCES `featured_products` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `product_promotions`
--
ALTER TABLE `product_promotions`
  ADD CONSTRAINT `product_promotions_ibfk_1` FOREIGN KEY (`product_id`) REFERENCES `featured_products` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `product_promotions_ibfk_2` FOREIGN KEY (`discount_id`) REFERENCES `discounts` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `reviews`
--
ALTER TABLE `reviews`
  ADD CONSTRAINT `fk_reviews_seller` FOREIGN KEY (`seller_id`) REFERENCES `sellers` (`seller_id`) ON DELETE CASCADE,
  ADD CONSTRAINT `reviews_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`) ON DELETE CASCADE,
  ADD CONSTRAINT `reviews_ibfk_2` FOREIGN KEY (`product_id`) REFERENCES `featured_products` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `sellers`
--
ALTER TABLE `sellers`
  ADD CONSTRAINT `sellers_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`) ON DELETE CASCADE;

--
-- Constraints for table `user_discounts`
--
ALTER TABLE `user_discounts`
  ADD CONSTRAINT `fk_user_discounts_discount` FOREIGN KEY (`discount_id`) REFERENCES `discounts` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_user_discounts_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`) ON DELETE CASCADE;

--
-- Constraints for table `user_roles`
--
ALTER TABLE `user_roles`
  ADD CONSTRAINT `user_roles_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`) ON DELETE CASCADE,
  ADD CONSTRAINT `user_roles_ibfk_2` FOREIGN KEY (`role_id`) REFERENCES `roles` (`role_id`) ON DELETE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
