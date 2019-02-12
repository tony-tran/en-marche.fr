<?php

use Behat\Gherkin\Node\PyStringNode;
use Behat\Mink\Exception\ExpectationException;
use Behatch\Context\JsonContext as BehatchJsonContext;
use Coduo\PHPMatcher\PHPMatcher;

class JsonContext extends BehatchJsonContext
{
    public function theJsonShouldBeEqualTo(PyStringNode $content)
    {
        $this->assertJson($content->getRaw(), $this->getJson());
    }

    public function assertJson(string $expected, string $actual): void
    {
        $expected = preg_replace('/\s(?=([^"]*"[^"]*")*[^"]*$)/', '', $expected);

        if (!PHPMatcher::match($actual, $expected, $error)) {
            throw new ExpectationException($error, $this->getSession());
        }
    }

    public function theJsonNodeShouldContain($node, $text)
    {
        $actual = $this->inspector->evaluate($this->getJson(), $node);

        $this->assertContains(
            $text,
            \is_bool($actual) ? json_encode($actual) : (string) $actual
        );
    }

    protected function assertContains($expected, $actual, $message = null)
    {
        if (!PHPMatcher::match($actual, $expected, $error)) {
            throw new ExpectationException($error, $this->getSession());
        }
    }
}
