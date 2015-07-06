<?php

namespace App\Twig;

class MarkdownExtension extends \Twig_Extension
{
    private $parsedown;

    public function __construct(\Parsedown $parsedown)
    {
        $this->parsedown = $parsedown;
    }

    public function getFilters()
    {
        return [
            new \Twig_SimpleFilter('markdown', [$this, 'parse']),
            new \Twig_SimpleFilter('markdown_line', [$this, 'parseLine']),
        ];
    }

    public function parse($content)
    {
        return $this->parsedown->parse($content);
    }

    public function parseLine($line)
    {
        return $this->parsedown->line($line);
    }

    public function getName()
    {
        return 'markdown';
    }
}
