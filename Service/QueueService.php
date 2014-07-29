<?php

namespace WhiteOctober\QueueBundle\Service;

use Symfony\Component\EventDispatcher\EventDispatcher;

use Doctrine\ORM\EntityManager;

use WhiteOctober\QueueBundle\Entity\QueueEntry,
    WhiteOctober\QueueBundle\Event\QueueEntryCreatedEvent;

/**
 * WhiteOctober job queueing service
 */
class QueueService
{
    protected $em;

    protected $dispatcher;

    /**
     * Constructor
     *
     * @param \Doctrine\ORM\EntityManager $em
     * @param EventDispatcher             $dispatcher
     */
    public function __construct(EntityManager $em, EventDispatcher $dispatcher)
    {
        $this->em = $em;
        $this->dispatcher = $dispatcher;
    }

    /**
     * Creates a new queue entry.
     * Returns true if successful
     *
     * @param $type
     * @param  string $data
     * @return bool
     */
    public function create($type, $data = "", $priority = 10)
    {
        $entry = new QueueEntry();
        $entry->setType($type);
        $entry->setData(serialize($data));
        $entry->setPriority($priority);

        $this->em->persist($entry);
        $this->em->flush();

        // Send new queue entry notification
        $event = new QueueEntryCreatedEvent($entry);
        $this->dispatcher->dispatch("whiteoctober.queue.entry.created", $event);

        return true;
    }

    /**
     * Removes any entries based on criteria ('data' field is serialised if provided)
     *
     * @param array $criteria Column name => value
     */
    public function remove(array $criteria)
    {
        if (isset($criteria['data'])) {
            $criteria['data'] = serialize($criteria['data']);
        }

        $this->em->getRepository("WhiteOctoberQueueBundle:QueueEntry")->removeBy($criteria);
    }
}
