<?php

declare(strict_types=1);

namespace App\EventSubscriber;

use function json_last_error;
use function json_last_error_msg;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Serializer\Normalizer\AbstractNormalizer;
use Symfony\Component\Serializer\SerializerInterface;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\KernelEvents;
use Symfony\Component\HttpKernel\Event\ExceptionEvent;

class ExceptionHandlerSubscriber implements EventSubscriberInterface
{
    private SerializerInterface $serializer;

    public function __construct(SerializerInterface $serializer)
    {
        $this->serializer = $serializer;
    }

    public static function getSubscribedEvents(): array
    {
       return [KernelEvents::EXCEPTION => 'onExceptionConvertJson'];
    }

    /**
     * @throws \Symfony\Component\Serializer\Exception\ExceptionInterface
     */
    final public function onExceptionConvertJson(ExceptionEvent $event): void
    {
        $exception = $event->getThrowable();
        $event->allowCustomResponseCode();

        $normalized = $this->serializer->normalize($exception, null, [
            AbstractNormalizer::ATTRIBUTES => [
                'statusCode',
                'headers',
                'message',
                'code',
                'file',
                'line',
            ]
        ]);

        $data = $this->serializer->serialize($normalized, 'json', [
            'json_encode_options' => \JSON_OBJECT_AS_ARRAY,
        ]);

        if (json_last_error() !== JSON_ERROR_NONE) {
            throw new BadRequestHttpException(
                sprintf("INVALID JSON:\n%s", json_last_error_msg())
            );
        }

        $response = new JsonResponse(['data' => $data], Response::HTTP_NO_CONTENT);
        $event->setResponse($response);
    }
}
