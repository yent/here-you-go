<?php


namespace HereYouGo\Auth\SP\Internal;


use HereYouGo\Enum;

class States extends Enum {
    const NONE                  = '';
    const MISSING_CREDENTIAL    = 'missing_credentials';
    const UNKNOWN_USER          = 'unknown_user';
}