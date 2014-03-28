<?php

namespace WhiteOctober\QueueBundle\Tests;

require_once(__DIR__ . "/../../../../app/AppKernel.php");

use Symfony\Component\Validator\ConstraintViolationList;
use Doctrine\ORM\Tools\SchemaTool;

class WhiteOctoberCoreTestCase extends \PHPUnit_Framework_TestCase
{
    protected $_kernel;

    protected $_application;

    protected $_container;

    /**
     * @var \Doctrine\ORM\EntityManager
     */
    protected $_entityManager;

    /**
     * Sets up the schema etc
     *
     * @param $env
     * @return void
     */
    public function setUp($env = "test")
    {
        // Boot the AppKernel in the test environment and with the debug.
        $this->_kernel = new \AppKernel($env, true);
        $this->_kernel->boot();

        // Store the container and the entity manager in test case properties
        $this->_container = $this->_kernel->getContainer();
        $this->_entityManager = $this->_container->get('doctrine')->getManager();
        $this->_entityManager->clear();

        $this->dropTables();

        parent::setUp();

        // Build the schema for sqlite
        $this->generateSchema();
    }

    /**
     * Tear-down for phpunit
     *
     * @return void
     */
    public function tearDown()
    {
        // Shutdown the kernel.
        $this->_kernel->shutdown();

        parent::tearDown();
    }

    /**
     * Define constants after requires/includes
     * This stops constants attempting to be redefined
     * and is particularly helpful when running with --process-isolation
     *
     * from https://gist.github.com/ec35af03594246c6dd52
     * and http://kpayne.me/2012/07/02/phpunit-process-isolation-and-constant-already-defined/
     *
     * @param  \Text_Template $template
     * @return void
     */
    public function prepareTemplate(\Text_Template $template)
    {
        $property = new \ReflectionProperty($template, "template");
        $property->setAccessible(true);
        $str = $property->getValue($template);
        $str = str_replace("{constants}", "", $str);
        $str .= "\n{constants}\n";
        $property->setValue($template, $str);
        parent::prepareTemplate($template);
    }

    /**
     * Generates the schema to use
     *
     * @throws Doctrine\DBAL\Schema\SchemaException
     * @return void
     */
    protected function generateSchema()
    {
        // Get the metadatas of the application to create the schema.
        $metadatas = $this->getMetadatas();

        if (!empty($metadatas)) {
            // Create SchemaTool
            $tool = new SchemaTool($this->_entityManager);
            $tool->createSchema($metadatas);
        }
    }

    /**
     * Overwrite this method to get specific metadatas.
     *
     * @return Array
     */
    protected function getMetadatas()
    {
        return $this->_entityManager->getMetadataFactory()->getAllMetadata();
    }

    /**
     * Handles dropping tables from the schema
     */
    protected function dropTables()
    {
        $tool = new SchemaTool($this->_entityManager);
        $tool->dropSchema($this->getMetadatas());
    }

    /**
     * Checks a violation is present in a constraint violation list
     *
     * @param \Symfony\Component\Validator\ConstraintViolationList $list
     * @param $property
     */
    protected function assertPropertyInViolationList(ConstraintViolationList $list, $property)
    {
        foreach ($list as $violation) {
            if ($violation->getPropertyPath() == $property) {
                $this->assertTrue(true);

                return;
            }
        }
        throw new \LogicException("Property {$property} not present in violations list");
    }

    /**
     * Checks a violation is NOT present in a constraint violation list
     *
     * @param \Symfony\Component\Validator\ConstraintViolationList $list
     * @param $property
     */
    protected function assertPropertyNotInViolationList(ConstraintViolationList $list, $property)
    {
        foreach ($list as $violation) {
            if ($violation->getPropertyPath() == $property) {
                throw new \LogicException("Property {$property} is present in violations list");
            }
        }
        $this->assertTrue(true);
    }

    /**
     * Checks a class has a constant present in it
     *
     * @param $constantName
     * @param $class
     * @return void
     */
    protected function assertClassHasConstant($constantName, $class)
    {
        $refl = new \ReflectionClass($class);
        $this->assertArrayHasKey($constantName, $refl->getConstants(), "Constant {$constantName} does not exist");
    }

    /**
     * Validates a supplied object and returns
     * the violation list
     *
     * @param $object
     * @return\Symfony\Component\Validator\ConstraintViolationList
     */
    protected function validate($object)
    {
        return $this->_container->get("validator")->validate($object);
    }
}
