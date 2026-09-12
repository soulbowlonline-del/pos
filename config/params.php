<?php

// this contains the application parameters that can be maintained via GUI
return array(
		// this is used in error pages
		'adminEmail'=>'admin@outlinesystemsindia.com',
		// the copyright information displayed in the footer section
		'company'=>'DAS POS',
		'mail_email'=>'marketing@soulbowl.in',
		'saleStatus' => true,
		'ftp_username' => 'soulbowlftp',
		'ftp_password' => (getenv('POS_FTP_PASSWORD') !== false) ? getenv('POS_FTP_PASSWORD') : '',
		'soul_bowl_url' => 'https://sect4.soulbowl.in/pos/product_pos/',
		'chaos_constant' => 20,
		'chaos_constant_reduce' => -20,
		);
