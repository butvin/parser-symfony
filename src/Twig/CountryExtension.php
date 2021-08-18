<?php

declare(strict_types=1);

namespace App\Twig;

use App\Helper\IntlHelper;
use Twig\Extension\AbstractExtension;
use Twig\TwigFunction;

class CountryExtension extends AbstractExtension
{
    final public function getFunctions(): array
    {
        return [
            new TwigFunction(
                'get_country_flag',
                [$this, 'getCountryFlag']
            ),
            new TwigFunction(
                'get_country_name',
                [$this, 'getCountryName']
            ),
        ];
    }

    final public function getCountryFlag(string $code): string
    {
        $code = mb_strtoupper(trim($code));

        if (array_key_exists($code, IntlHelper::FLAGS)) {
            return IntlHelper::FLAGS[$code];
        }

        return '🏳';
    }

    final public function getCountryName(string $code): string
    {
        if (array_key_exists($code, IntlHelper::COUNTRIES) || array_key_exists(mb_strtoupper($code), IntlHelper::COUNTRIES) ) {
            return IntlHelper::COUNTRIES[mb_strtoupper($code)];
        }

        return 'undefined country code: '.$code;
    }
}