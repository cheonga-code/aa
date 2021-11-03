<?php

try{
    $writeDB = DB::ConnectWriteDB();
    $readDB = DB::ConnectReadDB();
}
catch (PDOException $ex){
    error_log("Connection error -". $ex, 0);
    $response = new Response();
    $response->setHttpStatusCode(500);
    $response->setSuccess(false);
    $response->addMessage("Database connection error");
    $response->send();
    exit();
}

//메인페이지에서 최신음악 버튼을 클릭하면 최신순으로 음악 50개를 불러온다
if(array_key_exists("get-new-music",$_GET)){

    if($_SERVER['REQUEST_METHOD'] == 'GET'){

        //페이징 페이지
        $page = $_GET['page'];

        if($page == ''|| !is_numeric($page)){
            $response = new Response();
            $response->setHttpStatusCode(400);
            $response->setSuccess(false);
            $response->addMessage("Page number cannot be blank and must be numeric");
            $response->send();
            exit;
        }

        $limitPerPage = 200;
        $state = 'approve';

        //헤더에 있는 토큰 검사
        $auth = new user_auth();
        $auth_data = $auth->auth_check(1);

        try{

            $query = $readDB->prepare("select count(music_PK) as totalNoOfTasks from MUSICS ,USERS where MUSICS.user_id = USERS.user_PK and MUSICS.music_state = :state");
            $query->bindParam(':state', $state);

            $query->execute();

            $row = $query->fetch(PDO::FETCH_ASSOC);

            //전체 음악 갯수
            $musicCount = intval($row['totalNoOfTasks']);

            //페이지 갯수
            $numOfPages = ceil($musicCount/$limitPerPage);


            if($numOfPages ==0){
                $numOfPages =1;
            }

            if($page > $numOfPages || $page == 0){
                $response = new Response();
                $response->setHttpStatusCode(404);
                $response->setSuccess(false);
                $response->addMessage("Page not found");
                $response->send();
                exit;
            }

            $offset = ($page ==1 ? 0 :  ($limitPerPage*($page -1)));

            $query = $readDB->prepare('select MUSICS.* from MUSICS , USERS where MUSICS.user_id = USERS.user_PK and MUSICS.music_state = :state ORDER BY music_registerday DESC  limit :pglimit offset :offset ');

            $query->bindParam(':state',$state );
            $query->bindParam(':pglimit', $limitPerPage, PDO::PARAM_INT);
            $query->bindParam(':offset', $offset, PDO::PARAM_INT);
            $query->execute();

            $rowCount = $query->rowCount();

            $musicArray = array();

            while ($row = $query->fetch(PDO::FETCH_ASSOC)) {

                array_push($musicArray, array(
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
                    'music_streaming_path' => $row['music_streaming_path'],
                ));
            }

            $response = new Response();
            $response->setHttpStatusCode(200);
            $response->setSuccess(true);
            $response->toCache(true);
            $response->setData($musicArray);
            $response->send();

            exit;

        }
        catch (TaskException $ex){
            $response = new Response();
            $response->setHttpStatusCode(500);
            $response->setSuccess(false);
            $response->addMessage($ex->getMessage());
            $response->send();
            exit;

        }
        catch (PDOException $ex){
            error_log("Database query error - ".$ex, 0 );
            $response = new Response();
            $response->setHttpStatusCode(500);
            $response->setSuccess(false);
            $response->addMessage("Failed to get tasks". $ex);
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

else{
    $response = new Response();
    $response->setHttpStatusCode(404);
    $response->setSuccess(false);
    $response->addMessage("Page not found");
    $response->send();
    exit;
}



?>