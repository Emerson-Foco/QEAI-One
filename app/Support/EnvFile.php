<?php

namespace App\Support;

class EnvFile
{
    /** Atualiza (ou adiciona) chaves no arquivo .env preservando o restante. */
    public static function set(string $path, array $values): void
    {
        $content = is_file($path) ? (string) file_get_contents($path) : '';
        foreach ($values as $key => $value) {
            $value = (string) $value;
            $line = $key . '=' . (preg_match('/\s|#|"/', $value) ? '"' . addcslashes($value, '"') . '"' : $value);
            $pattern = '/^' . preg_quote($key, '/') . '=.*$/m';
            if (preg_match($pattern, $content)) {
                $content = (string) preg_replace($pattern, $line, $content);
            } else {
                $content = rtrim($content) . "\n" . $line . "\n";
            }
        }
        file_put_contents($path, $content);
    }
}
