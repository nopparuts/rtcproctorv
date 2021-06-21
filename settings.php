<?php

// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// Moodle is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Moodle.  If not, see <http://www.gnu.org/licenses/>.

/**
 * RTCProctorV module admin settings and defaults
 *
 * @package   mod_rtcproctorv
 * @copyright  2020 Nopparut Saelim
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die;

if ($ADMIN->fulltree) {
    //--- general settings ---
    $settings->add(new admin_setting_configtext('rtcproctorv/rtc_signaling_server',
        get_string('rtc_signaling_server', 'rtcproctorv'),
        get_string('rtc_signaling_server_help', 'rtcproctorv'), '', PARAM_URL));

//    echo get_config("rtcproctorv", "additional_config") . "test<br>";
    $settings->add(new admin_setting_configtextarea('rtcproctorv/additional_config',
        get_string('additional_config', 'rtcproctorv'),
        get_string('additional_config_help', 'rtcproctorv'), "see default: Please blank this area"));

    if (get_config("rtcproctorv", "additional_config") == '') {
        echo "test";
        set_config("additional_config", "videoConstraints = {
            width: {
                ideal: 320
    },
            height: {
                ideal: 240
    },
            frameRate: 10
}

//connection.mediaConstraints = {
//    video: videoConstraints,
//    audio: true
//}
//
//connection.iceServers = [{
//   'urls': [
//        'stun:stun.l.google.com:19302',
//        'stun:stun.l.google.com:19302?transport=udp',
//    ]
//}]
//connection.iceServers.push({
//    urls: 'turn:example.it.kmitl.ac.th:3478',
//    credential: 'yourcredential',
//    username: 'yourturnusername'
//})
", "rtcproctorv");
    }
}
