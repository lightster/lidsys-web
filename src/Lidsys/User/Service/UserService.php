<?php
/*
 * Lightdatasys web site source code
 *
 * Copyright Matt Light <matt.light@lightdatasys.com>
 *
 * For copyright and licensing information, please view the LICENSE
 * that is distributed with this source code.
 */

namespace Lidsys\User\Service;

use Pdo;

use Lstr\Silex\Database\DatabaseService;

use Silex\Application;

class UserService
{
    private $db;



    public function __construct(DatabaseService $db)
    {
        $this->db    = $db;
    }

    public function createUser($user_info)
    {
        $db = $this->db;

        $errors = array();

        if (strlen($user_info['first_name']) < 3) {
            $errors['first_name'] = 'Please enter at least 3 characters of your first name.';
        }
        if (strlen($user_info['last_name']) > 1) {
            $errors['last_name'] = 'Please enter only the first letter of your last name.';
        } elseif (strlen($user_info['last_name']) < 1) {
            $errors['last_name'] = 'Please enter the first letter of your last name.';
        }

        if (!preg_match('#\b[a-zA-Z0-9._%+-]+@[a-zA-Z0-9.-]+\.[a-zA-Z]{2,4}\b#', $user_info['email'])) {
            $errors['email'] = 'The email address you entered is invalid.';
        } else {
            $email_exists = (bool)$db->query(
                "
                    SELECT 1
                    FROM user
                    WHERE email = :email
                ",
                array(
                    'email'   => $user_info['email'],
                )
            )->fetch();
            if ($email_exists) {
                $errors['email'] = 'The email address you entered is already in use.';
            }
        }

        if (count($errors)) {
            return array('error' => $errors);
        }

        $group = $db->query(
            "
                SELECT groupId AS group_id
                FROM userGroup
                WHERE name = 'Users'
            "
        )->fetch();

        if (!$group) {
            throw new Exception("The default user group 'Users' could not be found.");
        }

        $db->insert(
            'user',
            array(
                'username'     => $user_info['email'],
                'email'        => $user_info['email'],
                'password'     => md5(microtime()),
                'securityHash' => substr(base64_encode(md5(microtime())), 0, 4),
                'joinDate'     => date('Y-m-d H:i:s'),
            )
        );
        $user_id = $db->getLastInsertId();

        $db->insert(
            'player',
            array(
                'name'    => $user_info['first_name'] . ' ' . $user_info['last_name'],
                'bgcolor' => substr(md5(microtime()), 0, 6),
            )
        );
        $player_id = $db->getLastInsertId();

        $db->insert(
            'user_userGroup',
            array(
                'groupId' => $group['group_id'],
                'userId'  => $user_id,
            )
        );

        $db->insert(
            'player_user',
            array(
                'playerId' => $player_id,
                'userId'   => $user_id,
            )
        );

        return $user_id;
    }

    public function updateUserColor($user_id, $color)
    {
        $db = $this->db;
        $db->query(
            "
                UPDATE player
                SET bgcolor = :color
                WHERE playerId = (
                    SELECT playerId
                    FROM player_user
                    WHERE userId = :user_id
                )
            ",
            array(
                'color'   => $color,
                'user_id' => $user_id,
            )
        );

        return true;
    }

    public function updateLastActive($user_id)
    {
        $db = $this->db;
        $db->query(
            "
                UPDATE user
                SET lastActive = NOW()
                WHERE userId = :user_id
            ",
            array(
                'user_id' => $user_id,
            )
        );
    }
}
