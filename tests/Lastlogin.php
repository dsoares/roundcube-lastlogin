<?php

class Lastlogin_Plugin extends PHPUnit_Framework_TestCase
{

    public function setUp()
    {
        include_once __DIR__ . '/../lastlogin.php';
    }

    /**
     * Plugin object construction test
     */
    public function test_constructor()
    {
        $rcube  = rcube::get_instance();
        $plugin = new lastlogin($rcube->api);

        $this->assertInstanceOf('lastlogin', $plugin);
        $this->assertInstanceOf('rcube_plugin', $plugin);
    }
}
