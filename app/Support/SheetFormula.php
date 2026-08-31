<?php

namespace App\Support;

use InvalidArgumentException;

/**
 * Safe arithmetic for sheet cells. Only numbers and + - * / ( ) are allowed.
 */
class SheetFormula
{
    public static function looksLikeExpression(string $value): bool
    {
        $value = trim(str_replace(',', '', $value));

        if ($value === '') {
            return false;
        }

        if (str_starts_with($value, '=')) {
            return true;
        }

        if (preg_match('/^[+\-*\/(]/', $value)) {
            return true;
        }

        return (bool) preg_match('/[+\-*\/]/', $value);
    }

    public static function normalize(string $value): string
    {
        $value = trim($value);

        if ($value === '') {
            return '';
        }

        if (str_starts_with($value, '=')) {
            return $value;
        }

        if (static::looksLikeExpression($value)) {
            return '='.ltrim($value, '=');
        }

        return $value;
    }

    public static function evaluate(string $expression): float
    {
        $src = trim(str_replace(',', '', $expression));

        if (str_starts_with($src, '=')) {
            $src = substr($src, 1);
        }

        $src = trim($src);

        if ($src === '') {
            return 0.0;
        }

        if (! static::looksLikeExpression($expression) && ! preg_match('/[+\-*\/()]/', $src)) {
            if (! is_numeric($src)) {
                throw new InvalidArgumentException('Not a number.');
            }

            return round((float) $src, 2);
        }

        $parser = new self($src);
        $value  = $parser->parseExpression();

        if ($parser->position !== strlen($parser->source)) {
            throw new InvalidArgumentException('Cannot read "'.substr($parser->source, $parser->position).'".');
        }

        return round($value, 2);
    }

    private string $source;

    private int $position = 0;

    private function __construct(string $source)
    {
        $this->source = $source;
    }

    private function parseExpression(): float
    {
        $value = $this->parseTerm();

        $this->skipWhitespace();

        while ($this->position < strlen($this->source)
            && ($this->source[$this->position] === '+' || $this->source[$this->position] === '-')) {
            $operator = $this->source[$this->position++];
            $rhs      = $this->parseTerm();
            $value    = $operator === '+' ? $value + $rhs : $value - $rhs;
            $this->skipWhitespace();
        }

        return $value;
    }

    private function parseTerm(): float
    {
        $value = $this->parseFactor();

        $this->skipWhitespace();

        while ($this->position < strlen($this->source)
            && ($this->source[$this->position] === '*' || $this->source[$this->position] === '/')) {
            $operator = $this->source[$this->position++];
            $rhs      = $this->parseFactor();

            if ($operator === '/' && $rhs == 0.0) {
                throw new InvalidArgumentException('Divide by zero.');
            }

            $value = $operator === '*' ? $value * $rhs : $value / $rhs;
            $this->skipWhitespace();
        }

        return $value;
    }

    private function parseFactor(): float
    {
        $this->skipWhitespace();

        if ($this->position < strlen($this->source) && $this->source[$this->position] === '+') {
            $this->position++;

            return $this->parseFactor();
        }

        if ($this->position < strlen($this->source) && $this->source[$this->position] === '-') {
            $this->position++;

            return -$this->parseFactor();
        }

        if ($this->position < strlen($this->source) && $this->source[$this->position] === '(') {
            $this->position++;
            $value = $this->parseExpression();
            $this->skipWhitespace();

            if ($this->position >= strlen($this->source) || $this->source[$this->position] !== ')') {
                throw new InvalidArgumentException('Missing ).');
            }

            $this->position++;

            return $value;
        }

        if (! preg_match('/^\d*\.?\d+/', substr($this->source, $this->position), $match)) {
            throw new InvalidArgumentException('Cannot read "'.substr($this->source, $this->position).'".');
        }

        $this->position += strlen($match[0]);

        return (float) $match[0];
    }

    private function skipWhitespace(): void
    {
        while ($this->position < strlen($this->source) && ctype_space($this->source[$this->position])) {
            $this->position++;
        }
    }
}
