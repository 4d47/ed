<!DOCTYPE html>
<html lang="en">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>
            <?= $table ?>
            <?php
            if ($data->row):
                echo $data->row->__tostring;
            endif;
            ?>
        </title>
        <link rel="stylesheet" href="<?= \Ed\AssetsResource::link('css/ed.css') ?>">
        <?php foreach ($data->config['stylesheets'] as $stylesheet): ?>
            <link rel="stylesheet" href="<?= $stylesheet ?>">
        <?php endforeach ?>
    </head>
    <body>
        <div id="wrap">
            <div class="navbar navbar-default navbar-fixed-top">
                <div class="container">
                    <a class="navbar-brand" href="<?= static::link() ?>">
                        <span class="glyphicon glyphicon-leaf"></span>
                        <span class="sr-only"><?= static::link() ?></span>
                    </a>
                    <ul class="nav navbar-nav">
                        <?php foreach (array_keys($data->schema) as $tbl): ?>
                        <li class="<?= ($tbl === $table) ? 'active' : '' ?>">
                            <a href="<?= static::link($tbl) ?>"><?= $data->config['labelize']($tbl) ?></a>
                        </li>
                        <?php if ($tbl == $table): ?>
                        <li>
                            <a href="<?= static::link($tbl, 'new') ?>" title="<?= _('New') ?> <?= $data->config['labelize']($tbl) ?>">+</a>
                        </li>
                        <?php endif ?>
                        <?php endforeach ?>
                    </ul>
                </div>
            </div>

            <div class="container">
                <?php if ($data->flash): ?>
                <div class="alert alert-success alert-dismissable">
                    <button type="button" class="close" data-dismiss="alert" aria-hidden="true">&times;</button>
                    <span class="glyphicon glyphicon-ok"></span>
                    <?= $data->flash ?>
                </div>
                <?php endif ?>

                <?php if ($id): ?>
                <?php
                $pk = key(array_filter($data->schema[$table], function($el) { return !empty($el['pk']); }));
                $df = $pk;
                ?>
                <form method="post" enctype="multipart/form-data" class="form-record form" role="form">

                    <?php foreach ($data->schema[ $table ] as $column => $attr): ?>
                    <div class="form-group">
                        <?php if (empty($attr['auto']) || $id != 'new'): ?>
                        <label for="<?= $column ?>" class="control-label"><?= $data->config['labelize']($column) ?>:</label>
                        <?php endif ?>

                        <?php
                        if (!empty($attr['auto'])):
                            if ($id != 'new'):
                                echo tag::input(array('type' => 'text', 'id' => $column, 'name' => $column, 'class' => 'form-control', 'disabled' => true, 'value' => @$data->row->$column));
                            endif;
                        elseif (isset($data->schema[ $table ][$column]['ref'])):
                            # if is new, try to see if associated record was passed
                            # in the querystring .. only work with association per table now
                            $assoc_table = $data->schema[ $table ][$column]['ref']['table'];
                            // note: can we turn the select in radio button if >= 3 or something
                            // i guess this would be a feature of the js plugin select2

                            $value = @$data->row->$column ?: @$_GET[$column]; // or try to use autoselect from querystring
                            echo tag::br();
                            echo tag::input(array('type' => 'text', 'class' => 'ref', 'name' => $column, 'data-table' => $assoc_table, 'value' => $value, 'required' => empty($attr['null'])));

                            if ($id != 'new'):
                                // add a little goto link
                                echo ' ';
                                echo tag::a(array('href' => static::link($data->schema[ $table ][$column]['ref']['table'], $data->row->$column)), new tag('<span class="glyphicon glyphicon-chevron-right"></span>'));
                            endif;
                        elseif ($attr['type'] === 'text'):
                            echo tag::textarea(array('id' => $column, 'name' => $column, 'class' => 'form-control', 'required' => empty($attr['null'])), @$data->row->$column);
                        elseif ($attr['type'] == 'boolean'):
                            echo tag::br();
                            echo tag::div(array('class' => 'btn-group', 'data-toggle' => 'buttons'),
                                tag::label(array('class' => 'btn btn-default' . (@$data->row->$column ? ' active' : '')),
                                    tag::input(array('type' => 'radio', 'id' => $column, 'name' => $column, 'value' => '1', 'class' => 'form-control', 'checked' => @$data->row->$column)),
                                    'Yes'
                                ),
                                tag::label(array('class' => 'btn btn-default' . (!@$data->row->$column ? ' active' : '')),
                                    tag::input(array('type' => 'radio', 'id' => $column, 'name' => $column, 'value' => '0', 'class' => 'form-control', 'checked' => ! @$data->row->$column)),
                                    'No'
                                )
                            );
                        elseif ($attr['type'] === 'integer'):
                            echo tag::input(array('type' => 'number', 'id' => $column, 'name' => $column, 'class' => 'form-control', 'value' => @$data->row->$column, 'required' => empty($attr['null'])));
                        elseif ($attr['type'] === 'numeric'):
                            echo tag::input(array('type' => 'number', 'step' => 'any', 'id' => $column, 'name' => $column, 'class' => 'form-control', 'value' => @$data->row->$column, 'required' => empty($attr['null'])));
                        elseif ($attr['type'] == 'date'):
                            echo tag::input(array('type' => 'date', 'id' => $column, 'name' => $column, 'class' => 'form-control', 'data-date-format' => 'yyyy-mm-dd', 'value' => @$data->row->$column, 'required' => empty($attr['null'])));
                        elseif ($attr['type'] == 'time'):
                            echo tag::input(array('type' => 'time', 'id' => $column, 'name' => $column, 'class' => 'form-control', 'value' => @$data->row->$column, 'required' => empty($attr['null'])));
                        elseif ($attr['type'] == 'datetime'):
                            echo tag::input(array('type' => 'datetime', 'id' => $column, 'name' => $column, 'class' => 'form-control', 'data-date-format' => 'yyyy-mm-dd hh:ii:ss', 'value' => @$data->row->$column, 'required' => empty($attr['null'])));
                        elseif ($attr['type'] == 'enum'):
                            echo tag::br();
                            echo tag::begin_select(array('id' => $column, 'name' => $column, 'class' => 'enum', 'required' => empty($attr['null'])));
                            foreach ($attr['precision'] as $option):
                                echo tag::option(array('selected' => @$data->row->$column == $option), $option);
                            endforeach;
                            echo tag::end_select();
                        elseif ($attr['type'] == 'blob'):
                            echo tag::div(array('class' => 'file'),
                                tag::a(array('class' => 'btn btn-default'),
                                    'Browse...',
                                    tag::input(array('type' => 'file', 'name' => $column))
                                ),
                                tag::span(array('class' => 'upload-file-info'),
                                    $data->row && $data->row->$column
                                        ? tag::a(array('href' => \Ed\ColumnResource::link($table, $id, $column), 'target' => '_blank'), $column)
                                        : ''
                                )
                            );
                        else:
                            $options = array('type' => 'text', 'id' => $column, 'name' => $column, 'value' => @$data->row->$column, 'class' => 'form-control ' . $attr['type'], 'required' => empty($attr['null']));
                            if (!empty($attr['precision'])):
                                $options['maxlength'] = $attr['precision'];
                                if ($attr['precision'] < 20): # 20 is just whats close to browser defaults
                                    $options['size'] = $attr['precision'] + 1;
                                endif;
                            endif;
                            $input = tag::input($options);
                            if ($id != 'new'):
                                $v = $data->row->$column;
                                if (filter_var($v, FILTER_VALIDATE_URL)):
                                    $input = tag::div(array('class' => 'input-group'),
                                        tag::a(array('href' => $v, 'target' => '_blank', 'class' => 'input-group-addon'), tag::span(array('class' => 'glyphicon glyphicon-chevron-right'))),
                                        $input
                                    );
                                elseif (filter_var($v, FILTER_VALIDATE_EMAIL)):
                                    $input = tag::div(array('class' => 'input-group'),
                                        tag::a(array('href' => "mailto:$v", 'class' => 'input-group-addon'), tag::span(array('class' => 'glyphicon'), '@')),
                                        $input
                                    );
                                endif;
                            endif;
                            echo $input;
                        endif;
                        ?>
                    </div>
                    <?php endforeach ?>

                    <div class="button-group" style="background:#f0f0f0;padding:1em .5em; border-top:1px solid #cecece;">
                        <button type="submit" class="btn btn-primary">
                            <span class="glyphicon glyphicon-ok-sign"></span>
                            <?= $id == 'new' ? _('Create') : _('Update') ?>
                        </button>
                        <?php if ($id !== 'new'): ?>
                        <a href="?_method=delete"
                           class="btn btn-default"
                           style="margin-left:1em;color:#B92C28;"
                           data-bb="confirm">
                            <span class="glyphicon glyphicon-remove"></span>
                            <?= _('Delete') ?>
                        </a>
                        <?php endif ?>
                    </div>
                    <div class="clearfix" style="margin-bottom:2em;"></div>
                </form>
            <?php endif ?>


            <?php foreach ($data->has as $tbl => $infos): ?>

                <div id="<?= $tbl ?>" class="table-responsive">
                    <table class="table">
                        <thead>
                            <tr>
                                <td colspan="<?= count($data->schema[$tbl]) ?>" style="padding:0;">
                                    <?php
                                    // autoselect association with this record
                                    $query = array();
                                    foreach ($data->schema[$tbl] as $name => $column):
                                        if (isset($column['ref']['table']) && $column['ref']['table'] == $table):
                                            $query[$name] = $id;
                                        endif;
                                    endforeach;
                                    $query = $query ? '?' . http_build_query($query) : '';
                                    ?>


                                    <div class="pull-left" style="margin:12px 0;">
                                        <span style="color:gray;">
                                            <?php $offset = (($infos->page - 1) * $infos->limit) + 1 ?>
                                            <?php 
                                            if ($table != $tbl):
                                                echo $data->config['labelize']($tbl);
                                            endif;
                                            ?>
                                            <b><?= $offset ?></b>-<b><?= ($offset - 1) + count($infos->results) ?></b>
                                            <?php if (count($infos->results) != $infos->total): ?>
                                                / <b><?= $infos->total ?></b> <?= _('total') ?>
                                            <?php endif ?>
                                        </span>
                                        <a href="<?= static::link($tbl, 'new') . $query ?>" style="padding-left:1em;">
                                            <span class="glyphicon glyphicon-plus-sign"></span>
                                            New
                                        </a>
                                    </div>

                                    <?php if ($infos->pages > 1 || !empty($_GET["$tbl-search"])): ?>
                                        <form action="#<?= $table == $tbl ? '' : "#$tbl" ?>" method="get" class="navbar-form navbar-right" role="search" style="padding:0;">
                                            <?php foreach ($_GET as $name => $value): ?>
                                                <?= tag::input(array('type' => 'hidden', 'name' => $name, 'value' => $value)); ?>
                                            <?php endforeach ?>
                                            <div class="input-group">
                                                <?= tag::input(array('type' => 'text', 'name' => "$tbl-search", 'class' => 'form-control input-sm', 'placeholder' => 'Search', 'value' => isset($_GET["$tbl-search"]) ? $_GET["$tbl-search"] : '')) ?>
                                                <span class="input-group-btn">
                                                    <button type="submit" class="btn btn-default input-sm">
                                                        <span class="glyphicon glyphicon-search"></span>
                                                        <span class="sr-only"><?= _('Search') ?></span>
                                                    </button>

                                                    <?php if (!empty($_GET["$tbl-search"])): ?>
                                                    <button type="submit" class="close" style="position:absolute;left:-20px;top:3px;z-index:10;"
                                                        onclick="$('input[name=<?= "$tbl-search" ?>]').val('')">
                                                        &times;
                                                    </button>
                                                    <?php endif ?>
                                                </span>
                                            </div>
                                        </form>
                                    <?php endif ?>
                                </td>
                            </tr>
                            <tr>
                            <?php foreach ($data->schema[$tbl] as $name => $col): ?>
                                <th style="white-space:nowrap;">
                                    <?php if (!empty($infos->aggregates[$name])): ?>
                                        <a href="#"
                                            class="infos"
                                            data-toggle="popover"
                                            data-html="true"
                                            data-placement="bottom"
                                            data-content="
                                                <?php
                                                foreach ($infos->aggregates[$name] as $f => $value):
                                                    echo "$f: $value<br>";
                                                endforeach;
                                                ?>
                                            ">
                                            <span class="glyphicon glyphicon-info-sign"></span>
                                        </a>
                                    <?php endif ?>
                                    <?php
                                    $icon = '';
                                    $dir = '';
                                    if (empty($_GET["$tbl-sort"])):
                                        if (!empty($data->schema[$tbl][$name]['pk'])):
                                            $icon = tag::span(array('class' => 'glyphicon glyphicon-chevron-down'));
                                            $dir = '-';
                                        endif;
                                    elseif ($_GET["$tbl-sort"] === $name):
                                        $icon = tag::span(array('class' => 'glyphicon glyphicon-chevron-down'));
                                        $dir = '-';
                                    elseif ($_GET["$tbl-sort"] === "-$name"):
                                        $icon = tag::span(array('class' => 'glyphicon glyphicon-chevron-up'));
                                        $dir = '';
                                    endif;

                                    ?>
                                    <a href="?<?= http_build_query(array("$tbl-sort" => "$dir$name") + $_GET) ?>">
                                        <?= $data->config['labelize']($name) ?>
                                    </a>
                                    <?= $icon ?>
                                </th>
                            <?php endforeach ?>
                            </tr>
                        </thead>

                        <?php if ($infos->pages > 1): ?>
                        <tfoot>
                            <tr>
                                <td colspan="<?= count($data->schema[$tbl]) ?>">
                                    <?php
                                    $adapter = new \Pagerfanta\Adapter\FixedAdapter($infos->total, $infos->results);
                                    $pagerfanta = new \Pagerfanta\Pagerfanta($adapter);
                                    $pagerfanta->setMaxPerPage($data->config['max_per_page']);
                                    $pagerfanta->setCurrentPage( !empty($_GET["$tbl-page"]) ? $_GET["$tbl-page"] : 1 );
                                    $pagination = new \Pagerfanta\View\TwitterBootstrap3View();
                                    $routeGenerator = function($page) use ($tbl) {
                                        return '?' . http_build_query(array("$tbl-page" => $page) + $_GET);
                                    };
                                    echo $pagination->render($pagerfanta, $routeGenerator, $data->config['pagination']);
                                    ?>
                                </td>
                            </tr>
                        </tfoot>
                        <?php endif ?>
                        <tbody>
                            <?php
                            if (count($infos->results) == 0):
                                echo tag::tr(
                                    tag::td(array('colspan' => count($data->schema[$tbl])), tag::b(_('No results')))
                                );
                            else:
                                $pk = key(array_filter($data->schema[$tbl], function($el) { return !empty($el['pk']); }));
                                $df = $pk;
                                foreach ($infos->results as $obj):
                                    echo '<tr>';
                                    foreach ($data->schema[$tbl] as $name => $col):
                                        $value = $obj->$name;
                                        if (!empty($col['pk'])):
                                            $label = !empty($col['auto']) ? "#$value" : $value;
                                            $content = tag::a(array('href' => static::link($tbl, $value)), $label);
                                        elseif (!empty($col['ref'])):
                                            $label = ($value && !empty($data->schema[$col['ref']['table']][$col['ref']['column']])) ? "#$value" : $value;
                                            $content = tag::a(array('href' => static::link($col['ref']['table'], $value)), $label);
                                        else:
                                            if (in_array($data->schema[$tbl][$name]['type'], array('boolean', 'blob'))):
                                                $content = tag::code($obj->$name ? _('yes') : _('no'));
                                            else:
                                                $args = strlen($value) > 20
                                                    ? array('title' => \Stringy\StaticStringy::truncate($value, 500, '...'))
                                                    : array();
                                                $content = tag::span($args, \Stringy\StaticStringy::truncate($value, 20, '...'));
                                            endif;
                                        endif;
                                        echo tag::td($content);
                                    endforeach;
                                    echo '</tr>';
                                endforeach;
                            endif;
                            ?>
                        </tbody>
                    </table>
                </div>
            <?php endforeach ?>
            </div>
        </div>
        <script src="<?= \Ed\AssetsResource::link('js/ed.js') ?>"></script>
        <?php foreach ($data->config['scripts'] as $script): ?>
            <script src="<?= $script ?>"></script>
        <?php endforeach ?>
    </body>
</html>
