<?php

declare(strict_types=1);

namespace App\EventSubscriber;

use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\KernelEvents;

class RequestSubscriber implements EventSubscriberInterface
{
    public static function getSubscribedEvents(): array
    {
        return [
            KernelEvents::REQUEST => 'onKernelRequestOptionMethod',
        ];
    }

    final public function onKernelRequestOptionMethod(RequestEvent $event): void
    {
        if ($event->getRequest()->getMethod() === 'OPTIONS') {
            $event->setResponse(
                new Response('', 204, [
                    'Access-Control-Allow-Origin' => '*',
                    'Access-Control-Allow-Credentials' => 'true',
                    'Access-Control-Allow-Methods' => 'GET, POST, PUT, DELETE, OPTIONS',
                    'Access-Control-Allow-Headers' => 'DNT, X-User-Token, Keep-Alive, User-Agent, X-Requested-With, If-Modified-Since, Cache-Control, Content-Type',
                    'Access-Control-Max-Age' => 1728000,
                    'Content-Type' => 'text/plain charset=UTF-8',
                    'Content-Length' => 0,
                ])
            );
        }
    }
}