<?php

declare(strict_types=1);

require_once __DIR__ . '/BaseController.php';
require_once __DIR__ . '/../Map.php';

class MapController extends BaseController
{
    private Map $map;

    public function __construct()
    {
        parent::__construct();
        // $this->map = new Map($this->config);
    }

    public function index(): void
    {
        // Render a view for the maps index page
        $this->view('maps.index', [
            'title' => 'Maps Service Engine',
            'description' => 'Welcome to the Maps Service Engine. Use the API to retrieve map tiles and convert coordinates.'
        ]);
    }
}