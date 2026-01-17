<?php
    namespace Tripmate\Backend\config;

    // APP_NAME  애플리케이션 이름 설정
    define('APP_NAME', 'Tripmate');

    // 애플리케이션 환경('development-개발'/'production-실서비스'/'staging-테스트')
    define('APP_ENV', 'development');

    // 디버그 모드(debug mode)
    define('APP_DEBUG', true); // true-상세한 오류/ false-실 서비스

    if (APP_DEBUG) {
        // 디버그 모드가 true이면 모든 에러를 화면에 표시
        ini_set('display_errors', 1);
        error_reporting(E_ALL);
    } else {
        // 디버그 모드가 false이면 에러를 화면에 표시하지 않음
        ini_set('display_errors', 0);
        error_reporting(0);
    }
