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
class any_failed_quiz_attempt extends \core_analytics\local\indicator\binary {

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
        return new \lang_string('anyfailedquiz', 'local_assessesindicators');
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

        $failedquiz = false;
        $anyattempt = false;
        $quizzes = $modinfo->get_instances_of('quiz');
        foreach($quizzes as $quiz) {
            if ($quiz->sectionnum == 0) {
                // Skip top section quiz activities.
                continue;
            }

            $key = $course->id . '-' . $quiz->instance;
            if (empty(self::$gis[$key])) {
                self::$gis[$key] = new \grade_item([
                    'courseid' => $course->id,
                    'iteminstance' => $quiz->instance,
                    'itemmodule' => 'quiz'
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
            $gghs = $DB->get_records_select('grade_grades_history', $select, $params);
            foreach ($gghs as $ggh) {
                $ggh->finalgrade = floatval($ggh->finalgrade);
                if (!$ggh->finalgrade) {
                    // Discards 0.000 and nulls.
                    continue;
                }
                if (empty($anyattempt)) {
                    $anyattempt = true;
                }

                if (floatval(self::$gis[$key]->gradepass) > 0) {
                    $gradepass = self::$gis[$key]->gradepass;
                } else {
                    // (Max - min mean) + min.
                    $gradepass = (($ggh->rawgrademax - $ggh->rawgrademin) / 2) + $ggh->rawgrademin;
                }
                if ($ggh->finalgrade < $gradepass) {
                    $failedquiz = true;
                    break;
                }
            }
        }

        if ($anyattempt === false) {
            // We separate users without quiz attempts from users without fails.
            return null;
        }

        if ($failedquiz) {
            return self::get_max_value();
        }
        return self::get_min_value();
    }
}
