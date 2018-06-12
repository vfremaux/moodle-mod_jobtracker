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
 * Version details.
 *
 * @package mod_jobtracker
 * @category mod
 * @author     Valery Fremaux (valery.fremaux@gmail.com)
 * @copyright 2013 onwards Valery Fremaux (valery.fremaux@gmail.com)
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

$plugin->version  = 2015011902;  // The current module version (Date: YYYYMMDDXX).
$plugin->requires = 2017110800;
$plugin->component = 'mod_jobtracker';   // Full name of the plugin (used for diagnostics).
$plugin->maturity = MATURITY_BETA;
$plugin->release = '3.5.0 (Build 2015011902)';
$plugin->dependencies = array('local_vflibs' => 2015053000);

// Non Moodle attributes.
$plugin->codeincrement = '3.5.0000';