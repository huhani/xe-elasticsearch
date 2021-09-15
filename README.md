XE 엘라스틱서치 모듈
======================

# 1. 엘라스틱서치 모듈

## 1.1. 목적
mysql 엔진 기반 db에서 게시글 검색 및 통합 검색 속도 향상을 위한 엘라스틱서치 검색 엔진 모듈입니다.
## 1.2. 개요
아파치 루씬 기반의 검색엔진인 Elasticsearch를 이용하여 기존 RDBMS의 Full-text search보다 빠른 속도로 검색을 지원합니다.

## 1.3. 장점 및 주의사항
### 1.3.1. 장점
    1. 빠른 속도의 인덱싱 지원
    2. 페이지 분할(계속 검색) 없이 연속적인 페이징 지원
    3. 통합검색 지원
    4. 기존DB(mysql)의 조작 불필요
### 1.3.1. 주의사항
    1. 검색결과 정렬은 문서번호, 등록일 순으로만 가능
    2. score순(정확도순) 기능 미구현
    3. Elasticsearch는 NoSQL 특성상 ACID 성질이 충족되지 않음.
    4. 실시간 검색어 순위, 연관 검색어, 자동완성 미구현 (이걸 구현하려면 일이 너무 커진다)
    
# 2. 설치
## 2.1. 설치 환경

### 2.2.1. H/W 최소사양
https://www.elastic.co/guide/en/elasticsearch/guide/current/hardware.html

### 2.2.1. S/W 환경
    - Elasticsearch 7.14 버전 이상
    - php 7.4 이상, php-curl 설치
    - php composer 사용이 가능해야 함
    - XE 1.11.6 (라이믹스 미지원)
    - MySQL(MariaDB)

## 2.2. 설치
상기 기술된 설치방법은 서버의 규모나 구성에 따라 달라질 수 있습니다.   
설정 내용은 엘라스틱서치 7.14 버전, Ubuntu 환경을 기준으로 작성되었습니다.

### 2.2.1. elasticsearch 설치
* Debian 계열 : https://www.elastic.co/guide/en/elasticsearch/reference/current/deb.html   
* RedHat 계열 : https://www.elastic.co/guide/en/elasticsearch/reference/current/rpm.html

### 2.2.2. elasticsearch 환경설정

#### 2.2.2.1. elasticsearch.yml 설정
/etc/elasticsearch/elasticsearch.yml 파일을 수정하여 ElasticSearch 서버에 사용할 아이피, 명칭, 데이터 경로 등을 설정합니다.   
처음 elasticsearch를 설치하였을땐 elasticsearch.yml 파일의 모든 설정이 주석처리가 되어있습니다.
elasticsearch.yml 설정 예제 파일: [Link][yml_link]   

[yml_link]: https://gist.github.com/huhani/d5ff4ea1886ed9667b94e1095732a782 "Go Gist"


```
cluster.name: my-application
node.name: node-1
node.attr.rack: r1
path.data: /var/lib/elasticsearch
path.logs: /var/log/elasticsearch
network.host: 0.0.0.0
http.port: 9200
discovery.seed_hosts: ["127.0.0.1"]
cluster.initial_master_nodes: ["node-1"]
action.destructive_requires_name: true
```




#### 2.2.2.2. jvm.options 수정
/etc/elasticsearch/jvm.options 파일을 수정하여 OOM(Out Of Memory)발생으로 ElasticSearch 서버가 뻗어버리지 않도록 설정.   
Heap 사이즈는 컴퓨터 RAM의 50%로 할당.    
아래 예제는 RAM이 8GB인 서버에서 Heap 용량을 4GB로 설정할때의 값임.   
jvm.options 설정 예제 파일: [Link][jvm_options_link]   

[jvm_options_link]: https://gist.github.com/huhani/a9300fcbe9c46f8a416777c5212fcc3a "Go Gist"
```
-Xms4g
-Xmx4g
```

### 2.2.3. elasticsearch 서비스 실행
Debian 계열 : https://www.elastic.co/guide/en/elasticsearch/reference/current/starting-elasticsearch.html#start-es-deb-init   
RedHat 계열 : https://www.elastic.co/guide/en/elasticsearch/reference/current/starting-elasticsearch.html#start-rpm


### 2.2.4. elasticsearch-php 설치 (composer 사용)
https://github.com/elastic/elasticsearch-php#installation-via-composer   
composer.json 파일은 xe 사이트의 최상단(index.php가 있는곳)에 위치함.   
composer.json 파일이 없는 경우 별도로 생성해야 하며, elasticsearch-php가 정상적으로 설치된 경우 /vendor/elassticsearch 폴더(를 포함한 다수)가 생성됨.


### 2.2.5. xe-elasticsearch 모듈 설치
/modules/elasticsearch에 상기 github에 있는 파일들을 설치한다.

#### 2.2.5.1. xe-elasticsearch 모듈 설정
파일 설치후 엘라스틱서치 서버로 통신하기 위한 기본정보 입력.   
1. modules/elasticsearch/elasticsearch.model.php 파일에 있는 elasticsearchModel 클래스의 static으로 설정된 $host, $port, $prefix 변수의 값을 입력.   
 $prefix 값은 기본값인 "es"로 사용하여도 문제없음. 만약 다른 사이트에서 같은 ElasticSearch를 사용하게 된다면 필히 접두어를 사이트마다 다르게 해야함.
 
2. xe admin 메인페이지에서 elasticsearch 모듈 설치 및 db생성 버튼 클릭

3. 서버의 /index.php?module=admin&act=dispElasticsearchAdminModuleSetting 주소로 접속하여 "서버 정보" 항목의 status값이 running 상태인지 확인.   
running이 아닌 경우 연동이 제대로 되지 않은 상태임.

#### 2.2.5.2. 데이터 인덱싱
데이터 인덱싱은 php-cli를 이용하여 작업한다.   
/modules/elasticsearch/cli 위치로 이동 후 아래의 명령어를 이용하여 데이터 인덱싱 작업을 시작한다.
```
php setup.php
```
인덱싱 작업이 정상적으로 종료될 경우 "DONE!!" 라는 메세지가 출력된다.

## 2.3. 테스트

- XE admin -> Elasticsearch 모듈 설정 -> 인덱스 목록에서 documents, commets, files 문서 갯수(docs.count)가 실제 문서, 댓글, 파일 갯수와 일치하는지 확인.
- 실제 게시판 내의 검색을 테스트하여 속도향상이 이루어졌는지 확인.

# 3. 기타
- 검색 결과는 성능 문제로 10000개로 제한됩니다.  
 성능 저하 문제를 감안하고 전체를 출력해야 한다면 모듈 설정의 "Search After" 기능을 사용하세요.
- 해당 모듈은 기존 mysql의 like검색과 유사하게 작동하게끔 nGram, edgeNGram을 사용하였습니다.   
한글 형태소와 같이 기타 형태소 분석기로 사용하실려면 'elasticsearch.admin.model.php'의 $indexSettings 값을 직접 작성하세요.
- 해당 모듈은 XE를 기반으로 제작되었습니다. 라이믹스(~2.0)와의 호환성 작업이 없어 설치엔 문제가 없을수도 있으나 실 사용시 문제가 발생할 수 있습니다.
