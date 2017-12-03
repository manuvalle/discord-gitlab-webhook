<?php

require_once 'vendor/autoload.php';
require_once 'utils.php';
require_once 'providers/gitlab.php';

use \DiscordWebhooks\Client;
use \DiscordWebhooks\Embed;

$dotenv = new Dotenv\Dotenv(__DIR__);
$dotenv->load();

$utils = new Utils();
$webhook = new Client(getenv('DISCORD_WEBHOOK_URL'));
$embed = new Embed();
