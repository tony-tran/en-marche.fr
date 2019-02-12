<?php

namespace Tests\AppBundle\Command;

use AppBundle\Mailer\Message\IdeaContributionsMessage;
use Liip\FunctionalTestBundle\Test\WebTestCase;
use Tests\AppBundle\Controller\ControllerTestTrait;

/**
 * @group command
 */
class NotifyIdeaAuthorAboutContributionsCommandTest extends WebTestCase
{
    use ControllerTestTrait;

    public function testSendMailAboutContributions(): void
    {
        $this->runCommand('idea-workshop:notification:contributions');

        $this->assertCountMails(1, IdeaContributionsMessage::class, 'jacques.picard@en-marche.fr');
    }

    public function setUp()
    {
        $this->init();

        parent::setUp();
    }

    public function tearDown()
    {
        $this->kill();

        parent::tearDown();
    }
}
