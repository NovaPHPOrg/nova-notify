<?php

declare(strict_types=1);
/*
 *  Copyright (c) 2023. Ankio. All Rights Reserved.
 */

namespace nova\plugin\notify\phpmail;

/**
 * PHPMailer exception handler.
 *
 * @author Marcus Bointon <phpmailer@synchromedia.co.uk>
 */
class Exception extends \Exception
{
    /**
     * Prettify error message output.
     *
     * @return string
     */
    public function errorMessage(): string
    {
        return '<strong>' . htmlspecialchars($this->getMessage()) . "</strong><br />\n";
    }
}
