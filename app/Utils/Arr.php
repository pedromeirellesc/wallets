<?php

declare(strict_types=1);

namespace App\Utils;

class Arr
{
    /**
     * Obtém um valor de um array aninhado usando a notação "ponto".
     *
     * @param array $array O array de onde buscar o valor.
     * @param string|null $key A chave para buscar, usando notação de ponto (ex: 'user.profile.name').
     * @param mixed $default O valor padrão a ser retornado se a chave não for encontrada.
     * @return mixed
     */
    public static function get(array $array, ?string $key, mixed $default = null): mixed
    {
        if ($key === null) {
            return $array;
        }

        if (isset($array[$key])) {
            return $array[$key];
        }

        if (strpos($key, '.') === false) {
            return $array[$key] ?? $default;
        }

        foreach (explode('.', $key) as $segment) {
            if (is_array($array) && isset($array[$segment])) {
                $array = $array[$segment];
            } else {
                return $default;
            }
        }

        return $array;
    }

    public static function only(array $array, array $keys): array
    {
        return array_intersect_key($array, array_flip($keys));
    }

    public static function except(array $array, array $keys): array
    {
        return array_diff_key($array, array_flip($keys));
    }
}