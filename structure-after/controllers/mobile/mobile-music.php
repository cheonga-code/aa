<?php
/**
 * @File Info : 모바일에서 음악과 관련된 기능을 처리하는 파일
 * @File Function list
 *  - @음악 관련 기능
 *      └ 음악 간단 목록 조회
 */

//음악 SQL 클래스 파일 연결
require_once('../models/SqlMusic.php');
//음악 SQL 클래스 객체 생성
$SqlMusic = new SqlMusic();

//요청 API URL 의 $key 값에 따라, 클라에서 요청한 세부 기능을 구분한다
switch ($key){

    /**
     * @ 음악 : 음악 관련 기능
     *
     * @ 기능 리스트
     *  - 음악 간단 목록 조회 | pre-list /GET
     *  - 음악 전체 목록 조회 | detail-list /GET
     */

    //음악 간단 목록 조회
    case 'pre-list':

        switch ($_SERVER['REQUEST_METHOD']){

            case 'GET' :

                try {

                    //클라한테 받은 데이터
                    $type = $param_str1;  //타입
                    $request_count = $param_int1;  //요청 데이터 갯수

                    //클라한테 받은 데이터 유효성 검사
                    if(!isset($request_count) || !is_numeric($request_count) || !isset($type) || !is_string($type)){
                        $response = new Response();
                        $response->setHttpStatusCode(400);
                        $response->setSuccess(false);
                        $response->addMessage("Data is not valid");
                        $response->send();
                        exit;
                    }

                    //최신 데이터 리턴
                    if($type == 'new'){

                        /*** SQL - 음악 테이블(MUSICS)에서 최신순으로, 데이터 요청 갯수만큼 비디오 목록 데이터 조회 ***/
                        $query_music = $SqlMusic->selectMusicPreListNew($request_count);

                    }
                    //추천 데이터 리턴
                    elseif ($type == 'reco'){

                        /*** SQL - 플레이리스트 테이블(PLAYLIST)에서 데이터 요청 갯수만큼 (인디즈 운영자가 만든) 플레이리스트 목록 데이터 조회 ***/
                        //$query_music = $SqlMusic->selectPlaylistPreList($request_count);

                    }
                    else{
                        //클라한테 받은 데이터 $type 값이 유효하지 않은 경우
                        $response = new Response();
                        $response->setHttpStatusCode(400);
                        $response->setSuccess(false);
                        $response->addMessage("Data is not valid");
                        $response->send();
                        exit;
                    }

                    //음악 목록 데이터를 담는 배열 선언
                    $array_music_list = array();

                    while ($music = $query_music->fetch(PDO::FETCH_ASSOC)) {

                        array_push($array_music_list, array(
                            'music_id' => $music['music_PK'],                           //음악 PK
                            'music_name' => $music['music_name'],                       //음악 제목
                            'music_artist_name' => $music['music_artist_name'],         //음악 아티스트 이름
                            'music_photo_path' => $music['music_photo_path'],           //음악 표지 이미지 경로
                            'music_streaming_path' => $music['music_streaming_path'],   //음악 음원 경로
                            'music_registerday' => $music['music_registerday'],         //음악 등록 일시
                            'user_id' => $music['user_PK'],                             //음악 작성자 PK
                            'user_nickname' => $music['user_nickname'],                 //음악 작성자 닉네임
                            'user_photo_path' => $music['user_photo_path']              //음악 작성자 프로필 이미지 경로
                        ));
                    }

                    //데이터 조회에 성공했을 경우 - 성공 리턴
                    $response = new Response();
                    $response->setHttpStatusCode(200);
                    $response->setSuccess(true);
                    $response->toCache(true);
                    $response->setData($array_music_list);
                    $response->addMessage("Success ok");
                    $response->send();
                    exit;

                }
                catch (PDOException $ex) {

                    $response = new Response();
                    $response->setHttpStatusCode(500);
                    $response->setSuccess(false);
                    $response->addMessage("DB error - ".$ex);
                    $response->send();
                    exit;
                }

            default:
                $response = new Response();
                $response->setHttpStatusCode(405);
                $response->setSuccess(false);
                $response->addMessage("Request method not allowed");
                $response->send();
                exit;
        }
        break;
        
    //음악 전체 목록 조회
    case 'detail-list':

        switch ($_SERVER['REQUEST_METHOD']){

            case 'GET' :

                try {

                    //클라한테 받은 데이터
                    $type = $param_str1;  //타입
                    $page = $param_int1;  //페이징 숫자

                    //클라한테 받은 데이터 유효성 검사
                    if(!isset($page) || !is_numeric($page) || !isset($type) || !is_string($type)){
                        $response = new Response();
                        $response->setHttpStatusCode(400);
                        $response->setSuccess(false);
                        $response->addMessage("Data is not valid");
                        $response->send();
                        exit;
                    }

                    /*** SQL - 음악 테이블(MUSICS)에서 음악 게시물 총 갯수 조회 ***/
                    $query_music = $SqlMusic->selectMusicCount();
                    $row = $query_music->fetch(PDO::FETCH_ASSOC);

                    //10개씩 페이징 처리
                    $limit_per_page = 10;

                    $music_count = intval($row['music_count']);

                    $num_of_page = ceil($music_count / $limit_per_page);

                    if ($num_of_page == 0) {
                        $num_of_page = 1;
                    }

                    if ($page > $num_of_page || $page == 0) {
                        $response = new Response();
                        $response->setHttpStatusCode(404);
                        $response->setSuccess(false);
                        $response->addMessage("Page not found");
                        $response->send();
                        exit;
                    }

                    $offset = ($page == 1 ? 0 : ($limit_per_page * ($page - 1)));

                    //최신 데이터 리턴
                    if($type == 'new'){

                        /*** SQL - 음악 테이블(MUSICS)에서 최신순으로, 페이징 처리하여 비디오 목록 데이터 조회 ***/
                        $query_music = $SqlMusic->selectMusicDetailListNew($limit_per_page, $offset);

                    }
                    //추천 데이터 리턴
                    elseif ($type == 'hot'){

                        /*** SQL - 음악 테이블(MUSICS)에서 인기순으로, 페이징 처리하여 비디오 목록 데이터 조회 ***/
                        $query_music = $SqlMusic->selectMusicDetailListHot($limit_per_page, $offset);

                    }
                    else{
                        //클라한테 받은 데이터 $type 값이 유효하지 않은 경우
                        $response = new Response();
                        $response->setHttpStatusCode(400);
                        $response->setSuccess(false);
                        $response->addMessage("Data is not valid");
                        $response->send();
                        exit;
                    }

                    //음악 목록 데이터를 담는 배열 선언
                    $array_music_list = array();

                    while ($music = $query_music->fetch(PDO::FETCH_ASSOC)) {

                        array_push($array_music_list, array(
                            'music_id' => $music['music_PK'],                           //음악 PK
                            'music_name' => $music['music_name'],                       //음악 제목
                            'music_artist_name' => $music['music_artist_name'],         //음악 아티스트 이름
                            'music_photo_path' => $music['music_photo_path'],           //음악 표지 이미지 경로
                            'music_streaming_path' => $music['music_streaming_path'],   //음악 음원 경로
                            'music_registerday' => $music['music_registerday'],         //음악 등록 일시
                            'user_id' => $music['user_PK'],                             //음악 작성자 PK
                            'user_nickname' => $music['user_nickname'],                 //음악 작성자 닉네임
                            'user_photo_path' => $music['user_photo_path']              //음악 작성자 프로필 이미지 경로
                        ));
                    }

                    //데이터 조회에 성공했을 경우 - 성공 리턴
                    $response = new Response();
                    $response->setHttpStatusCode(200);
                    $response->setSuccess(true);
                    $response->toCache(true);
                    $response->setData($array_music_list);
                    $response->addMessage("Success ok");
                    $response->send();
                    exit;

                }
                catch (PDOException $ex) {

                    $response = new Response();
                    $response->setHttpStatusCode(500);
                    $response->setSuccess(false);
                    $response->addMessage("DB error - ".$ex);
                    $response->send();
                    exit;
                }

            default:
                $response = new Response();
                $response->setHttpStatusCode(405);
                $response->setSuccess(false);
                $response->addMessage("Request method not allowed");
                $response->send();
                exit;
        }
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