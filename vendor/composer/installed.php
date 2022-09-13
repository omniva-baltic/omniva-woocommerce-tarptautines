<?php return array(
    'root' => array(
        'name' => 'mijora/omniva-tarptautines-woo',
        'pretty_version' => '1.0.0+no-version-set',
        'version' => '1.0.0.0',
        'reference' => NULL,
        'type' => 'wordpress-module',
        'install_path' => __DIR__ . '/../../',
        'aliases' => array(),
        'dev' => true,
    ),
    'versions' => array(
        'mijora/omniva-tarptautines-woo' => array(
            'pretty_version' => '1.0.0+no-version-set',
            'version' => '1.0.0.0',
            'reference' => NULL,
            'type' => 'wordpress-module',
            'install_path' => __DIR__ . '/../../',
            'aliases' => array(),
            'dev_requirement' => false,
        ),
        'omniva/api-lib' => array(
            'pretty_version' => 'dev-master',
            'version' => 'dev-master',
            'reference' => '23e274fce80f77e2b8aaa6c7d1f1bacbcfafdcd1',
            'type' => 'library',
            'install_path' => __DIR__ . '/../omniva/api-lib',
            'aliases' => array(
                0 => '9999999-dev',
            ),
            'dev_requirement' => false,
        ),
    ),
);
