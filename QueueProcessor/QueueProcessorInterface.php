<?php

namespace WhiteOctober\QueueBundle\QueueProcessor;

use Symfony\Component\Console\Output\OutputInterface;

interface QueueProcessorInterface
{
    public function process(OutputInterface $output);

    public function setData($data);

    public function getType();
}
