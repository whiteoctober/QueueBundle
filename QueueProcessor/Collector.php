<?php

namespace WhiteOctober\QueueBundle\QueueProcessor;

class Collector
{
    private $processors;

    public function __construct()
    {
        $this->processors = array();
    }

    public function addProcessor(QueueProcessorInterface $processor, $id)
    {
        $this->processors[$id] = $processor;
    }

    public function getProcessors()
    {
        return $this->processors;
    }
}
