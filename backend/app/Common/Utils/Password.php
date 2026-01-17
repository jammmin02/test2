<?php

namespace Tripmate\Backend\Common\Utils;

// 해쉬
class Password
{
    // 비밀번호 해쉬
    public static function hash($pwd): string
    {
        //비밀번호 해쉬
        return \password_hash((string) $pwd, PASSWORD_BCRYPT);
    }

    // 비밀번호 검증
    public static function verify($pwd, $pwdHash): bool
    {
        // 비밀번호 검증
        return \password_verify((string) $pwd, (string) $pwdHash);
    }
}
