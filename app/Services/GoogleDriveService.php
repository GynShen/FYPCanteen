<?php

namespace App\Services;
use Google\Client as GoogleClient;
use Google\Service\Drive as GoogleDrive;
use Google\Service\Drive\Permission;

class GoogleDriveService
{
    protected $client;
    protected $service;

    /**
     * Create a new class instance.
     */
    public function __construct()
    {
        $this->client = new GoogleClient();

        $this->client->setClientId(config('filesystems.disks.google.clientId'));
        $this->client->setClientSecret(config('filesystems.disks.google.clientSecret'));
        $this->client->refreshToken(config('filesystems.disks.google.refreshToken'));

        $this->service = new GoogleDrive($this->client);
    }

    /**
     * Function: getAccessToken
     */
    public function getAccessToken(): string
{
    $token = $this->client->fetchAccessTokenWithRefreshToken(
        config('filesystems.disks.google.refreshToken')
    );

    if (isset($token['error'])) {
        throw new \RuntimeException(
            $token['error_description'] ?? $token['error']
        );
    }

    if (empty($token['access_token'])) {
        throw new \RuntimeException('Google did not return an access token.');
    }

    return $token['access_token'];
}

    /**
     * Function: makeFileToPublic
     */
    public function makeFileToPublic($driveFileId)
    {
        $permission = new Permission([
            'type' => 'anyone',
            'role' => 'reader',
        ]);

        $this->service->permissions->create($driveFileId, $permission);
    }
}