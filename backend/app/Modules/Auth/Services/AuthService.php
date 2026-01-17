<?php

namespace Tripmate\Backend\Modules\Auth\Services;

use Tripmate\Backend\Common\Exceptions\DbException;
use Tripmate\Backend\Common\Exceptions\HttpException;
use Tripmate\Backend\Common\Middleware\AuthMiddleware as amw;
use Tripmate\Backend\Common\Utils\Password;
use Tripmate\Backend\Core\DB;
use Tripmate\Backend\Core\Service;
use Tripmate\Backend\Modules\Auth\Repositories\UserRepository;

/**
 *  유저관리(회원가입, 로그인, 로그아웃) 서비스
 */
class AuthService extends Service
{
    private readonly UserRepository $userRepository;

    /**
     * 생성자 호출
     * - 부모 생성자 PDO 주입 및 리포지토리 초기화
     */
    public function __construct()
    {
        parent::__construct(DB::conn());
        $this->userRepository = new UserRepository($this->db);
    }

    /**
     * 회원가입 서비스
     * - 비밀번호 해쉬 후 DB 저장 호출
     * @return string
     */
    public function registerUser(array $data)
    {

        try {
            return $this->transaction(function () use ($data) {
                $email = $data['email'];
                $password = $data['password'];
                $nickname = $data['nickname'];

                // 이메일 중복 확인
                $normalizedEmail = \strtolower($email); // 이메일 정규화
                $result = $this->userRepository->findEmail($normalizedEmail);
                if ($result !== 0) {
                    throw new HttpException(409, 'USER_DUPLICATE_EMAIL', '이미 사용중인 이메일입니다.');
                }

                // 회원 데이터 저장
                $hashedPassword = Password::hash($password);
                return $this->userRepository->createUser($normalizedEmail, $hashedPassword, $nickname);
            });
        } catch (DbException $e) {
            throw new HttpException(500, 'UNEXPECTED_ERROR', '회원가입 중 알 수 없는 에러가 발생하였습니다.', $e);
        }
    }

    /**
     * 로그인 서비스
     * - DB 검증 후 토큰 값 반환
     * @param array $data
     * @return string
     */
    public function loginUser($data)
    {
        try {
            return $this->transaction(function () use ($data): string {
                $email = $data['email'];
                $password = $data['password'];

                // 유저 검증
                $userId = $this->userRepository->findUser($email, $password);

                return amw::tokenRequest($userId);
            });
        } catch (DbException $e) {
            switch ($e->getCodeName()) {
                case 'INVALID_CREDENTIALS':
                    throw new HttpException(400, 'INVALID_CREDENTIALS', '이메일 또는 비밀번호가 올바르지 않습니다.');
                default:
                    throw new HttpException(500, 'LOGIN_ERROR', '로그인 도중 알 수 없는 에러가 발생하였습니다.');
            }
        }
    }
}
