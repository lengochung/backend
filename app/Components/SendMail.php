<?php
namespace App\Components;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\URL;
use Exception;

class SendMail
{
    public function __construct()
    {
    }

    /**
     * send mail
     *
     * @param object $mailData
     * [
     *      'subject' => string, //Subject email
     *      'mail_to_name' => string, //Email recipient's name
     *      'mail_to' => string | array,   //Email sent to (Ex array: ['m1@gmail.com', 'm2@gmail.com, ...'])
     *      'from_name' => '', //Email from name
     *      'from_mail' => '', //Email sent from
     *      'mail_cc' => string | array, //Email CC (Ex array: ['m1@gmail.com', 'm2@gmail.com, ...'])
     *      'attachments' =>
     *      [
     *          [
     *              file_path => '', // Attach file path,
     *              file_name => '', // Attach file name
     *              file_type => '', // Attach file type (ex: application/pdf, image/png,....)
     *          ]
     *      ],
     *      'mail_content' => '', //Email content
     *      'assign_data_to_view' => array, // data to display on view
     *      'view' => '', // file name from /resources
     * ]
     *
     * @returs boolean
     */
    public function sendMail($mailData = null){
        try {
            if (!$mailData) {
                return false;
            }
            //Subject email
            $subject = (!empty($mailData['subject'])) ? $mailData['subject'] : '';
            //Email recipient's name
            $mailToName = (!empty($mailData['mail_to_name'])) ? $mailData['mail_to_name'] : '';
            //Email sent to
            $mailTo = (!empty($mailData['mail_to'])) ? $mailData['mail_to'] : '';
            //Email CC
            $mailCc = (!empty($mailData['mail_cc'])) ? $mailData['mail_cc'] : '';
            //Email from name
            $fromName = '';
            if (!empty($mailData['from_name'])) {
                $fromName = $mailData['from_name'];
            }
            //Email sent from
            $fromEmail = '';
            if (!empty($mailData['from_mail'])) {
                $fromEmail = $mailData['from_mail'];
            }
            //Email content
            $mailContent = (!empty($mailData['mail_content'])) ? $mailData['mail_content'] : '';
            //Attachments
            $attachments = (!empty($mailData['attachments'])) ? $mailData['attachments'] : '';
            /**
             *  View contains email content from folder: /resources/views/email/
             *  Ex: $view = "email.forgotpassword";
             *  email: forder name from /resources/views
             *  forgotpassword: file name .blade
             */
            $view = (!empty($mailData['view'])) ? $mailData['view'] : '';
            if ($view) {
                //Assign data to view
                $assignDataToView = (!empty($mailData['assign_data_to_view'])) ? $mailData['assign_data_to_view'] : '';
                if ($assignDataToView) {
                    $mailContent = view($view, $assignDataToView)->render();
                } else {
                    $mailContent = view($view)->render();
                }
            }
            //Check data input
            if (
                !$subject
                || !$mailTo
                || !$mailContent
            ) {
                return false;
            }
            Mail::send([], [], function($message) use (
                $subject,
                $fromName,
                $fromEmail,
                $mailToName,
                $mailTo,
                $mailCc,
                $attachments,
                $mailContent
            ) {
                if ($mailToName) {
                    $message->to($mailTo, $mailToName)->subject($subject);
                } else {
                    $message->to($mailTo)->subject($subject);
                }
                if ($fromEmail) {
                    $message->from($fromEmail, $fromName);
                }
                if ($mailCc) {
                    $message->cc($mailCc);
                }
                if ($attachments && is_array($attachments) && count($attachments) > 0) {
                    foreach($attachments as $attachment) {
                        if (
                            empty($attachment['file_path'])
                            || empty($attachment['file_name'])
                        ) {
                            return;
                        }
                        $option['as'] = $attachment['file_name'];
                        if (!empty($attachment['file_type'])) {
                            $option['mime'] = $attachment['file_type'];
                        }
                        $message->attach($attachment['file_path'], $option);
                    }
                }
                $message->html($mailContent);
                $message->text($mailContent);
            });
            if (Mail::failures()) {
                return false;
            }
            return true;
        } catch (Exception $e) {
            Log::error($e->getMessage());
            return false;
        }
    }
}
