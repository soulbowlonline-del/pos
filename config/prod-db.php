<?php
return array( 
	'connectionString' => 'mysql:host=db;dbname=pos_live',
	'emulatePrepare' => true,

	// PHP 8.1 changed PDO_MySQL to return native PHP types for FLOAT/INT columns
	// instead of strings. This schema stores money and quantities in FLOAT
	// columns (price, mrp, total_amt, tax_amount, ... are float(10,3)), so a
	// value that rendered as '0.890' on PHP 5.6 came back as the double 0.89 and
	// printed as "0.89" - trailing zeros silently dropped from every invoice,
	// GST report and Excel export. DECIMAL columns are unaffected (still strings).
	//
	// Forcing STRINGIFY_FETCHES restores the pre-8.1 behaviour for the whole
	// application in one place, rather than reformatting hundreds of views.
	// Revisit only if those columns are migrated FLOAT -> DECIMAL, which is the
	// real fix for storing money.
	'attributes' => array(
		PDO::ATTR_STRINGIFY_FETCHES => true,
	),
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

