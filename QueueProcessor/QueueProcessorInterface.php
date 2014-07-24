<?php

namespace WhiteOctober\QueueBundle\QueueProcessor;

use Symfony\Component\Console\Output\OutputInterface;

interface QueueProcessorInterface
{
    public function process(OutputInterface $output);

    public function setData($data);

    /**
     * Returns the type that this processor responds to
     *
     * @return string
     */
    public function getType();
}
