CREATE TABLE users (
  id INT AUTO_INCREMENT PRIMARY KEY,
  name VARCHAR(100) NOT NULL,
  email VARCHAR(100) NOT NULL UNIQUE,
  password VARCHAR(255) NOT NULL,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);


CREATE TABLE adminuser (
  id INT AUTO_INCREMENT PRIMARY KEY,
  username VARCHAR(50) NOT NULL UNIQUE,
  password VARCHAR(255) NOT NULL
);

INSERT INTO adminuser (username, password) VALUES ('admin','admin@123);


CREATE TABLE pass_applications (
  id INT AUTO_INCREMENT PRIMARY KEY,
  pass_type VARCHAR(50) NOT NULL,
  full_name VARCHAR(100) NOT NULL,
  mobile VARCHAR(15) NOT NULL,
  email VARCHAR(100),
  address TEXT NOT NULL,
  aadhar_number VARCHAR(255) NOT NULL UNIQUE,
  payment_method VARCHAR(50) NOT NULL,
  card_number VARCHAR(20),
  card_name VARCHAR(100),
  pass_expiry_date VARCHAR(7), -- Format: YYYY-MM
  pass_duration INT NOT NULL, -- Duration in months
  cvv VARCHAR(4),
  pass_number VARCHAR(20),
  created_at DATETIME DEFAULT CURRENT_TIMESTAMP
);


CREATE TABLE pass_renewals (
    id INT AUTO_INCREMENT PRIMARY KEY,
    pass_number VARCHAR(20) NOT NULL,
    original_pass_id INT NOT NULL,
    full_name VARCHAR(100) NOT NULL,
    mobile VARCHAR(15) NOT NULL,
    email VARCHAR(100),
    address TEXT NOT NULL,
    pass_type VARCHAR(50) NOT NULL,
    renewal_duration INT NOT NULL,
    renewal_expiry_date DATE NOT NULL,
    payment_method VARCHAR(20) NOT NULL,
    card_number VARCHAR(20),
    card_name VARCHAR(100),
    card_expiry VARCHAR(10),
    cvv VARCHAR(4),
    status VARCHAR(20) NOT NULL DEFAULT 'pending',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NULL ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (original_pass_id) REFERENCES pass_applications(id),
    CONSTRAINT chk_status CHECK (status IN ('pending', 'approved', 'rejected'))
);


CREATE TABLE `feedback` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) DEFAULT NULL COMMENT 'User who submitted feedback',
  `type` enum('complaint','suggestion','question','compliment','bug_report') NOT NULL,
  `subject` varchar(255) NOT NULL,
  `message` text NOT NULL,
  `status` enum('pending','in_progress','resolved','rejected') NOT NULL DEFAULT 'pending',
  `priority` enum('low','medium','high','critical') DEFAULT 'medium',
  `department` enum('support','technical','sales','billing','general') DEFAULT 'general',
  `admin_notes` text DEFAULT NULL COMMENT 'Internal notes',
  `response` text DEFAULT NULL COMMENT 'Official response to user',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NULL DEFAULT NULL ON UPDATE current_timestamp(),
  `resolved_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `user_id` (`user_id`),
  KEY `status` (`status`),
  KEY `type` (`type`),
  KEY `priority` (`priority`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;