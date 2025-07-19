<?php

class NonHTMLTemplates{

    public function getVerificationEmailTemplate(array $user, string $verificationToken): string{
        $redirectUrl = "https://weekiemochi.com/users/verify/$verificationToken";

        return "
            Hi {$user["username"]}, thank you for signing up on Weekie mochi!
            Just one step more. Please, verify your email address by clicking on the following link: $redirectUrl
            (This link will be valid for two hours. In case it expires, please sign up again or re-send the email through the 'Email sent successfully' section after signing up on https://weekiemochi.com/ if possible)
        ";
    }
}
