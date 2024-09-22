<?php

namespace CP\Filter\Tokens;

class LikeExpr extends BinaryExpression
{
    public function apply(array $data)
    {
        $fieldName = $this->left->apply($data);
        $findValue = $this->right->apply($data);

        $result = [];
        foreach ($data as $listObject) {
            foreach ($listObject as $key => $value) {
                if ($key === $fieldName) {
                    if ($this->isPercentAtStart($findValue) && $this->isPercentAtEnd($findValue)) {
                        if ($this->strContains($value, trim($findValue, '%'))) {
                            $result[] = $listObject;
                        }
                    } elseif ($this->isPercentAtStart($findValue)) {
                        if ($this->endsWith($value, ltrim($findValue, '%'))) {
                            $result[] = $listObject;
                        }
                    } elseif ($this->isPercentAtEnd($findValue)) {
                        if ($this->startsWith($value, rtrim($findValue, '%'))) {
                            $result[] = $listObject;
                        }
                    }
                }
            }
        }

        return $result;
    }

    private function isPercentAtStart(string $value)
    {
        return strpos($value, '%') === 0;
    }

    private function isPercentAtEnd(string $value)
    {
        return strpos($value, '%') === (strlen($value) - 1);
    }

    private function strContains($haystack, $needle): bool
    {
        return (bool) strpos($haystack, $needle);
    }

    private function endsWith($haystack, $needle): bool
    {
        $length = strlen($needle);

        return !($length > 0) || substr($haystack, -$length) === $needle;
    }

    private function startsWith($haystack, $needle): bool
    {
        return strpos($haystack, $needle) === 0;
    }
}