<?php
/**
 * Roundcube Plugin Lastlogin.
 *
 * Roundcube plugin to provide information (IP, DNS, Geo) about the user last logins.
 *
 * @author Diana Soares
 * @requires geolocation
 *
 * Copyright (C) 2013 Diana Soares
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
    private $timeout   = 10;
    private $log_table = 'userlogins';
    private $rc;

    /**
     * Plugin initialization.
     */
    public function init()
    {
        $this->load_config();
        $this->add_texts('localization/');
        $this->rc = rcmail::get_instance();

        if ($this->rc->config->get('lastlogin_geolocation', true)) {
            $this->require_plugin('geolocation');
        }

        // add hooks
        if (!$this->get_flag() && $this->rc->task == 'login' && $this->rc->action == 'login') {
            $this->add_hook('login_after', array($this, 'login_after'));
            $this->add_hook('write_log', array($this, 'write_log'));
        } elseif ($this->get_flag() && $this->rc->task == 'mail') {
            $this->add_hook('render_page', array($this, 'render_page'));
        } elseif ($this->rc->task == 'settings') {
            $this->add_hook('preferences_sections_list', array($this, 'preferences_section_list'));
            $this->add_hook('preferences_list', array($this, 'preferences_list'));
            $this->add_hook('preferences_save', array($this, 'preferences_save'));
        }
    }

    /**
     * Render page hook.
     */
    public function render_page($args)
    {
        if ($this->get_flag() && $args['template'] == 'mail') {
            $this->show_info();
            $this->save_info();
        }

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
            $this->api->output->show_message('lastlogin.lastlogin', 'notice', $vars, false, $vars['timeout']);
        }
    }

    /**
     * Get last login information.
     */
    public function get_info()
    {
        $info = $this->rc->config->get('lastlogin', array());

        if (!isset($info['timeout'])) {
            $info['timeout'] = $this->rc->config->get('lastlogin_timeout', $this->timeout);
        }

        $info['more'] = $this->rc->url(array('_task'=>'settings', '_action'=>'lastlogin_preferences'));
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
                : $this->rc->config->get('lastlogin_timeout', $this->timeout))
        );

        $this->rc->user->save_prefs(array('lastlogin'=>$info));
    }

    /**
     * Override userlogins log.
     */
    public function write_log($args)  //array('name' => $name, 'date' => $date, 'line' => $line))
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

        $session_id = session_id();
        $ips = $this->remote_ip();
        $ip  = $this->remote_ip('single');
        $geo = $this->get_geo($ip);
        $dns = $this->get_dns($ip);

        $sql = "INSERT INTO " . $this->table_name() .
            "(id, user_id, username, session_id, ip, real_ip, hostname, geoloc) ".
            "VALUES (\N, ?, ?, ?, ?, ?, ?, ?);";

        $ret = $this->rc->db->query($sql,  $user_id, $username, $session_id,
            $ips['ip'], $ips['forwarded_ip'], $dns, $geo);

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
        $sql = "SELECT if(real_ip!='',real_ip,ip) AS `from`, hostname, ".
            "UNIX_TIMESTAMP(timestamp) AS `date`, geoloc AS `geo` FROM " .
            $this->table_name() . " WHERE user_id=? ORDER BY id DESC LIMIT " .
            $this->rc->config->get('lastlogin_lastrecords', 10);
        $sth = $this->rc->db->query($sql,  $this->rc->user->ID);

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
     * Save preferences.
     */
    public function preferences_save($args)
    {
        if ($args['section'] == 'lastlogin_preferences') {
            $config = $this->rc->config->get('lastlogin', array());
            $config['timeout'] = intval(rcube_utils::get_input_value('_lastlogin_timeout', rcube_utils::INPUT_POST));
            $args['prefs']['lastlogin'] = $config;
        }

        return($args);
    }

    /**
     * Add a section to the preferences section list.
     */
    public function preferences_section_list($args)
    {
        $args['list']['lastlogin_preferences'] =
            array(
                'id' => 'lastlogin_preferences',
                'section' => rcube::Q($this->gettext('section_title', 'Last login'))
            );
        return($args);
    }

    /**
     * Display lastlogin preferences tab.
     */
    public function preferences_list($args)
    {
        $this->include_stylesheet($this->local_skin_path()."/lastlogin.css");

        if ($args['section'] == 'lastlogin_preferences') {
            $domain = 'lastlogin';
            $blocks = array(
                'lastlogin_info' => array('name' => rcube::Q($this->rc->gettext('info', $domain))),
                'lastlogin_conf' => array('name' => rcube::Q($this->rc->gettext('conf', $domain))),
            );

            // config
            $info     = $this->get_info();
            $field_id = 'rcmfd_lastlogin_timeout';
            $value = intval($info['timeout']);
            $input = new html_select(array('name' => '_lastlogin_timeout', 'id' => $field_id));
            $input->add($this->rc->gettext('never'), '0');

            foreach (array(5, 10, 15, 20) as $sec) {
                $input->add($this->rc->gettext(array('name'=>'fornseconds', 'vars'=>array('n'=>$sec)), $domain), $sec);
            }

            $blocks['lastlogin_conf']['options']['timeout'] = array(
                'title' => html::label($field_id, rcube::Q($this->rc->gettext('timeout', $domain))),
                'content' => $input->show($value),
            );

            // info
            $msg = (!empty($info['from']) && !empty($info['date']))
                ? $this->rc->gettext(array('name'=>'lastlogin', 'vars'=>array_map(array('rcube', 'Q'), $info)), $domain)
                : $this->rc->gettext('noinfo', $domain);
            $msg = preg_replace('/\[.+\]/', '', $msg);

            $blocks['lastlogin_info']['options'][] = array('content' => $this->recentlogins());

            // end
            $args['blocks'] = $blocks;
        }

        return($args);
    }

    /**
     * HTML table with recent user logins.
     */
    public function recentlogins()
    {
        $logs = $this->load_log();
        $table = new html_table(array('cols'=>4, 'class'=>'lastlogin', 'border'=>1,
            'cellspacing'=>0, 'cellpadding'=>4));

        foreach (array('timestamp', 'ip', 'hostname', 'location') as $k) {
            $table->add_header(
                array('title' => rcube::Q($this->gettext($k))),
                rcube::Q($this->gettext($k))
            );
        }

        foreach ($logs as $log) {
            $date = $this->_format_date($log['date']);
            $geo  = $log['geo'];
            $dns  = $log['hostname'];
            $from = $log['from'];
            //if ($dns != '') { $from .= " ($dns) "; }

            $table->add(array(), rcube::Q($date));
            $table->add(array(), rcube::Q($from));
            $table->add(array(), rcube::Q($dns));
            $table->add(array(), rcube::Q($geo));
        }

        return
            html::tag('p', null, rcube::Q($this->gettext('recentactivity'))) .
            $table->show() .
            html::tag('p', 'license', $this->gettext('geoip_license'));
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
        $dns = (intval($ip) ? gethostbyaddr($ip) : '');
        return ($dns != $ip ? $dns : '');
    }

    /**
     * Geolocation info.
     */
    private function get_geo($ip)
    {
        if (! $this->rc->config->get('lastlogin_geolocation', true)) {
            return '';
        }

        $geo = geolocation::get_instance()->get_geolocation($ip);

        if (is_array($geo)) {
            $geo = sprintf("%s, %s, %s", $geo['city'], $geo['region'], $geo['country']);
        }

        return $geo;
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
