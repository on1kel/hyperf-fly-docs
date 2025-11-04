<?php

declare(strict_types=1);

namespace On1kel\HyperfFlyDocs\Generator\DTO;

use Khazhinov\PhpSupport\DTO\DataTransferObject;

class InfoDTO extends DataTransferObject
{
    public string $title;
    public string $version;
    public string $description;
}
