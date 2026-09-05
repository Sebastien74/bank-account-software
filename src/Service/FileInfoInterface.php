<?php

declare(strict_types=1);

namespace App\Service;

/**
 * FileInfoInterface.
 *
 * @author Sébastien FOURNIER <fournier.sebastien@outlook.com>
 */
interface FileInfoInterface
{
    public function file(?string $filename = null, ?string $dirname = null, array $options = []);

    public function dirname(): ?string;

    public function extension(): ?string;

    public function filename(): ?string;

    public function path(): ?string;

    public function attributes(): ?string;

    public function mime(): ?string;

    public function size(): ?int;

    public function width(): ?int;

    public function height(): ?int;

    public function screensSizes(array $options = []): object;

    public function runtimeConfigs(): object;

    public function files(): object;

    public function screensFiles(): object;

    public function bits(): ?int;

    public function generateThumb(): bool;

    public function isImage(): bool;

    public function isAllowedRuntime(): bool;

    public function isPlaceHolder(): bool;

    public function formatBytes(?int $bytes = null, int $precision = 2): ?string;
}
