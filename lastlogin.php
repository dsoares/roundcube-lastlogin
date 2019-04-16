<?php
/**
 * Roundcube Plugin Lastlogin.
 *
 * Roundcube plugin to provide information (IP, DNS, Geo) about the user last logins.
 *
 * @version 1.2.0
 * @author Diana Soares
 * @requires geolocation
 *
 * Copyright (C) Diana Soares
 *
 * This program is a Roundcube (http://www.roundcube.net) plugin.
 * For more information see README.md.
 * For configuration see config.inc.php.dist.
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with Roundcube. If not, see http://www.gnu.org/licenses/.
 */

class lastlogin extends rcube_plugin
{
    public $task = 'login|mail|settings';
    private $log_table = 'userlogins';
    private $rc;

    /**
     * Plugin initialization.
     */
    public function init()
    {
        $this->load_config('config.inc.php.dist');
        $this->load_config();
        $this->add_texts('localization/');
        $this->rc = rcmail::get_instance();

        if ($this->rc->config->get('lastlogin_geolocation')) {
            $this->require_plugin('geolocation');
        }

        // add hooks
        if (!$this->get_flag() && $this->rc->task == 'login' && $this->rc->action == 'login') {
            $this->add_hook('login_after', array($this, 'login_after'));
            $this->add_hook('write_log', array($this, 'write_log'));
        }
        else if ($this->get_flag() && $this->rc->task == 'mail') {
            $this->add_hook('render_page', array($this, 'render_page'));
        }
        else if ($this->rc->task == 'settings') {
            $this->add_hook('preferences_list', array($this, 'preferences_list'));
            $this->add_hook('preferences_save', array($this, 'preferences_save'));

            $this->add_hook('settings_actions', array($this, 'settings_actions'));
            $this->register_action('plugin.lastlogin', array($this, 'show_more'));
        }
    }

    /**
     * Render page hook.
     */
    public function render_page($args)
    {
        $this->show_info();
        $this->save_info();
        $this->unset_flag();
        return $args;
    }

    /**
     * Login after hook.
     */
    public function login_after($args)
    {
        $this->set_flag();
        return $args;
    }

    /**
     * Show last login information.
     */
    public function show_info()
    {
        $vars = $this->get_info();

        if (!empty($vars['from']) && !empty($vars['date']) && $vars['timeout'] > 0) {
            $this->api->output->show_message('lastlogin.lastlogin_info', 'notice', $vars, false, $vars['timeout']);
        }
    }

    /**
     * Get last login information.
     */
    public function get_info()
    {
        $info = $this->rc->config->get('lastlogin', array());

        if (!isset($info['timeout'])) {
            $info['timeout'] = intval($this->rc->config->get('lastlogin_timeout'));
        }

        $info['more'] = $this->rc->url(array('_task'=>'settings', '_action'=>'plugin.lastlogin'));
        $info['date'] = $this->_format_date($info['date']);
        $info['dns']  = $this->get_dns($info['from']);

        if ($info['dns'] != '') {
            $info['dns'] = " (".$info['dns'].") ";
        }
        if ($info['geo'] != '') {
            $info['geo'] = " (".$info['geo'].") ";
        }

        return $info;
    }

    /**
     * Save last login information.
     */
    public function save_info()
    {
        $ip   = $this->remote_ip('single');
        $info = $this->rc->config->get('lastlogin', array());
        $info = array(
            'from' => $ip,
            'date' => date('U'),
            'geo'  => $this->get_geo($ip),
            'timeout' => (isset($info['timeout'])
                ? $info['timeout']
                : intval($this->rc->config->get('lastlogin_timeout')))
        );

        $this->rc->user->save_prefs(array('lastlogin'=>$info));
    }

    /**
     * Override userlogins log.
     *
     * @param array $args  array('name' => $name, 'date' => $date, 'line' => $line))
     */
    public function write_log($args)
    {
        // only log userlogins
        if ($args['name'] != 'userlogins') {
            return $args;
        }

        $username = $this->rc->user->get_username('local');
        $user_id  = $this->rc->user->ID;

        // in a failed login, do nothing
        if ($user_id <= 0) {
            return $args;
        }

        $sess_id = session_id();
        $ips = $this->remote_ip();
        $ip  = $this->remote_ip('single');
        $geo = $this->get_geo($ip);
        $dns = $this->get_dns($ip);
        $tor = $this->is_tor($ip);
        $ua  = ($this->rc->config->get('lastlogin_useragent', false) ? $_SERVER['HTTP_USER_AGENT'] : '');

        $sql = "INSERT INTO " . $this->table_name() .
            "(user_id, username, sess_id, ip, real_ip, hostname, geoloc, ua".($tor?", tor":"").")".
            " VALUES (?, ?, ?, ?, ?, ?, ?, ?".($tor?", TRUE":"").")";

        $ret = $this->rc->db->query($sql, $user_id, $username, $sess_id,
            $ips['ip'], $ips['forwarded_ip'], $dns, $geo, $ua);

        if ($ret) {
            $args['abort'] = false;
        }

        return $args;
    }

    /**
     * Load current user log.
     */
    public function load_log()
    {
        $sth = $this->rc->db->limitquery(
            "SELECT hostname, geoloc, ua"
            .", CASE WHEN real_ip<>'' THEN real_ip ELSE ip END AS `from`"
            .", " . $this->unixtimestamp('timestamp') . " AS `date`"
            ." FROM " . $this->table_name()
            ." WHERE user_id = ? "
            ." ORDER BY id DESC ",
            0, intval($this->rc->config->get('lastlogin_lastrecords')),
            $this->rc->user->ID
        );

        $rows = array();
        while ($res = $this->rc->db->fetch_assoc($sth)) {
            $rows[] = $res;
        }

        return $rows;
    }

    /**
     * Get session flag.
     */
    private function get_flag()
    {
        return $_SESSION['plugin.lastlogin.show_info'];
    }

    /**
     * Set session flag to show last login information.
     */
    private function set_flag()
    {
        $_SESSION['plugin.lastlogin.show_info'] = true;
    }

    /**
     * Unset session flag.
     */
    private function unset_flag()
    {
        $_SESSION['plugin.lastlogin.show_info'] = false;
    }

    /**
     * Add a tab to Settings.
     */
    public function settings_actions($args)
    {
        $args['actions'][] = array(
            'action' => 'plugin.lastlogin',
            'class'  => 'lastlogin',
            'label'  => 'lastlogin',
            'domain' => 'lastlogin',
        );

        return $args;
    }

    /**
     * Lastlogin settings tab/menu entry.
     */
    public function show_more()
    {
        $this->register_handler('plugin.body', array($this, 'infohtml'));
        $this->rc->output->set_pagetitle($this->gettext('lastlogin'));
        $this->rc->output->send('plugin');
    }

    /**
     * Settings tab content.
     */
    public function infohtml()
    {
        $this->include_stylesheet($this->local_skin_path()."/lastlogin.css");

        $html = html::tag(
            'fieldset', '',
            html::tag('legend', null, $this->gettext('recentactivity'))
            . $this->recentlogins()
            . html::tag('p', 'license', $this->gettext('geoip_license'))
        );

        return html::div(
            array('class' => 'box formcontent lastlogin'),
            html::div(array('class' => 'boxtitle'), $this->gettext('lastlogin')) .
            html::div(array('class' => 'boxcontent propform'), $html)
        );
    }

    /**
     * Display lastlogin preferences tab.
     */
    public function preferences_list($args)
    {
        if ($args['section'] == 'general' &&
            !in_array('lastlogin_timeout', (array)$this->rc->config->get('dont_override'))) {

            $this->include_stylesheet($this->local_skin_path()."/lastlogin.css");

            $field_id = 'rcmfd_lastlogin_timeout';
            $info  = $this->get_info();
            $value = intval($info['timeout']);

            $input = new html_select(array('name' => '_lastlogin_timeout', 'id' => $field_id));
            $input->add($this->gettext('never'), '0');

            foreach (array(5, 10, 15, 20) as $sec) {
                $input->add($this->gettext(array('name'=>'fornseconds', 'vars'=>array('n'=>$sec))), $sec);
            }

            $args['blocks']['main']['options']['timeout'] = array(
                'title' => html::label($field_id, $this->gettext('timeout')),
                'content' => $input->show($value),
            );
        }

        return $args;
    }

    /**
     * Save preferences.
     */
    public function preferences_save($args)
    {
        if ($args['section'] == 'general') {
            $config = $this->rc->config->get('lastlogin', array());
            if (!in_array('lastlogin_timeout', (array)$this->rc->config->get('dont_override'))) {
                $config['timeout'] = intval(rcube_utils::get_input_value('_lastlogin_timeout', rcube_utils::INPUT_POST));
            }
            $args['prefs']['lastlogin'] = $config;
        }

        return $args;
    }

    /**
     * HTML table with recent user logins.
     */
    public function recentlogins()
    {
        $table = new html_table(array(
            'cols'=>5, 'class'=>'uibox records-table',
            'border'=>1, 'cellspacing'=>0, 'cellpadding'=>4)
        );

        foreach (array('timestamp', 'ip', 'hostname', 'location', 'ua') as $key) {
            $key = rcube::Q($this->gettext($key));
            $table->add_header(array('title' => $key), $key);
        }

        $logs = $this->load_log();

        foreach ($logs as $log) {
            $date = $this->_format_date($log['date']);
            $geo  = $log['geoloc'];
            $dns  = $log['hostname'];
            $ua   = $log['ua'];
            $from = ($this->rc->config->get('lastlogin_mask_ip', false)
                ? preg_replace('/\.[0-9]{0,3}\.[0-9]{0,3}\./', '.*.*.', $log['from'])
                : $log['from']
            );
            $table->add(array(), rcube::Q($date));
            $table->add(array(), rcube::Q($from));
            $table->add(array(), rcube::Q($dns));
            $table->add(array(), rcube::Q($geo));
            $table->add(array('title' => rcube::Q($ua)), rcube::Q($ua));
        }

        return $table->show();
    }

    /**
     * Returns remote IP address and forwarded addresses if found.
     * Based on roundcube rcube_utils::remote_ip().
     *
     * @param string $mode  mode=(multiple|single)
     * @return array
     */
    private function remote_ip($mode='multiple')
    {
        $ips = array('ip' => $_SERVER['REMOTE_ADDR'], 'real_ip' => '', 'forwarded_ip' => '');

        // append the NGINX X-Real-IP header, if set
        if (!empty($_SERVER['HTTP_X_REAL_IP'])) {
            $ips['real_ip'] = $_SERVER['HTTP_X_REAL_IP'];
        }
        // append the X-Forwarded-For header, if set
        if (!empty($_SERVER['HTTP_X_FORWARDED_FOR'])) {
            $ips['forwarded_ip'] = $_SERVER['HTTP_X_FORWARDED_FOR'];
        }
        // if mode is single, return only one IP
        if ($mode == 'single') {
            foreach (array('forwarded_ip', 'real_ip') as $k) {
                if ($ips[$k] != '') {
                    return $ips[$k];
                }
            }
            return $ips['ip'];
        }

        return $ips;
    }

    /**
     * DNS name.
     */
    private function get_dns($ip)
    {
        if ($this->rc->config->get('lastlogin_dns')) {
            $dns = (intval($ip) ? gethostbyaddr($ip) : '');
            if ($dns != $ip) {
                return $dns;
            }
        }

        return '';
    }

    /**
     * Geolocation info.
     */
    private function get_geo($ip)
    {
        if (! $this->rc->config->get('lastlogin_geolocation')) {
            return '';
        }

        $geo = geolocation::get_instance()->get_geolocation($ip);

        if (is_array($geo)) {
            $geo = array_map('trim', $geo);
            $geo = array_filter($geo);
            $geo = implode(', ', $geo);
        }

        return $geo;
    }

    /**
     * Check if IP is a TOR-network exit point.
     */
    private function is_tor($ip)
    {
        if ($this->rc->config->get('lastlogin_tor')) {
            $ip = $this->reverse_ip_octets($ip).
                ".".$_SERVER['SERVER_PORT'].
                ".".$this->reverse_ip_octets($_SERVER['SERVER_ADDR']).
                $this->rc->config->get('lastlogin_tor_suffix');
            $tor_ip = $this->rc->config->get('lastlogin_tor_ip');
            return (gethostbyname($ip) == $tor_ip);
        }
        return false;
    }

    /**
     * Reverse the octets of an IP.
     */
    private function reverse_ip_octets($ip)
    {
        return implode('.', array_reverse(explode('.', $ip)));
    }

    /**
     * Return SQL statement to convert a field value into a unix timestamp.
     */
    private function unixtimestamp($field)
    {
        switch ($this->rc->db->db_provider) {
        case 'sqlite':
            $ts = ($field === 'NOW()') ? "strftime('%s', 'now')" : $field;
            break;
        case 'pgsql':
        case 'postgres':
            $ts = "EXTRACT (EPOCH FROM $field)";
            break;
        default:
            $ts = "UNIX_TIMESTAMP($field)";
        }

        return $ts;
    }

    /**
     * Format date.
     */
    private function _format_date($date)
    {
        if ($date >= strtotime("today")) {
            $weekday = preg_replace("//", "\\", ucfirst($this->gettext('today')));
        } elseif ($date >= strtotime("yesterday")) {
            $weekday = preg_replace("//", "\\", ucfirst($this->gettext('yesterday')));
        } else {
            $weekday = 'l';
        }

        return $this->rc->format_date($date, "$weekday, d F Y, H:i");
    }

    /**
     * Get table name.
     */
    private function table_name()
    {
        return $this->rc->db->table_name($this->log_table, true);
    }
}
