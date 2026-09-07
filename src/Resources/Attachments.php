<?php

declare(strict_types=1);

namespace Maya719\ClickUp\Resources;

use Maya719\ClickUp\Exceptions\ClickUpException;
use Psr\Http\Message\StreamInterface;

/**
 * Task attachment uploads.
 *
 * @see https://developer.clickup.com/reference/createtaskattachment
 */
class Attachments extends Resource
{
    /**
     * Upload a file from disk.
     *
     * @param  array<string, mixed>  $query  Supports `custom_task_ids` + `team_id`.
     * @return array<string, mixed>
     */
    public function upload(string $taskId, string $path, ?string $filename = null, array $query = []): array
    {
        if (! is_readable($path)) {
            throw new ClickUpException(sprintf('Attachment "%s" does not exist or is not readable.', $path));
        }

        $handle = fopen($path, 'rb');

        if ($handle === false) {
            throw new ClickUpException(sprintf('Could not open attachment "%s" for reading.', $path));
        }

        return $this->uploadStream($taskId, $handle, $filename ?? basename($path), $query);
    }

    /**
     * Upload raw file contents already held in memory.
     *
     * @param  array<string, mixed>  $query
     * @return array<string, mixed>
     */
    public function uploadContents(string $taskId, string $contents, string $filename, array $query = []): array
    {
        return $this->uploadStream($taskId, $contents, $filename, $query);
    }

    /**
     * Upload from any value Guzzle can turn into a body: a string, resource or stream.
     *
     * @param  resource|string|StreamInterface  $body
     * @param  array<string, mixed>             $query
     * @return array<string, mixed>
     */
    public function uploadStream(string $taskId, mixed $body, string $filename, array $query = []): array
    {
        return $this->client->multipart(
            'task/' . $this->segment($taskId) . '/attachment',
            [
                [
                    'name' => 'attachment',
                    'contents' => $body,
                    'filename' => $filename,
                ],
                [
                    'name' => 'filename',
                    'contents' => $filename,
                ],
            ],
            $query
        );
    }
}
