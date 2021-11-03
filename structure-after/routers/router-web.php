<?php
/**
 * @File Info : 웹에서 오는 요청을 처리하는 라우터 파일
 * @Design Pattern : MVC
 * @Router Structure
 *
 */

//TODO : 추후 삭제 예정 (데이터 확인용)
//echo "router-web.php 진입"."\n";
//echo var_dump($_GET)."\n";

//예외 처리 : 배열 $_GET 의 $value 에 '/' 값이 있을 경우
foreach($_GET as $value){
    if(strpos($value, '/') !== false) {
        //잘못된 API URL 요청
        $response = new Response();
        $response->addMessage("Endpoint not found");
        $response->setHttpStatusCode(404);
        $response->setSuccess(false);
        $response->send();
        exit;
    }
}

//요청 API URL에 적혀있는 '필수적' 데이터를 변수에 담는다
$version = $_GET['version'];        //버전명 ex) v1, v2 ..
$function = $_GET['function'];      //기능명 ex) music, feed ..

//요청 API URL에 적혀있는 '선택적' 데이터를 변수에 담는다
$key = $_GET['key'];                //키값 ex) review, review-like ..
$param_int1 = $_GET['param_int1'];  //파라미터 Int형 값1
$param_int2 = $_GET['param_int2'];  //파라미터 Int형 값2
$param_str1 = $_GET['param_str1'];  //파라미터 String형 값1
$param_str2 = $_GET['param_str2'];  //파라미터 String형 값2

//$key 변수에 값을 대입한다
if(array_key_exists('key', $_GET)){
    //요청 API URL에 key 값이 있다면, $key 변수에 $_GET['key'] 데이터를 담는다 ex) review, review-like ..
    $key = $key;
}
else{
    //요청 API URL에 key 값이 없다면, $key 변수에 $_GET['function'] 데이터를 담는다 ex) music, feed ..
    $key = $function;
}

//헤더에 있는 토큰 검사
$auth = new user_auth();
$auth_data = $auth->auth_check(2);

//토큰을 통해 얻은 사용자 PK , 프로필, 닉네임
$returned_userid = $auth_data['user_id'];
$returned_profile = $auth_data['user_profile'];
$returned_usernickname = $auth_data['user_nickname'];

//TODO : 추후 삭제 예정 (에러 확인용)
//error_reporting(E_ALL);
//ini_set("display_errors", 1);

try {
    $writeDB = DB::ConnectWriteDB();
    $readDB = DB::ConnectReadDB();

} catch (PDOException $ex) {
    error_log("Connection error -" . $ex, 0);
    $response = new Response();
    $response->setHttpStatusCode(500);
    $response->setSuccess(false);
    $response->addMessage("Database connection error");
    $response->send();
    exit();
}

switch ($function) {

    //음악 기능
    case 'music':
        require_once('../controllers/web/web-music.php');
        break;

    //피드 기능
    case 'feed':
        require_once('../controllers/web/web-feed.php');
        break;

    //클라에서 요청하는 API URL 이 존재하지 않을 경우
    default:
        $response = new Response();
        $response->setHttpStatusCode(404);
        $response->setSuccess(false);
        $response->addMessage("Endpoint not found");
        $response->send();
        exit;
}

?>