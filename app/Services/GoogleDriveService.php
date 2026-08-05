<?php

namespace App\Services;

use Google\Client as GoogleClient;
use Google\Service\Drive as GoogleDrive;
use Google\Service\Drive\DriveFile;
use Google\Service\Drive\Permission;
use Illuminate\Http\UploadedFile;
use RuntimeException;

class GoogleDriveService
{
    private GoogleClient $client;

    private GoogleDrive $drive;

    public function __construct()
    {
        $this->client = new GoogleClient;
        $this->client->setClientId(config('filesystems.disks.google.clientId'));
        $this->client->setClientSecret(config('filesystems.disks.google.clientSecret'));

        $this->drive = new GoogleDrive($this->client);
    }

    /**
     * @return array{id: string, url: string}
     */
    public function uploadPublicImage(UploadedFile $image): array
    {
        $this->authenticate();

        $metadata = new DriveFile([
            'name' => sprintf('%s-%s.%s', now()->format('YmdHis'), str()->uuid(), $image->extension()),
            'parents' => $this->parentFolders(),
        ]);

        $file = $this->drive->files->create($metadata, [
            'data' => file_get_contents($image->getRealPath()),
            'mimeType' => $image->getMimeType(),
            'uploadType' => 'multipart',
            'fields' => 'id',
        ]);

        if (! $file->id) {
            throw new RuntimeException('Google Drive did not return an ID for the uploaded image.');
        }

        $this->drive->permissions->create($file->id, new Permission([
            'type' => 'anyone',
            'role' => 'reader',
        ]));

        return [
            'id' => $file->id,
            'url' => "https://drive.google.com/thumbnail?id={$file->id}&sz=w1200",
        ];
    }

    public function delete(?string $fileId): void
    {
        if (! $fileId) {
            return;
        }

        $this->authenticate();
        $this->drive->files->delete($fileId);
    }

    private function authenticate(): void
    {
        $refreshToken = config('filesystems.disks.google.refreshToken');

        if (! $refreshToken) {
            throw new RuntimeException('GOOGLE_DRIVE_REFRESH_TOKEN is not configured.');
        }

        $token = $this->client->fetchAccessTokenWithRefreshToken($refreshToken);

        if (isset($token['error'])) {
            throw new RuntimeException($token['error_description'] ?? $token['error']);
        }

        if (empty($token['access_token'])) {
            throw new RuntimeException('Google did not return an access token.');
        }
    }

    /**
     * @return list<string>
     */
    private function parentFolders(): array
    {
        $folderId = config('filesystems.disks.google.folder');

        return $folderId ? [$folderId] : [];
    }
}
