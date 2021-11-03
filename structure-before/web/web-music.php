<?php

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

//달별로 최신음악을 조회하는 코드
if(array_key_exists('get-music-list-month-new', $_GET)){

    if($_SERVER['REQUEST_METHOD']=='GET'){

        try{


            //클라이언트에서 받은 page값
            $page = $_GET['page'];
            //최신 음악 리스트 : 월
            $month = $_GET['month'];
            //최신 음악 리스트 : 년도
            $year = $_GET['year'];
            //승인된 곡만 가져오기 위해서 'approve' 변수 만든다
            $state = 'approve';

            //검색하고자 하는 최신곡 리스트의 시작 날짜
            $start_day = $year . '-' . $month .'-' .  '01';
            //검색하고자 하는 최신곡 리스트의 끝 날짜
            $end_day = date('Y-m-d', strtotime('+1 month', strtotime($start_day)));

            //헤더에 있는 토큰 검사
            $auth = new user_auth();
            $auth_data = $auth->auth_check(2);

            //헤더에 사용자 세션 토큰이 없는 경우
            if($auth_data == null) {


                if ($page == '' || !is_numeric($page)) {
                    $response = new Response();
                    $response->setHttpStatusCode(400);
                    $response->setSuccess(false);
                    $response->addMessage("Page number cannot be blank and must be numeric");
                    $response->send();
                    exit;
                }

                //댓글 10개씩 페이징 처리
                $limitPerPage = 100;


                $query = $readDB->prepare("select count(*) as totalNoOfTasks  from MUSICS, USERS where MUSICS.user_id = USERS.user_PK and  music_registerday>=:start_day and music_registerday<:end_day and music_state =:state ");
                $query->bindParam(':state', $state);
                $query->bindParam(':start_day', $start_day);
                $query->bindParam(':end_day', $end_day);
                $query->execute();

                $row = $query->fetch(PDO::FETCH_ASSOC);
                //총개수
                $musicCount = intval($row['totalNoOfTasks']);


                $numOfPages = ceil($musicCount / $limitPerPage);


                if ($numOfPages == 0) {
                    $numOfPages = 1;
                }

                if ($page > $numOfPages || $page == 0) {
                    $response = new Response();
                    $response->setHttpStatusCode(404);
                    $response->setSuccess(false);
                    $response->addMessage("Page not found");
                    $response->send();
                    exit;
                }

                $offset = ($page == 1 ? 0 : ($limitPerPage * ($page - 1)));


                $query = $readDB->prepare('select MUSICS.* from MUSICS, USERS where MUSICS.user_id = USERS.user_PK and  music_registerday>=:start_day and music_registerday<:end_day and music_state =:state order by music_registerday desc limit :pglimit offset :offset');
                $query->bindParam(':state', $state);
                $query->bindParam(':start_day', $start_day);
                $query->bindParam(':end_day', $end_day);
                $query->bindParam(':pglimit', $limitPerPage, PDO::PARAM_INT);
                $query->bindParam(':offset', $offset, PDO::PARAM_INT);
                $query->execute();


                $data = array();

                while ($row = $query->fetch(PDO::FETCH_ASSOC)) {

                    $like_check='off';


                    //좋아요 갯수를 구한다
                    $query1 = $readDB->prepare('select count(*) as likeCount from LIKES_MUSIC where music_id=:music_id');
                    $query1->bindParam(':music_id', $row['music_PK']);
                    $query1->execute();

                    $row1 = $query1->fetch(PDO::FETCH_ASSOC);
                    $like_count = $row1['likeCount'];

                    array_push($data, array(

                        'music_PK' => $row['music_PK'],
                        'user_PK' => $row['user_id'],
                        'music_name' => $row['music_name'],
                        'music_artist_name' => $row['music_artist_name'],
                        'music_sub_artist_name' => $row['music_sub_artist_name'],
                        'music_writer' => $row['music_writer'],
                        'music_composer' => $row['music_composer'],
                        'music_arranger' => $row['music_arranger'],
                        'music_genre' => $row['music_genre'],
                        'music_mood' => $row['music_mood'],
                        'music_photo_path' => $row['music_photo_path'],
                        'music_liric' => $row['music_liric'],
                        'music_registerday' => $row['music_registerday'],
                        'music_introduction' => $row['music_introduction'],
                        'music_state' => $row['music_state'],
                        'music_streaming_path' => $row['music_streaming_path'],
                        'album_PK' => $row['album_PK'],
                        'like_check'=>$like_check,
                        'like_count'=>$like_count,
                        'total_count' => $musicCount
                    ));
                }


                $response = new Response();
                $response->setHttpStatusCode(200);
                $response->setSuccess(true);
                $response->addMessage("datas");
                $response->setData($data);
                $response->send();
                exit;
            }

            else {

                if ($page == '' || !is_numeric($page)) {
                    $response = new Response();
                    $response->setHttpStatusCode(400);
                    $response->setSuccess(false);
                    $response->addMessage("Page number cannot be blank and must be numeric");
                    $response->send();
                    exit;
                }

                //댓글 10개씩 페이징 처리
                $limitPerPage = 100;


                $query = $readDB->prepare("select count(*) as totalNoOfTasks  from MUSICS, USERS where MUSICS.user_id = USERS.user_PK and  music_registerday>=:start_day and music_registerday<:end_day and music_state =:state ");
                $query->bindParam(':state', $state);
                $query->bindParam(':start_day', $start_day);
                $query->bindParam(':end_day', $end_day);
                $query->execute();

                $row = $query->fetch(PDO::FETCH_ASSOC);
                //음악 총개수
                $musicCount = intval($row['totalNoOfTasks']);

                //페이지 갯수
                $numOfPages = ceil($musicCount / $limitPerPage);


                if ($numOfPages == 0) {
                    $numOfPages = 1;
                }

                if ($page > $numOfPages || $page == 0) {
                    $response = new Response();
                    $response->setHttpStatusCode(404);
                    $response->setSuccess(false);
                    $response->addMessage("Page not found");
                    $response->send();
                    exit;
                }

                $offset = ($page == 1 ? 0 : ($limitPerPage * ($page - 1)));


                $query = $readDB->prepare('select MUSICS.* from MUSICS, USERS where MUSICS.user_id = USERS.user_PK and  music_registerday>=:start_day and music_registerday<:end_day and music_state =:state order by music_registerday desc limit :pglimit offset :offset');
                $query->bindParam(':state', $state);
                $query->bindParam(':start_day', $start_day);
                $query->bindParam(':end_day', $end_day);
                $query->bindParam(':pglimit', $limitPerPage, PDO::PARAM_INT);
                $query->bindParam(':offset', $offset, PDO::PARAM_INT);
                $query->execute();


                $data = array();

                while ($row = $query->fetch(PDO::FETCH_ASSOC)) {


                    //내가 해당 음악을 좋아요 했는지 안했는지 검사
                    $query1 = $readDB->prepare('select count(*) as taskCount from LIKES_MUSIC where user_id =:user_id and music_id=:music_id');
                    $query1->bindParam(':user_id', $returned_userid);
                    $query1->bindParam(':music_id', $row['music_PK']);
                    $query1->execute();

                    $row1 = $query1->fetch(PDO::FETCH_ASSOC);

                    $like_count = $row1['taskCount'];


                    $like_check = 'off';
                    if($like_count>0){
                        $like_check = 'on';
                    }

                    //좋아요 갯수를 구한다
                    $query1 = $readDB->prepare('select count(*) as likeCount from LIKES_MUSIC where music_id=:music_id');
                    $query1->bindParam(':music_id', $row['music_PK']);
                    $query1->execute();

                    $row1 = $query1->fetch(PDO::FETCH_ASSOC);
                    $like_count = $row1['likeCount'];

                    array_push($data, array(
                        'music_PK' => $row['music_PK'],
                        'user_PK' => $row['user_id'],
                        'music_name' => $row['music_name'],
                        'music_artist_name' => $row['music_artist_name'],
                        'music_sub_artist_name' => $row['music_sub_artist_name'],
                        'music_writer' => $row['music_writer'],
                        'music_composer' => $row['music_composer'],
                        'music_arranger' => $row['music_arranger'],
                        'music_genre' => $row['music_genre'],
                        'music_mood' => $row['music_mood'],
                        'music_photo_path' => $row['music_photo_path'],
                        'music_liric' => $row['music_liric'],
                        'music_registerday' => $row['music_registerday'],
                        'music_introduction' => $row['music_introduction'],
                        'music_state' => $row['music_state'],
                        'music_streaming_path' => $row['music_streaming_path'],
                        'like_check'=>$like_check,
                        'like_count'=>$like_count,
                        'album_PK' => $row['album_PK'],
                        'total_count' => $musicCount,
                    ));
                }


                $response = new Response();
                $response->setHttpStatusCode(200);
                $response->setSuccess(true);
                $response->addMessage("datas");
                $response->setData($data);
                $response->send();
                exit;


            }


        }
        catch (PDOException $ex){
            $response = new Response();
            $response->setHttpStatusCode(500);
            $response->setSuccess(false);
            $response->addMessage('DB error');
            $response->send();
            exit;
        }



    }
    else{
        $response = new Response();
        $response->setHttpStatusCode(405);
        $response->setSuccess(false);
        $response->addMessage("Request method not allowed");
        $response->send();
        exit;
    }



}

else {
    $response = new Response();
    $response->setHttpStatusCode(404);
    $response->setSuccess(false);
    $response->addMessage("Endpoint not found");
    $response->send();
    exit;
}


?>