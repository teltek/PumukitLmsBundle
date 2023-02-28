<?php

declare(strict_types=1);

namespace Pumukit\LmsBundle\Services;

class ConfigurationService
{
    private $allowCreateUsersFromRequest;
    private $password;
    private $role;
    private $nakedBackofficeDomain;
    private $nakedBackofficeBackground;
    private $nakedBackofficeColor;
    private $nakedCustomCssUrl;

    private $defaultSeriesTitle;
    private $domainsPatterns;

    public function __construct(
        bool $allowCreateUsersFromRequest,
        string $password,
        string $role,
        ?string $nakedBackofficeDomain,
        string $nakedBackofficeBackground,
        string $nakedBackofficeColor,
        ?string $nakedCustomCssUrl,
        string $defaultSeriesTitle,
        array $domainsPatterns
    ) {
        $this->allowCreateUsersFromRequest = $allowCreateUsersFromRequest;
        $this->password = $password;
        $this->role = $role;
        $this->nakedBackofficeDomain = $nakedBackofficeDomain;
        $this->nakedBackofficeBackground = $nakedBackofficeBackground;
        $this->nakedBackofficeColor = $nakedBackofficeColor;
        $this->nakedCustomCssUrl = $nakedCustomCssUrl;
        $this->defaultSeriesTitle = $defaultSeriesTitle;
        $this->domainsPatterns = $domainsPatterns;
    }

    public function isAllowCreateUsersFromRequest(): bool
    {
        return $this->allowCreateUsersFromRequest;
    }

    public function getPassword(): string
    {
        return $this->password;
    }

    public function getRole(): string
    {
        return $this->role;
    }

    public function getNakedBackofficeDomain(): string
    {
        return $this->nakedBackofficeDomain;
    }

    public function getNakedBackofficeBackground(): string
    {
        return $this->nakedBackofficeBackground;
    }

    public function getNakedBackofficeColor(): string
    {
        return $this->nakedBackofficeColor;
    }

    public function getNakedCustomCssUrl(): string
    {
        return $this->nakedCustomCssUrl;
    }

    public function getDefaultSeriesTitle(): string
    {
        return $this->defaultSeriesTitle;
    }

    public function getDomainsPatterns(): array
    {
        return $this->domainsPatterns;
    }

    public function isAllowedDomain(string $domain): bool
    {
        $currentDomain = parse_url($domain, PHP_URL_HOST);

        return $this->validateRegexDomain($this->domainsPatterns, $currentDomain);
    }

    public function generateHash(string $email): string
    {
        $date = date('d/m/Y');

        return md5($email.$this->getPassword().$date.$this->getNakedBackofficeDomain());
    }

    public function isValidHash(string $hash, string $email): bool
    {
        return $hash === $this->generateHash($email);
    }

    private function validateRegexDomain(array $domainsPatterns, string $currentDomain): bool
    {
        foreach ($domainsPatterns as $pattern) {
            if ('*' === $pattern) {
                return true;
            }

            if (1 === preg_match('/'.$pattern.'/i', $currentDomain)) {
                return true;
            }
        }

        return false;
    }
}
