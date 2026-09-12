<?php
return array( 
	'connectionString' => 'mysql:host=db;dbname=pos_live',
	'emulatePrepare' => true,
	'username' => (getenv('POS_DB_USER') !== false) ? getenv('POS_DB_USER') : 'root',
	'password' => (getenv('POS_DB_PASSWORD') !== false) ? getenv('POS_DB_PASSWORD') : '',
	'charset' => 'utf8',
	'tablePrefix' => 'tbl_',
	'schemaCachingDuration' => (60 * 60 * 24),
    // Query-result caching disabled: with a cache component now enabled, a 24h
    // global TTL would serve stale POS data (new orders/items invisible for a day).
    // Schema caching above is safe; per-query caching should be opt-in per query.
    'queryCachingDuration'=> 0 ,
);

