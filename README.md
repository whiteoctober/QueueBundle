QueueBundle
===========

Turning the [QueueBundle in MyPace](https://github.com/whiteoctober/myPace/tree/develop/src/WhiteOctober/QueueBundle) (which was originally from GBK) into a project in its own right.

In case it's not obvious, this documentation is a rough draft!

# Installing

Install via composer:

    "require": {
        "whiteoctober/queue-bundle": "version-here"
    },

Add to AppKernel:

    // app/AppKernel.php
    public function registerBundles()
    {
        $bundles = array(
            // Your other bundles here
            new WhiteOctober\QueueBundle\WhiteOctoberQueueBundle(),
        );

        return $bundles;
    }
