<?php

return [
    /*
     * Available languages of the application.
     * List of all languages, separated by comas.
     */
    'languages' => env('LANGUAGES', 'ar,cs,de,es,fr,he,hr,it,ja,nl,pt,pt-BR,ru,tr,zh-CN'),

    /*
     * Source language of the application.
     */
    'source_language' => env('SOURCE_LANGUAGE', 'en'),

    /*
     * Crowdin api key.
     * See https://crowdin.com/project/{project_name}/settings#api
     */
    'apikey' => env('CROWDIN_APIKEY'),

    /*
     * Crowdin project.
     * See https://crowdin.com/project/{project_name}/settings#api
     */
    'project' => env('CROWIND_PROJECT'),

    /*
     * Resulting file format.
     * @see https://support.crowdin.com/files-management/#file-export-settings
     */
    'resulting_file' => '/master/resources/lang/%two_letters_code%/%original_file_name%',

];
