<?php

declare(strict_types=1);

namespace FGTCLB\OAuth2Server\Tests\Functional;

use PHPUnit\Framework\Attributes\Test;
use Psr\Log\LogLevel;
use TYPO3\CMS\Core\Core\Environment;
use TYPO3\CMS\Core\Http\Uri;
use TYPO3\CMS\Core\Log\Writer\FileWriter;
use TYPO3\TestingFramework\Core\Functional\Framework\Frontend\InternalRequest;
use TYPO3\TestingFramework\Core\Functional\Framework\Frontend\InternalRequestContext;

final class OAuth2LoggingTest extends AbstractOauth2ServerTestCase
{
    private const LOG_FILE_INFIX = 'oauth2_server_test';

    protected function setUp(): void
    {
        $this->configurationToUseInTestInstance = array_replace_recursive(
            $this->configurationToUseInTestInstance,
            [
                'LOG' => [
                    'FGTCLB' => [
                        'OAuth2Server' => [
                            'writerConfiguration' => [
                                LogLevel::DEBUG => [
                                    FileWriter::class => [
                                        'logFileInfix' => self::LOG_FILE_INFIX,
                                    ],
                                ],
                            ],
                        ],
                    ],
                ],
            ]
        );
        parent::setUp();

        // FileWriter keeps its handles open for the whole process, so the
        // files are emptied instead of deleted to keep later writes visible.
        foreach ($this->findLogFiles() as $logFile) {
            file_put_contents($logFile, '');
        }
    }

    #[Test]
    public function invalidClientSecretIsLoggedByClientRepository(): void
    {
        $uri = (string)(new Uri(self::BASE_URL))
            ->withPath('/oauth/token');

        $this->executeFrontendSubRequest(
            (new InternalRequest($uri))
                ->withParsedBody([
                    'grant_type' => 'authorization_code',
                    'client_id' => 'acme_client',
                    'client_secret' => 'wrong_secret',
                ])
        );

        $logContent = $this->readLogContent();
        $this->assertStringContainsString('[DEBUG]', $logContent);
        $this->assertStringContainsString('FGTCLB.OAuth2Server.Domain.Repository.ClientRepository', $logContent);
        $this->assertStringContainsString('is invalid for client acme_client', $logContent);
    }

    #[Test]
    public function failedAuthorizationRequestIsLoggedByAuthorizationMiddleware(): void
    {
        $uri = (string)(new Uri(self::BASE_URL))
            ->withPath('/oauth/authorize')
            ->withQuery('?response_type=code&client_id=wrong');

        $this->executeFrontendSubRequest(
            new InternalRequest($uri),
            (new InternalRequestContext())->withFrontendUserId(self::FE_USER_ID)
        );

        $logContent = $this->readLogContent();
        $this->assertStringContainsString('[WARNING]', $logContent);
        $this->assertStringContainsString('FGTCLB.OAuth2Server.Middleware.OAuth2Authorization', $logContent);
        $this->assertStringContainsString('Validating authorization request failed', $logContent);
    }

    /**
     * @return string[]
     */
    private function findLogFiles(): array
    {
        return glob(Environment::getVarPath() . '/log/typo3_' . self::LOG_FILE_INFIX . '_*.log') ?: [];
    }

    private function readLogContent(): string
    {
        $logContent = '';
        foreach ($this->findLogFiles() as $logFile) {
            $logContent .= (string)file_get_contents($logFile);
        }
        return $logContent;
    }
}
