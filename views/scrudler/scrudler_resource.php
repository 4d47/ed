<!DOCTYPE html>
<html lang="en">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title><?= $table ?></title>
        <link rel="stylesheet" href="//netdna.bootstrapcdn.com/bootstrap/3.0.1/css/bootstrap.min.css">
        <link rel="stylesheet" href="//netdna.bootstrapcdn.com/bootstrap/3.0.1/css/bootstrap-theme.min.css">
        <?php foreach (explode(',', 'datepicker.css,bootstrap-datetimepicker.min.css,bootstrap-timepicker.min.css,select2.css,main.css') as $stylesheet): ?>
            <link rel="stylesheet" href="<?= static::path('assets', 'admin', $stylesheet) ?>">
        <?php endforeach ?>
        <?php foreach ($config['stylesheets'] as $stylesheet): ?>
            <link rel="stylesheet" href="<?= $stylesheet ?>">
        <?php endforeach ?>
    </head>
    <body>
        <div id="wrap">
            <div class="navbar navbar-default navbar-fixed-top">
                <div class="container">
                    <a class="navbar-brand" href="<?= static::link() ?>">
                        <span class="glyphicon glyphicon-leaf"></span>
                        <span class="sr-only">/admin</span>
                    </a>
                    <ul class="nav navbar-nav">
                        <?php foreach (array_keys($schema) as $tbl): ?>
                        <li class="<?= ($tbl === $table) ? 'active' : '' ?>">
                            <a href="<?= static::link($tbl) ?>"><?= $config['labelize']($tbl) ?></a>
                        </li>
                        <li>
                            <a href="<?= static::link($tbl, 'new') ?>" title="new record to <?= $config['labelize']($tbl) ?>">+</a>
                        </li>
                        <?php endforeach ?>
                    </ul>
                </div>
            </div>

            <div class="container">
                <?php if ($flash): ?>
                <div class="alert alert-success alert-dismissable">
                    <button type="button" class="close" data-dismiss="alert" aria-hidden="true">&times;</button>
                    <span class="glyphicon glyphicon-ok"></span>
                    <?= $flash ?>
                </div>
                <?php endif ?>

                <?php if ($key): ?>
                <?php
                $pk = key(array_filter($schema[$table], function($el) { return !empty($el['pk']); }));
                $df = $pk;
                ?>
                <form method="post" enctype="multipart/form-data" class="form-record form" role="form">

                    <?php foreach ($schema[ $table ] as $column => $attr): ?>
                    <div class="form-group">
                        <?php if (empty($attr['auto']) || $key != 'new'): ?>
                        <label for="<?= $column ?>" class="control-label"><?= $config['labelize']($column) ?>:</label>
                        <?php endif ?>

                        <?php
                        if (!empty($attr['auto'])) {
                            if ($key != 'new') {
                                echo tag::input(array('type' => 'text', 'id' => $column, 'name' => $column, 'class' => 'form-control', 'disabled' => true, 'value' => @$row->$column));
                            }
                        } else if (isset($schema[ $table ][$column]['ref'])) {
                            # if is new, try to see if associated record was passed
                            # in the querystring .. only work with association per table now
                            $assoc_table = $schema[ $table ][$column]['ref']['table'];
                            // note: can we turn the select in radio button if >= 3 or something
                            // i guess this would be a feature of the js plugin select2

                            $value = @$row->$column ?: @$_GET[$column]; // or try to use autoselect from querystring
                            echo tag::br();
                            echo tag::input(array('type' => 'text', 'class' => 'ref', 'name' => $column, 'data-table' => $assoc_table, 'value' => $value, 'required' => empty($attr['null'])));

                            if ($key != 'new') {
                                // add a little goto link
                                echo ' ';
                                echo tag::a(array('href' => static::link($schema[ $table ][$column]['ref']['table'], $row->$column)), new tag('<span class="glyphicon glyphicon-chevron-right"></span>'));
                            }
                        } else if ($attr['type'] === 'text') {
                            echo tag::textarea(array('id' => $column, 'name' => $column, 'class' => 'form-control', 'required' => empty($attr['null'])), @$row->$column);
                        } else if ($attr['type'] == 'boolean') {
                            echo tag::br();
                            echo tag::div(array('class' => 'btn-group', 'data-toggle' => 'buttons'),
                                tag::label(array('class' => 'btn btn-default' . (@$row->$column ? ' active' : '')),
                                    tag::input(array('type' => 'radio', 'id' => $column, 'name' => $column, 'value' => '1', 'class' => 'form-control', 'checked' => @$row->$column)),
                                    'Yes'
                                ),
                                tag::label(array('class' => 'btn btn-default' . (!@$row->$column ? ' active' : '')),
                                    tag::input(array('type' => 'radio', 'id' => $column, 'name' => $column, 'value' => '0', 'class' => 'form-control', 'checked' => ! @$row->$column)),
                                    'No'
                                )
                            );
                        } else if ($attr['type'] === 'integer') {
                            echo tag::input(array('type' => 'number', 'id' => $column, 'name' => $column, 'class' => 'form-control', 'value' => @$row->$column, 'required' => empty($attr['null'])));
                        } else if ($attr['type'] === 'numeric') {
                            echo tag::input(array('type' => 'number', 'step' => 'any', 'id' => $column, 'name' => $column, 'class' => 'form-control', 'value' => @$row->$column, 'required' => empty($attr['null'])));
                        } else if ($attr['type'] == 'date') {
                            echo tag::input(array('type' => 'date', 'id' => $column, 'name' => $column, 'class' => 'form-control', 'data-date-format' => 'yyyy-mm-dd', 'value' => @$row->$column, 'required' => empty($attr['null'])));
                        } else if ($attr['type'] == 'time') {
                            echo tag::input(array('type' => 'time', 'id' => $column, 'name' => $column, 'class' => 'form-control', 'value' => @$row->$column, 'required' => empty($attr['null'])));
                        } else if ($attr['type'] == 'datetime') {
                            echo tag::input(array('type' => 'datetime', 'id' => $column, 'name' => $column, 'class' => 'form-control', 'data-date-format' => 'yyyy-mm-dd hh:ii:ss', 'value' => @$row->$column, 'required' => empty($attr['null'])));
                        } else if ($attr['type'] == 'enum') {
                            echo tag::br();
                            echo tag::begin_select(array('id' => $column, 'name' => $column, 'class' => 'enum', 'required' => empty($attr['null'])));
                            foreach ($attr['precision'] as $option) {
                                echo tag::option(array('selected' => @$row->$column == $option), $option);
                            }
                            echo tag::end_select();
                        } else if ($attr['type'] == 'blob') {
                            echo tag::div(array('class' => 'file'),
                                tag::a(array('class' => 'btn btn-default'),
                                    'Browse...',
                                    tag::input(array('type' => 'file', 'name' => $column))
                                ),
                                tag::span(array('class' => 'upload-file-info'),
                                    $row->$column
                                        ? tag::a(array('href' => \Scrudler\BlobResource::link($table, $key, $column), 'target' => '_blank'), $column)
                                        : ''
                                )
                            );
                        } else {
                            $options = array('type' => 'text', 'id' => $column, 'name' => $column, 'value' => @$row->$column, 'class' => 'form-control ' . $attr['type'], 'required' => empty($attr['null']));
                            if (!empty($attr['precision'])) {
                                $options['maxlength'] = $attr['precision'];
                                if ($attr['precision'] < 20) { # 20 is just whats close to browser defaults
                                    $options['size'] = $attr['precision'] + 1;
                                }
                            }
                            $input = tag::input($options);
                            if ($key != 'new') {
                                $v = $row->$column;
                                if (filter_var($v, FILTER_VALIDATE_URL)) {
                                    $input = tag::div(array('class' => 'input-group'),
                                        tag::a(array('href' => $v, 'target' => '_blank', 'class' => 'input-group-addon'), tag::span(array('class' => 'glyphicon glyphicon-chevron-right'))),
                                        $input
                                    );
                                } else if (filter_var($v, FILTER_VALIDATE_EMAIL)) {
                                    $input = tag::div(array('class' => 'input-group'),
                                        tag::a(array('href' => "mailto:$v", 'class' => 'input-group-addon'), tag::span(array('class' => 'glyphicon'), '@')),
                                        $input
                                    );
                                }
                            }
                            echo $input;
                        }
                        ?>
                    </div>
                    <?php endforeach ?>

                    <div class="button-group" style="background:#f0f0f0;padding:1em .5em; border-top:1px solid #cecece;">
                        <button type="submit" class="btn btn-primary">
                            <span class="glyphicon glyphicon-ok-sign"></span>
                            <?= $key == 'new' ? 'Create' : 'Update' ?>
                        </button>
                        <?php if ($key !== 'new'): ?>
                        <a href="?_method=delete"
                           class="btn btn-default"
                           style="margin-left:1em;color:#B92C28;"
                           data-bb="confirm">
                            <span class="glyphicon glyphicon-remove"></span>
                            Delete
                        </a>
                        <?php endif ?>
                    </div>
                    <div class="clearfix" style="margin-bottom:2em;"></div>
                </form>
            <?php endif ?>


            <?php foreach ($has as $tbl => $infos): ?>

                <div id="<?= $tbl ?>" class="table-responsive">
                    <table class="table">
                        <thead>
                            <tr>
                                <td colspan="<?= count($schema[$tbl]) ?>" style="padding:0;">
                                    <?php
                                    // autoselect association with this record
                                    $query = array();
                                    foreach ($schema[$tbl] as $name => $column) {
                                        if (isset($column['ref']['table']) && $column['ref']['table'] == $table) {
                                            $query[$name] = $key;
                                        }
                                    }
                                    $query = $query ? '?' . http_build_query($query) : '';
                                    ?>


                                    <div class="pull-left" style="margin:12px 0;">
                                        <span style="color:gray;">
                                            <?php $offset = (($infos->page - 1) * $infos->limit) + 1 ?>
                                            <?= $config['labelize']($tbl) ?>
                                            <b><?= $offset ?></b>-<b><?= ($offset - 1) + count($infos->results) ?></b>
                                            <?php if (count($infos->results) != $infos->total): ?>
                                                / <b><?= $infos->total ?></b> in total
                                            <?php endif ?>
                                        </span>
                                        <a href="<?= static::link($tbl, 'new') . $query ?>" style="padding-left:1em;">
                                            <span class="glyphicon glyphicon-plus-sign"></span>
                                            New
                                        </a>
                                    </div>

                                    <form action="#<?= $tbl ?>" method="get" class="navbar-form navbar-right" role="search" style="padding:0;">
                                        <?php foreach ($_GET as $name => $value): ?>
                                            <?= tag::input(array('type' => 'hidden', 'name' => $name, 'value' => $value)); ?>
                                        <?php endforeach ?>
                                        <div class="input-group">
                                            <?= tag::input(array('type' => 'text', 'name' => "$tbl-search", 'class' => 'form-control input-sm', 'placeholder' => 'Search', 'value' => isset($_GET["$tbl-search"]) ? $_GET["$tbl-search"] : '')) ?>
                                            <span class="input-group-btn">
                                                <button type="submit" class="btn btn-default input-sm">
                                                    <span class="glyphicon glyphicon-search"></span>
                                                    <span class="sr-only">Search</span>
                                                </button>

                                                <?php if (!empty($_GET["$tbl-search"])): ?>
                                                <button type="submit" class="close" style="float:none;margin-left:0.3em;"
                                                    onclick="$('input[name=<?= "$tbl-search" ?>]').val('')">
                                                    &times;
                                                </button>
                                                <?php endif ?>
                                            </span>
                                        </div>
                                    </form>
                                </td>
                            </tr>
                            <tr>
                            <?php foreach ($schema[$tbl] as $name => $col): ?>
                                <th style="white-space:nowrap;">
                                    <?php if (!empty($infos->aggregates[$name])): ?>
                                        <a href="#"
                                            class="infos"
                                            data-toggle="popover"
                                            data-html="true"
                                            data-placement="bottom"
                                            data-content="
                                                <?php
                                                foreach ($infos->aggregates[$name] as $f => $value) {
                                                    echo "$f: $value<br>";
                                                }
                                                ?>
                                            ">
                                            <span class="glyphicon glyphicon-info-sign"></span>
                                        </a>
                                    <?php endif ?>
                                    <?php
                                    $icon = '';
                                    $dir = '';
                                    if (empty($_GET["$tbl-sort"])) {
                                        if (!empty($schema[$tbl][$name]['pk'])) {
                                            $icon = tag::span(array('class' => 'glyphicon glyphicon-chevron-down'));
                                            $dir = '-';
                                        }
                                    } else if ($_GET["$tbl-sort"] === $name) {
                                        $icon = tag::span(array('class' => 'glyphicon glyphicon-chevron-down'));
                                        $dir = '-';
                                    } else if ($_GET["$tbl-sort"] === "-$name") {
                                        $icon = tag::span(array('class' => 'glyphicon glyphicon-chevron-up'));
                                        $dir = '';
                                    }

                                    ?>
                                    <a href="?<?= http_build_query(array("$tbl-sort" => "$dir$name") + $_GET) ?>">
                                        <?= $config['labelize']($name) ?>
                                    </a>
                                    <?= $icon ?>
                                </th>
                            <?php endforeach ?>
                            </tr>
                        </thead>

                        <?php if ($infos->pages > 1): ?>
                        <tfoot>
                            <tr>
                                <td colspan="<?= count($schema[$tbl]) ?>">
                                    <?php
                                    $adapter = new \Pagerfanta\Adapter\FixedAdapter($infos->total, $infos->results);
                                    $pagerfanta = new \Pagerfanta\Pagerfanta($adapter);
                                    $pagerfanta->setMaxPerPage($config['max_per_page']);
                                    $pagerfanta->setCurrentPage( !empty($_GET["$tbl-page"]) ? $_GET["$tbl-page"] : 1 );
                                    $pagination = new \Pagerfanta\View\TwitterBootstrap3View();
                                    $routeGenerator = function($page) use ($tbl) {
                                        return '?' . http_build_query(array("$tbl-page" => $page) + $_GET);
                                    };
                                    echo $pagination->render($pagerfanta, $routeGenerator, $config['pagination']);
                                    ?>
                                </td>
                            </tr>
                        </tfoot>
                        <?php endif ?>
                        <tbody>
                            <?php
                            if (count($infos->results) == 0) {
                                echo tag::tr(
                                    tag::td(array('colspan' => count($schema[$tbl])), tag::b('No results'))
                                );
                            } else {
                                $pk = key(array_filter($schema[$tbl], function($el) { return !empty($el['pk']); }));
                                $df = $pk;
                                foreach ($infos->results as $obj) {
                                    echo '<tr>';
                                    foreach ($schema[$tbl] as $name => $col) {
                                        $value = $obj->$name;
                                        if ($schema[$tbl][$name]['type'] === 'boolean') {
                                            $value = tag::code($obj->$name ? 'yes' : 'no');
                                        }
                                        $args = strlen($value) > 20
                                            ? array('title' => \Stringy\StaticStringy::truncate($value, 500, '...'))
                                            : array();
                                        if (!empty($col['pk'])) {
                                            $label = !empty($col['auto']) ? "#$value" : $value;
                                            $content = tag::a(array('href' => static::link($tbl, $value)), $label);
                                        } else if (!empty($col['ref'])) {
                                            $label = ($value && !empty($schema[$col['ref']['table']][$col['ref']['column']])) ? "#$value" : $value;
                                            $content = tag::a(array('href' => static::link($col['ref']['table'], $value)), $label);
                                        } else {
                                            $content = tag::span($args, \Stringy\StaticStringy::truncate($value, 20, '...'));
                                        }
                                        echo tag::td($content);
                                    }
                                    echo '</tr>';
                                }
                            }
                            ?>
                        </tbody>
                    </table>
                </div>
            <?php endforeach ?>
            </div>
        </div>
        <?php foreach (explode(',', 'jquery-2.0.3.min.js,bootstrap.min.js,bootstrap-datetimepicker.min.js,bootstrap-timepicker.min.js,bootstrap-datepicker.js,select2.min.js,bootbox.min.js,main.js') as $script): ?>
            <script src="<?= static::path('assets', 'admin', $script) ?>"></script>
        <?php endforeach ?>
        <?php foreach ($config['scripts'] as $script): ?>
            <script src="<?= $script ?>"></script>
        <?php endforeach ?>
    </body>
</html>
