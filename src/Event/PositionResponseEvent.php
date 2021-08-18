<?php

namespace App\Event;

use Symfony\Contracts\EventDispatcher\Event;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\HttpKernelInterface;

class PositionResponseEvent extends Event
{
    public const NAME = 'position.response';
    private array $data;
    private Request $request;
    private ?int $requestType;
    private HttpKernelInterface $kernel;
    private ?\DateTime $dateSelected;

    private Response $response;

    public function __construct(
//        HttpKernelInterface $kernel,
        Request $request,
//        ?int $requestType,
//        Response $response,
        array $data,
        ?\DateTime $dateSelected = null
    ) {
//        $this->kernel = $kernel;
        $this->request = $request;
//        $this->requestType = $requestType;
        $this->data = $data;
        $this->dateSelected = $dateSelected;

//        $this->setResponse($response);
    }

    final public function getData(): array
    {
        return $this->data;
    }

    final public function getDateSelected(): ?\DateTime
    {
        return $this->dateSelected;
    }

    final public function getResponse(): Response
    {
        return $this->response;
    }

    final public function setResponse(Response $response): self
    {
        $this->response = $response;

        return $this;
    }

    final public function getRequest(): Request
    {
        return $this->request;
    }

    final public function setRequest(Request $request): self
    {
        $this->request = $request;

        return $this;
    }

    final public function getRequestType(): int
    {
        return $this->requestType;
    }

    final public function setRequestType(int $requestType): self
    {
        $this->requestType = $requestType;

        return $this;
    }

    final public function getKernel(): HttpKernelInterface
    {
        return $this->kernel;
    }

    final public function setKernel(HttpKernelInterface $kernel): self
    {
        $this->kernel = $kernel;

        return $this;
    }

    final public function isMasterRequest(): bool
    {
        return HttpKernelInterface::MASTER_REQUEST === $this->requestType;
    }
}
