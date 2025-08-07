<?php

class NonHTMLTemplates{

    public function getVerificationEmailTemplate(array $user, string $verificationToken): string{
        $redirectUrl = "https://weekiemochi.com/users/verify/$verificationToken";

        return "
            Hi, {$user["username"]}, thank you for signing up on Weekie mochi!
            Just one step more. Please, verify your email address by clicking on the following link: $redirectUrl
        ";
    }

    public function getConfirmPasswordChangeTemplate(array $user, string $verificationToken): string{
        $redirectUrl = "https://weekiemochi.com/users/changePassword/$verificationToken";

        return "
            Hi, {$user["username"]}, we received a request from you to change your password.
            Please, click the following link to confirm and proceed with the change: $redirectUrl. If you didn't ask for this, please, ignore this email.
        ";
    }

    public function getPasswordChangeNotificationTemplate(array $user): string{
        $supportTeamEmail = 'support-team@weekiemochi.com';

        return "
            Hi, {$user["username"]}, we were just passing by to notify your password has been changed.
            If you didn't make this change, please reset your password or contact our support team by sending an email to $supportTeamEmail
        ";
    }
}
