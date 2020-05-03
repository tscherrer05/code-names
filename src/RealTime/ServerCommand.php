<?php
namespace App\RealTime;

use Ratchet\Http\HttpServer;
use Ratchet\Server\IoServer;
use Ratchet\WebSocket\WsServer;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\Session\SessionInterface;

class ServerCommand extends Command
{
    protected $container;


    public function __construct(ContainerInterface $container)
    {
        $this->container = $container;
        parent::__construct();
    }    

    /**
     * Configure a new Command Line
     */
    protected function configure()
    {
        $this
            ->setName('CodeNames:RealTime:server')
            ->setDescription('Start the realtime game server.');
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {

        $server = IoServer::factory(
            new HttpServer(
                new WsServer(
                    new Messager($this->container)
                )
            ),
            8080
        );

        $server->run();

    }

}