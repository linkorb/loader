<?php

namespace Loader;

use Symfony\Component\ExpressionLanguage\ExpressionLanguage;

class Interpolator
{
    protected $expressionLanguage;
    protected $flags;

    public function __construct(int $flags, $expressionLanguage)
    {
        $this->flags = $flags;
        $this->expressionLanguage = $expressionLanguage;
    }

    public function getExpressionLanguage(): ExpressionLanguage
    {
        return $this->expressionLanguage;
    }

    public function register(string $name, $callback)
    {
        $this->expressionLanguage->register(
            $name,
            function ($items) {},
            $callback
        );
    }
    public static function createDefault(int $flags)
    {
        $expressionLanguage = new ExpressionLanguage();

        $obj = new self($flags, $expressionLanguage);

        $obj->register(
            'dict',
            function ($arguments, $items) {
                $res = [];
                foreach ($items as $key => $value) {
                    $res[] = [
                        'key' => $key,
                        'value' => $value,
                    ];
                }
                return $res;
            }
        );

        $obj->register(
            'strtolower',
            function ($arguments, $str) {
                return strtolower($str);
            }
        );
        $obj->register(
            'strtoupper',
            function ($arguments, $str) {
                return strtoupper($str);
            }
        );
        $obj->register(
            'ucfirst',
            function ($arguments, $str) {
                return ucfirst($str);
            }
        );
        $obj->register(
            'array_merge_recursive',
            function ($arguments, $a, $b) {
                if (!$a) $a = [];
                if (!$b) $b = [];
                return array_merge_recursive($a, $b);
            }
        );

        return $obj;
    }

    public function interpolate(string $str, array $variables) {
        preg_match_all('/\{\{(.*?)\}\}/i', $str, $matches, PREG_PATTERN_ORDER);
        for ($i = 0; $i < count($matches[1]); $i++) {
            $expression = trim($matches[1][$i]);

            // turn sub-keys into objects for dot-notation access in expressions
            $variables2 = [];
            foreach ($variables as $k=>$v) {
                $variables2[$k] = json_decode(json_encode($v));
            }
            // evaluate
            $res = $this->expressionLanguage->evaluate($expression, $variables2);
            if ($matches[0][$i] == $str) {
                // if the entire value is an expression, replace the output entirely
                $str = $res;
            } else {
                // only replace the templated part
                $str = str_replace($matches[0][$i], $res, $str);
            }
        }
        return $str;
    }
}
