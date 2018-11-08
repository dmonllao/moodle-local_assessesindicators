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
 *
 * @package   core
 * @copyright 2016 David Monllao {@link http://www.davidmonllao.com}
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_assessesindicators\analytics\indicator;

defined('MOODLE_INTERNAL') || die();

/**
 *
 * @package   core
 * @copyright 2016 David Monllao {@link http://www.davidmonllao.com}
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class received_database_entry_ratings extends \core_analytics\local\indicator\binary {

    /**
     * @var \grade_item
     */
    protected static $gis = [];

    /**
     * Returns the name.
     *
     * If there is a corresponding '_help' string this will be shown as well.
     *
     * @return \lang_string
     */
    public static function get_name() : \lang_string {
        return new \lang_string('receiveddatabaseratings', 'local_assessesindicators');
    }

    /**
     * required_sample_data
     *
     * @return string[]
     */
    public static function required_sample_data() {
        return array('user', 'course');
    }

    /**
     * calculate_sample
     *
     * @param int $sampleid
     * @param string $sampleorigin
     * @param int $starttime
     * @param int $endtime
     * @return float
     */
    protected function calculate_sample($sampleid, $sampleorigin, $starttime = false, $endtime = false) {
        global $DB, $CFG;

        if (!empty($CFG->disablegradehistory)) {
            return null;
        }

        $user = $this->retrieve('user', $sampleid);
        $course = $this->retrieve('course', $sampleid);
        $modinfo = get_fast_modinfo($course);

        $ratingfound = false;
        $databases = $modinfo->get_instances_of('data');
        foreach($databases as $database) {
            if ($database->sectionnum == 0) {
                // Skip top section database activities.
                continue;
            }

            $key = $course->id . '-' . $database->instance;
            if (empty(self::$gis[$key])) {
                self::$gis[$key] = new \grade_item([
                    'courseid' => $course->id,
                    'iteminstance' => $database->instance,
                    'itemmodule' => 'data'
                ]);
            }

            // We check grade grades history because we probably have a $endtime restriction.
            $params = [
                'itemid' => self::$gis[$key]->id,
                'userid' => $user->id,
            ];
            $select = 'itemid = :itemid AND userid = :userid';
            if ($endtime) {
                $params['endtime'] = $endtime;
                $select .= ' AND timemodified <= :endtime';
            }
            if ($DB->record_exists_select('grade_grades_history', $select, $params)) {
                $ratingfound = true;
                break;
            }
        }

        if ($ratingfound) {
            return self::get_max_value();
        }
        return self::get_min_value();
    }
}
