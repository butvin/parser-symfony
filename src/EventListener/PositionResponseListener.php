<?php

declare(strict_types=1);

namespace App\EventListener;

use function json_last_error;
use function json_last_error_msg;
use App\Entity\Position;
use App\Event\PositionResponseEvent;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\Serializer\SerializerInterface;

class PositionResponseListener
{
    private SerializerInterface $serializer;

    public function __construct(SerializerInterface $serializer)
    {
        $this->serializer = $serializer;
    }

    final public function onPositionResponse(PositionResponseEvent $event): void
    {
        $dataTableHeaders = [
            'Content-Type' => 'application/json',
            'Special-Forward' => 'datatable/json',
        ];

        /** Position[] */
        $positions = $event->getData();
        if (!is_array($positions) && !($positions[0] instanceof Position)) {
            return;
        }

        $data = [];
        foreach ($positions as $property => $value) {
            $data[$property] = $this->serializer->serialize($value, 'json', [
                'groups' => 'jsonResponse',
                'json_encode_options' => JSON_THROW_ON_ERROR | JSON_OBJECT_AS_ARRAY,
            ]);
        }

        if (json_last_error() !== JSON_ERROR_NONE) {
            $exception = new BadRequestHttpException(sprintf("invalid json:\n%s", json_last_error_msg()));
            $response = new JsonResponse(['error' => $exception], Response::HTTP_NO_CONTENT, $dataTableHeaders);
            $response->send();
        }

        if (empty($data)) {
            $response = new JsonResponse(['error' => 'not found'], Response::HTTP_NO_CONTENT, $dataTableHeaders);
            $response->send();
        }

        $response = new JsonResponse($data, Response::HTTP_OK, $dataTableHeaders);
        $response->send();
    }
}
