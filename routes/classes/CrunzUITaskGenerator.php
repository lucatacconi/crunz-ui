<?php

declare(strict_types=1);

namespace CrunzUI\Task;

class CrunzUITaskGenerator
{


    /**
     * Executes the current command.
     *
     * @param \Symfony\Component\Console\Input\InputInterface   $input
     * @param \Symfony\Component\Console\Output\OutputInterface $output
     *
     * @return int|null null or 0 if everything went fine, or an error code
     */



    public function __construct()
    {
        //$this->configuration = $configuration;
    }


    public function test(): void
    {
        // $this->getMailer()
        //     ->send(
        //         $this->getMessage($subject, $message)
        //     )
        // ;
    }


    /**
     * Get the task type.
     *
     * @param string $pippo
     *
     * @return string
     */
    protected function getTaskTemplate($pippo)
    {
        return $this->options['type'];
    }
}
