<?php

/* Please do not edit this file, it will be overwritten if you update.
 * Instead copy and change only what you want to `config.php`,
 * see `config.php.sample`
 */
return
    array(
        'debug' =>
            false,
        'locale' =>
            'en_CA',
        'db' =>
            array(
                'dsn' => 'sqlite:chinook.db',
                'tag' => false
                # Caching tag, change whenever the schema is changed, disable with `false`
            ),
        'stylesheets' =>
            # Custom stylesheets to be included
            array(
            ),
        'scripts' =>
            # Custom javascript to be included
            array(
            ),
        'schema_filter' =>
            # Provides a way to remove tables and columns from the display
            function ($schema) {
                # unset($schema['Customer'], $schema['Employee'], $schema['Invoice'], $schema['InvoiceLine'], $schema['Track']['UnitPrice']);
                return $schema;
            },
        'select_filter' =>
            # Provides a way to systematically apply filters to 
            # search queries.  For example this could be used to
            # hide disabled data to user.
            function ($table, $params) {
                // if ($table == 'Album')
                //    $params['Title Like ?'] = '%Rock%';
                return $params;
            },
        'labelize' =>
            # Provides a way to customize the display name for the tables and columns
            function ($label) {
                return \Stringy\StaticStringy::humanize(\Stringy\StaticStringy::underscored($label));
            },
        'max_per_page' =>
            # The maximum number of results in a listing
            50,
        'pagination' =>
            array(
                'proximity' => 4,
                'prev_message' => '&laquo;',
                'next_message' => '&raquo;'
            ),
        'resources' =>
            array(
                'Scrudler\ScrudlerResource',
                'Scrudler\BlobResource',
            ),
    );
