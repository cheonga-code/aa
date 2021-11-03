<?php

require_once('../common/db.php');
require_once('State.php');

class SqlMusic {

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
     * @Function 음악 간단 목록 조회 (최신순)
     * @param $request_count : 데이터 요청 갯수
     * @return mixed
     */
    public function selectMusicPreListNew($request_count){

        $query_music = $this->readDB->prepare("SELECT MUSICS.music_PK, MUSICS.music_name, MUSICS.music_artist_name, MUSICS.music_photo_path, MUSICS.music_streaming_path, MUSICS.music_registerday,
                        USERS.user_PK, USERS.user_photo_path, USERS.user_nickname 
                        FROM USERS, MUSICS WHERE music_state = :state AND MUSICS.user_id = USERS.user_PK ORDER BY music_registerday DESC 
                        LIMIT :request_count");
        $query_music->bindParam(':state', strval(State::APPROVE));
        $query_music->bindParam(':request_count', $request_count, PDO::PARAM_INT);
        $query_music->execute();

        return $query_music;

    }

    /**
     * @Function 음악 간단 목록 조회 (인기순)
     * @param $request_count : 데이터 요청 갯수
     * @return mixed
     */
    public function selectMusicPreListHot($request_count){

        //일주일간 이용자가 많이 들은 인기음악을 산출한다 (-7일전 날짜를 구해서 변수에 담는다)
        $today = date("Y-m-d");
        $before7day = date("Y-m-d", strtotime($today." -7 day"));

        $query_music = $this->readDB->prepare("SELECT MUSICS.music_PK, MUSICS.music_name, MUSICS.music_artist_name, MUSICS.music_photo_path, MUSICS.music_streaming_path, MUSICS.music_registerday, 
                        COUNT(*)*2 AS score, USERS.user_PK, USERS.user_photo_path, USERS.user_nickname
                        FROM USERS, DOUBLE_CHECK_MUSIC LEFT JOIN MUSICS ON MUSICS.music_PK = DOUBLE_CHECK_MUSIC.music_id
                        WHERE play_time>= :before7day AND music_state = :state AND MUSICS.user_id = USERS.user_PK GROUP BY music_PK ORDER BY score DESC 
                        LIMIT :request_count");
        $query_music->bindParam(':before7day', $before7day);
        $query_music->bindParam(':state', strval(State::APPROVE));
        $query_music->bindParam(':request_count', $request_count, PDO::PARAM_INT);
        $query_music->execute();

        return $query_music;

    }

    /**
     * @Function 음악 전체 목록 조회 (최신순)
     * @param $limit_per_page : 페이징 처리 - 출력할 행의 수 ex) 10
     * @param $offset : 페이징 처리 - 몇번째 행부터 출력할지  ex) 0 = 10행($offset)부터 10개($limit_per_page)씩 출력
     * @return mixed
     */
    public function selectMusicDetailListNew($limit_per_page, $offset){

        $query_music = $this->readDB->prepare("SELECT MUSICS.music_PK, MUSICS.music_name, MUSICS.music_artist_name, MUSICS.music_photo_path, MUSICS.music_streaming_path, MUSICS.music_registerday,
                        USERS.user_PK, USERS.user_photo_path, USERS.user_nickname 
                        FROM USERS, MUSICS WHERE music_state = :state AND MUSICS.user_id = USERS.user_PK ORDER BY music_registerday DESC 
                        LIMIT :pglimit OFFSET :offset");
        $query_music->bindParam(':state', strval(State::APPROVE));
        $query_music->bindParam(':pglimit', $limit_per_page, PDO::PARAM_INT);
        $query_music->bindParam(':offset', $offset, PDO::PARAM_INT);
        $query_music->execute();

        return $query_music;

    }

    /**
     * @Function 음악 전체 목록 조회 (인기순)
     * @param $limit_per_page : 페이징 처리 - 출력할 행의 수 ex) 10
     * @param $offset : 페이징 처리 - 몇번째 행부터 출력할지  ex) 0 = 10행($offset)부터 10개($limit_per_page)씩 출력
     * @return mixed
     */
    public function selectMusicDetailListHot($limit_per_page, $offset){

        //일주일간 이용자가 많이 들은 인기음악을 산출한다 (-7일전 날짜를 구해서 변수에 담는다)
        $today = date("Y-m-d");
        $before7day = date("Y-m-d", strtotime($today." -7 day"));

        $query_music = $this->readDB->prepare("SELECT MUSICS.music_PK, MUSICS.music_name, MUSICS.music_artist_name, MUSICS.music_photo_path, MUSICS.music_streaming_path, MUSICS.music_registerday, 
                        COUNT(*)*2 AS score, USERS.user_PK, USERS.user_photo_path, USERS.user_nickname
                        FROM USERS, DOUBLE_CHECK_MUSIC LEFT JOIN MUSICS ON MUSICS.music_PK = DOUBLE_CHECK_MUSIC.music_id
                        WHERE play_time>= :before7day AND music_state = :state AND MUSICS.user_id = USERS.user_PK GROUP BY music_PK ORDER BY score DESC 
                        LIMIT :pglimit OFFSET :offset");
        $query_music->bindParam(':before7day', $before7day);
        $query_music->bindParam(':state', strval(State::APPROVE));
        $query_music->bindParam(':pglimit', $limit_per_page, PDO::PARAM_INT);
        $query_music->bindParam(':offset', $offset, PDO::PARAM_INT);
        $query_music->execute();

        return $query_music;

    }

    /**
     * @Function 유저의 업로드 음악 목록 조회 (최신순)
     * @param $user_id : 유저 id
     * @return mixed
     */
    public function selectMusicUserUploadListNew($user_id){

        $query_music = $this->readDB->prepare("SELECT MUSICS.music_PK, MUSICS.music_name, MUSICS.music_artist_name, MUSICS.music_photo_path, MUSICS.music_streaming_path, MUSICS.music_registerday,
                        USERS.user_PK, USERS.user_photo_path, USERS.user_nickname, USERS.user_introduction
                        FROM MUSICS LEFT JOIN USERS ON MUSICS.user_id = USERS.user_PK WHERE music_state = :music_state AND user_id = :user_id");
        $query_music->bindParam(':music_state', strval(State::APPROVE));
        $query_music->bindParam(':user_id', $user_id, PDO::PARAM_INT);
        $query_music->execute();

        return $query_music;

    }

    /**
     * @Function 음악 게시물 총 갯수 조회
     * @return
     */
    public function selectMusicCount(){

        $query_music = $this->readDB->prepare("SELECT count(music_PK) AS music_count FROM MUSICS, USERS WHERE MUSICS.user_id = USERS.user_PK AND music_state = :state");
        $query_music->bindParam(':state', strval(State::APPROVE));
        $query_music->execute();

        return $query_music;
    }

    /**
     * @Function 뮤지션의 음악 중, 제일 인기 많은 곡 1개 조회 (기준 : 전체 스트리밍수)
     * @param $user_id : 유저(뮤지션) PK
     * @return
     */
    public function selectMusicHotLimitOne($user_id){

        $query_music = $this->readDB->prepare('SELECT music_PK, music_name, music_photo_path FROM MUSICS 
                            WHERE music_state = :music_state AND user_id = :user_id ORDER BY total_streaming_count DESC LIMIT 1');
        $query_music->bindParam(':music_state', strval(State::APPROVE));
        $query_music->bindParam(':user_id', $user_id);
        $query_music->execute();

        return $query_music;

    }

    /**
     * @Function 음악 정보 조회
     * @param $music_id : 조회할 음악 PK
     * @return
     */
    public function selectMusic($music_id){

        $query_music = $this->readDB->prepare("SELECT MUSICS.* FROM MUSICS WHERE music_state = :music_state AND music_PK = :music_id");
        $query_music->bindParam(':music_state', strval(State::APPROVE));
        $query_music->bindParam(':music_id', $music_id);
        $query_music->execute();

        return $query_music;
    }


}

?>