<?php

namespace WhiteOctober\QueueBundle\Event;

use Symfony\Component\EventDispatcher\Event;

use WhiteOctober\QueueBundle\Entity\QueueEntry;

class QueueEntryCreatedEvent extends Event
{
    /**
     * The queue entry
     *
     * @var \WhiteOctober\QueueBundle\Entity\QueueEntry
     */
    protected $entry;

    /**
     * Class constructor.
     * Supply the queue entry object here if required.
     * Alternatively, use setEntry()
     *
     *
     * @param \WhiteOctober\QueueBundle\Entity\QueueEntry $entry
     */
    public function __construct(QueueEntry $entry = null)
    {
        $this->entry= $entry;
    }

    /**
     * Sets the queue entry object
     *
     * @param $entry
     */
    public function setEntry(QueueEntry $entry)
    {
        $this->entry = $entry;
    }

    /**
     * Returns the entry object
     *
     * @return QueueEntry
     */
    public function getEntry()
    {
        return $this->entry;
    }
}
