<?php

namespace WhiteOctober\QueueBundle\QueueProcessor;

interface QueueProcessorInterface
{
    public function process();

    public function setData($data);

    public function getType();
}
