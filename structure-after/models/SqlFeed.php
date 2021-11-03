<?php

require_once('../common/db.php');
require_once('State.php');

class SqlFeed {

    public $readDB;
    public $writeDB;

    function __construct(){
        $this->setDB();
    }

    function setDB(){
        $this->readDB = DB::ConnectReadDB();
        $this->writeDB = DB::ConnectWriteDB();
    }

    /**
     * @Function 피드 간단 목록 조회 (최신순)
     * @param $request_count : 데이터 요청 갯수
     * @return
     */
    public function selectFeedPreListNew($request_count){

        $query_feed = $this->readDB->prepare("SELECT FEED.feed_PK, FEED.text, FEED.photo_path, FEED.register_time, 
                        USERS.user_PK, USERS.user_photo_path, USERS.user_nickname
                        FROM USERS, FEED WHERE state = :state AND FEED.user_id = USERS.user_PK ORDER BY register_time DESC LIMIT :request_count");
        $query_feed->bindParam(':state', strval(State::ON));
        $query_feed->bindParam(':request_count', $request_count, PDO::PARAM_INT);
        $query_feed->execute();

        return $query_feed;

    }


    /**
     * @Function 피드 전체 목록 조회 (최신순)
     * @param $returned_userid : 피드 목록을 조회하는 유저 PK
     * @param $limit_per_page : 페이징 처리 - 출력할 행의 수 ex) 10
     * @param $offset : 페이징 처리 - 몇번째 행부터 출력할지  ex) 0 = 10행($offset)부터 10개($limit_per_page)씩 출력
     * @return
     */
    public function selectFeedDetailListNew($returned_userid, $limit_per_page, $offset){

        $query_feed = $this->readDB->prepare("SELECT IF(FEED_LIKE.feed_like_PK is not null,'on','off') AS like_check,
                        FEED.feed_PK, FEED.photo_number, FEED.text, FEED.register_time, FEED.photo_path, USERS.user_PK, USERS.user_photo_path, USERS.user_nickname
                        FROM  USERS,FEED LEFT OUTER JOIN FEED_LIKE ON FEED_LIKE.liker_id = :liker_id AND FEED_LIKE.feed_id = FEED.feed_PK
                        WHERE state = :state AND FEED.user_id = USERS.user_PK ORDER BY register_time DESC LIMIT :pglimit OFFSET :offset");
        $query_feed->bindParam(':state', strval(State::ON));
        $query_feed->bindParam(':liker_id', $returned_userid);
        $query_feed->bindParam(':pglimit', $limit_per_page, PDO::PARAM_INT);
        $query_feed->bindParam(':offset', $offset, PDO::PARAM_INT);
        $query_feed->execute();

        return $query_feed;

    }

    /**
     * @Function 피드 게시물 조회
     * @param $returned_userid : 피드를 조회하는 유저 PK
     * @param $feed_id : 조회할 피드 PK
     * @return
     */
    public function selectFeed($returned_userid, $feed_id){

        $query_feed = $this->readDB->prepare("SELECT IF(FEED_LIKE.feed_like_PK is not null,'on','off') AS like_check, FEED.*, USERS.user_photo_path, USERS.user_nickname
                        FROM USERS, FEED LEFT OUTER JOIN FEED_LIKE ON FEED_LIKE.liker_id = :liker_id AND FEED_LIKE.feed_id = FEED.feed_PK
                        WHERE state = :state AND FEED.user_id = USERS.user_PK AND FEED.feed_PK = :feed_id");
        $query_feed->bindParam(':liker_id', $returned_userid);
        $query_feed->bindParam(':state', strval(State::ON));
        $query_feed->bindParam(':feed_id', $feed_id);
        $query_feed->execute();

        return $query_feed;
    }

    /**
     * @Function 피드 게시물에 유저가 좋아요 한 데이터가 있는지 조회
     * @param $returned_userid : 피드에 좋아요 한 유저 PK
     * @param $feed_id : 피드 PK
     * @return
     */
    public function selectFeedLike($returned_userid, $feed_id){

        $query_feed_like = $this->readDB->prepare('SELECT * FROM FEED_LIKE WHERE liker_id =:liker_id AND feed_id = :feed_id');
        $query_feed_like->bindParam(':liker_id', $returned_userid);
        $query_feed_like->bindParam(':feed_id', $feed_id);
        $query_feed_like->execute();

        return $query_feed_like;
    }

    /**
     * @Function 피드 게시물에 좋아요 데이터 추가
     * @param $feed_id : 피드 PK
     * @param $returned_userid : 피드에 좋아요 한 유저 PK
     * @return
     */
    public function insertFeedLike($feed_id, $returned_userid){

        //등록 날짜
        $register_time = date('Y-m-d H:i:s', time());

        $query_feed_like = $this->writeDB->prepare('INSERT INTO FEED_LIKE (feed_id, liker_id, register_time) VALUES (:feed_id, :liker_id, :register_time)');
        $query_feed_like->bindParam(':feed_id', $feed_id);
        $query_feed_like->bindParam(':liker_id', $returned_userid);
        $query_feed_like->bindParam(':register_time', $register_time);
        $query_feed_like->execute();

        return $query_feed_like;
    }

    /**
     * @Function 피드 게시물에 좋아요 데이터 삭제
     * @param $feed_id : 피드 PK
     * @param $returned_userid : 피드에 좋아요 취소한 유저 PK
     * @return
     */
    public function deleteFeedLike($feed_id, $returned_userid){

        $query_feed_like = $this->writeDB->prepare('DELETE FROM FEED_LIKE WHERE feed_id = :feed_id AND liker_id = :liker_id');
        $query_feed_like->bindParam(':feed_id', $feed_id);
        $query_feed_like->bindParam(':liker_id', $returned_userid);
        $query_feed_like->execute();

        return $query_feed_like;
    }

    /**
     * @Function 피드 댓글 목록 조회 (최신순)
     * @param $returned_userid : 피드 댓글 목록을 조회하는 유저 PK
     * @param $feed_id : 피드 PK
     * @param $limit_per_page : 페이징 처리 - 출력할 행의 수 ex) 10
     * @param $offset : 페이징 처리 - 몇번째 행부터 출력할지  ex) 0 = 10행($offset)부터 10개($limit_per_page)씩 출력
     * @return
     */
    public function selectFeedReviewList($returned_userid, $feed_id, $limit_per_page, $offset){

        $query_feed_review = $this->readDB->prepare("SELECT IF(FEED_REVIEW_LIKE.feed_review_like_PK is not null,'on','off') AS review_like_check, FEED_REVIEW.*, USERS.user_photo_path, USERS.user_nickname
                                FROM USERS, FEED_REVIEW LEFT OUTER JOIN FEED_REVIEW_LIKE ON FEED_REVIEW_LIKE.liker_id = :liker_id AND FEED_REVIEW_LIKE.feed_review_id = FEED_REVIEW.feed_review_PK
                                WHERE state = :state AND FEED_REVIEW.user_id = USERS.user_PK AND FEED_REVIEW.feed_id = :feed_id ORDER BY register_time DESC LIMIT :pglimit OFFSET :offset");
        $query_feed_review->bindParam(':state', strval(State::ON));
        $query_feed_review->bindParam(':liker_id', $returned_userid);
        $query_feed_review->bindParam(':feed_id', $feed_id);
        $query_feed_review->bindParam(':pglimit', $limit_per_page, PDO::PARAM_INT);
        $query_feed_review->bindParam(':offset', $offset, PDO::PARAM_INT);
        $query_feed_review->execute();

        return $query_feed_review;

    }

    /**
     * @Function 피드 댓글에 달린 최신 대댓글 1개 조회
     * @param $feed_review_id : 피드 댓글 PK
     * @return
     */
    public function selectFeedReviewReviewLimitOne($feed_review_id){

        $query_feed_review_review = $this->readDB->prepare('SELECT SQL_CALC_FOUND_ROWS * FROM FEED_REVIEW_REVIEW WHERE feed_review_id = :feed_review_id AND state = :state ORDER BY register_time DESC LIMIT 1');
        $query_feed_review_review->bindParam(':feed_review_id', $feed_review_id);
        $query_feed_review_review->bindParam(':state', strval(State::ON));
        $query_feed_review_review->execute();

        return $query_feed_review_review;

    }

    /**
     * @Function 피드 댓글에 유저가 좋아요 한 데이터가 있는지 조회
     * @param $review_id : 피드 댓글 PK
     * @param $returned_userid : 피드에 댓글에 좋아요 한 유저 PK
     * @return
     */
    public function selectFeedReviewLike($review_id, $returned_userid){

        $query_feed_review_like = $this->readDB->prepare('SELECT * FROM FEED_REVIEW_LIKE WHERE liker_id =:liker_id AND feed_review_id = :feed_review_id');
        $query_feed_review_like->bindParam(':liker_id', $returned_userid);
        $query_feed_review_like->bindParam(':feed_review_id', $review_id);
        $query_feed_review_like->execute();

        return $query_feed_review_like;
    }

    /**
     * @Function 피드 댓글에 좋아요 데이터 추가
     * @param $review_id : 피드 댓글 PK
     * @param $returned_userid : 피드 댓글에 좋아요 한 유저 PK
     * @return
     */
    public function insertFeedReviewLike($review_id, $returned_userid){

        //등록 날짜
        $register_time = date('Y-m-d H:i:s', time());

        $query_feed_review_like = $this->writeDB->prepare('INSERT INTO FEED_REVIEW_LIKE (feed_review_id, liker_id, register_time) values (:feed_review_id, :liker_id, :register_time)');
        $query_feed_review_like->bindParam(':feed_review_id', $review_id);
        $query_feed_review_like->bindParam(':liker_id', $returned_userid);
        $query_feed_review_like->bindParam(':register_time', $register_time);
        $query_feed_review_like->execute();

        return $query_feed_review_like;
    }

    /**
     * @Function 피드 댓글에 좋아요 데이터 삭제
     * @param $review_id : 피드 댓글 PK
     * @param $returned_userid : 피드 댓글에 좋아요 취소한 유저 PK
     * @return
     */
    public function deleteFeedReviewLike($review_id, $returned_userid){

        $query_feed_review_like = $this->writeDB->prepare('DELETE FROM FEED_REVIEW_LIKE WHERE feed_review_id = :feed_review_id AND liker_id = :liker_id');
        $query_feed_review_like->bindParam(':feed_review_id', $review_id);
        $query_feed_review_like->bindParam(':liker_id', $returned_userid);
        $query_feed_review_like->execute();

        return $query_feed_review_like;
    }

    /**
     * @Function 피드 대댓글 목록 조회 (최신순)
     * @param $review_id : 피드 댓글 PK
     * @param $limit_per_page : 페이징 처리 - 출력할 행의 수 ex) 10
     * @param $offset : 페이징 처리 - 몇번째 행부터 출력할지  ex) 0 = 10행($offset)부터 10개($limit_per_page)씩 출력
     * @return
     */
    public function selectFeedReviewReviewList($review_id, $limit_per_page, $offset){

        $query_feed_review_review = $this->readDB->prepare("SELECT FEED_REVIEW_REVIEW.*, USERS.user_nickname, USERS.user_photo_path FROM FEED_REVIEW_REVIEW LEFT OUTER JOIN USERS 
                                        ON FEED_REVIEW_REVIEW.user_id = USERS.user_PK WHERE feed_review_id = :feed_review_id AND state = :state ORDER BY register_time DESC 
                                        LIMIT :pglimit OFFSET :offset");
        $query_feed_review_review->bindParam(':feed_review_id', $review_id);
        $query_feed_review_review->bindParam(':state', strval(State::ON));
        $query_feed_review_review->bindParam(':pglimit', $limit_per_page, PDO::PARAM_INT);
        $query_feed_review_review->bindParam(':offset', $offset, PDO::PARAM_INT);
        $query_feed_review_review->execute();

        return $query_feed_review_review;

    }

    /**
     * @Function 피드 댓글에 달린 대댓글 갯수 조회
     * @param $feed_id : 조회할 피드 PK
     * @return
     */
    public function selectFeedReviewReviewCount($review_id){

        $query_feed_review_review = $this->readDB->prepare("SELECT count(*) AS review_review_count FROM FEED_REVIEW_REVIEW WHERE feed_review_id = :feed_review_id AND state = :state");
        $query_feed_review_review->bindParam(':feed_review_id', $review_id);
        $query_feed_review_review->bindParam(':state', strval(State::ON));
        $query_feed_review_review->execute();

        return $query_feed_review_review;
    }


}

?>
