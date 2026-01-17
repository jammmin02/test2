<?php

namespace Tripmate\Backend\Modules\Users\Controllers;

use Tripmate\Backend\Core\Controller;
use Tripmate\Backend\Core\Request;
use Tripmate\Backend\Core\Response;
use Tripmate\Backend\Core\Validator;
use Tripmate\Backend\Modules\Users\Services\UsersService;

/**
 * 유저 관리(정보 조회, 회원 탈퇴)
 */
class UsersController extends Controller
{
    private readonly UsersService $usersService;
    private readonly Validator $validator;

    public function __construct(Request $request, Response $response)
    {
        parent::__construct($request, $response);
        $this->usersService = new UsersService();
        $this->validator = new Validator();
    }

    /**
     * 내 정보 조회 컨트롤러
     * - 토큰 검증 후 사용자 정보 반환
     * - 성공 시 데이터(200OK, email, nickname) 반환
     */
    public function userMyPage(): Response
    {
        return $this->run(function () {
            $userId = $this->getUserId(); // 토큰 검증

            $result = $this->usersService->myPage($userId);

            return $result;
        });
    }

    /**
     * 회원 탈퇴 컨트롤러
     * 토큰 검증 후 회원 삭제
     * 성공 시 200 NoContent 반환
     */
    public function userSecession(): Response
    {
        return $this->run(function (): Response {
            $userId = $this->getUserId(); // 토큰 검증

            $data = $this->request->body();
            $this->validator->validatePassword($data);
            $password = $data['password'];

            $this->usersService->secession($userId, $password);

            return $this->response->noContent();
        });
    }
}
