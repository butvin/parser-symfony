<?php

namespace App\Controller;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;

final class ProxyCheckController extends AbstractController
{
    public function index(Request $request): Response
    {
        return $this->json(getallheaders());
    }
}
