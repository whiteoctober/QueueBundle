<?php

namespace WhiteOctober\QueueBundle\Entity;

use Symfony\Component\Validator\Constraints as Assert;

use Doctrine\ORM\Mapping as ORM;

use Gedmo\Mapping\Annotation as Gedmo;

/**
 * @ORM\Entity(repositoryClass="WhiteOctober\QueueBundle\Repository\QueueEntryRepository")
 * @ORM\Table(name="queue_entry", indexes={@ORM\Index(name="search_idx", columns={"status", "priority", "createdAt"})})
 */
class QueueEntry
{
    /**
     * Constants for the 'status' column
     */
    const ERROR = -1;
    const NOT_STARTED = 0;
    const IN_PROGRESS = 1;
    const COMPLETED = 2;

    /**
     * @ORM\Id
     * @ORM\Column(type="integer")
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    protected $id;

    /**
     * The type of the queue entry
     *
     * @ORM\Column(type="string", length=50)
     * @Assert\NotBlank()
     */
    protected $type = "";

    /**
     * The priority of the queue entry
     *
     * @ORM\Column(type="integer")
     */
    protected $priority = 10;

    /**
     * The status of the entry
     *
     * @ORM\Column(type="integer")
     * @Assert\NotNull()
     * @var integer
     */
    protected $status = self::NOT_STARTED;

    /**
     * Any data required for the queue entry
     *
     * @ORM\Column(type="text", nullable=true)
     * @var string
     */
    protected $data;

    /**
     * @ORM\Column(type="datetime")
     * @Gedmo\Timestampable(on="create")
     */
    protected $createdAt;

    /**
     * Time the entry's processing started
     *
     * @ORM\Column(type="datetime", nullable=true)
     * @var \Datetime
     */
    protected $startedAt;

    /**
     * Time the entry's processing finished
     *
     * @ORM\Column(type="datetime", nullable=true)
     * @var \Datetime
     */
    protected $finishedAt;

    /**
     * Get id
     *
     * @return integer
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * Set type
     *
     * @param string $type
     */
    public function setType($type)
    {
        $this->type = $type;
    }

    /**
     * Get type
     *
     * @return string
     */
    public function getType()
    {
        return $this->type;
    }

    /**
     * Set status
     *
     * @param integer $status
     */
    public function setStatus($status)
    {
        $this->status = $status;
    }

    /**
     * Get status
     *
     * @return integer
     */
    public function getStatus()
    {
        return $this->status;
    }

    /**
     * Set data
     *
     * @param string $data
     */
    public function setData($data)
    {
        $this->data = $data;
    }

    /**
     * Get data
     *
     * @return string
     */
    public function getData()
    {
        return $this->data;
    }

    /**
     * Set createdAt
     *
     * @param \Datetime $createdAt
     */
    public function setCreatedAt($createdAt)
    {
        $this->createdAt = $createdAt;
    }

    /**
     * Get createdAt
     *
     * @return \Datetime
     */
    public function getCreatedAt()
    {
        return $this->createdAt;
    }

    /**
     * Get startedAt
     *
     * @return \Datetime
     */
    public function getStartedAt()
    {
        return $this->startedAt;
    }

    /**
     * Get finishedAt
     *
     * @return \Datetime
     */
    public function getFinishedAt()
    {
        return $this->finishedAt;
    }

    /**
     * Starts the job processing
     */
    public function start()
    {
        $this->startedAt = new \DateTime("now");
        $this->status = self::IN_PROGRESS;
    }

    /**
     * Finishes the job processing
     */
    public function finish()
    {
        $this->finishedAt = new \DateTime("now");
        $this->status = self::COMPLETED;
    }

    /**
     * Mark the entry as having errored
     */
    public function hasErrored()
    {
        $this->status = self::ERROR;
    }

    /**
     * Set startedAt
     *
     * @param \Datetime $startedAt
     */
    public function setStartedAt($startedAt)
    {
        $this->startedAt = $startedAt;
    }

    /**
     * Set finishedAt
     *
     * @param \Datetime $finishedAt
     */
    public function setFinishedAt($finishedAt)
    {
        $this->finishedAt = $finishedAt;
    }

    /**
     * Set priority
     *
     * @param integer $priority
     */
    public function setPriority($priority)
    {
        $this->priority = $priority;
    }

    /**
     * Get priority
     *
     * @return integer
     */
    public function getPriority()
    {
        return $this->priority;
    }
}
