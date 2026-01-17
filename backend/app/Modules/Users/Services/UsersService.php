<?php

namespace Tripmate\Backend\Modules\Users\Services;

use Tripmate\Backend\Common\Exceptions\DbException;
use Tripmate\Backend\Common\Exceptions\HttpException;
use Tripmate\Backend\Core\DB;
use Tripmate\Backend\Core\Service;
use Tripmate\Backend\Modules\Users\Repositories\UsersReadRepository;

/**
 * 유저관리 서비스
 */
class UsersService extends Service
{
    private readonly UsersReadRepository $usersReadRepository;

    public function __construct()
    {
        parent::__construct(DB::conn());
        $this->usersReadRepository = new UsersReadRepository($this->db);
    }

    /**
     * 내 정보 조회 서비스
     * @return array{created_at: mixed, email: mixed, nickname: mixed|string}
     */
    public function myPage(mixed $userId)
    {
        try {
            return $this->transaction(function () use ($userId) {
                $result = $this->usersReadRepository->find($userId);
                if ($result == null) {
                    throw new HttpException(404, 'USER_NOT_FOUNT', '해당 유저를 찾을 수 없어 조회에 실패했습니다.');
                }

                return $result;
            });
        } catch (DbException $e) {
            throw new HttpException(500, 'NOT_USERPAGE_DATA', '페이지의 데이터를 불러오는데에 실패했습니다.', $e);
        }
    }

    /**
     * 회원탈퇴 서비스
     * @return string
     */
    public function secession(mixed $userId, $password)
    {
        try {
            return $this->transaction(function () use ($userId, $password): void {
                $result = $this->usersReadRepository->delete($userId, $password);
                if ($result === 0) {
                    throw new HttpException(404, 'USER_NOT_FOUND', '삭제할 유저를 찾을 수 없습니다.');
                }
            });
        } catch (DbException $e) {
            throw new HttpException(500, 'USER_DELETE_FAIL', '회원 삭제에 실패하였습니다.', $e);
        }
    }
}
