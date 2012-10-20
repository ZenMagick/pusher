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
namespace ZenMagick\plugins\pusher;

use ZenMagick\Base\Toolbox;
use ZenMagick\Base\ZMObject;

/**
 * Pusher event pusher.
 *
 * @author DerManoMann <mano@zenmagick.org>
 */
class PusherEventPusher extends ZMObject {

    public function onViewStart($event) {
if (false) {
$account = $this->container->get('accountService')->getAccountForId(17);
$product = $this->container->get('productService')->getProductForId(2, 1);
$this->onReviewSubmitted(new \Symfony\Component\EventDispatcher\GenericEvent($this, array('account' => $account, 'product' => $product, 'request' => $event->getArgument('request'))));
$this->onCreateOrder(new \Symfony\Component\EventDispatcher\GenericEvent($this, array('account' => $account, 'orderId' => 1, 'request' => $event->getArgument('request'))));
$this->onCreateAccount(new \Symfony\Component\EventDispatcher\GenericEvent($this, array('account' => $account, 'request' => $event->getArgument('request'))));
die();
}
    }
    /**
     * Push an event.
     *
     * @param event The event name.
     * @param mixed data The event data.
     */
    public function pushEvent($event, $data) {
        if ($plugin = $this->container->get('pluginService')->getPluginForId('pusher')) {
            $plugin->pushEvent($event, $data);
        }
    }

    /**
     * Review submitted.
     */
    public function onReviewSubmitted($event) {
        $toolbox = $event->getArgument('request')->getToolbox();
        $product = $event->getArgument('product');
        $purl = sprintf('<a href="%s">%s</a>', $toolbox->net->product($product->getId()), $product->getName());
        $imgurl = $toolbox->html->image($product->getImageInfo(), 'small', 'width=40&height=43');
        $img = '<a href="'.$toolbox->net->product($product->getId()).'">'.$imgurl.'</a>';
        $message = null;
        if ($account = $event->getArgument('account')) {
            $address = $this->container->get('addressService')->getAddressForId($account->getDefaultAddressId());
            $message = sprintf(_zm('%4$s<div><span class="pusr">%1$s</span> from %2$s just reviewed <span class="ppr">%3$s</span>. Good work %1$s!</div>'),
                          $account->getFirstName(), $account->getCity(), $purl, $img);
        } else {
            $message = sprintf(_zm('%2$s Just reviewed: %s.'), $purl, $img);
        }
        $this->pushEvent('review', array('msg' => $message, 'ts' => time()));
    }

    /**
     * Order created.
     */
    public function onCreateOrder($event) {
        $toolbox = $event->getArgument('request')->getToolbox();
        $languageId = $this->container->get('session')->getLanguageId();
        $order = $this->container->get('orderService')->getOrderForId($event->getArgument('orderId'), $languageId);
        $items = $order->getOrderItems();
        // pick product
        $product = $items[0]->getProduct();
        $purl = sprintf('<a href="%s">%s</a>', $toolbox->net->product($product->getId()), $product->getName());
        $imgurl = $toolbox->html->image($product->getImageInfo(), 'small', 'width=40&height=43');
        $img = '<a href="'.$toolbox->net->product($product->getId()).'">'.$imgurl.'</a>';

        $account = $this->container->get('accountService')->getAccountForId($order->getAccountId());
        $address = $order->getBillingAddress();
        $message = sprintf(_zm('%4$s<div><span class="pusr">%1$s</span> from %2$s just ordered <span class="ppr">%3$s</span>. Keep riding %1$s:)</div>'),
                      $account->getFirstName(), $account->getCity(), $purl, $img);
        $this->pushEvent('order', array('msg' => $message, 'ts' => time()));
    }

    /**
     * Account created.
     */
    public function onCreateAccount($event) {
        $resourceResolver = $this->container->get('themeResourceResolver');
        $resourceManager = $this->container->get('defaultView')->getResourceManager();
        if (null != ($path = $resourceResolver->findResource('resource:images/create_account.png'))) {
            $imgurl = $resourceManager->file2uri($path);
        }

        $account = $event->getArgument('account');
        $img = '<img width="40" height="43" src="'.$imgurl.'">';
        $message = sprintf(_zm('%3$s<div><span class="pusr">%1$s</span> from %2$s just joined. Welcome to the ride %1$s:)</div>'),
                      $account->getFirstName(), $account->getCity(), $img);
        $this->pushEvent('account', array('msg' => $message, 'ts' => time()));
    }

}
