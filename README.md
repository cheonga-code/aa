# mvc-folder-structure
Improve server folder structure by applying mvc pattern

### 변경 전 구조
- mobile
  - mobile-music.php
- web
  - web-music.php


### 변경 후 구조
- controllers
  - mobile
    - mobile-music.php
  - web
    - web-music.php
- models
  - SqlMusic.php
- routers
  - router-mobile.php
  - router-web.php
