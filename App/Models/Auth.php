<?php

/**
 * This file is part of the Sunhill Framework package.
 *
 * (c) Sunhill Technology <info@sunhillint.com>
 *
 * Licensed under The GNU Lesser General Public License, version 3.0. Redistributions of files must retain the above copyright notice.
 */

/**
 * Namespace for model
 * Use App/Models directory
 */
namespace App\Models;

/**
 * Inherit from the main model
 * Don't change parent model path and name
 */
class Auth extends \Core\Model
{

    /**
     * Main method of the model
     * Don't change the method's name
     */
    public function show() {
        // no direct db work here - Auth\Controller uses $GLOBALS['auth'] (SunAuth) directly
    }

    /**
     * Look up a user by email, active or not - needed for the signup flow
     * (detecting a pre-existing-but-unverified account) and for resending a
     * verification code by email. SunAuth's own user lookups are private,
     * so this goes straight at the "users" table (SunAuth's default table
     * name, System/SunAuth.php $config['table']) the same way any other
     * model would query it.
     *
     * @param string $email
     * @return array|null
     */
    public function userByEmail($email = null) {
        return $this->pdo->select('users')->where('email', $email, '=')->first()->run();
    }

    /**
     * Look up a user by id - used by resendverification() (the pending
     * verification session only stores a user id, not the email).
     *
     * @param int $userId
     * @return array|null
     */
    public function userById($userId = null) {
        return $this->pdo->select('users')->where('id', $userId, '=')->first()->run();
    }

    /**
     * Generate a new 6-digit email verification code for a user, storing
     * only its hash (never the plaintext code). Invalidates any codes
     * already issued to this user first, so only the most recent one sent
     * is ever valid - otherwise an old, still-unused code from a previous
     * registration/resend attempt would stay guessable indefinitely.
     *
     * @param int $userId
     * @return string the plaintext 6-digit code (only place it ever exists)
     */
    public function createVerificationCode($userId = null) {
        $this->pdo->update('sun_email_verifications', ['used' => 1])->where('user_id', $userId, '=')->where('used', 0, '=')->run();
        $code = str_pad((string) random_int(0, 999999), 6, '0', STR_PAD_LEFT);
        $this->pdo->insert('sun_email_verifications', [
            'user_id'    => $userId,
            'code_hash'  => hash('sha256', $code),
            'expires_at' => date('Y-m-d H:i:s', time() + 1800), // 30 minutes
            'used'       => 0,
        ])->run();
        return $code;
    }

    /**
     * Verify a submitted code for a user. On success, marks the code used
     * and activates the account (users.status = 1) - SunAuth's login()
     * already refuses status != activeStatus, so this one update is the
     * entire "activation".
     *
     * @param int $userId
     * @param string $code
     * @return bool
     */
    public function verifyEmailCode($userId = null, $code = null) {
        if (empty($code)) {
            return false;
        }
        $row = $this->pdo->select('sun_email_verifications')
            ->where('user_id', $userId, '=')
            ->where('code_hash', hash('sha256', $code), '=')
            ->where('used', 0, '=')
            ->orderBy('id', 'desc')->first()->run();
        if (empty($row) || strtotime($row['expires_at']) < time()) {
            return false;
        }
        $this->pdo->update('sun_email_verifications', ['used' => 1])->where('id', $row['id'], '=')->run();
        $this->pdo->update('users', ['status' => 1])->where('id', $userId, '=')->run();
        return true;
    }

}

?>
