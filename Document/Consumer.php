<?php

declare(strict_types=1);

namespace Pumukit\LmsBundle\Document;

use MongoDB\BSON\ObjectId;
use Doctrine\ODM\MongoDB\Mapping\Annotations as MongoDB;

/**
 * @MongoDB\Document
 */
class Consumer
{
    /**
     * @MongoDB\Id
     */
    private $id;

    /**
     * @MongoDB\Field(type="string")
     */
    private $consumerKey;

    /**
     * @MongoDB\Field(type="string")
     */
    private $sharedSecret;

    private function __construct(string $consumerKey, string $sharedSecret)
    {
        $this->id = new ObjectId();
        $this->consumerKey = $consumerKey;
        $this->sharedSecret = $sharedSecret;
    }

    public static function create(string $consumerKey, string $sharedSecret): Consumer
    {
        return new self($consumerKey, $sharedSecret);
    }

    public function id(): ?ObjectId
    {
        return $this->id;
    }

    public function consumerKey(): ?string
    {
        return $this->consumerKey;
    }

    public function sharedSecret(): ?string
    {
        return $this->sharedSecret;
    }
}
