<?php

declare(strict_types=1);

if (!function_exists('array_is_list_safe')) {
    /**
     * Проверка, что массив является списком с нулевой индексацией.
     *
     * @param  array<mixed> $array
     * @return bool
     */
    function array_is_list_safe(array $array): bool
    {
        if (function_exists('array_is_list')) {
            return array_is_list($array);
        }

        $i = 0;
        foreach ($array as $k => $_) {
            if ($k !== $i) {
                return false;
            }
            $i++;
        }

        return true;
    }
}

if (!function_exists('base_path')) {
    /**
     * Возвращает абсолютный путь к базовой директории приложения.
     *
     * @param  string $path
     * @return string
     */
    function base_path(string $path = ''): string
    {
        $cwd = getcwd();

        /** @var string $base */
        $base = defined('BASE_PATH') ? BASE_PATH : $cwd;

        if (!$base) {
            throw new \RuntimeException('BASE_PATH не определен или getcwd() вернул false');
        }

        return $path === '' ? $base : $base . '/' . ltrim($path, '/');
    }
}
