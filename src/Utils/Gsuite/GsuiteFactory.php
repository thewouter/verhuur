<?php

declare(strict_types=1);
namespace App\Utils\Gsuite;

/**
 * A class for generating a singleton instance of the Gsuite interface
 *
 * @author Wouter van Harten <wouter@woutervanharten.nl>
 */
class GsuiteFactory {
    /**
     * The credentials
     * @var string
     */
    private $credentials;
    /**
     * The token
     * @var string
     */
    private $token;

    /**
     * Stores the credentials and token for authorization
     *
     * @param string $credentials The credentials file location
     * @param string $token The token file location
     */
    public function __construct(?string $credentials, ?string $token) {
        $this->credentials = $credentials;
        $this->token = $token;
    }

    /**
     * Creates an instance of a GsuiteInterface. Creates a dummy if
     * no credentials or token is present.
     * @return GsuiteInterface
     */
    public function getGsuite(): GsuiteInterface {
        if (!$this->credentials || !$this->token) {
            return new DummyGsuite();
        } else {
            return new Gsuite($this->credentials, $this->token);
        }
    }
}
