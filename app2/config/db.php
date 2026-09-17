<?php
/**
 * Yii 2 database connection.
 *
 * Deliberately the same database as the Yii 1 application: during the port both
 * frameworks read and write the same tables. Credentials come from the same
 * environment variables config/prod-db.php uses, loaded by the entry script.
 */
return [
    'class' => \yii\db\Connection::class,
    'dsn' => sprintf(
        'mysql:host=%s;dbname=%s',
        getenv('POS_DB_HOST') !== false ? getenv('POS_DB_HOST') : 'db',
        getenv('DB_NAME') !== false ? getenv('DB_NAME') : 'pos_live'
    ),
    'username' => getenv('POS_DB_USER') !== false ? getenv('POS_DB_USER') : 'root',
    'password' => getenv('POS_DB_PASSWORD') !== false ? getenv('POS_DB_PASSWORD') : '',
    'charset' => 'utf8',
    'tablePrefix' => 'tbl_',
    'enableSchemaCache' => true,
    'schemaCacheDuration' => 3600,
    // Money and quantity columns are DECIMAL, which PDO returns as strings
    // anyway. Kept explicit so Yii 2 formats identically to the Yii 1 side,
    // which sets the same attribute in config/prod-db.php.
    'attributes' => [
        \PDO::ATTR_STRINGIFY_FETCHES => true,
    ],
];
