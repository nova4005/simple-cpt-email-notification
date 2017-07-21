<?php

/*
Plugin Name: New Article Notification
Plugin URI: https://garrettseymour.com
Description: This plugin will send a notification email to users when a post is published. To opt in to the notification go to your user account and check the box to receive notifications.
Version: 0.1
Author: Garrett Seymour
Author URI: https://garrettseymour.com
*/

/**
 * Copyright (c) 2017 Garrett Seymour. All rights reserved.
 *
 * Released under the GPL license
 * http://www.opensource.org/licenses/gpl-license.php
 *
 * This is an add-on for WordPress
 * http://wordpress.org/
 *
 * **********************************************************************
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 2 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 * **********************************************************************
 */

class Norb_Notifications
{
    public function __construct()
    {
        add_action('show_user_profile', array($this, 'norb_user_notification_field') );
        add_action('edit_user_profile', array($this, 'norb_user_notification_field') );

        add_action('personal_options_update', array($this, 'norb_save_user_notification_field') );
        add_action('edit_user_profile_update', array($this, 'norb_save_user_notification_field') );

        add_action('pending_to_publish', array($this, 'norb_send_notification_on_new_post_publish'), 10, 1 );
        add_action('draft_to_publish', array($this, 'norb_send_notification_on_new_post_publish'), 10, 1 );
        add_action('private_to_publish', array($this, 'norb_send_notification_on_new_post_publish'), 10, 1 );
    }


    public function norb_user_notification_field( $user )
    {
        $checkMeta = get_user_meta($user->ID, 'send_notification', true);
        $isChecked = ($checkMeta == 'true') ? 'checked="checked"' : '';
        ?>
        <h3><?php _e("New Post Notification Preferences", "blank"); ?></h3>

        <table class="form-table">
            <tr>
                <th><label for="send_notification"><?php _e("Send Notification"); ?></label></th>
                <td>
                    <input type="checkbox" name="send_notification" <?=$isChecked;?> id="send_notification" value="true" class="regular-text" /><br />
                    <span class="description"><?php _e("Check to receive email notifications on new posts."); ?></span>
                </td>
            </tr>
        </table>

    <?php }

    public function norb_save_user_notification_field($user_id)
    {
        if (!current_user_can('edit_user', $user_id)) {
            return false;
        }


        update_user_meta($user_id, 'send_notification', $_POST['send_notification']);
    }

    public function norb_send_notification_on_new_post_publish($post = null)
    {
        if($post->post_type === 'ht_kb') {
            $args = [
                'meta_key' => 'send_notification',
                'meta_value' => 'true',
                'meta_compare' => '=',
                'fields' => ['user_email']
            ];

            $users = get_users($args);

            foreach($users as $user) {
                $this->norb_send_new_mail($user, $post);
            }
        }
    }

    public function norb_send_new_mail($user = '', $post = null)
    {
        $author = $post->post_author; /* Post author ID. */
        $name = get_the_author_meta( 'display_name', $author );

        $to = $user->user_email;
        $subject = "New Post";

        $message = '
        <html>
        <head>
        <title>HTML email</title>
        </head>
        <body>
        <img src="" alt="AltText" />
        <p>There is a new post to check out called &rdquo;<a href="' . get_permalink($post->ID) . '">' . $post->post_title . '</a>&ldquo;.</p>
        <p>Post Created By: ' . $name . '</p>
        </body>
        </html>
        ';

        // Always set content-type when sending HTML email
        $headers = "MIME-Version: 1.0" . "\r\n";
        $headers .= "Content-type:text/html;charset=UTF-8" . "\r\n";

        // More headers
        $headers .= 'From: <noreply@example.com>' . "\r\n";

        mail($to,$subject,$message,$headers);

        return;
    }
}

$notify = new Norb_Notifications;
