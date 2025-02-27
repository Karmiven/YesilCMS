<?php

defined('BASEPATH') or exit('No direct script access allowed');

/**
 * @property CI_DB_query_builder $auth
 * @property Module_model        $wowmodule
 * @property Config_Writer       $config_writer
 */
class Home_model extends CI_Model
{
    /**
     * Home_model constructor.
     */
    public function __construct()
    {
        parent::__construct();
        $this->auth = $this->load->database('auth', true);
        $this->load->config('home');
    }

    public function getSlides(): CI_DB_result
    {
        return $this->db->select('*')->order_by('id', 'ASC')->get('slides');
    }

    public function getDiscordInfo()
    {
        $invitation = $this->config->item('discord_invitation');
        error_reporting(0);

        if ($this->wowmodule->getDiscordStatus()) {
            $discordapi = $this->cache->file->get('discordapi');

            if ($discordapi !== false) {
                $api = json_decode($discordapi, true);

                return $api;
            } else {
                $this->cache->file->save('discordapi', file_get_contents('https://discordapp.com/api/v8/invites/' . $invitation . '?with_counts=true'), 300);
                $check = $this->cache->file->get('discordapi');

                if ($check !== false) {
                    return $this->getDiscordInfo();
                }
            }
        }
    }

    public function updateconfigs($data)
    {
        $this->load->library('config_writer');
        $blizz = $this->config_writer->get_instance(APPPATH . 'config/blizzcms.php', 'config');

        if ($this->config_writer->isEnabled($data['bnet'])) {
            $bnet_enable = true;
        } else {
            $bnet_enable = false;
        }

        if ($this->config_writer->isEnabled($data['redis'])) {
            $redis = true;
        } else {
            $redis = false;
        }

        $blizz->write('website_name', $data['name']);
        $blizz->write('discord_invitation', $data['invitation']);
        $blizz->write('realmlist', $data['realmlist']);
        $blizz->write('expansion', $data['expansion']);
        $blizz->write('bnet_enabled', $bnet_enable);
        $blizz->write('emulator', $data['emulator']);
        $blizz->write('redis_in_cms', $redis);
        $blizz->write('migrate_status', '0');
    }

    public function insertRealm($hostname, $username, $password, $database, $realm_id, $soaphost, $soapuser, $soappass, $soapport, $emulator): bool
    {
        $data = array(
            'hostname'         => $hostname,
            'username'         => $username,
            'password'         => $password,
            'char_database'    => $database,
            'realmID'          => $realm_id,
            'console_hostname' => $soaphost,
            'console_username' => $soapuser,
            'console_password' => $soappass,
            'console_port'     => $soapport,
            'emulator'         => $emulator
        );

        $this->db->insert('realms', $data);

        return true;
    }
}
