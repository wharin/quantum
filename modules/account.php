<?php

class Account extends QModule {

    protected $version = '1.0';

    public function addLanguage($lang) {
        $this->params['placeholder_name_' . $lang]      = $this->params['placeholder_name_' . DEF_LANG];
        $this->params['placeholder_email_' . $lang]     = $this->params['placeholder_email_' . DEF_LANG];
        $this->params['placeholder_password_' . $lang]  = $this->params['placeholder_password_' . DEF_LANG];
        $this->params['old_pass_' . $lang]              = $this->params['old_pass_' . DEF_LANG];
        $this->params['new_pass_' . $lang]              = $this->params['new_pass_' . DEF_LANG];
        $this->params['title_registration_' . $lang]    = $this->params['title_registration_' . DEF_LANG];
        $this->params['title_account_' . $lang]         = $this->params['title_account_' . DEF_LANG];
        $this->params['log_in_' . $lang]                = $this->params['log_in_' . DEF_LANG];
        $this->params['log_out_' . $lang]               = $this->params['log_out_' . DEF_LANG];
        $this->params['confirm_' . $lang]               = $this->params['confirm_' . DEF_LANG];
        $this->params['save_' . $lang]                  = $this->params['save_' . DEF_LANG];
        $this->params['agree_' . $lang]                 = $this->params['agree_' . DEF_LANG];
        $this->params['account_exists_' . $lang]        = $this->params['account_exists_' . DEF_LANG];
        $this->params['not_valid_email_' . $lang]       = $this->params['not_valid_email_' . DEF_LANG];
        $this->params['not_valid_name_' . $lang]        = $this->params['not_valid_name_' . DEF_LANG];
        $this->params['not_valid_password_' . $lang]    = $this->params['not_valid_password_' . DEF_LANG];
        $q = $this->engine->db->query("UPDATE " . DB_PREF . "modules SET `params`='" . $this->engine->db->escape(serialize($this->params)) . "' WHERE name='account'");
        if ($q) return true; else return false;
    }

    public function register($name, $email, $password) {
        $result = $this->validate($name, $email, $password);
        if ($result == '') {
            $name = $this->engine->db->escape($name);
            $email = $this->engine->db->escape(strtolower($email));
            $password = md5(md5($this->engine->db->escape($password)));
            if ($this->engine->db->query("INSERT INTO " . DB_PREF . "users (`name`, `email`, `password`, `user_group`) VALUES ('" . $name . "', '" . $email . "', '" . $password . "', '5')")) {
                $this->sendConfirm($email);
            }
            return true;
        } else {
            return $result;
        }
    }

    private function validate($name, $email, $password) {
        $result = '';
        $email = $this->engine->db->escape(strtolower($email));
        $name = $this->engine->db->escape($name);
        if (preg_match('/\w+@\w+?\.[a-zA-Z]{2,6}/', $email) && strlen($email) < 40) {
            $exists = $this->engine->db->query("SELECT COUNT(1) FROM " . DB_PREF . "users WHERE email = '" . $email . "' OR name = '" . $name . "'")->row;
            if (!empty($exists)) {
                $result .= $this->params['account_exists_' . $_SESSION['lang']] . '<br />';
            }
        } else {
            $result .= $this->params['not_valid_email_' . $_SESSION['lang']] . '<br />';
        }
        if (strlen($name) < 2 || strlen($name) > 254) {
            $result .= $this->params['not_valid_name_' . $_SESSION['lang']] . '<br />';
        }
        if (strlen($password) < 5 || strlen($password) > 20) {
            $result .= $this->params['not_valid_password_' . $_SESSION['lang']] . '<br />';
        }

        return (int)$result;
    }

    private function sendConfirm($email) {
        $confirm_key = md5(md5($email));
        $this->engine->db->query("UPDATE " . DB_PREF . "users SET `confirm` = '" . $confirm_key . "' WHERE LOWER(email) = '" . strtolower($email) . "' ");
        $message = '<h1>Congratulations! You have successfully registered on ' . $_SERVER['HTTP_HOST'] . '</h1>';
        $message .= '<p>To finish your registration just follow the link.</p>';
        $message .= '<p><a href="' . $this->engine->url->link('route=account&action=register&amp;confirm_key=' . $confirm_key) . '">Confirm registration</a></p>';
        $message .= '<p>If it was not You just ignore this message.</p>';
        $this->engine->sendMail($email, 'system@' . $_SERVER['HTTP_HOST'], $_SERVER['HTTP_HOST'] . ' - Notification system', 'Подтверждение регистрации на "Блабла"', $message);
    }

    public function confirm() {
        $this->engine->db->query("UPDATE " . DB_PREF . "users SET `confirm` = '1' WHERE `confirm` = '" . $_GET['confirm_key'] . "'");
    }

    public function index() {
        $this->engine->ERROR_404 = FALSE;
        if ($_GET['action'] == 'register') {
            $this->engine->document->setTitle($this->params['title_registration_' . $_SESSION['lang']]);

            if (isset($_SESSION['msg'])) {
                if ($_SESSION['msg'] == 'sent') {
                    $this->data['text_message'] = $this->params['sent_' . $_SESSION['lang']];
                    $this->data['class_message'] = 'success';
                } elseif ($_SESSION['msg'] == 'message_fail') {
                    $this->data['text_message'] = $this->params['message_fail_' . $_SESSION['lang']];
                    $this->data['class_message'] = 'error';
                } elseif ($_SESSION['msg'] == 'empty_vars') {
                    $this->data['text_message'] = $this->params['empty_vars_' . $_SESSION['lang']];
                    $this->data['class_message'] = 'error';
                }
                unset($_SESSION['msg']);
            }
            if ((bool)$this->params['captcha_required']) {
                $captcha = new QCaptcha();
                $this->data['captcha'] = $captcha->getContent();
                $_SESSION['captcha'] = $captcha->getCode();
                unset($captcha);
            }
            $this->data['caption']              = $this->params['title_registration_' . $_SESSION['lang']];
            $this->data['placeholder_name']     = $this->params['placeholder_name_' . $_SESSION['lang']];
            $this->data['placeholder_email']    = $this->params['placeholder_email_' . $_SESSION['lang']];
            $this->data['placeholder_password'] = $this->params['placeholder_password_' . $_SESSION['lang']];
            $this->data['confirm']              = $this->params['confirm_' . $_SESSION['lang']];
            $this->data['agree']                = sprintf(html_entity_decode($this->params['agree_' . $_SESSION['lang']]), htmlspecialchars($this->engine->url->link($this->params['agreement'])));
            $this->data['name']                 = isset($_POST['name']) ? htmlspecialchars($_POST['name']) : '';
            $this->data['password']             = isset($_POST['name']) ? htmlspecialchars($_POST['password']) : '';
            $this->data['phone']                = isset($_POST['phone']) ? htmlspecialchars($_POST['phone']) : '';
            $this->data['email']                = isset($_POST['email']) ? htmlspecialchars($_POST['email']) : '';
            $this->data['message']              = isset($_POST['message']) ? htmlspecialchars($_POST['message']) : '';

            $this->template = TEMPLATE . 'account_register.tpl';
        }
    }
}