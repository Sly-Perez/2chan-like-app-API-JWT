<?php

class HTMLTemplates{

    public function getVerificationEmailTemplate(array $user, string $verificationToken): string{

        $redirectUrl = "https://weekiemochi.com/users/verify/$verificationToken";
        $currentYear = date("Y");

        return <<<HTML
        <!DOCTYPE html>
            <html lang="en">
            <head>
            <meta charset="UTF-8">
            <title>Verify your email</title>
            <meta name="viewport" content="width=device-width, initial-scale=1.0">
            <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@100..900&display=swap" rel="stylesheet">
            </head>
            <body style="Margin:0;padding:0;background-color:#fff;">
            <table role="presentation" border="0" cellpadding="0" cellspacing="0" width="100%" style="background-color:#f2f2f2; padding: 20px 0;">
                <tr>
                <td align="center">
                    <!-- Container -->
                    <table role="presentation" border="0" cellpadding="0" cellspacing="0" width="600" style="background-color: #fff; border-radius:8px; overflow:hidden; font-family:'Outfit',Arial,sans-serif;">
                    <!-- Header -->
                    <tr>
                        <td align="center" style="padding: 40px 20px; background-color: #000;border-top: 0.188rem solid green;border-bottom: 0.188rem solid #239;border-right: 0.188rem solid #bb5;border-left: 0.188rem solid red;color: aqua;">
                        <h1 style="color:#ffffff; font-size:30px; margin:0;">Welcome to Weekie Mochi!!!</h1>
                        </td>
                    </tr>
                    <!-- Body -->
                    <tr>
                        <td style="padding: 40px 30px; color:#000; font-size:16px; line-height:1.5;">
                        <p style="margin:0 0 20px; color: #000;">Hi, <strong>{$user["username"]}</strong>! Thank you for signing up on <strong>Weekie Mochi</strong>!</p>
                        <p style="color: #000;">We welcome you to our little yet warm community.</p>
                        <strong style="font-size: 35px; text-decoration: underline; color: #000;">Just one more step!</strong>
                        <p style="color: #000;">Please verify your email address by clicking the button below:</p>
                        <p style="color: #000; font-size: 12px;">(This link will be valid for two hours. In case it expires, please sign up again or re-send the email through the "Email sent successfully" section after signing up on <a href="https://weekiemochi.com/"><strong>Weekie mochi</strong></a> if possible)</p>
                        <!-- Button -->
                        <table role="presentation" border="0" cellpadding="0" cellspacing="0" style="margin: 20px 0;">
                            <tr>
                            <td align="center">
                                <a href="$redirectUrl"
                                style="display:inline-block; background:#1a73e8; color:#ffffff; text-decoration:none; padding:12px 24px; border-radius:4px; font-weight:bold;">
                                    Verify Email
                                </a>
                                <p style="color:#000;font-size:15px">If the button does not work, please go to this link: </p>
                                <a href="$redirectUrl"
                                    style="padding:12px 24px;">
                                    $redirectUrl
                                </a>
                            </td>
                            </tr>
                        </table>
                        <p style="margin:0 0 20px;color:#000;">If you didn't sign up, please ignore this email.</p>
                        <p style="margin:0;color:#000;">Thanks,<br>The Weekie Mochi Team</p>
                        </td>
                    </tr>
                    <!-- Footer -->
                    <tr>
                        <td style="background:#f2f2f2; text-align:center; color:#999999; font-size:12px; padding:20px;">
                        © $currentYear Weekie Mochi. All rights reserved.
                        </td>
                    </tr>
                    </table>
                </td>
                </tr>
            </table>
            </body>
            </html>
        HTML;
    }


    public function getConfirmPasswordChangeTemplate(array $user, string $verificationToken): string{

        $redirectUrl = "https://weekiemochi.com/users/changePassword/$verificationToken";
        $currentYear = date("Y");

        return <<<HTML
        <!DOCTYPE html>
            <html lang="en">
            <head>
            <meta charset="UTF-8">
            <title>Change your password</title>
            <meta name="viewport" content="width=device-width, initial-scale=1.0">
            <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@100..900&display=swap" rel="stylesheet">
            </head>
            <body style="Margin:0;padding:0;background-color:#fff;">
            <table role="presentation" border="0" cellpadding="0" cellspacing="0" width="100%" style="background-color:#f2f2f2; padding: 20px 0;">
                <tr>
                <td align="center">
                    <!-- Container -->
                    <table role="presentation" border="0" cellpadding="0" cellspacing="0" width="600" style="background-color: #fff; border-radius:8px; overflow:hidden; font-family:'Outfit',Arial,sans-serif;">
                    <!-- Header -->
                    <tr>
                        <td align="center" style="padding: 40px 20px; background-color: #000;">
                        <h1 style="color:#ffffff; font-size:30px; margin:0;">Instructions to change your passsword</h1>
                        </td>
                    </tr>
                    <!-- Body -->
                    <tr>
                        <td style="padding: 40px 30px; color:#000; font-size:16px; line-height:1.5;">
                        <p style="margin:0 0 20px; color: #000;">Hi, <strong>{$user["username"]}</strong>! We received a request from you to change your password.</p>
                        <p style="color: #000;">We send you the instructions to do so.</p>
                        <strong style="font-size: 35px; text-decoration: underline; color: #000;">Change your password</strong>
                        <p style="color: #000;">Please, click the button below (these links will expire in 15 minutes. You can always request them again on the "Change password" on <strong>Weekie Mochi</strong>):</p>
                        <!-- Button -->
                        <table role="presentation" border="0" cellpadding="0" cellspacing="0" style="margin: 20px 0;">
                            <tr>
                            <td align="center">
                                <a href="$redirectUrl"
                                style="display:inline-block; background:#1a73e8; color:#ffffff; text-decoration:none; padding:12px 24px; border-radius:4px; font-weight:bold;">
                                    Change password
                                </a>
                                <p style="color:#000;font-size:15px">If the button does not work, please go to this link: </p>
                                <a href="$redirectUrl"
                                    style="padding:12px 24px;">
                                    $redirectUrl
                                </a>
                            </td>
                            </tr>
                        </table>
                        <p style="margin:0 0 20px;color:#000;">If you didn't request these instructions, please ignore this email. For preventive measures change your password, someone might be trying to get unauthorized access to your account.</p>
                        <p style="margin:0;color:#000;">Thanks,<br>The Weekie Mochi Team</p>
                        </td>
                    </tr>
                    <!-- Footer -->
                    <tr>
                        <td style="background:#f2f2f2; text-align:center; color:#999999; font-size:12px; padding:20px;">
                        © $currentYear Weekie Mochi. All rights reserved.
                        </td>
                    </tr>
                    </table>
                </td>
                </tr>
            </table>
            </body>
            </html>
        HTML;
    }

    public function getPasswordChangeNotificationTemplate(array $user): string{

        $currentYear = date("Y");

        return <<<HTML
        <!DOCTYPE html>
            <html lang="en">
            <head>
            <meta charset="UTF-8">
            <title>Your password was reset</title>
            <meta name="viewport" content="width=device-width, initial-scale=1.0">
            <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@100..900&display=swap" rel="stylesheet">
            </head>
            <body style="Margin:0;padding:0;background-color:#fff;">
            <table role="presentation" border="0" cellpadding="0" cellspacing="0" width="100%" style="background-color:#f2f2f2; padding: 20px 0;">
                <tr>
                <td align="center">
                    <!-- Container -->
                    <table role="presentation" border="0" cellpadding="0" cellspacing="0" width="600" style="background-color: #fff; border-radius:8px; overflow:hidden; font-family:'Outfit',Arial,sans-serif;">
                    <!-- Header -->
                    <tr>
                        <td align="center" style="padding: 40px 20px; background-color: #000;">
                        <h1 style="color:#ffffff; font-size:30px; margin:0;">Password Reset</h1>
                        </td>
                    </tr>
                    <!-- Body -->
                    <tr>
                        <td style="padding: 40px 30px; color:#000; font-size:16px; line-height:1.5;">
                        <p style="margin:0 0 20px; color: #000;">Hi, <strong>{$user["username"]}</strong>! We were just passing by to notify your password has been changed.</p>
                        <p style="margin:0 0 20px;color:#000;">If you didn't make this change, please reset your password or contact our support team by sending an email to <a href="mailto:support-team@weekiemochi.com"> support-team@weekiemochi.com. </a> </p>
                        <p style="margin:0;color:#000;">Thanks,<br>The Weekie Mochi Team</p>
                        </td>
                    </tr>
                    <!-- Footer -->
                    <tr>
                        <td style="background:#f2f2f2; text-align:center; color:#999999; font-size:12px; padding:20px;">
                        © $currentYear Weekie Mochi. All rights reserved.
                        </td>
                    </tr>
                    </table>
                </td>
                </tr>
            </table>
            </body>
            </html>
        HTML;
    }
}
