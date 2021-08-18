<?php


namespace App\EventListener;

use App\Entity\Publisher;
use Doctrine\Persistence\Event\LifecycleEventArgs;
use Symfony\Component\Filesystem\Filesystem;

class PublisherListener
{
    protected const WORKDIR = '/application/public/icons';

    public function preRemove(Publisher $publisher, LifecycleEventArgs $event): void
    {
        foreach ($publisher->getApplications() as $application) {
            $path = self::WORKDIR . DIRECTORY_SEPARATOR . $application->getIcon();
            if ((new Filesystem())->exists($path)) {
                (new Filesystem())->remove($path);
            }
        }
    }
}
