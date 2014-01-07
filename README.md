# Scrudler

*Scrudler* is a web user interface to relational databases that supports
[SCRUDL](https://en.wikipedia.org/wiki/Create,_read,_update_and_delete)
manipulations.

By default it use [Chinook](https://chinookdatabase.codeplex.com),
so update the `dsn` config to point to your database.  To install and run, type the following:

    $ composer install
    $ php -S localhost:8080 -t public/ public/index.php    # or ./start.cmd if your a lazy cat

## Featuring

- [Auryn](https://github.com/rdlowrey/Auryn) PHP Dependency Injection Container
- [Stringy](http://danielstjules.github.io/Stringy) A PHP string manipulation library with multibyte support
- [Pagerfanta](https://github.com/whiteoctober/Pagerfanta) Pagination for PHP 5.3
- [jQuery](http://jquery.com) write less, do more
- [Select2](http://ivaynberg.github.com/select2) Select boxes replacement
- [Bootstrap](http://getbootstrap.com) Front-end framework
- [Bootbox.js](http://bootboxjs.com) Modal dialog boxes for Bootstrap
- [bootstrap-datepicker.js](http://www.eyecon.ro/bootstrap-datepicker) Date picker for Bootstrap
- [bootstrap-datetimepicker.js](http://www.malot.fr/bootstrap-datetimepicker) Date time picker for Bootstrap
- [bootstrap-timepicker.js](https://github.com/jdewit/bootstrap-timepicker) Time picker for Bootstrap

## Todo

- Config to remove CRUD rights
- Add checkbox (rounded) in listing to allow multiple delete/edit
- Understand `modified_at`/`created_at` kind of fields for getLastModified
- Write PostgreSQL reflection , turn metadata() into it's own little project
- Add a 's' shortcut that focus to search box
- Make nav responsive
- CSS print media type
- // elseif name match: password, month, week, email, url, tel, color
- Support for SET, BINARY and other datatypes
- Add .xlsx download link (default limit to a high value)
- Inject Config object instead of using a global variable
- Add link with min/max aggregates, also why no aggregates on dates too ?
- Add http basic auth, integrates with the MySQL connection
- Attach uploaded files to rows
- Use LocalStorage to save state, eg. edited fields, current sorts and search etc.
- Would it be possible custom auth based on relations, eg. must have fk down to loggedin user ?
- This could be cool: http://stackoverflow.com/questions/3005441/web-based-concurrency-solution-required-for-database-editor-in-php#3005575
- Try a different stack http://askubuntu.com/questions/9357/how-to-set-up-php-with-nginx-apc-and-postgresql#9407

