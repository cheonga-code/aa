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

//피드 목록 조회 - 전체 (전체 피드를 페이징 처리하여서 최신 순으로 조회)
if (array_key_exists('get-feed-all', $_GET)) {

    if ($_SERVER['REQUEST_METHOD'] == 'GET') {
        $page = $_GET['page'];

        if ($page == '' || !is_numeric($page)) {
            $response = new Response();
            $response->setHttpStatusCode(400);
            $response->setSuccess(false);
            $response->addMessage("Page number cannot be blank and must be numeric");
            $response->send();
            exit;
        }

        //10개씩 페이징
        $limitPerPage = 10;

        //헤더에 있는 토큰 검사
        $auth = new user_auth();
        $auth_data = $auth->auth_check(1);

        try {

            $state = 'on';


            $query = $readDB->prepare("select count(feed_PK) as totalNoOfTasks from FEED , USERS where FEED.user_id = USERS.user_PK and state = :state");
            $query->bindParam(':state', $state);
            $query->execute();


            $row = $query->fetch(PDO::FETCH_ASSOC);

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

            $query = $readDB->prepare('select FEED.feed_PK, FEED.like_number, FEED.review_number, FEED.photo_number, FEED.text, FEED.register_time,
        FEED.photo_path , USERS.user_PK as user_id, USERS.user_photo_path as profile_image, USERS.user_nickname as nickname from FEED, USERS where state = :state and FEED.user_id = USERS.user_PK ORDER BY register_time DESC  limit :pglimit offset :offset ');
            $query->bindParam(':pglimit', $limitPerPage, PDO::PARAM_INT);
            $query->bindParam(':offset', $offset, PDO::PARAM_INT);
            $query->bindParam(':state', $state);
            $query->execute();

            $rowCount = $query->rowCount();

            $feed_data = array();

            while ($row = $query->fetch(PDO::FETCH_ASSOC)) {

                //내가 해당 댓글에 좋아요 했는지 안했는지 알기 위해
                //feed 좋아요 테이블에서 해당 게시물에 대한 나의 좋아요 데이터가 있는지 select
                $query1 = $readDB->prepare('select * from FEED_LIKE where liker_id = :liker_id and feed_id =:feed_id');
                $query1->bindParam(':liker_id', $returned_userid);
                $query1->bindParam(':feed_id', $row['feed_PK']);
                $query1->execute();

                $rowCount = $query1->rowCount();

                $is_like = 'off';

                if ($rowCount > 0) {
                    $is_like = 'on';
                }

                //해당 피드의 댓글 갯수를 가져온다
                $query2 = $readDB->prepare('select count(*) as reviewCount from FEED_REVIEW where feed_id = :feed_id AND state =:state');
                $query2->bindParam(':feed_id', $row['feed_PK']);
                $query2->bindParam(':state', $state);
                $query2->execute();

                $row2 = $query2->fetch(PDO::FETCH_ASSOC);
                $review_count = $row2['reviewCount'];

                array_push($feed_data, array(
                    'sns_PK' => $row['feed_PK'],
                    'user_id' => $row['user_id'],
                    'nickname' => $row['nickname'],
                    'profile_image' => $row['profile_image'],
                    'like_number' => $row['like_number'],
                    'review_number' => $review_count,
                    'photo_number' => $row['photo_number'],
                    'text' => $row['text'],
                    'register_time' => $row['register_time'],
                    'photo_path' => $row['photo_path'],
                    'like_check' => $is_like
                ));
            }

            $response = new Response();
            $response->setHttpStatusCode(200);
            $response->setSuccess(true);
            $response->toCache(true);
            $response->setData($feed_data);
            $response->send();

            exit;


        } catch (PDOException $ex) {
            error_log("Database query error - " . $ex, 0);
            $response = new Response();
            $response->setHttpStatusCode(500);
            $response->setSuccess(false);
            $response->addMessage("Failed to get tasks");
            $response->send();
            exit;
        }
    } else {
        $response = new Response();
        $response->setHttpStatusCode(405);
        $response->setSuccess(false);
        $response->addMessage("Request method not allowed");
        $response->send();
        exit;
    }
}
//피드 목록 조회 - 팔로우 (유저가 팔로우한 아티스트들의 피드 조회)
elseif (array_key_exists('get-feed-follow', $_GET)) {

    if ($_SERVER['REQUEST_METHOD'] == 'GET') {

        $page = $_GET['page'];
        $state = 'on';

        if ($page == '' || !is_numeric($page)) {
            $response = new Response();
            $response->setHttpStatusCode(400);
            $response->setSuccess(false);
            $response->addMessage("Page number cannot be blank and must be numeric");
            $response->send();
            exit;
        }

        //10개씩 페이징
        $limitPerPage = 10;

        //헤더에 있는 토큰 검사
        $auth = new user_auth();
        $auth_data = $auth->auth_check(1);
        
        try {

            $query = $readDB->prepare("select feed_PK from FEED, USERS, FORROW where FEED.state = :state1 and FEED.user_id = USERS.user_PK and FEED.user_id = FORROW.artist_id and FORROW.user_id = :user_id
                union (select feed_PK from FEED , USERS where FEED.state = :state2 and FEED.user_id = USERS.user_PK and FEED.user_id = :ss) ");
            $query->bindParam(':user_id', $returned_userid);
            $query->bindParam(':state1', $state);
            $query->bindParam(':state2', $state);
            $query->bindParam(':ss', $returned_userid);

            $query->execute();

            $row = $query->fetch(PDO::FETCH_ASSOC);

            $rowCount = $query->rowCount();


            $numOfPages = ceil($rowCount / $limitPerPage);


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

            $query = $readDB->prepare('select FEED.feed_PK, FEED.like_number, FEED.review_number, FEED.photo_number, FEED.text, FEED.register_time,
                FEED.photo_path , USERS.user_PK as user_id, USERS.user_photo_path as profile_image, USERS.user_nickname as nickname from FEED, USERS, FORROW where FEED.state = :state1 and FEED.user_id = USERS.user_PK and FEED.user_id = FORROW.artist_id and FORROW.user_id = :user_id
                union
                (select FEED.feed_PK, FEED.like_number, FEED.review_number, FEED.photo_number, FEED.text, FEED.register_time,
                FEED.photo_path , USERS.user_PK as user_id, USERS.user_photo_path as profile_image, USERS.user_nickname as nickname from FEED , USERS where FEED.state = :state2 and FEED.user_id = USERS.user_PK and FEED.user_id = :ss)
                ORDER BY register_time DESC  limit :pglimit offset :offset ');
            $query->bindParam(':state1', $state);
            $query->bindParam(':state2', $state);

            $query->bindParam(':pglimit', $limitPerPage, PDO::PARAM_INT);
            $query->bindParam(':user_id', $returned_userid);
            $query->bindParam(':ss', $returned_userid);

            $query->bindParam(':offset', $offset, PDO::PARAM_INT);
            $query->execute();

            $rowCount = $query->rowCount();

            $feed_data = array();

            while ($row = $query->fetch(PDO::FETCH_ASSOC)) {

                //내가 해당 댓글에 좋아요 했는지 안했는지 알기 위해
                //feed 좋아요 테이블에서 해당 게시물에 대한 나의 좋아요 데이터가 있는지 select
                $query1 = $readDB->prepare('select * from FEED_LIKE where liker_id = :liker_id and feed_id =:feed_id');
                $query1->bindParam(':liker_id', $returned_userid);
                $query1->bindParam(':feed_id', $row['feed_PK']);
                $query1->execute();

                $rowCount = $query1->rowCount();

                $is_like = 'off';

                if ($rowCount > 0) {
                    $is_like = 'on';
                }

                //mysql데이터 파싱
                array_push($feed_data, array(
                    'sns_PK' => $row['feed_PK'],
                    'user_id' => $row['user_id'],
                    'nickname' => $row['nickname'],
                    'profile_image' => $row['profile_image'],
                    'like_number' => $row['like_number'],
                    'review_number' => $row['review_number'],
                    'photo_number' => $row['photo_number'],
                    'text' => $row['text'],
                    'register_time' => $row['register_time'],
                    'photo_path' => $row['photo_path'],
                    'like_check' => $is_like
                ));
            }

            $response = new Response();
            $response->setHttpStatusCode(200);
            $response->setSuccess(true);
            $response->toCache(true);
            $response->setData($feed_data);
            $response->send();

            exit;


        } catch (PDOException $ex) {
            //error_log("Database query error - " . $ex, 0);
            $response = new Response();
            $response->setHttpStatusCode(500);
            $response->setSuccess(false);
            $response->addMessage("Failed to get tasks - " . $ex);
            $response->send();
            exit;
        }

    } else {
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