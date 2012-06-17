<?php
/*
 * ZenMagick - Smart e-commerce
 * Copyright (C) 2006-2012 zenmagick.org
 *
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 2 of the License, or (at
 * your option) any later version.
 *
 * This program is distributed in the hope that it will be useful, but
 * WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the GNU
 * General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program; if not, write to the Free Software
 * Foundation, Inc., 51 Franklin Street - Fifth Floor, Boston, MA  02110-1301, USA.
 */
namespace zenmagick\plugins\pusher;

use Plugin;
use zenmagick\base\Toolbox;
use zenmagick\http\view\TemplateView;

use Pusher\Pusher;

/**
 * Pusher plugin.
 *
 * @author DerManoMann <mano@zenmagick.org>
 */
class PusherPlugin extends Plugin {
    private $pusher;


    /**
     * Create new instance.
     */
    public function __construct() {
        parent::__construct('Pusher', 'Adds pusher support to the site.', '${plugin.version}');
        $this->setContext('storefront');
        $this->pusher = null;
    }


    /**
     * {@inheritDoc}
     */
    public function install() {
        parent::install();

        $this->addConfigValue('App Id', 'appId', '', 'Your Application Id');
        $this->addConfigValue('App Key', 'appKey', '', 'Your Application Key');
        $this->addConfigValue('App Secret', 'appSecret', '', 'Your Application Secret');
        $this->addConfigValue('Pusher Version', 'pusherVersion', '1.12', 'Pusher API version');
        $this->addConfigValue('jQuery Version', 'jQuery', '1.7.1', 'Version of jQuery to include; leave empty if jQuery is already loaded');
        $this->addConfigValue('Channel', 'channel', 'site-activity', 'The channel to subscribe to');
        $this->addConfigValue('Activity Stream Support', 'activityStream', 'false', 'Enable activity stream support',
            'widget@booleanFormWidget#name=activityStream&default=false&label=Enable Activity Stream&style=checkbox');
    }

    /**
     * {@inheritDoc}
     */
    public function init() {
        parent::init();
        $this->container->get('eventDispatcher')->listen($this);
    }

    /**
     * Inject scripts.
     */
    public function onViewStart($event) {
        $view = $event->get('view');
        if ($view instanceof TemplateView) {
            // got content, so lets see what we need to add
            $resourceManager = $view->getResourceManager();
            if (Toolbox::asBoolean($this->get('activityStream'))) {
                $jQuery = trim($this->get('jQuery'));
                if (!empty($jQuery)) {
                    $resourceManager->jsFile(sprintf('//code.jquery.com/jquery-%s.min.js', $jQuery), $resourceManager::FOOTER);
                }
                $resourceManager->jsFile(sprintf('//js.pusher.com/%s/pusher.min.js', $this->get('pusherVersion')), $resourceManager::FOOTER);
                $resourceManager->jsFile('js/PusherActivityStreamer.js', $resourceManager::FOOTER);
                $resourceManager->cssFile('css/activity-streams.css');
                $resourceManager->jsFile('js/PusherActivityStreamer.js', $resourceManager::FOOTER);
            }
        }
    }

    /**
     * Inject streamer.
     */
    public function onFinaliseContent($event) {
        $code = null;
        $appKey = $this->get('appKey');
        if (Toolbox::asBoolean($this->get('activityStream'))) {
            $channel = $this->get('channel');
            $code = <<<EOT
<script type="text/javascript">
$(function() {
  var pusher = new Pusher('$appKey');
  var channel = pusher.subscribe('$channel');
  //var streamer = new PusherActivityStreamer(channel, '#site_activity_stream', { events: ['my_event'] });
    channel.bind('my_event', function(data) { alert('xx'+data); });

});
</script>
EOT;
        }

        if ($code) {
            $content = $event->get('content');
            $content = preg_replace('/<\/body>/', $code . '</body>', $content, 1);
            $event->set('content', $content);
        }
    }

    /**
     * Get a pusher instance.
     */
    public function getPusher() {
        if (null == $this->pusher) {
            $this->pusher = new Pusher($this->get('appKey'), $this->get('appSecret'), $this->get('appId'));
        }

        return $this->pusher;
    }

    /**
     * Login event handler.
     */
    public function onLoginSuccess($event) {
        $account = $event->get('account');
        $this->getPusher()->trigger($this->get('channel'), 'login', sprintf(_zm('%s just logged in.'), $account->getFirstName()));
    }

}
