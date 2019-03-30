<?php

declare(strict_types=1);
namespace App\Utils\Gsuite;

/**
 * The Gsuite alias management class
 * @author Wouter van Harten <wouter@woutervanharten.nl>
 * @see \App\Utility\GsuiteAlias GsuiteAlias
 */
class Gsuite implements GsuiteInterface {
    /** The auth to log in to Gsuite
     * @var string
     */
    private $service;

    /**
     * The cached list of aliases
     * Null if not cached yet
     *
     * @var array|null
     */
    private $aliases = null;

    /**
     * Authenticates to the Gsuite server
     *
     * @param string $credentials string containing the absolute path to the credentials
     * @param string $token Json string containing the login token
     */
    public function __construct(string $credentials, string $token) {
        $token = json_decode(file_get_contents(__DIR__ . "/../../../config/" . $token), true);
        $client = new \Google_Client();
        $client->setApplicationName("leaseApplication");
        $client->setAuthConfig(__DIR__ . "/../../../config/" . $credentials);
        $client->setScopes(\Google_Service_Directory::ADMIN_DIRECTORY_GROUP);
        $client->setRedirectUri("https://abacus.utwente.nl");
        $client->setAccessType('offline');
        $client->setApprovalPrompt('force');
        $client->setAccessToken($token);
        $client->fetchAccessTokenWithRefreshToken($client->getRefreshToken());
        $this->service = new \Google_Service_Directory($client);
    }
}
