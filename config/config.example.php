<?php

// Copy to config.php and replace these local-development values.
// Keep the real file out of version control and outside the public web root.
return [
    'db' => [
        'host' => '127.0.0.1',
        'port' => 3306,
        'name' => 'crm_db',
        'user' => 'crm_user',
        'password' => 'CHANGE_THIS_PASSWORD',
    ],
    'app' => [
        // Example: '' for a virtual host pointing to public/, or '/crm/public' on XAMPP.
        'base_path' => '/crm/public',
        'timezone' => 'UTC',
    ],
];
