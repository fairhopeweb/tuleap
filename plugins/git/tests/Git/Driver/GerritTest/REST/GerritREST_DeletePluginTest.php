<?php
/**
 * Copyright (c) Enalean, 2014 - Present. All Rights Reserved.
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

require_once '/usr/share/php/Guzzle/autoload.php';
require_once __DIR__.'/../../../../bootstrap.php';

class Git_DriverREST_Gerrit_DeletePluginTest extends TuleapTestCase
{
    protected $logger;
    protected $gerrit_server_host = 'http://gerrit.example.com';
    /** @var Project */
    protected $project;
    protected $guzzle_request;
    protected $project_name = 'fire/fox';
    /** @var GitRepository */
    protected $repository;
    protected $gerrit_server_port = 8080;
    protected $temporary_file_for_body = "a php resource to a file";
    /** @var Git_Driver_GerritREST */
    protected $driver;
    protected $gerrit_project_name = 'fire/fox/jean-claude/dusse';
    protected $namespace = 'jean-claude';
    protected $gerrit_server_user = 'admin-tuleap.example.com';
    /** @var Git_RemoteServer_GerritServer */
    protected $gerrit_server;
    protected $gerrit_server_pass = 'correct horse battery staple';
    protected $repository_name = 'dusse';
    protected $guzzle_client;
    private $response_with_plugin;
    private $response_without_plugin;

    public function setUp()
    {
        parent::setUp();

        $this->gerrit_server = mock('Git_RemoteServer_GerritServer');
        $this->logger        = mock('BackendLogger');

        stub($this->gerrit_server)->getHost()->returns($this->gerrit_server_host);
        stub($this->gerrit_server)->getHTTPPassword()->returns($this->gerrit_server_pass);
        stub($this->gerrit_server)->getLogin()->returns($this->gerrit_server_user);
        stub($this->gerrit_server)->getHTTPPort()->returns($this->gerrit_server_port);
        stub($this->gerrit_server)->getBaseUrl()->returns($this->gerrit_server_host . ':' . $this->gerrit_server_port);

        $this->project    = stub('Project')->getUnixName()->returns($this->project_name);
        $this->repository = aGitRepository()
            ->withProject($this->project)
            ->withNamespace($this->namespace)
            ->withName($this->repository_name)
            ->build();

        $this->guzzle_client  = mock('Guzzle\Http\Client');
        $this->guzzle_request = mock('Guzzle\Http\Message\EntityEnclosingRequest');

        $this->driver = new Git_Driver_GerritREST($this->guzzle_client, $this->logger, 'Digest');

        $this->response_with_plugin = <<<EOS
)]}'
{
  "deleteproject": {
    "kind": "gerritcodereview#plugin",
    "id": "deleteproject",
    "version": "v2.8.2"
  },
  "replication": {
    "kind": "gerritcodereview#plugin",
    "id": "replication",
    "version": "v2.8.1"
  }
}
EOS;
        $this->response_without_plugin = <<<EOS
)]}'
{
  "replication": {
    "kind": "gerritcodereview#plugin",
    "id": "replication",
    "version": "v2.8.1"
  }
}
EOS;
    }
    public function itReturnsFalseIfPluginIsNotInstalled()
    {
        $response = stub('Guzzle\Http\Message\Response')->getBody(true)->returns($this->response_without_plugin);
        stub($this->guzzle_request)->send()->returns($response);
        stub($this->guzzle_client)->get()->returns($this->guzzle_request);

        $enabled = $this->driver->isDeletePluginEnabled($this->gerrit_server);

        $this->assertFalse($enabled);
    }

    public function itReturnsFalseIfPluginIsInstalledAndNotEnabled()
    {
        $response = stub('Guzzle\Http\Message\Response')->getBody(true)->returns($this->response_without_plugin);
        stub($this->guzzle_request)->send()->returns($response);
        stub($this->guzzle_client)->get()->returns($this->guzzle_request);

        $enabled = $this->driver->isDeletePluginEnabled($this->gerrit_server);

        $this->assertFalse($enabled);
    }

    public function itReturnsTrueIfPluginIsInstalledAndEnabled()
    {
        $response = stub('Guzzle\Http\Message\Response')->getBody(true)->returns($this->response_with_plugin);
        stub($this->guzzle_request)->send()->returns($response);
        stub($this->guzzle_client)->get()->returns($this->guzzle_request);

        $enabled = $this->driver->isDeletePluginEnabled($this->gerrit_server);

        $this->assertTrue($enabled);
    }

    public function itCallsGerritServerWithOptions()
    {
        $url = $this->gerrit_server_host
            .':'. $this->gerrit_server_port
            .'/a/plugins/';

        $response = stub('Guzzle\Http\Message\Response')->getBody(true)->returns('');
        stub($this->guzzle_request)->send()->returns($response);

        expect($this->guzzle_client)->get(
            $url,
            array(
                'verify' => false,
            )
        )->once();
        stub($this->guzzle_client)->get()->returns($this->guzzle_request);

        $this->driver->isDeletePluginEnabled($this->gerrit_server);
    }

    public function itThrowsAProjectDeletionExceptionIfThereAreOpenChanges()
    {
        $exception = new Guzzle\Http\Exception\ClientErrorResponseException();
        stub($this->guzzle_client)->delete()->throws($exception);

        $this->expectException('ProjectDeletionException');

        $this->driver->deleteProject($this->gerrit_server, 'project');
    }
}
