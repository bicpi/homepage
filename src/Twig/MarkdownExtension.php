<?php

namespace App\Twig;

use Michelf\MarkdownExtra;

class MarkdownExtension extends \Twig_Extension
{
    private $parsedown;

    public function __construct(\Parsedown $parsedown)
    {
        $this->parsedown = $parsedown;
    }

    public function getFilters()
    {
        return array(
            new \Twig_SimpleFilter('markdown', array($this, 'parse')),
            new \Twig_SimpleFilter('markdown_line', array($this, 'parseLine')),
        );
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
