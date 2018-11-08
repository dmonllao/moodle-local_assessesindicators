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
 * Unit tests for this plugin indicators.
 *
 * @package   local_assessesindicators
 * @copyright 2018 David Monllaó {@link http://www.davidmonllao.com}
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

/**
 * Unit tests for this plugin indicators.
 *
 * @package   local_assessesindicators
 * @copyright 2018 David Monllaó {@link http://www.davidmonllao.com}
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class local_assessesindicators_indicators_testcase extends advanced_testcase {

    /**
     * test_no_teacher
     *
     * @return void
     */
    public function test_received_database_entry_ratings() {
        global $DB, $CFG;
        require_once($CFG->dirroot . '/rating/lib.php');

        $this->resetAfterTest(true);

        $generator = $this->getDataGenerator()->get_plugin_generator('mod_data');

        $course1 = $this->getDataGenerator()->create_course();
        $coursecontext1 = \context_course::instance($course1->id);

        $user1 = $this->getDataGenerator()->create_user();
        $user2 = $this->getDataGenerator()->create_user();

        $this->getDataGenerator()->enrol_user($user1->id, $course1->id, 'student');
        $this->getDataGenerator()->enrol_user($user2->id, $course1->id, 'student');

        $data1 = $this->getDataGenerator()->create_module('data', array('course' => $course1->id));
        //$data1context = \context_module::instance($data1->cmid);
        //$DB->set_field('data', 'assessed', RATING_AGGREGATE_SUM, array('id' => $data1->id));
        //$DB->set_field('data', 'scale', 100, array('id' => $data1->id));

        //$field1 = $generator->create_field(['type' => 'text', 'name' => 'textyeah']);

        //$entry1 = $generator->create_entry($data1, 'text for testing', 0);

        //// Rate the entry as user2.
        //$rating1 = new stdClass();
        //$rating1->contextid = $data1context;
        //$rating1->component = 'mod_data';
        //$rating1->ratingarea = 'entry';
        //$rating1->itemid = $entry1;
        //$rating1->rating = 50;
        //$rating1->scaleid = 100;
        //$rating1->userid = $user1->id;
        //$rating1->timecreated = time();
        //$rating1->timemodified = time();
        //$rating1->id = $DB->insert_record('rating', $rating1);

        $indicator = new \local_assessesindicators\analytics\indicator\received_database_entry_ratings();

        $sampleids = array($user1->id => $user1->id, $user2->id => $user2->id);
        $data = array(
            $user1->id => array(
                'user' => $user1,
                'course' => $course1,
            ),
            $user2->id => array(
                'user' => $user2,
                'course' => $course1,
            )
        );
        $indicator->add_sample_data($data);

        list($values, $ignored) = $indicator->calculate($sampleids, 'user');
        $this->assertEquals($indicator::get_min_value(), $values[$user1->id][0]);
        $this->assertEquals($indicator::get_min_value(), $values[$user2->id][0]);
    }
}
