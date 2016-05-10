Roundcube Plugin Lastlogin
============================

Roundcube plugin to save and show user login information (user login history).

When a user logs in into Roundcube, this plugin:

1. shows the last user login information in a small box for N configurable seconds;
2. saves the information to a database table for history purposes.

If using the geolocation plugin and you have configured your organization internal networks, that information will be shown. For more information, see the geolocation plugin configuration.

This plugin provides a section in settings for the user to configure the number of seconds to show the information on login.

Stable versions of Lastlogin are available from the [Roundcube plugin repository][rcplugrepo] or the [releases section][releases] of the GitHub repository.


Requirements
------------

- [Roundcube Plugin Geolocation][geolocation] if the config option `lastlogin_geolocation` is `true` (default).


Installation with composer
----------------------------------------

Add the plugin to your `composer.json` file:

    "require": {
        (...)
        "dsoares/lastlogin": "~0.1"
    }

And run `$ composer update [--your-options]`.

Manual Installation
----------------------------------------

Place this directory under your Rouncdube `plugins/` folder, copy `config.inc.php.dist` to `config.inc.php` and modify it as necessary.
Then, you need to import the database script:

    mysql -your_mysql_connection_options your_roundcube_database_name < SQL/mysql.initial.sql

NOTE: The plugin ships only with a MySQL/MariaDB script `SQL/mysql.initial.sql`; you are welcome to contribute with other database drivers.

Don't forget to enable the lastlogin plugin within the main Roundcube configuration file `config/config.inc.php`.


Configuration
----------------------------------------

- **$config['lastlogin_timeout']** - number of seconds to show the user login info at login; default is `10`.

- **$config['lastlogin_lastrecords']** - number of history entries to show in the settings section; default is `10`.

- **$config['lastlogin_geolocation']** - use the geolocation plugin; default is `true`.

See the `config.inc.php.dist` for more information.

License
----------------------------------------

This plugin is released under the [GNU General Public License Version 3+][gpl].

Contact
----------------------------------------

Comments and suggestions are welcome!

Email: [Diana Soares][dsoares]

[rcplugrepo]: http://plugins.roundcube.net/packages/dsoares/lastlogin
[releases]: http://github.com/JohnDoh/Roundcube-Plugin-Lastlogin/releases
[geolocation]: http://plugins.roundcube.net/packages/dsoares/geolocation
[gpl]: http://www.gnu.org/licenses/gpl.html
[dsoares]: mailto:diana.soares@gmail.com
