<?php

/**
 * View - Template Rendering Engine
 * 
 * Provides view rendering with layout support, sections,
 * and helper methods for templates.
 * 
 * @package App\Helpers
 * @author SoftTech Team
 */

declare(strict_types=1);

namespace App\Helpers;

class View
{
    /**
     * Views directory path
     * @var string
     */
    private string $viewsPath;

    /**
     * Default layout
     * @var string
     */
    private string $defaultLayout = 'main';

    /**
     * Current sections being captured
     * @var array
     */
    private static array $sections = [];

    /**
     * Current section being captured
     * @var string|null
     */
    private static ?string $currentSection = null;

    /**
     * Constructor
     */
    public function __construct()
    {
        $this->viewsPath = __DIR__ . '/../Views';
    }

    /**
     * Render a view file with layout
     * 
     * @param string $view View path (e.g., 'pages/dashboard')
     * @param array $data Data to pass to view
     * @param string|null|false $layout Layout to use (null = default, false = no layout)
     * @return void
     */
    public function render(string $view, array $data = [], $layout = null): void
    {
        // Ensure BASE_URL is defined for views
        if (!defined('BASE_URL')) {
            define('BASE_URL', getenv('BASE_URL') ?: '/certificate/');
        }

        // Resolve layout
        if ($layout === null) {
            $layout = $this->defaultLayout;
        }

        // Extract data to variables
        extract($data);

        // Get view file path
        $viewFile = $this->resolveViewPath($view);

        if (!file_exists($viewFile)) {
            throw new \RuntimeException("View file not found: {$view}");
        }

        // Buffer the view content
        ob_start();
        require $viewFile;
        $content = ob_get_clean();

        // If no layout, just output content
        if ($layout === false) {
            echo $content;
            return;
        }

        // Load layout
        $layoutFile = $this->resolveLayoutPath($layout);

        if (file_exists($layoutFile)) {
            // Make content available to layout
            $content = $content;
            require $layoutFile;
        } else {
            // Fallback: just output content
            echo $content;
        }
    }

    /**
     * Render a view and return as string
     * 
     * @param string $view View path
     * @param array $data Data to pass to view
     * @return string
     */
    public function fetch(string $view, array $data = []): string
    {
        ob_start();
        $this->render($view, $data, false);
        return ob_get_clean();
    }

    /**
     * Include a partial view
     * 
     * @param string $partial Partial path (e.g., 'partials/header')
     * @param array $data Data to pass to partial
     * @return void
     */
    public static function partial(string $partial, array $data = []): void
    {
        extract($data);

        $partialFile = __DIR__ . '/../Views/' . str_replace('.', '/', $partial) . '.php';

        if (file_exists($partialFile)) {
            require $partialFile;
        }
    }

    /**
     * Start a section
     * 
     * @param string $name Section name
     * @return void
     */
    public static function section(string $name): void
    {
        self::$currentSection = $name;
        ob_start();
    }

    /**
     * End the current section
     * 
     * @return void
     */
    public static function endSection(): void
    {
        if (self::$currentSection !== null) {
            self::$sections[self::$currentSection] = ob_get_clean();
            self::$currentSection = null;
        }
    }

    /**
     * Output a section
     * 
     * @param string $name Section name
     * @param string $default Default content
     * @return void
     */
    public static function yield(string $name, string $default = ''): void
    {
        echo self::$sections[$name] ?? $default;
    }

    /**
     * Check if section exists
     * 
     * @param string $name Section name
     * @return bool
     */
    public static function hasSection(string $name): bool
    {
        return isset(self::$sections[$name]);
    }

    /**
     * Escape HTML entities
     * 
     * @param string $value Value to escape
     * @return string
     */
    public static function escape(string $value): string
    {
        return htmlspecialchars($value, ENT_QUOTES, 'UTF-8');
    }

    /**
     * Generate URL
     * 
     * @param string $path Path
     * @return string
     */
    public static function url(string $path = ''): string
    {
        return BASE_URL . ltrim($path, '/');
    }

    /**
     * Generate asset URL
     * 
     * @param string $path Asset path
     * @return string
     */
    public static function asset(string $path): string
    {
        return BASE_URL . 'assets/' . ltrim($path, '/');
    }

    /**
     * Get SSO asset URL
     * 
     * @param string $path Asset path
     * @return string
     */
    public static function ssoAsset(string $path): string
    {
        $ssoAssets = getenv('SSO_ASSET_URL');
        if ($ssoAssets) {
            return rtrim($ssoAssets, '/') . '/' . ltrim($path, '/');
        }
        return self::asset($path);
    }

    /**
     * Resolve view file path
     * 
     * @param string $view View name
     * @return string
     */
    private function resolveViewPath(string $view): string
    {
        // Support dot notation
        $view = str_replace('.', '/', $view);

        // Prevent directory traversal
        $view = preg_replace('/\.\.+/', '', $view);

        return $this->viewsPath . '/' . $view . '.php';
    }

    /**
     * Resolve layout file path
     * 
     * @param string $layout Layout name
     * @return string
     */
    private function resolveLayoutPath(string $layout): string
    {
        $layout = preg_replace('/\.\.+/', '', $layout);
        return $this->viewsPath . '/layouts/' . $layout . '.php';
    }

    /**
     * Set views directory
     * 
     * @param string $path Directory path
     * @return self
     */
    public function setViewsPath(string $path): self
    {
        $this->viewsPath = $path;
        return $this;
    }

    /**
     * Set default layout
     * 
     * @param string $layout Layout name
     * @return self
     */
    public function setDefaultLayout(string $layout): self
    {
        $this->defaultLayout = $layout;
        return $this;
    }

    /**
     * Check if view exists
     * 
     * @param string $view View name
     * @return bool
     */
    public function exists(string $view): bool
    {
        return file_exists($this->resolveViewPath($view));
    }
}
