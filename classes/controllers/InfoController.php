<?php

declare(strict_types=1);

require_once __DIR__ . '/BaseController.php';
require_once __DIR__ . '/../Map.php';

class InfoController extends BaseController
{
    public function index(): void
    {
        echo "Maps Service Engine API v1.0";
    }
}