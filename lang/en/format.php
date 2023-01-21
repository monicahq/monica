<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Formats for dates, hours, etc.
    |--------------------------------------------------------------------------
    |
    | The following language lines are used for date formatting.
    | Be careful to translate it to the right format.
    |
    | We use Carbon to format dates, see http://carbon.nesbot.com/docs/#api-commonformats
    | Format parameters are described here http://www.php.net/manual/en/function.date.php
    |
    */
    /* date and time in a format like "Oct 29, 1981 19:32" - see https://carbon.nesbot.com/docs/#iso-format-available-replacements */
    'short_date_year_time' => 'MMM DD, YYYY hh:mm A',

    /* day and the month in a format like "Jul 29" or "Jul 01" - see https://carbon.nesbot.com/docs/#iso-format-available-replacements */
    'short_date' => 'MMM DD',

    /* short month and the year in a format like "Jul 2020" - see https://carbon.nesbot.com/docs/#iso-format-available-replacements */
    'short_month_year' => 'MMM Y',

    /* short month and the year in a format like "July 2020" - see https://carbon.nesbot.com/docs/#iso-format-available-replacements */
    'long_month_year' => 'MMMM Y',

    /* month as a string like "Jul" - see https://carbon.nesbot.com/docs/#iso-format-available-replacements */
    'short_month' => 'MMM',

    /* day and month in a format like "July 29th" - see https://carbon.nesbot.com/docs/#iso-format-available-replacements */
    'long_month_day' => 'MMMM Do',

    /* day and the month in a format like "Monday (July 29th)" - see https://carbon.nesbot.com/docs/#iso-format-available-replacements */
    'day_month_parenthesis' => 'dddd (MMM Do)',

    /* date in a format like "Oct 01, 1981" - see https://carbon.nesbot.com/docs/#iso-format-available-replacements */
    'date' => 'MMM DD, YYYY',

    /* month name - see https://carbon.nesbot.com/docs/#iso-format-available-replacements */
    'full_month' => 'MMMM',

    /* complete date in a format like "Monday, July 29th 2020" - see https://carbon.nesbot.com/docs/#iso-format-available-replacements */
    'full_date' => 'dddd, MMM Do YYYY',

    /* day as a string like "Wednesday" - see https://carbon.nesbot.com/docs/#iso-format-available-replacements */
    'day' => 'dddd',

    /* day as a string like "Wed" - see https://carbon.nesbot.com/docs/#iso-format-available-replacements */
    'short_day' => 'ddd',

    /* day as a number like "03" - see https://carbon.nesbot.com/docs/#iso-format-available-replacements */
    'day_number' => 'DD',

    /* day as a string like "Jul. 29th" - see https://carbon.nesbot.com/docs/#iso-format-available-replacements */
    'day_short_month' => 'MMM Do',
];
