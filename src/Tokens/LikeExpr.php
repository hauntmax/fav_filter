<?php

namespace CP\Filter\Tokens;

class LikeExpr extends BinaryExpression
{
    public function apply(array $data)
    {
        $fieldName = $this->left->getValue();
        $findValue = $this->right->getValue();

        $percentPositionConditionMap = [
            'StartEnd' => "\$isContains = \$this->strContains(\$value, trim(\"$findValue\", '%'));",
            'Start' => "\$isContains = \$this->strEndsWith(\$value, ltrim(\"$findValue\", '%'));",
            'End' => "\$isContains = \$this->strStartsWith(\$value, rtrim(\"$findValue\", '%'));",
        ];

        if ($this->isPercentAtStart($findValue) && $this->isPercentAtEnd($findValue)) {
            $percentPosition = 'StartEnd';
        } elseif ($this->isPercentAtStart($findValue)) {
            $percentPosition = 'Start';
        } elseif ($this->isPercentAtEnd($findValue)) {
            $percentPosition = 'End';
        } else {
            return [];
        }

        return $this->getFiltered($fieldName, $data, $percentPositionConditionMap[$percentPosition]);
    }

    private function getFiltered(string $fieldName, array $data, string $condition): array
    {
        $filtered = [];
        $isContains = false;
        foreach ($data as $listObject) {
            foreach ($listObject as $key => $value) {
                if ($key === $fieldName) {
                    eval($condition);
                    if ($isContains) {
                        $filtered[] = $listObject;
                    }
                }
            }
        }

        return $filtered;
    }

    private function isPercentAtStart(string $value): bool
    {
        return strpos($value, '%') === 0;
    }

    private function isPercentAtEnd(string $value): bool
    {
        return $this->strEndsWith($value, '%');
    }

    private function strContains($haystack, $needle): bool
    {
        return (bool) strpos($haystack, $needle);
    }

    private function strEndsWith($haystack, $needle): bool
    {
        $length = strlen($needle);

        return !($length > 0) || substr($haystack, -$length) === $needle;
    }

    private function strStartsWith($haystack, $needle): bool
    {
        return strpos($haystack, $needle) === 0;
    }
}