<?php

namespace TheDeceased\PHParser;

class Parser
{
    private $commentTokens = [T_COMMENT, T_DOC_COMMENT];

    public function parse($content)
    {
        $tokens = array_filter(token_get_all($content), function ($token) {
            return !is_array($token) || !in_array($token[0], $this->commentTokens);
        });
        $tokens = array_map(function ($token) {
            return is_array($token) ? $token[1] : $token;
        }, $tokens);

        return implode('', $tokens);
    }
}
