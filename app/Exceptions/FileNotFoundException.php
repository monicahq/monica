<?php

namespace App\Exceptions;

use Illuminate\Contracts\Filesystem\FileNotFoundException as FileNotFoundExceptionBase;

class FileNotFoundException extends FileNotFoundExceptionBase
{
    /**
     * @var string
     */
    public $fileName;

    /**
     * Create a new instance.
     *
     * @param string $fileName
     * @return void
     */
    public function __construct($fileName)
    {
        $this->fileName = $fileName;
        parent::__construct();
    }

    public function __toString()
    {
        return 'File not found: '.$this->fileName;
    }
}
