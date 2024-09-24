<?php

namespace CP\Filter\Tokens;

class LikeExpr extends BinaryExpression
{
    public function apply(array $data)
    {
        $left = $this->left->apply($data);
        $right = $this->right->apply($data);
        $entries = array_values(array_filter(explode('%', $right), function ($entry) {
            return $entry !== '';
        }));
        $symLeftValueCounter = 0;
        foreach ($entries as $entry) {
            if ($this->strContains($entry, '_')) {
                $s = substr($left, $symLeftValueCounter, strlen($left));
                $pattern = '/';
                for ($i = 0; $i < strlen($entry); $i++) {
                    if ($entry[$i] === '_') {
                        $pattern .= "\w";
                    } else {
                        $pattern .= $entry[$i];
                    }
                }
                $pattern .= '/i';

                if (preg_match($pattern, $s, $matches)) {
                    $match = reset($matches);
                    $symLeftValueCounter += strlen($match);
                } else {
                    return false;
                };
            } else {
                $s = substr($left, $symLeftValueCounter, strlen($left));
                $entry_pos = strpos($s, $entry);
                if ($entry_pos === false) {
                    return false;
                }
                $symLeftValueCounter += ($entry_pos + 1);
            }
        }

        return true;
    }

    private function strContains($haystack, $needle): bool
    {
        return !is_bool(strpos($haystack, $needle));
    }
}