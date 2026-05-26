<?php
// This file is part of Moodle - http://moodle.org/

declare(strict_types=1);

namespace mod_processassign\grades;

use core_grades\local\gradeitem\advancedgrading_mapping;
use core_grades\local\gradeitem\itemnumber_mapping;

class gradeitems implements itemnumber_mapping, advancedgrading_mapping {

    public static function get_itemname_mapping_for_component(): array {
        return [
            0 => 'submissions',
            1 => 'stage1',
            2 => 'stage2',
            3 => 'stage3',
            4 => 'stage4',
            5 => 'stage5',
        ];
    }

    public static function get_advancedgrading_itemnames(): array {
        return [
            'stage1',
            'stage2',
            'stage3',
            'stage4',
            'stage5',
        ];
    }
}
