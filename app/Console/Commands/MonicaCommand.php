<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Cerbero\CommandValidator\ValidatesInput;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

abstract class MonicaCommand extends Command
{
    use ValidatesInput;

    protected function rules()
    {
        // No validation rules by default
        return [];
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        if ($this->validator()->passes()) {
            return parent::execute($input, $output);
        }
        throw new \InvalidArgumentException(implode("\n", $this->validator()->errors()->all()));
        return $this->error($this->getFormattedErrors());
    }

    protected function countDownFrom($seconds)
    {
        $seconds = 10;
        $this->line('Deleting all contact activities in '.$seconds.' seconds. THIS CAN NOT BE UNDONE (ctrl+c to cancel)');
        while ($seconds--) {
            $this->line(($seconds + 1).'...');
            sleep(1);
        }
    }
}
