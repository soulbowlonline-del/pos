CREATE TABLE `tbl_whatsapp_logs` (
  `id` int NOT NULL AUTO_INCREMENT PRIMARY KEY,
  `number` varchar(255) COLLATE 'utf8_general_ci' NOT NULL,
  `message_id` varchar(255) COLLATE 'utf8_general_ci' NULL,
  `message` text COLLATE 'utf8_general_ci' NOT NULL,
  `template_name` varchar(255) COLLATE 'utf8_general_ci' NULL,
  `status` int NOT NULL,
  `created_at` datetime NOT NULL
) ENGINE='InnoDB' COLLATE 'utf8_general_ci';