<?php
/**
 * @File Info : 모바일에서 피드와 관련된 기능을 처리하는 파일
 * @File Function list
 *  - @피드 관련 기능
 *      └ 피드 간단 목록 조회
 *      └ 피드 전체 목록 조회
 *      └ 피드 게시글 추가, 수정, 삭제, 조회
 *      └ 피드 좋아요 추가, 취소
 *  - @피드 댓글 관련 기능
 *      └ 피드 댓글 목록 조회
 *      └ 피드 댓글 추가, 수정, 삭제
 *      └ 피드 댓글 좋아요 추가, 취소
 *  - @피드 대댓글 관련 기능
 *      └ 피드 대댓글 목록 조회
 *      └ 피드 대댓글 추가, 수정, 삭제
 */

//피드 SQL 클래스 파일 연결
require_once('../models/SqlFeed.php');
//피드 SQL 클래스 객체 생성
$SqlFeed = new SqlFeed();

//요청 API URL 의 $key 값에 따라, 클라에서 요청한 세부 기능을 구분한다
switch ($key){

    /**
     * @ 피드 : 피드 관련 기능
     *
     * @ 기능 리스트
     *  - 피드 간단 목록 조회 | pre-list /GET
     *  - 피드 전체 목록 조회 | detail-list /GET
     *  - 피드 게시글 추가, 수정, 삭제, 조회 | feed /POST, PATCH, DELETE, GET
     *  - 피드 좋아요 추가, 취소 | like /POST, DELETE
     */

    //피드 간단 목록 조회
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

                        /*** SQL - 피드 테이블(FEED)에서 최신순으로, 데이터 요청 갯수만큼 피드 목록 데이터 조회 ***/
                        $query_feed = $SqlFeed->selectFeedPreListNew($request_count);

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

                    //피드 목록 데이터를 담는 배열 선언
                    $array_feed_list = array();

                    while ($feed = $query_feed->fetch(PDO::FETCH_ASSOC)) {

                        array_push($array_feed_list, array(
                            'feed_id' => $feed['feed_PK'],                  //피드 PK
                            'text' => $feed['text'],                        //피드 내용
                            'photo_path' => $feed['photo_path'],            //피드 이미지 경로
                            'register_time' => $feed['register_time'],      //피드 승인 일시
                            'user_id' => $feed['user_PK'],                  //피드 작성자 PK
                            'user_nickname' => $feed['user_nickname'],      //피드 작성자 닉네임
                            'user_photo_path' => $feed['user_photo_path']   //피드 작성자 프로필 이미지 경로
                        ));
                    }

                    //데이터 조회에 성공했을 경우 - 성공 리턴
                    $response = new Response();
                    $response->setHttpStatusCode(200);
                    $response->setSuccess(true);
                    $response->toCache(true);
                    $response->setData($array_feed_list);
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

    //피드 전체 목록 조회 (페이징 처리)
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

                    /*** SQL - 피드 테이블(FEED)에서 피드 게시물 총 갯수 조회 ***/
                    $query_feed = $SqlFeed->selectFeedCount();
                    $row = $query_feed->fetch(PDO::FETCH_ASSOC);

                    //10개씩 페이징 처리
                    $limit_per_page = 10;

                    $feed_count = intval($row['feed_count']);

                    $num_of_page = ceil($feed_count / $limit_per_page);

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

                        /*** SQL - 피드 테이블(FEED)에서 최신순으로 피드 목록 데이터 조회 ***/
                        $query_feed = $SqlFeed->selectFeedDetailListNew($returned_userid, $limit_per_page, $offset);

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

                    //피드 목록 데이터를 담는 배열 선언
                    $array_feed_list = array();

                    while ($feed = $query_feed->fetch(PDO::FETCH_ASSOC)) {

                        /*** SQL - 피드 댓글 테이블(FEED_REVIEW)에서 해당 피드의 댓글 갯수 조회 ***/
                        $query_feed_review = $SqlFeed->selectFeedReviewCount($feed['feed_PK']);
                        $feed_review = $query_feed_review->fetch(PDO::FETCH_ASSOC);

                        /*** SQL - 피드 좋아요 테이블(FEED_LIKE)에서 해당 피드의 좋아요 갯수 조회 ***/
                        $query_feed_like = $SqlFeed->selectFeedLikeCount($feed['feed_PK']);
                        $feed_like = $query_feed_like->fetch(PDO::FETCH_ASSOC);

                        array_push($array_feed_list, array(
                            'feed_id' => $feed['feed_PK'],                  //피드 PK
                            'text' => $feed['text'],                        //피드 내용
                            'photo_path' => $feed['photo_path'],            //피드 이미지 경로
                            'photo_number' => $feed['photo_number'],        //피드 사진 갯수
                            'register_time' => $feed['register_time'],      //피드 등록 날짜
                            'review_count' => $feed_review['review_count'], //피드 댓글 갯수
                            'like_count' => $feed_like['like_count'],       //피드 좋아요 갯수
                            'like_check' => $feed['like_check'],            //피드에 좋아요 했는지 여부
                            'user_id' => $feed['user_PK'],                  //피드 게시자의 PK
                            'user_nickname' => $feed['user_nickname'],      //피드 게시자의 닉네임
                            'user_photo_path' => $feed['user_photo_path']   //피드 게시자의 프로필 이미지 경로
                        ));
                    }

                    //데이터 조회에 성공했을 경우 - 성공 리턴
                    $response = new Response();
                    $response->setHttpStatusCode(200);
                    $response->setSuccess(true);
                    $response->toCache(true);
                    $response->setData($array_feed_list);
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

    //피드 게시글 추가, 수정, 삭제, 조회
    case 'feed':

        switch ($_SERVER['REQUEST_METHOD']){

            //피드 게시글 추가
            case 'POST' :

            //피드 게시글 수정
            case 'PATCH' :

            //피드 게시글 삭제
            case 'DELETE' :

            //피드 게시글 조회
            case 'GET' :

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