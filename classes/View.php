<?php
declare(strict_types=1);

class View
{
    private string $viewsPath;
    private array $data = [];

    public function __construct(string $viewsPath = '')
    {
        $this->viewsPath = $viewsPath ?: __DIR__ . '/../views/';
    }

    public function render(string $view, array $data = []): string
    {
        $this->data = array_merge($this->data, $data);

        $viewFile = $this->viewsPath . str_replace('.', '/', $view) . '.php';

        if (!file_exists($viewFile)) {
            throw new Exception("View file not found: {$viewFile}");
        }

        // Extract data to variables
        extract($this->data);

        // Start output buffering
        ob_start();

        // Include the view file
        include $viewFile;

        // Get the contents and clean buffer
        return ob_get_clean();
    }

    public function with(string $key, $value): self
    {
        $this->data[$key] = $value;
        return $this;
    }

    public function withData(array $data): self
    {
        $this->data = array_merge($this->data, $data);
        return $this;
    }
}