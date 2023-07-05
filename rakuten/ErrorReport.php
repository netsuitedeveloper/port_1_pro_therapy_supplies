<?php 

class ErrorReport
{
    private static $_context = 'Rakuten';

    public static function init($context)
    {
        self::$_context = $context;
    }

    public static function add($action)
    {
        $message = func_get_arg(2);
        if ($message instanceof Exception) {
            $message = $message->getMessage();
        }

        $errors = TEMP::popValue(array(), 'errors-' . $action);

        if (is_array($message) == false) {
            $message = array($message);
        }

        foreach($message as $_message) {
            if (stripos($_message, 'Order status is not ‘Unshipped’ or ‘Partially Shipped’ and can not do any operation') !== false) {
                continue;
            }
            $errors[] = array(
                'context' => func_get_arg(1),
                'message' => $_message,
                'datetime' => DT::getPrettyNowString(),
            );
        }

        Log::error("[", func_get_arg(0), '] ', func_get_arg(1), ': ', $message);

        TEMP::pushValue($errors, 'errors-' . $action);
    }

    public static function sendErrorMail($action, $email_from, $email_tos, $template_uri, $content = false) {
        $group_variables = array(
            'ERRORS',
        );
        $variables = array(
            'DATETIME',
            'CONTEXT',
            'MESSAGE',
        );
        $messages = array(
            'ERRORS' => TEMP::popValue(array(), 'errors-' . $action)
        );

        if ($messages['ERRORS']) {
            $mail = array(
                'subject' =>  self::$_context . ' Rakuten Integration Error Report [' . $action . ']',
                'from' => $email_from,
                'to' => $email_tos,
                'content-type' => 'text/html',
            );

            Log::data('error mail: ', $messages);

            MailClient::sendGroupedTemplateMail($mail, $template_uri, $group_variables, $variables, $messages);

            TEMP::clear('errors-' . $action);
        }
    }


}

