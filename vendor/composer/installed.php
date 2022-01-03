<?php return array(
    'root' => array(
        'pretty_version' => '1.0.0+no-version-set',
        'version' => '1.0.0.0',
        'type' => 'wordpress-module',
        'install_path' => __DIR__ . '/../../',
        'aliases' => array(),
        'reference' => NULL,
        'name' => 'mijora/omniva-tarptautines-woo',
        'dev' => true,
    ),
    'versions' => array(
        'mijora/omniva-tarptautines-woo' => array(
            'pretty_version' => '1.0.0+no-version-set',
            'version' => '1.0.0.0',
            'type' => 'wordpress-module',
            'install_path' => __DIR__ . '/../../',
            'aliases' => array(),
            'reference' => NULL,
            'dev_requirement' => false,
        ),
        'omniva/api-lib' => array(
            'pretty_version' => 'dev-master',
            'version' => 'dev-master',
            'type' => 'library',
            'install_path' => __DIR__ . '/../omniva/api-lib',
            'aliases' => array(
                0 => '9999999-dev',
            ),
            'reference' => 'baa5aa2d7256dc3d46e7bae48a9f8fddf1c05499',
            'dev_requirement' => false,
        ),
    ),
);
