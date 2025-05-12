<?php

namespace app\Enum;

enum Otp: string
{
    case REGISTER = "register";
    case FORGOT_PASSWORD = "forgot";
}
