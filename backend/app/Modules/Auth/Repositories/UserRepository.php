<?php

namespace Tripmate\Backend\Modules\Auth\Repositories;

use PDO;
use Tripmate\Backend\Common\Exceptions\DbException;
use Tripmate\Backend\Common\Utils\Password;
use Tripmate\Backend\Core\Repository;

/**
 * 유저 관리(회원가입, 로그인) 리포지토리
 */
class UserRepository extends Repository
{
    public function __construct(PDO $pdo)
    {
        parent::__construct($pdo);
    }

    // 이메일 중복 검증 로직
    public function findEmail($normalizedEmail): int
    {
        $selectSql = 'SELECT email_norm FROM Users WHERE email_norm = :email';
        $selectParm = ['email' => $normalizedEmail];
        return $this->execute($selectSql, $selectParm);
    }

    // 회원 저장 로직
    public function createUser(string $normalizedEmail, string $hashedPassword, string $nickname): void
    {
        // 유저 생성
        $insertSql = 'INSERT INTO Users (email_norm, password_hash, name) VALUES (:email_norm, :password_hash, :name);';
        $insertParm = ['email_norm' => $normalizedEmail, 'password_hash' => $hashedPassword, 'name' => $nickname];
        $this->query($insertSql, $insertParm);
    }

    // 로그인 로직
    public function findUser($email, $password)
    {
        $selectSql = 'SELECT user_id, password_hash FROM Users WHERE email_norm = :email;';
        $selectParm = ['email' => $email];
        $data = $this->fetchOne($selectSql, $selectParm);

        // 이메일 조회 반환 값이 없을 경우
        if (!$data) {
            throw new DbException('INVALID_CREDENTIALS', '이메일 또는 비밀번호가 올바르지 않습니다.');
        }
        $userId = $data['user_id'];
        $pwdHash = $data['password_hash'];
        \error_log($userId);

        // 비밀번호 검증
        if ((Password::verify($password, $pwdHash)) === false) {
            throw new DbException('INVALID_CREDENTIALS', '이메일 또는 비밀번호가 올바르지 않습니다.');
        }

        return $userId;
    }
}
