<?php

declare(strict_types=1);

namespace On1kel\HyperfFlyDocs\Generator\Services;

final class SpecCleaner
{
    /**
     * @var array<string, bool>
     */
    private const DROP_FALSE_KEYS = [
        'required' => false,
        'deprecated' => false,
        'allowEmptyValue' => false,
        'explode' => false,
        'allowReserved' => false,
        'readOnly' => false,
        'writeOnly' => false,
        'nullable' => false,
        'uniqueItems' => false,
        'exclusiveMinimum' => false,
        'exclusiveMaximum' => false,
    ];

    public function clean(mixed $value): mixed
    {
        if (\is_array($value)) {
            $isAssoc = $this->isAssoc($value);

            if (!$isAssoc) {
                $out = [];
                foreach ($value as $v) {
                    $cv = $this->clean($v);
                    if ($this->isEmptyNode($cv)) {
                        continue;
                    }
                    $out[] = $cv;
                }

                return $out;
            }

            $out = [];
            foreach ($value as $k => $v) {
                $cv = $this->clean($v);

                if ($cv === null) {
                    continue;
                }

                if ($this->isEmptyNode($cv)) {
                    continue;
                }

                if ($cv === false && \array_key_exists($k, self::DROP_FALSE_KEYS)) {
                    continue;
                }

                $out[$k] = $cv;
            }

            foreach (['examples','parameters','required','enum','allOf','anyOf','oneOf','xml','extensions','headers','tags','security'] as $maybeEmpty) {
                if (isset($out[$maybeEmpty]) && \is_array($out[$maybeEmpty]) && $out[$maybeEmpty] === []) {
                    unset($out[$maybeEmpty]);
                }
            }

            if (isset($out['schema']) && \is_array($out['schema'])) {
                if (isset($out['schema']['properties']) && $out['schema']['properties'] === []) {
                    unset($out['schema']['properties']);
                }
                if (isset($out['schema']['required']) && $out['schema']['required'] === []) {
                    unset($out['schema']['required']);
                }
            }

            return $out;
        }

        return $value;
    }

    /**
     * @param  array<int|string, mixed> $arr
     * @return bool
     */
    private function isAssoc(array $arr): bool
    {
        if ($arr === []) {
            return true;
        }

        return \array_keys($arr) !== \range(0, \count($arr) - 1);
    }

    private function isEmptyNode(mixed $v): bool
    {
        if (\is_array($v)) {
            return $v === [];
        }
        if (\is_object($v)) {
            return [] === (array) $v;
        }

        return false;
    }
}
