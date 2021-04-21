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
 * Version file for Redis Lock plugin.
 *
 * @package   local_redislock
 * @author    Josh Willcock
 * @copyright Copyright (c) 2019 Josh Willcock (www.josh.cloud)
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
require_once('../../config.php');
require_login();
$PAGE->set_context(\context_system::instance());
require_capability('moodle/site:config', \context_system::instance());
$PAGE->set_url('/blocks/programs/createautomaticrule.php');
$PAGE->set_title('Redis Lock Check');
$PAGE->set_heading('Redis Lock Check');
$PAGE->set_pagelayout('admin');
echo $OUTPUT->header();
$redisClient = new Redis();
$port = 6379;
$errorNo = false;
try{
  if( $socket = fsockopen($CFG->session_redis_host, $CFG->session_redis_port, $errorNo, $errorStr )){
        if( $errorNo ){
          throw new RedisException("Socket cannot be opened");
        }
  }
}catch( Exception $e ){
  echo $e->getMessage();
}
$redisClient->connect($CFG->session_redis_host);

$delkey = optional_param('key', '', PARAM_TEXT);
if (strpos($delkey, $CFG->dbname.'_') !== false) {
    $redisClient->del($delkey);
}

$list = $redisClient->keys($CFG->dbname."_*");
sort($list);
$tbody = '';
foreach ($list as $key) {
    $row = \html_writer::tag('td', $key);
    $row .= \html_writer::tag('td', $redisClient->get($key));
    $row .= \html_writer::tag('td', $redisClient->pttl($key))));
    $link = \html_writer::link(new \moodle_url($CFG->wwwroot.'/local/redislock/check_lock.php', array('key' => $key)), 'Delete');
    $row .= \html_writer::tag('td', $link);
    $tbody .= \html_writer::tag('tr', $row);
}
$header = \html_writer::tag('td', 'Key');
$header .= \html_writer::tag('td', 'Value');
$header .= \html_writer::tag('td', 'Expiry');
$header .= \html_writer::tag('td', 'Delete');
$thead = \html_writer::tag('thead', $header, ['class' => 'thead-dark']);
echo \html_writer::tag('table', $thead.$tbody, ['class' => 'table']);
$redisClient->close();
echo $OUTPUT->footer();