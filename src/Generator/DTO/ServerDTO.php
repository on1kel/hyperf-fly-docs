<?php

declare(strict_types=1);

namespace On1kel\HyperfFlyDocs\Generator\DTO;

use Khazhinov\PhpSupport\DTO\DataTransferObject;

class ServerDTO extends DataTransferObject
{
    public string $url;
    public string $description = '';
}
