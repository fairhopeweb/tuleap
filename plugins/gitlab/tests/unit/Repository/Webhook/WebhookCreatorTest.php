<?php
/**
 * Copyright (c) Enalean, 2021 - Present. All Rights Reserved.
 *
 * This file is a part of Tuleap.
 *
 * Tuleap is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 2 of the License, or
 * (at your option) any later version.
 *
 * Tuleap is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with Tuleap. If not, see <http://www.gnu.org/licenses/>.
 */

declare(strict_types=1);

namespace Tuleap\Gitlab\Repository\Webhook;

use Mockery;
use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use PHPUnit\Framework\TestCase;
use Tuleap\Cryptography\ConcealedString;
use Tuleap\Cryptography\KeyFactory;
use Tuleap\Cryptography\Symmetric\EncryptionKey;
use Tuleap\Gitlab\API\ClientWrapper;
use Tuleap\Gitlab\API\Credentials;
use Tuleap\Gitlab\API\GitlabRequestException;
use Tuleap\Gitlab\Repository\GitlabRepository;
use Tuleap\InstanceBaseURLBuilder;

class WebhookCreatorTest extends TestCase
{
    use MockeryPHPUnitIntegration;

    /**
     * @var WebhookCreator
     */
    private $creator;
    /**
     * @var Mockery\LegacyMockInterface|Mockery\MockInterface|KeyFactory
     */
    private $key_factory;
    /**
     * @var Mockery\LegacyMockInterface|Mockery\MockInterface|WebhookDao
     */
    private $dao;
    /**
     * @var Mockery\LegacyMockInterface|Mockery\MockInterface|ClientWrapper
     */
    private $gitlab_api_client;

    protected function setUp(): void
    {
        $this->key_factory       = Mockery::mock(KeyFactory::class);
        $this->dao               = Mockery::mock(WebhookDao::class);
        $this->gitlab_api_client = Mockery::mock(ClientWrapper::class);

        $instance_base_url = Mockery::mock(InstanceBaseURLBuilder::class, ['build' => 'https://tuleap.example.com']);

        $this->creator = new WebhookCreator(
            $this->key_factory,
            $this->dao,
            $this->gitlab_api_client,
            $instance_base_url
        );
    }

    public function testItGeneratesAWebhookForRepository(): void
    {
        $credentials = new Credentials('https://gitlab.example.com', new ConcealedString('My Secret'));

        $repository = new GitlabRepository(
            1,
            2,
            'winter-is-coming',
            'Need more blankets, we are going to freeze our asses',
            'the_full_url',
            new \DateTimeImmutable(),
        );

        $encryption_key = \Mockery::mock(EncryptionKey::class);
        $encryption_key
            ->shouldReceive('getRawKeyMaterial')
            ->andReturns(
                str_repeat('a', SODIUM_CRYPTO_SECRETBOX_KEYBYTES)
            );
        $this->key_factory
            ->shouldReceive('getEncryptionKey')
            ->andReturn($encryption_key)
            ->once();

        $this->gitlab_api_client
            ->shouldReceive('postUrl')
            ->with(
                $credentials,
                "/projects/2/hooks",
                Mockery::on(function (array $config) {
                    return count(array_keys($config)) === 5
                        && $config['url'] === 'https://tuleap.example.com/plugins/gitlab/repository/webhook'
                        && is_string($config['token'])
                        && $config['push_events'] === true
                        && $config['merge_requests_events'] === true
                        && $config['enable_ssl_verification'] === true;
                })
            )
            ->once()
            ->andReturn(
                [
                    'id' => 7,
                ]
            );

        $this->dao
            ->shouldReceive('storeWebhook')
            ->with(1, 7, Mockery::type('string'))
            ->once();

        $this->creator->addWebhookInGitlabProject($credentials, $repository);
    }

    public function testItDoesNotSaveAnythingIfGitlabDidNotCreateTheWebhook(): void
    {
        $credentials = new Credentials('https://gitlab.example.com', new ConcealedString('My Secret'));

        $repository = new GitlabRepository(
            1,
            2,
            'winter-is-coming',
            'Need more blankets, we are going to freeze our asses',
            'the_full_url',
            new \DateTimeImmutable(),
        );

        $this->gitlab_api_client
            ->shouldReceive('postUrl')
            ->with(
                $credentials,
                "/projects/2/hooks",
                Mockery::on(function (array $config) {
                    return count(array_keys($config)) === 5
                        && $config['url'] === 'https://tuleap.example.com/plugins/gitlab/repository/webhook'
                        && is_string($config['token'])
                        && $config['push_events'] === true
                        && $config['merge_requests_events'] === true
                        && $config['enable_ssl_verification'] === true;
                })
            )
            ->once()
            ->andThrow(Mockery::mock(GitlabRequestException::class));

        $this->dao
            ->shouldReceive('storeWebhook')
            ->never();

        $this->expectException(GitlabRequestException::class);

        $this->creator->addWebhookInGitlabProject($credentials, $repository);
    }

    public function testItThrowsExceptionIfWebhookCreationReturnsUnexpectedPayload(): void
    {
        $credentials = new Credentials('https://gitlab.example.com', new ConcealedString('My Secret'));

        $repository = new GitlabRepository(
            1,
            2,
            'winter-is-coming',
            'Need more blankets, we are going to freeze our asses',
            'the_full_url',
            new \DateTimeImmutable(),
        );

        $this->gitlab_api_client
            ->shouldReceive('postUrl')
            ->with(
                $credentials,
                "/projects/2/hooks",
                Mockery::on(function (array $config) {
                    return count(array_keys($config)) === 5
                        && $config['url'] === 'https://tuleap.example.com/plugins/gitlab/repository/webhook'
                        && is_string($config['token'])
                        && $config['push_events'] === true
                        && $config['merge_requests_events'] === true
                        && $config['enable_ssl_verification'] === true;
                })
            )
            ->once()
            ->andReturn([]);

        $this->dao
            ->shouldReceive('storeWebhook')
            ->never();

        $this->expectException(WebhookCreationException::class);

        $this->creator->addWebhookInGitlabProject($credentials, $repository);
    }
}