<?php

/**
 * LocalOverrideService
 *
 * Stores user-edited overrides for Google Sheet records
 * in a local JSON file on the server (no DB, no API needed).
 *
 * File: storage/overrides/{dataset}.json
 * Format: { "record_key": { field => value, ... }, ... }
 *
 * @package App\Services
 */

declare(strict_types=1);

namespace App\Services;

class LocalOverrideService
{
    private string $storageDir;

    public function __construct()
    {
        $this->storageDir = rtrim(dirname(__DIR__, 2), '/\\') . '/storage/overrides';
        if (!is_dir($this->storageDir)) {
            mkdir($this->storageDir, 0755, true);
        }
    }

    /**
     * Get the file path for a dataset's overrides
     */
    private function filePath(string $dataset): string
    {
        // Sanitize dataset name
        $name = preg_replace('/[^a-z0-9_]/', '_', strtolower($dataset));
        return $this->storageDir . '/' . $name . '.json';
    }

    /**
     * Load all overrides for a dataset
     */
    public function loadAll(string $dataset): array
    {
        $path = $this->filePath($dataset);
        if (!file_exists($path)) {
            return [];
        }
        $content = file_get_contents($path);
        return json_decode($content ?: '{}', true) ?: [];
    }

    /**
     * Save an override for a specific record key
     *
     * @param string $dataset   e.g. 'certificates' or 'learners'
     * @param string $key       Unique record identifier (S.No / row key)
     * @param array  $fields    Only the changed fields
     */
    public function save(string $dataset, string $key, array $fields): void
    {
        $all = $this->loadAll($dataset);

        // Merge with existing override for this key (if any)
        $existing = $all[$key] ?? [];
        $all[$key] = array_merge($existing, $fields, ['_updated_at' => date('Y-m-d H:i:s')]);

        file_put_contents(
            $this->filePath($dataset),
            json_encode($all, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE),
            LOCK_EX
        );
    }

    /**
     * Get override for a single record key
     */
    public function get(string $dataset, string $key): array
    {
        return $this->loadAll($dataset)[$key] ?? [];
    }

    /**
     * Merge sheet rows with local overrides.
     * Override fields take priority over sheet data for the same key.
     *
     * @param string $dataset
     * @param array  $rows       Rows from Google Sheet (each must have a 'id' key)
     * @param string $keyField   Which field to use as override key (default: 'id')
     * @return array Merged rows
     */
    public function mergeRows(string $dataset, array $rows, string $keyField = 'id'): array
    {
        $overrides = $this->loadAll($dataset);
        if (empty($overrides)) {
            return $rows;
        }

        foreach ($rows as &$row) {
            $key = (string)($row[$keyField] ?? '');
            if ($key !== '' && isset($overrides[$key])) {
                // Merge override fields into the row (override wins)
                foreach ($overrides[$key] as $field => $value) {
                    if ($field !== '_updated_at') {
                        $row[$field] = $value;
                    }
                }
                $row['_has_override'] = true;
            }
        }
        unset($row);

        return $rows;
    }

    /**
     * Delete override for a record (e.g. after reverting)
     */
    public function delete(string $dataset, string $key): void
    {
        $all = $this->loadAll($dataset);
        unset($all[$key]);
        file_put_contents(
            $this->filePath($dataset),
            json_encode($all, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE),
            LOCK_EX
        );
    }
}
