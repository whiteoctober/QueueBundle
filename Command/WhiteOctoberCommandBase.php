<?php

namespace WhiteOctober\QueueBundle\Command;

use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;

use Symfony\Component\Console\Input\InputInterface,
    Symfony\Component\Console\Input\InputOption,
    Symfony\Component\Console\Output\OutputInterface;

class WhiteOctoberCommandBase extends ContainerAwareCommand
{
    const OPTION_OVERRIDE = "override-lock";

    /**
     * @var InputInterface
     */
    private $_i;

    /**
     * @var OutputInterface
     */
    private $_o;

    /**
     * Lock directory and file locations
     *
     * @var string
     */
    private $_lockFile;
    private $_lockDir;

    /**
     * Errors to be emailed
     * Put whatever you want in here as entries
     *
     * @var array
     */
    private $commandErrors = array();

    /**
     * {@inheritdoc}
     */
    public function __construct($name = null)
    {
        parent::__construct($name);
        $this->addOption(self::OPTION_OVERRIDE, null, InputOption::VALUE_NONE, null);
    }

    /**
     * {@inheritdoc}
     */
    public function run(InputInterface $input, OutputInterface $output)
    {
        $this->_i = $input;
        $this->_o = $output;

        $result = parent::run($input, $output);
        $this->clearLockFile();
        $this->emailErrors();

        return $result;
    }

    /**
     * {@inheritdoc}
     */
    protected function initialize(InputInterface $i, OutputInterface $o)
    {
        $this->setupLock();
        $this->writeLockFile();
    }

    /**
     * Sets the lock variable up
     * and handles the override request
     */
    protected function setupLock()
    {
        $this->_lockDir = sys_get_temp_dir() . DIRECTORY_SEPARATOR . "queue-bundle";
        $this->_lockFile = $this->_lockDir . DIRECTORY_SEPARATOR . $this->getName() . ".lck";
        if ($this->_i->getOption(self::OPTION_OVERRIDE)) {
            $this->_o->writeln("<info>QueueBundle: WARNING - Overriding lock!</info>");
            $this->clearLockFile();
        }
    }

    /**
     * Writes the lock file
     * @throws \RuntimeException
     * @return bool
     */
    protected function writeLockFile()
    {
        if (file_exists($this->_lockFile)) {
            // Check timestamp - greater than 15 mins?
            if (filemtime($this->_lockFile) > (strtotime("-15 minutes"))) {
                throw new \RuntimeException($this->getName() . " is currently running, not spawning new instance");
            }

            // Lock file has been sat there for longer than 15 mins, remove it
            unlink($this->_lockFile);
        }
        if (!file_exists($this->_lockDir)) {
            $this->_o->writeln("<info>QueueBundle: Creating lock directory</info>");
            mkdir($this->_lockDir);
        }
        $this->_o->writeln("<info>QueueBundle: Writing lock file</info>");
        touch($this->_lockFile);

        return true;
    }

    /**
     * Clears lock file
     */
    protected function clearLockFile()
    {
        $this->_o->writeln("<info>QueueBundle: Clearing lock file</info>");
        @unlink($this->_lockFile);
    }

    /**
     * Add an entry for emailing
     *
     * @param $entry
     */
    protected function addCommandError($entry)
    {
        $this->commandErrors[] = $entry;
    }

    /**
     * Emails errors to the address defined in
     * parameters
     */
    protected function emailErrors()
    {
        $ctr = $this->getContainer();
        if (!count($this->commandErrors)) {
            return;
        }
        if (!$ctr->hasParameter("task_errors_to") || !strlen($emailTo = $ctr->getParameter("task_errors_to"))) {
            return;
        }

        $msg = \Swift_Message::newInstance()->
            setSubject("ERRORS REPORTED: " . $this->getName())->
            setFrom($ctr->getParameter("emailsFromEmail"))->
            setTo($emailTo)
        ;

        $body = "";
        foreach ($this->commandErrors as $entry) {
            if (is_array($entry)) {
                $body .= print_r($entry, true) . "\n";
            } else {
                $body .= $entry . "\n";
            }
        }
        $msg->setBody($body);
        $ctr->get("mailer")->send($msg);
    }
}
