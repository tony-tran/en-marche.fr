<?php

namespace Tests\AppBundle\Donation;

use AppBundle\DataFixtures\ORM\LoadDonationData;
use AppBundle\Repository\DonationRepository;
use AppBundle\Repository\TransactionRepository;
use Symfony\Component\HttpFoundation\Request;
use Tests\AppBundle\Controller\ControllerTestTrait;
use Tests\AppBundle\SqliteWebTestCase;

/**
 * @group functional
 */
class TransactionSubscriberTest extends SqliteWebTestCase
{
    use ControllerTestTrait;

    /**
     * @var DonationRepository
     */
    private $donationRepository;
    /**
     * @var TransactionRepository
     */
    private $transactionRepository;

    public function payloadProvider(): iterable
    {
        yield 'transaction1' => ['424241'];
        yield 'transaction2' => ['424242'];
        yield 'transaction3' => ['424243'];
    }

    /**
     * @dataProvider payloadProvider
     */
    public function testOnPayboxIpnResponse(string $transactionId): void
    {
        static::assertNull($this->transactionRepository->findByPayboxTransactionId($transactionId));
        $params = $this->createPayload($transactionId);

        $this->client->request(Request::METHOD_POST, '/don/payment-ipn/1528114726', $params);

        static::assertSame('OK', $this->client->getResponse()->getContent());

        static::assertNotNull($this->transactionRepository->findByPayboxTransactionId($transactionId));
    }

    /**
     * @depends testOnPayboxIpnResponse
     */
    public function testAllTransactionCreated(): void
    {
        $transactions = $this->transactionRepository->findAllSuccessfulTransactionByEmail('jacques.picard@en-marche.fr');

        // b/c there are initial transactions when donationFixtures are loaded then 1+3 = 4
        static::assertCount(4, $transactions);
    }

    private function createSignature(array $params): string
    {
        $queryParams = http_build_query($params);
        $privateKey = openssl_pkey_get_private($this->container->getParameter('env(SSL_PRIVATE_KEY)'));
        openssl_sign($queryParams, $signature, $privateKey, 'sha1WithRSAEncryption');

        return base64_encode($signature);
    }

    private function createPayload(string $transactionId): array
    {
        $donation = $this->donationRepository->findAllSubscribedDonationByEmail('jacques.picard@en-marche.fr')[0];

        $params = [
            'id' => $donation->getUuid()->toString().'_test',
            'authorization' => 'XXXXXX',
            'result' => '00000',
            'transaction' => $transactionId,
            'amount' => '50000',
            'date' => '01062018',
            'time' => '14:52:22',
            'card_type' => 'MasterCard',
            'card_end' => '1812',
            'card_print' => '5B434C778490889697170E225029F56AFF19CA47',
            'subscription' => '1234',
        ];

        $params['Sign'] = $this->createSignature($params);

        return $params;
    }

    protected function setUp()
    {
        parent::setUp();

        $this->init([
            LoadDonationData::class,
        ]);

        $this->donationRepository = $this->getDonationRepository();
        $this->transactionRepository = $this->getTransactionRepository();
    }

    protected function tearDown()
    {
        $this->kill();

        $this->donationRepository = null;
        $this->transactionRepository = null;

        parent::tearDown();
    }
}
