<?php

/**
 * This file is part of the Sunhill Framework package.
 *
 * (c) Sunhill Technology <info@sunhillint.com>
 *
 * Licensed under The GNU Lesser General Public License, version 3.0. Redistributions of files must retain the above copyright notice.
 */

/**
 * Namespace for controller
 * Use App/Controllers directory
 */
namespace App\Controllers;

/**
 * Inherit from the main controller
 * Don't change parent controller path and name
 *
 * Full auth flow built on SunAuth (System/SunAuth.php): login, sign-up with
 * email verification code, forgot/reset password, 2FA code prompt, logout.
 * See App/Controllers/README.md for the request flow of each method and
 * App/Views/Auth.php for the matching forms.
 */
class Auth extends \Core\Controller
{

    /**
     * Construct method of the inherited controller
     * Don't change the parameters if not needed
     *
     * @param string $view
     * @param object $model
     * @param array $params
     */
    public function __construct($view = null, $model = null, $params = null) {
        $this->view = $view; // view file's address (in views directory)
        $this->model = $model; // model object (created by parent class)
        $this->params = $params; // parameters (if needed for model)
    }

    /**
     * Show the login form (GET) or process the submitted credentials (POST)
     * Csrf validation on the POST branch already runs in Core\Controller before this is called
     */
    public function login() {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $rawEmail = isset($_POST['email']) ? $_POST['email'] : '';
            $email = $GLOBALS['filter']->sanitize($rawEmail, 'email')->result();
            $password = isset($_POST['password']) ? (string) $_POST['password'] : ''; // never HTML-sanitize a password, only compared via hash

            $currentLang = strtolower($GLOBALS['sunApp']->routes[0]);
            if ($GLOBALS['auth']->login($email, $password)) {
                if ($GLOBALS['auth']->isLoggedIn()) {
                    $_SESSION['flash_notify'] = ['type' => 'success', 'message' => _tr('auth.flash_welcome_back')];
                    // TODO: point this at your app's real post-login landing
                    // page once you have one (a dashboard, a members area,
                    // ...) - home is just a safe default for a fresh template.
                    header('Location: ' . SYS_BASEURL . '/' . $currentLang . '/home');
                } else {
                    // Credentials were correct but the account has 2FA enabled -
                    // SunAuth opened the session as "pending" (twofa_pending=1),
                    // isLoggedIn()/id() both return null until verify2fa() below
                    // promotes it. Without this branch the user would silently
                    // land on an "Authentication required" error instead of a
                    // code prompt.
                    header('Location: ' . SYS_BASEURL . '/' . $currentLang . '/Auth/verify2fa');
                }
                exit;
            } else if ($GLOBALS['auth']->lastError() === 'Account is not active.') {
                // Correct credentials, but this is a signup that never
                // finished email verification (users.status = 0) - send
                // them straight back into that flow with a fresh code
                // instead of a confusing "invalid credentials" message.
                $user = $this->model->userByEmail($email);
                if (!empty($user)) {
                    $this->sendVerificationCode((int) $user['id'], $email);
                    $_SESSION['pending_verify_user_id'] = (int) $user['id'];
                }
                header('Location: ' . SYS_BASEURL . '/' . $currentLang . '/Auth/verifyemail?resent=1');
                exit;
            } else {
                header('Location: ' . SYS_BASEURL . '/' . $currentLang . '/Auth/login?error=1');
                exit;
            }
        } else {
            $mode = 'login';
            require_once ($this->view); // include view file
        }
    }

    /**
     * Generate a verification code for $userId and email it to $email.
     * Shared by register() (new signup) and login()'s inactive-account
     * branch (resuming a signup that was never verified).
     */
    private function sendVerificationCode($userId, $email) {
        $code = $this->model->createVerificationCode($userId);
        $mailer = new \SunMailer();
        $mailer->send(
            $email,
            'Verify your email',
            '<p>Enter this code to verify your email and activate your account:</p><p style="font-size:24px;font-weight:bold;letter-spacing:.2em;">' . $code . '</p><p>This code expires in 30 minutes.</p>',
            "Enter this code to verify your email and activate your account:\n\n" . $code . "\n\nThis code expires in 30 minutes."
        );
    }

    /**
     * Sign-up form (GET) / account creation (POST). New accounts start
     * inactive (status = 0) - SunAuth's login() already refuses inactive
     * accounts on its own, so there's no separate "is verified" check
     * needed anywhere else. A duplicate signup against an email that
     * exists but was never verified resumes that flow (fresh code, no
     * dead-end error) rather than refusing outright.
     */
    public function register() {
        $currentLang = strtolower($GLOBALS['sunApp']->routes[0]);
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $firstName = $GLOBALS['filter']->sanitize(isset($_POST['first_name']) ? $_POST['first_name'] : '', 'string')->result();
            $lastName = $GLOBALS['filter']->sanitize(isset($_POST['last_name']) ? $_POST['last_name'] : '', 'string')->result();
            $email = $GLOBALS['filter']->sanitize(isset($_POST['email']) ? $_POST['email'] : '', 'email')->result();
            $password = isset($_POST['password']) ? (string) $_POST['password'] : '';
            $confirm = isset($_POST['confirm_password']) ? (string) $_POST['confirm_password'] : '';

            if (empty($email) || strlen($password) < 8) {
                header('Location: ' . SYS_BASEURL . '/' . $currentLang . '/Auth/register?error=1');
                exit;
            }
            if ($password !== $confirm) {
                header('Location: ' . SYS_BASEURL . '/' . $currentLang . '/Auth/register?error=mismatch');
                exit;
            }

            $existing = $this->model->userByEmail($email);
            if (!empty($existing) && (int) $existing['status'] === 1) {
                header('Location: ' . SYS_BASEURL . '/' . $currentLang . '/Auth/register?error=exists');
                exit;
            }

            if (!empty($existing)) {
                // Existing but never-verified account - resume, don't re-insert.
                $userId = (int) $existing['id'];
            } else {
                $userId = $GLOBALS['auth']->register([
                    'email'      => $email,
                    'password'   => $password,
                    'first_name' => $firstName,
                    'last_name'  => $lastName,
                    'status'     => 0,
                    'language'   => $currentLang,
                ]);
                if (empty($userId)) {
                    header('Location: ' . SYS_BASEURL . '/' . $currentLang . '/Auth/register?error=exists');
                    exit;
                }
            }

            $this->sendVerificationCode($userId, $email);
            $_SESSION['pending_verify_user_id'] = $userId;
            header('Location: ' . SYS_BASEURL . '/' . $currentLang . '/Auth/verifyemail');
            exit;
        }
        $mode = 'register';
        require_once ($this->view);
    }

    /**
     * Email verification code prompt: shown right after register() (or
     * after a login attempt against a never-verified account). GET shows
     * the form, POST checks the code and activates the account.
     */
    public function verifyemail() {
        $currentLang = strtolower($GLOBALS['sunApp']->routes[0]);
        $pendingUserId = isset($_SESSION['pending_verify_user_id']) ? (int) $_SESSION['pending_verify_user_id'] : 0;

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $rawCode = isset($_POST['code']) ? $_POST['code'] : '';
            $code = $GLOBALS['filter']->sanitize($rawCode, 'string')->result();
            if (!empty($pendingUserId) && $this->model->verifyEmailCode($pendingUserId, $code)) {
                unset($_SESSION['pending_verify_user_id']);
                $_SESSION['flash_notify'] = ['type' => 'success', 'message' => _tr('auth.flash_email_verified')];
                header('Location: ' . SYS_BASEURL . '/' . $currentLang . '/Auth/login');
                exit;
            }
            header('Location: ' . SYS_BASEURL . '/' . $currentLang . '/Auth/verifyemail?error=1');
            exit;
        }

        if (empty($pendingUserId)) {
            header('Location: ' . SYS_BASEURL . '/' . $currentLang . '/Auth/login');
            exit;
        }
        $mode = 'verifyemail';
        require_once ($this->view);
    }

    /**
     * Resend the verification code to whoever's pending in this session.
     * POST-only (CSRF protected by Core\Controller's blanket POST check).
     */
    public function resendverification() {
        $currentLang = strtolower($GLOBALS['sunApp']->routes[0]);
        $pendingUserId = isset($_SESSION['pending_verify_user_id']) ? (int) $_SESSION['pending_verify_user_id'] : 0;
        if (!empty($pendingUserId)) {
            $user = $this->model->userById($pendingUserId);
            if (!empty($user)) {
                $this->sendVerificationCode($pendingUserId, $user['email']);
            }
        }
        header('Location: ' . SYS_BASEURL . '/' . $currentLang . '/Auth/verifyemail?resent=1');
        exit;
    }

    /**
     * Pending-2FA code prompt: shown right after login() when the account
     * has two-factor enabled. GET shows the form, POST verifies the code
     * and (on success) promotes the session the same way a normal login would.
     */
    public function verify2fa() {
        $currentLang = strtolower($GLOBALS['sunApp']->routes[0]);
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $rawCode = isset($_POST['code']) ? $_POST['code'] : '';
            $code = $GLOBALS['filter']->sanitize($rawCode, 'string')->result();
            if ($GLOBALS['auth']->verifyTwoFactor($code)) {
                $_SESSION['flash_notify'] = ['type' => 'success', 'message' => _tr('auth.flash_welcome_back')];
                header('Location: ' . SYS_BASEURL . '/' . $currentLang . '/home'); // see the TODO in login() above
                exit;
            }
            header('Location: ' . SYS_BASEURL . '/' . $currentLang . '/Auth/verify2fa?error=1');
            exit;
        }
        if (empty($GLOBALS['auth']->pendingTwoFactor())) {
            // Nothing pending (already logged in, or never started a login) - no code to verify.
            header('Location: ' . SYS_BASEURL . '/' . $currentLang . '/Auth/login');
            exit;
        }
        $mode = 'verify2fa';
        require_once ($this->view);
    }

    /**
     * "Forgot password?" - GET shows the email form, POST sends a reset
     * link if the address exists. Always shows the same success message
     * either way (never reveals whether an email is registered).
     */
    public function forgotpassword() {
        $currentLang = strtolower($GLOBALS['sunApp']->routes[0]);
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $rawEmail = isset($_POST['email']) ? $_POST['email'] : '';
            $email = $GLOBALS['filter']->sanitize($rawEmail, 'email')->result();

            $token = $GLOBALS['auth']->createResetToken($email);
            if (!empty($token)) {
                // Token goes in the query string, NOT a route segment -
                // Core\App::parseUrl() ucfirst()'s every path segment,
                // which would silently mangle the first character of this
                // case-sensitive hex token and make every reset link fail
                // its hash_equals() check.
                $resetUrl = SYS_BASEURL . '/' . $currentLang . '/Auth/resetpassword?token=' . urlencode($token);
                $mailer = new \SunMailer();
                $mailer->send(
                    $email,
                    'Reset your password',
                    '<p>We received a request to reset your password.</p><p><a href="' . $resetUrl . '">Click here to choose a new password</a></p><p>If you didn\'t request this, you can safely ignore this email.</p>',
                    "We received a request to reset your password.\n\nReset it here: " . $resetUrl . "\n\nIf you didn't request this, you can safely ignore this email."
                );
            }

            $_SESSION['flash_notify'] = ['type' => 'success', 'message' => _tr('auth.flash_reset_link_sent')];
            header('Location: ' . SYS_BASEURL . '/' . $currentLang . '/Auth/login');
            exit;
        }
        $mode = 'forgotpassword';
        require_once ($this->view);
    }

    /**
     * Reset-password landing page (link from the email above). GET
     * validates the token up front so a dead/expired link shows a clear
     * message instead of a confusing form; POST performs the actual reset.
     */
    public function resetpassword() {
        $currentLang = strtolower($GLOBALS['sunApp']->routes[0]);
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $rawToken = isset($_POST['token']) ? $_POST['token'] : '';
            $token = $GLOBALS['filter']->sanitize($rawToken, 'string')->result();
            $newPassword = isset($_POST['new_password']) ? (string) $_POST['new_password'] : '';
            $confirm = isset($_POST['confirm_password']) ? (string) $_POST['confirm_password'] : '';

            if ($newPassword === '' || $newPassword !== $confirm) {
                header('Location: ' . SYS_BASEURL . '/' . $currentLang . '/Auth/resetpassword?token=' . urlencode($token) . '&error=mismatch');
                exit;
            }
            if ($GLOBALS['auth']->resetPassword($token, $newPassword)) {
                $_SESSION['flash_notify'] = ['type' => 'success', 'message' => _tr('auth.flash_password_reset')];
                header('Location: ' . SYS_BASEURL . '/' . $currentLang . '/Auth/login');
                exit;
            }
            header('Location: ' . SYS_BASEURL . '/' . $currentLang . '/Auth/resetpassword?token=' . urlencode($token) . '&error=invalid');
            exit;
        }

        $rawToken = isset($_GET['token']) ? $_GET['token'] : '';
        $token = $GLOBALS['filter']->sanitize($rawToken, 'string')->result();
        $resetTokenValid = !empty($GLOBALS['auth']->verifyResetToken($token));
        $resetToken = $token;
        $mode = 'resetpassword';
        require_once ($this->view);
    }

    /**
     * Log the current session out and redirect home. POST-only (CSRF
     * protected by Core\Controller's blanket POST check) - the logout
     * button in each view's inlined nav is a plain form submit; wire up
     * a confirm dialog client-side if you want one.
     */
    public function logout() {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header('Location: ' . SYS_BASEURL . '/' . SYS_HOMEPAGE);
            exit;
        }
        $GLOBALS['auth']->logout();
        $_SESSION['flash_notify'] = ['type' => 'success', 'message' => _tr('auth.flash_logged_out')];
        header('Location: ' . SYS_BASEURL . '/' . SYS_HOMEPAGE);
        exit;
    }

}

?>
