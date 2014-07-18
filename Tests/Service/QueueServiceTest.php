<?php

namespace WhiteOctober\QueueBundle\Tests\Service;

use WhiteOctober\QueueBundle\Tests\WhiteOctoberCoreTestCase;

use WhiteOctober\QueueBundle\Entity\QueueEntry,
    WhiteOctober\QueueBundle\Service\QueueService;

class QueueServiceTest extends WhiteOctoberCoreTestCase
{
    /**
     * Tests our service is created
     */
    public function testServiceAvailable()
    {
        $svc = $this->_container->get("whiteoctober.queue.service");
        $this->assertInstanceOf("WhiteOctober\\QueueBundle\\Service\\QueueService", $svc);

        // and alias
        $svc = $this->_container->get("whiteoctober_queue");
        $this->assertInstanceOf("WhiteOctober\\QueueBundle\\Service\\QueueService", $svc);
    }

    /**
     * Test we can create a new job entry
     */
    public function testCreatingJobEntry()
    {
        /* @var $svc \WhiteOctober\QueueBundle\Service\QueueService */
        $svc = $this->_container->get("whiteoctober.queue.service");
        $this->assertTrue($svc->create("queue.test", "some data here"));

        $entry = $this->_entityManager->getRepository("WhiteOctoberQueueBundle:QueueEntry")->findOneBy(array("type" => "queue.test"));
        $this->assertNotNull($entry);
        $this->assertEquals(serialize("some data here"), $entry->getData());
    }
}
