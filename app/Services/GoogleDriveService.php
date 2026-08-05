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
    public function getAccessToken()
    {
        $token = $this->client->getAccessToken();

        if ($this->client->isAccessTokenExpired()) {
            $token = $this->client->fetchAccessTokenWithRefreshToken(config('filesystems.disks.google.refreshToken'));
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