# Roundcube Plugin Lastlogin

Roundcube plugin to save and show user login information (user login history).

When a user logs in into Roundcube, this plugin:

1. shows the user's last login information in a small box for N configurable seconds;
2. saves the information to a database table for history purposes.

The login history may be consulted in its own Settings tab.

If using the [geolocation plugin][geolocation] (config option `lastlogin_geolocation` is `true`) and you have some organization internal networks configured there, that information will be shown. For more information, see the [geolocation plugin][geolocation] configuration.

The plugin provides a section in settings for the user to configure for how long (number of seconds) to show a notification with last access information on login.

Stable versions of Lastlogin are available from the [Roundcube plugin repository][rcplugrepo] or the [releases section][releases] of the GitHub repository.


## Requirements

- [Roundcube Plugin Geolocation][geolocation] if the config option `lastlogin_geolocation` is `true` (default).
- The option `log_logins` in Roundcube main configuration must be enabled to register the logins.


## Installation

#### Install with composer

Add the plugin to your `composer.json` file:

    "require": {
        (...)
        "dsoares/lastlogin": "*"
    }

Run:

    $ composer update [--your-options]`

Copy `config.inc.php.dist` to `config.inc.php` and modify it as necessary.

#### Manual installation

Place this directory under your Rouncdube `plugins/` folder, copy `config.inc.php.dist` to `config.inc.php` and modify it as necessary.
Then, you need to import the database script:

    mysql -your_mysql_connection_options your_roundcube_database_name < SQL/mysql.initial.sql

NOTE: The plugin ships only with a MySQL/MariaDB script `SQL/mysql.initial.sql`; you are welcome to contribute with other database drivers.

Don't forget to enable the lastlogin plugin within the main Roundcube configuration file `config/config.inc.php`.


## Configuration

- **$config['lastlogin_timeout']** - number of seconds to show the user login info at login; default `10`.

- **$config['lastlogin_lastrecords']** - number of history entries to show in the settings section; default `10`.

- **$config['lastlogin_geolocation']** - use the geolocation plugin; default `true`.

- **$config['lastlogin_dns']** - also register IP DNS names; default `true`.

- **$config['lastlogin_tor']** - check if IP is a TOR-network exit point; default `true`.

- **$config['lastlogin_tor_suffix']** - the TOR-network DNS suffix.

- **$config['lastlogin_tor_ip']** - the TOR-network IP.

See the `config.inc.php.dist` for more information.

If you don't want the `lastlogin_timeout` setting to be overriden by the user, you can add it to your Roundcube [`dont_override`] settings.


## License

This plugin is released under the [GNU General Public License Version 3+][gpl].

## Contact

Comments and suggestions are welcome!

Email: [Diana Soares][dsoares]

[settings]: https://github.com/roundcube/roundcubemail/blob/master/config/defaults.inc.php#L363
[rcplugrepo]: http://plugins.roundcube.net/packages/dsoares/lastlogin
[releases]: http://github.com/JohnDoh/Roundcube-Plugin-Lastlogin/releases
[geolocation]: http://plugins.roundcube.net/packages/dsoares/geolocation
[gpl]: http://www.gnu.org/licenses/gpl.html
[dsoares]: mailto:diana.soares@gmail.com
