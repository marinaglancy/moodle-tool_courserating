<?php
namespace tool_courserating;

class constants {
    const REVIEWS_PER_PAGE = 10;

    const CFIELD_RATING = 'tool_courserating';
    const CFIELD_RATINGMODE = 'tool_courserating_mode';

    const RATEBY_NOONE = 1;
    const RATEBY_ANYTIME = 2;
    const RATEBY_COMPLETED = 3;

    const SETTING_RATINGMODE = 'ratingmode';
    const SETTING_PERCOURSE = 'percourse';
    const SETTING_STARCOLOR = 'starcolor';
    const SETTING_RATINGCOLOR = 'ratingcolor';
    const SETTING_DISPLAYEMPTY = 'displayempty';

    const SETTING_STARCOLOR_DEFAULT = '#e59819';
    const SETTING_RATINGCOLOR_DEFAULT = '#b4690e';

    const COLOR_GRAY = '#a0a0a0';

    public static function rated_courses_options() {
        return [
            self::RATEBY_NOONE => new \lang_string('ratebynoone', 'tool_courserating'),
            self::RATEBY_ANYTIME => new \lang_string('ratebyanybody', 'tool_courserating'),
            self::RATEBY_COMPLETED => new \lang_string('ratebycompleted', 'tool_courserating'),
        ];
    }
}