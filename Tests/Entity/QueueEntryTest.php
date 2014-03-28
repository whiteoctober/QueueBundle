<?php

namespace WhiteOctober\QueueBundle\Tests\Entity;

use WhiteOctober\QueueBundle\Tests\WhiteOctoberCoreTestCase;

use WhiteOctober\QueueBundle\Entity\QueueEntry;

class QueueEntryTest extends WhiteOctoberCoreTestCase
{
    /**
     * Tests our entity is created
     */
    public function testEntityAvailable()
    {
        $entry = new QueueEntry();
        $this->assertNotNull($entry);
    }

    /**
     * Test class constants are present
     */
    public function testConstants()
    {
        $r = new \ReflectionClass(get_class(new QueueEntry()));
        $this->assertTrue($r->hasConstant("NOT_STARTED"));
        $this->assertTrue($r->hasConstant("IN_PROGRESS"));
        $this->assertTrue($r->hasConstant("COMPLETED"));
    }

    /**
     * Test our setters are present
     */
    public function testCanSetAll()
    {
        $entry = new QueueEntry();
        $entry->setType("queue.test");
        $entry->setData("Some data here");
        $entry->setStatus(QueueEntry::NOT_STARTED);
    }

    /**
     * Test validation
     */
    public function testValidations()
    {
        $validator = $this->_container->get("validator");

        // Create invalid entity
        $entry = new QueueEntry();
        $errors = $validator->validate($entry);
        $this->assertEquals(1, count($errors));
        $this->assertPropertyInViolationList($errors, "type");

        // Update to valid
        $entry = new QueueEntry();
        $entry->setType("queue.test");
        $errors = $validator->validate($entry);
        $this->assertEquals(0, count($errors));
    }

    /**
     * Tests our start/finish methods
     */
    public function testStartFinish()
    {
        $entry = new QueueEntry();
        $this->assertNull($entry->getStartedAt());
        $this->assertNull($entry->getFinishedAt());

        $entry->start();
        $this->assertNotNull($start = $entry->getStartedAt());
        $this->assertNull($entry->getFinishedAt());

        sleep(1);

        $entry->finish();
        $this->assertNotNull($entry->getStartedAt());
        $this->assertNotNull($finish = $entry->getFinishedAt());
        $this->assertGreaterThan($start, $finish);
    }

    /**
     * Test entity defaults
     */
    public function testDefaults()
    {
        $entry = new QueueEntry();
        $this->assertEquals($entry->getStatus(), QueueEntry::NOT_STARTED);
    }
}
